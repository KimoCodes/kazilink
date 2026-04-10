<?php

declare(strict_types=1);

final class SubscriptionController
{
    private const PROOF_MAX_BYTES = 5242880;
    private const PROOF_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private Plan $plans;
    private Subscription $subscriptions;
    private PromoCode $promoCodes;
    private MomoTransaction $transactions;
    private MomoApi $momoApi;
    private SubscriptionPaymentProcessor $paymentProcessor;
    private MomoWebhookLog $webhookLogs;
    private SubscriptionPaymentIntent $paymentIntents;
    private SubscriptionPaymentIntentAudit $paymentIntentAudit;
    private NotificationService $notifications;

    public function __construct()
    {
        $this->plans = new Plan();
        $this->subscriptions = new Subscription();
        $this->promoCodes = new PromoCode();
        $this->transactions = new MomoTransaction();
        $this->momoApi = new MomoApi();
        $this->paymentProcessor = new SubscriptionPaymentProcessor();
        $this->webhookLogs = new MomoWebhookLog();
        $this->paymentIntents = new SubscriptionPaymentIntent();
        $this->paymentIntentAudit = new SubscriptionPaymentIntentAudit();
        $this->notifications = new NotificationService();
    }

    public function index(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);

        $userId = (int) Auth::id();
        $currentPlanId = (int) (SubscriptionAccess::summaryForUser($userId)['plan_id'] ?? 0);
        $statusRef = trim((string) ($_GET['ref'] ?? ''));
        $intentId = max(0, (int) ($_GET['intent'] ?? 0));
        $selectedIntent = $intentId > 0 ? $this->paymentIntents->findByIdForUser($intentId, $userId) : null;

        if ($selectedIntent === null) {
            $selectedIntent = $this->paymentIntents->latestOpenForUser($userId);
        }

        return View::render('subscriptions/index', [
            'pageTitle' => 'Subscriptions',
            'plans' => $this->plans->allActive(),
            'subscriptionSummary' => SubscriptionAccess::summaryForUser($userId),
            'currentPlanId' => $currentPlanId,
            'statusRef' => $statusRef,
            'statusTransaction' => $statusRef !== '' ? $this->transactions->findByExternalRef($statusRef) : null,
            'selectedPaymentIntent' => $selectedIntent,
            'recentPaymentIntents' => $this->paymentIntents->latestForUser($userId, 8),
            'momoDisplayNumber' => (string) app_config('momo.display_number', app_config('contact.phone', '+250 000 000 000')),
            'errors' => [],
            'fieldErrors' => [],
        ]);
    }

    public function subscribe(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);
        verifyPostRequest('subscriptions/index');

        $userId = (int) Auth::id();
        $input = Validator::trim($_POST);
        $planId = (int) ($input['plan_id'] ?? 0);
        $plan = $this->plans->findById($planId);

        if ($plan === null || (int) $plan['active'] !== 1) {
            Session::flash('error', 'Please choose an active subscription plan.');
            redirect('subscriptions/index');
        }

        $createdToday = $this->paymentIntents->countCreatedByUserSince($userId, date('Y-m-d 00:00:00'));
        if ($createdToday >= 10) {
            Session::flash('error', 'You have reached the daily limit for payment requests. Please try again tomorrow.');
            redirect('subscriptions/index');
        }

        $activationInput = trim((string) ($input['intended_activation_at'] ?? ''));
        $intendedActivationAt = $this->parseActivationAt($activationInput);
        if ($intendedActivationAt === null) {
            Session::flash('error', 'Choose a valid activation date and time.');
            redirect('subscriptions/index');
        }

        $now = new DateTimeImmutable('now', $this->appTimezone());
        $minimumActivation = $now->modify('+48 hours');
        if ($intendedActivationAt < $minimumActivation) {
            Session::flash('error', 'Choose an activation time at least 2 days from now so payment proof can be submitted on time.');
            redirect('subscriptions/index');
        }

        $deadlineAt = $intendedActivationAt->modify('-48 hours');
        $subscription = $this->subscriptions->currentForUser($userId);
        if ($subscription === null) {
            $this->subscriptions->createTrialForUser($userId);
            $subscription = $this->subscriptions->currentForUser($userId);
        }

        if ($subscription === null) {
            Session::flash('error', 'Unable to initialize your subscription record.');
            redirect('subscriptions/index');
        }

        $reference = $this->generateManualPaymentReference();
        $intentId = $this->paymentIntents->createDraft([
            'reference' => $reference,
            'plan_id' => (int) $plan['id'],
            'user_id' => $userId,
            'amount_expected_rwf' => (int) $plan['price_rwf'],
            'momo_number_displayed' => (string) app_config('momo.display_number', app_config('contact.phone', '+250 000 000 000')),
            'intended_activation_at' => $intendedActivationAt->format('Y-m-d H:i:s'),
            'deadline_at' => $deadlineAt->format('Y-m-d H:i:s'),
        ]);

        $this->subscriptions->markPaymentPending((int) $subscription['id'], (int) $plan['id'], $reference);
        $this->paymentIntentAudit->create([
            'payment_intent_id' => $intentId,
            'actor_user_id' => $userId,
            'actor_type' => 'user',
            'action' => 'intent_created',
            'from_status' => null,
            'to_status' => SubscriptionPaymentIntent::STATUS_DRAFT,
            'reason' => 'User selected a plan and opened manual MTN MoMo payment instructions.',
            'metadata' => [
                'plan_id' => (int) $plan['id'],
                'amount_expected_rwf' => (int) $plan['price_rwf'],
                'intended_activation_at' => $intendedActivationAt->format(DATE_ATOM),
                'deadline_at' => $deadlineAt->format(DATE_ATOM),
            ],
        ]);

        Session::flash('success', 'Payment instructions are ready. Send the exact amount, then upload your screenshot proof before the deadline.');
        redirect('subscriptions/index', ['intent' => $intentId]);
    }

    public function submitManualPayment(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);
        verifyPostRequest('subscriptions/index');

        $userId = (int) Auth::id();
        $input = Validator::trim($_POST);
        $intentId = (int) ($input['payment_intent_id'] ?? 0);
        $intent = $this->paymentIntents->findByIdForUser($intentId, $userId);

        if ($intent === null) {
            Session::flash('error', 'Payment request not found.');
            redirect('subscriptions/index');
        }

        if (!in_array((string) $intent['status'], [SubscriptionPaymentIntent::STATUS_DRAFT, SubscriptionPaymentIntent::STATUS_REJECTED], true)) {
            Session::flash('error', 'This payment request can no longer be updated.');
            redirect('subscriptions/index', ['intent' => $intentId]);
        }

        $uploadsInLastHour = $this->paymentIntentAudit->countActionForIntentSince($intentId, 'proof_uploaded', date('Y-m-d H:i:s', strtotime('-1 hour')));
        if ($uploadsInLastHour >= 5) {
            Session::flash('error', 'Too many proof upload attempts in the last hour. Please wait and try again.');
            redirect('subscriptions/index', ['intent' => $intentId]);
        }

        $submitAttempts = $this->paymentIntentAudit->countActionForIntent($intentId, 'submitted');
        if ($submitAttempts >= 3) {
            Session::flash('error', 'This payment request has reached the maximum number of submission attempts.');
            redirect('subscriptions/index', ['intent' => $intentId]);
        }

        if (!isset($_FILES['screenshot']) || !is_array($_FILES['screenshot'])) {
            Session::flash('error', 'Upload a payment screenshot before submitting.');
            redirect('subscriptions/index', ['intent' => $intentId]);
        }

        $payerPhone = trim((string) ($input['payer_phone'] ?? ''));
        if ($payerPhone !== '' && !preg_match('/^[\d +\-().]{6,30}$/', $payerPhone)) {
            Session::flash('error', 'Enter a valid payer phone number or leave it blank.');
            redirect('subscriptions/index', ['intent' => $intentId]);
        }

        $amountPaidInput = trim((string) ($input['amount_paid'] ?? ''));
        $amountPaid = null;
        if ($amountPaidInput !== '') {
            if (!preg_match('/^\d+$/', $amountPaidInput)) {
                Session::flash('error', 'Amount paid must be a whole-number RWF amount.');
                redirect('subscriptions/index', ['intent' => $intentId]);
            }

            $amountPaid = max(0, (int) $amountPaidInput);
        }

        $upload = $this->processProofUpload($_FILES['screenshot'], $intentId);
        if (isset($upload['errors'])) {
            foreach ((array) $upload['errors'] as $message) {
                Session::flash('error', (string) $message);
            }

            redirect('subscriptions/index', ['intent' => $intentId]);
        }

        $duplicateIntent = $this->paymentIntents->findByScreenshotHash((string) $upload['hash'], $intentId);
        if ($duplicateIntent !== null) {
            $this->deleteUploadedProof((string) ($upload['full_path'] ?? ''));
            Session::flash('error', 'This screenshot has already been used for another payment request. Upload a fresh proof image.');
            redirect('subscriptions/index', ['intent' => $intentId]);
        }

        $fromStatus = (string) $intent['status'];
        $submittedAt = new DateTimeImmutable('now', $this->appTimezone());
        $deadlineAt = new DateTimeImmutable((string) $intent['deadline_at'], $this->appTimezone());
        $isLate = $submittedAt > $deadlineAt;

        try {
            $this->paymentIntents->updateProof($intentId, [
                'amount_paid_rwf' => $amountPaid,
                'payer_phone' => $payerPhone !== '' ? $payerPhone : null,
                'screenshot_url' => (string) $upload['path'],
                'screenshot_hash' => (string) $upload['hash'],
            ]);

            $this->paymentIntentAudit->create([
                'payment_intent_id' => $intentId,
                'actor_user_id' => $userId,
                'actor_type' => 'user',
                'action' => 'proof_uploaded',
                'from_status' => $fromStatus,
                'to_status' => $fromStatus,
                'reason' => 'User uploaded or replaced payment proof.',
                'metadata' => [
                    'screenshot_url' => (string) $upload['path'],
                    'amount_paid_rwf' => $amountPaid,
                    'payer_phone' => $payerPhone !== '' ? $payerPhone : null,
                ],
            ]);

            $this->paymentIntentAudit->create([
                'payment_intent_id' => $intentId,
                'actor_user_id' => $userId,
                'actor_type' => 'user',
                'action' => 'submitted',
                'from_status' => $fromStatus,
                'to_status' => SubscriptionPaymentIntent::STATUS_SUBMITTED,
                'reason' => 'User submitted payment proof for review.',
                'metadata' => [
                    'submitted_at' => $submittedAt->format(DATE_ATOM),
                ],
            ]);

            $this->paymentIntents->transitionSubmission($intentId, $submittedAt->format('Y-m-d H:i:s'), $isLate);
            $this->paymentIntentAudit->create([
                'payment_intent_id' => $intentId,
                'actor_user_id' => $userId,
                'actor_type' => 'user',
                'action' => $isLate ? 'marked_late' : 'sent_to_pending_verification',
                'from_status' => SubscriptionPaymentIntent::STATUS_SUBMITTED,
                'to_status' => SubscriptionPaymentIntent::STATUS_PENDING_VERIFICATION,
                'reason' => $isLate
                    ? 'Proof was submitted after the 48-hour cutoff and cannot be activated.'
                    : 'Proof is ready for manual admin verification.',
            ]);
        } catch (Throwable $exception) {
            $this->deleteUploadedProof((string) ($upload['full_path'] ?? ''));
            Session::flash('error', 'We could not save your payment proof right now. Please try again.');
            redirect('subscriptions/index', ['intent' => $intentId]);
        }

        try {
            $this->notifications->emit('payment_submitted', sprintf('payment_submitted:%d:%s', $intentId, $submittedAt->format('Y-m-d H:i:s')), [
                'payment_intent_id' => $intentId,
                'entity_type' => 'payment_intent',
                'entity_id' => $intentId,
                'user_id' => $userId,
                'user_email' => (string) ($intent['email'] ?? ''),
                'user_name' => (string) (($intent['full_name'] ?? '') !== '' ? $intent['full_name'] : ($intent['email'] ?? 'User')),
                'plan_id' => (int) $intent['plan_id'],
                'plan_name' => (string) ($intent['plan_name'] ?? 'Plan'),
                'amount_expected_rwf' => (int) ($intent['amount_expected_rwf'] ?? 0),
                'amount_paid_rwf' => $amountPaid,
                'currency' => 'RWF',
                'payer_phone' => $payerPhone !== '' ? $payerPhone : null,
                'momo_number_displayed' => (string) ($intent['momo_number_displayed'] ?? ''),
                'status' => SubscriptionPaymentIntent::STATUS_PENDING_VERIFICATION,
                'submitted_at' => $submittedAt->format('Y-m-d H:i:s'),
                'deadline_at' => (string) $intent['deadline_at'],
                'intended_activation_at' => (string) $intent['intended_activation_at'],
                'is_late' => $isLate,
                'payment_link' => url_for('subscriptions/index', ['intent' => $intentId]),
                'admin_review_link' => url_for('admin/subscriptions'),
            ]);
            $this->notifications->processOutbox(20);
        } catch (Throwable $e) {
            error_log('Notification error during payment submission: ' . $e->getMessage());
        }

        if ($isLate) {
            Session::flash('error', 'Payment proof was received after the deadline. It will be marked late and cannot be approved for activation.');
        } else {
            Session::flash('success', 'Your payment proof has been submitted and is pending verification.');
        }

        redirect('subscriptions/index', ['intent' => $intentId]);
    }

    public function poll(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);
        header('Content-Type: application/json');

        $ref = trim((string) ($_GET['ref'] ?? ''));
        if ($ref === '') {
            http_response_code(400);

            return json_encode(['error' => 'Missing reference.']);
        }

        $transaction = $this->transactions->findByExternalRef($ref);
        if ($transaction === null || (int) $transaction['user_id'] !== (int) Auth::id()) {
            http_response_code(404);

            return json_encode(['error' => 'Transaction not found.']);
        }

        if ((string) $transaction['status'] === 'pending') {
            try {
                $response = $this->momoApi->getRequestToPayStatus($ref);
                $status = $this->paymentProcessor->mapGatewayStatus((string) ($response['body']['status'] ?? 'PENDING'));
                $this->transactions->updateGatewayPayload($ref, ['status_lookup' => $response], $status === 'successful' ? null : $status);

                if ($status === 'successful' && (string) $transaction['status'] === 'pending') {
                    $this->paymentProcessor->processSuccessfulTransaction($ref, $response['body']);
                }
            } catch (Throwable $exception) {
                $this->transactions->updateGatewayPayload($ref, [
                    'status_lookup_error' => $exception->getMessage(),
                ]);
            }

            $transaction = $this->transactions->findByExternalRef($ref) ?? $transaction;
        }

        return json_encode([
            'status' => (string) $transaction['status'],
            'reference' => $ref,
        ]);
    }

    public function callback(): string
    {
        $requestIp = request_ip();
        $signature = trim((string) ($_GET['sig'] ?? ''));
        $expectedToken = trim((string) app_config('momo.callback_secret', ''));
        $raw = file_get_contents('php://input');
        $payload = json_decode((string) $raw, true);
        $payload = is_array($payload) ? $payload : [];
        $ref = trim((string) ($_GET['ref'] ?? ($payload['referenceId'] ?? $payload['externalId'] ?? '')));
        $headers = request_headers_normalized();

        if ($expectedToken === '' || $ref === '' || !hash_equals(momo_callback_signature($ref), $signature)) {
            Logger::security('MoMo webhook blocked: invalid signature', [
                'reference' => $ref,
                'ip' => $requestIp,
                'signature_provided' => $signature !== '',
                'expected_token_configured' => $expectedToken !== '',
            ]);
            
            $this->webhookLogs->create($ref !== '' ? $ref : null, 'blocked_secret', $headers, (string) $raw, $requestIp);
            http_response_code(403);

            return 'forbidden';
        }

        if (!$this->ipAllowed($requestIp)) {
            Logger::security('MoMo webhook blocked: IP not allowed', [
                'reference' => $ref,
                'ip' => $requestIp,
            ]);
            
            $this->webhookLogs->create($ref !== '' ? $ref : null, 'blocked_ip', $headers, (string) $raw, $requestIp);
            http_response_code(403);

            return 'forbidden-ip';
        }

        if ($ref === '') {
            Logger::security('MoMo webhook blocked: missing reference', [
                'ip' => $requestIp,
                'payload_preview' => substr((string) $raw, 0, 200),
            ]);
            
            $this->webhookLogs->create(null, 'invalid_payload', $headers, (string) $raw, $requestIp);
            http_response_code(400);

            return 'missing-reference';
        }

        $transaction = $this->transactions->findByExternalRef($ref);
        if ($transaction === null || (string) $transaction['status'] !== 'pending') {
            $this->webhookLogs->create($ref, 'invalid_payload', $headers, (string) $raw, $requestIp);
            http_response_code(409);

            return 'invalid-transaction-state';
        }

        $mappedStatus = $this->paymentProcessor->mapGatewayStatus((string) ($payload['status'] ?? 'PENDING'));
        $webhookLogId = $this->webhookLogs->create($ref, 'accepted', $headers, (string) $raw, $requestIp);
        
        Logger::payment('MoMo webhook accepted', [
            'reference' => $ref,
            'mapped_status' => $mappedStatus,
            'gateway_status' => (string) ($payload['status'] ?? 'PENDING'),
            'ip' => $requestIp,
        ]);
        
        $this->transactions->updateGatewayPayload($ref, [
            'callback' => $payload,
            'raw' => $raw,
            'webhook_log_id' => $webhookLogId,
        ], $mappedStatus === 'successful' ? null : $mappedStatus);

        if ($mappedStatus === 'successful') {
            try {
                Logger::payment('Processing successful transaction', [
                    'reference' => $ref,
                    'amount' => (int) ($payload['amount'] ?? 0),
                    'currency' => (string) ($payload['currency'] ?? ''),
                ]);
                
                $this->paymentProcessor->processSuccessfulTransaction($ref, $payload);
                
                Logger::payment('Successfully processed transaction', [
                    'reference' => $ref,
                ]);
            } catch (Throwable $exception) {
                Logger::error('Failed to process successful transaction', [
                    'reference' => $ref,
                    'exception' => $exception->getMessage(),
                    'payload' => $payload,
                ]);
                
                $this->transactions->updateGatewayPayload($ref, [
                    'callback_validation_error' => $exception->getMessage(),
                ], 'failed');
                http_response_code(400);

                return 'validation-failed';
            }
        }

        return 'ok';
    }

    private function processProofUpload(array $file, int $intentId): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['errors' => ['Upload a clear image in JPG, JPEG, PNG, or WEBP format, up to 5 MB.']];
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return ['errors' => ['Invalid payment proof upload.']];
        }

        if ((int) ($file['size'] ?? 0) > self::PROOF_MAX_BYTES) {
            return ['errors' => ['Upload a clear image in JPG, JPEG, PNG, or WEBP format, up to 5 MB.']];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmpName);
        $extension = self::PROOF_MIME[$mime] ?? null;

        if ($extension === null) {
            return ['errors' => ['Upload a clear image in JPG, JPEG, PNG, or WEBP format, up to 5 MB.']];
        }

        $dimensions = @getimagesize($tmpName);
        if (!is_array($dimensions) || (int) ($dimensions[0] ?? 0) < 320 || (int) ($dimensions[1] ?? 0) < 320) {
            return ['errors' => ['Upload a clearer screenshot that shows the payment details more visibly.']];
        }

        $hash = sha1_file($tmpName);
        if ($hash === false) {
            return ['errors' => ['The uploaded screenshot could not be validated.']];
        }

        $directory = BASE_PATH . '/public/uploads/subscription-payment-proofs';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            return ['errors' => ['Unable to store the uploaded screenshot right now.']];
        }

        $filename = sprintf('intent_%d_%s.%s', $intentId, sha1(uniqid((string) $intentId, true) . microtime(true)), $extension);
        $destination = $directory . '/' . $filename;

        if (!move_uploaded_file($tmpName, $destination)) {
            return ['errors' => ['Unable to store the uploaded screenshot right now.']];
        }

        return [
            'path' => 'uploads/subscription-payment-proofs/' . $filename,
            'hash' => $hash,
            'full_path' => $destination,
        ];
    }

    private function deleteUploadedProof(string $fullPath): void
    {
        if ($fullPath !== '' && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    private function parseActivationAt(string $value): ?DateTimeImmutable
    {
        if ($value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value, $this->appTimezone());
        } catch (Throwable) {
            return null;
        }
    }

    private function appTimezone(): DateTimeZone
    {
        return new DateTimeZone((string) app_config('timezone', 'Africa/Kigali'));
    }

    private function generateManualPaymentReference(): string
    {
        return sprintf(
            'manual-sub-%s-%s',
            date('YmdHis'),
            bin2hex(random_bytes(6))
        );
    }

    private function ipAllowed(string $requestIp): bool
    {
        $allowlist = trim((string) app_config('momo.callback_allowlist', ''));

        // In production, IP allowlist should be mandatory
        if ($allowlist === '') {
            $appEnv = (string) app_config('env', 'production');
            if ($appEnv === 'production') {
                error_log('MoMo webhook: IP allowlist not configured in production environment');
                return false;
            }
            // Allow in non-production for testing, but log warning
            error_log('MoMo webhook: IP allowlist not configured - allowing in non-production environment');
            return true;
        }

        $allowedIps = array_values(array_filter(array_map('trim', explode(',', $allowlist))));

        return in_array($requestIp, $allowedIps, true);
    }
}

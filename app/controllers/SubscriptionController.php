<?php

declare(strict_types=1);

final class SubscriptionController
{
    private Plan $plans;
    private Subscription $subscriptions;
    private PromoCode $promoCodes;
    private MomoTransaction $transactions;
    private MomoApi $momoApi;
    private SubscriptionPaymentProcessor $paymentProcessor;
    private MomoWebhookLog $webhookLogs;

    public function __construct()
    {
        $this->plans = new Plan();
        $this->subscriptions = new Subscription();
        $this->promoCodes = new PromoCode();
        $this->transactions = new MomoTransaction();
        $this->momoApi = new MomoApi();
        $this->paymentProcessor = new SubscriptionPaymentProcessor();
        $this->webhookLogs = new MomoWebhookLog();
    }

    public function index(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);

        $currentPlanId = (int) (SubscriptionAccess::summaryForUser((int) Auth::id())['plan_id'] ?? 0);
        $statusRef = trim((string) ($_GET['ref'] ?? ''));

        return View::render('subscriptions/index', [
            'pageTitle' => 'Subscriptions',
            'plans' => $this->plans->allActive(),
            'subscriptionSummary' => SubscriptionAccess::summaryForUser((int) Auth::id()),
            'currentPlanId' => $currentPlanId,
            'statusRef' => $statusRef,
            'statusTransaction' => $statusRef !== '' ? $this->transactions->findByExternalRef($statusRef) : null,
            'errors' => [],
            'fieldErrors' => [],
        ]);
    }

    public function subscribe(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);
        verifyPostRequest('subscriptions/index');

        $input = Validator::trim($_POST);
        $planId = (int) ($input['plan_id'] ?? 0);
        $plan = $this->plans->findById($planId);

        if ($plan === null || (int) $plan['active'] !== 1) {
            Session::flash('error', 'Please choose an active subscription plan.');
            redirect('subscriptions/index');
        }

        $phone = preg_replace('/\s+/', '', (string) ($input['phone'] ?? '')) ?? '';
        if ($phone === '') {
            Session::flash('error', 'Enter the MTN MoMo phone number to charge.');
            redirect('subscriptions/index');
        }

        $promoCode = trim((string) ($input['promo_code'] ?? ''));
        $promo = null;
        $amountRwf = (int) $plan['price_rwf'];

        if ($promoCode !== '') {
            $promo = $this->promoCodes->validationForUser($promoCode, (int) Auth::id());
            if ($promo === null) {
                Session::flash('error', 'That promo code is invalid, expired, exhausted, or not assigned to your account.');
                redirect('subscriptions/index');
            }

            $amountRwf = $this->promoCodes->applyDiscount($promo, $amountRwf);
        }

        $subscription = $this->subscriptions->currentForUser((int) Auth::id());
        if ($subscription === null) {
            $this->subscriptions->createTrialForUser((int) Auth::id());
            $subscription = $this->subscriptions->currentForUser((int) Auth::id());
        }

        if ($subscription === null) {
            Session::flash('error', 'Unable to initialize your subscription record.');
            redirect('subscriptions/index');
        }

        $externalRef = $this->generateReference();
        $transactionId = $this->transactions->create([
            'user_id' => (int) Auth::id(),
            'amount_rwf' => $amountRwf,
            'external_ref' => $externalRef,
            'status' => 'pending',
            'raw_payload_json' => json_encode([
                'source' => 'subscribe',
                'plan_id' => (int) $plan['id'],
                'promo_code_id' => $promo['id'] ?? null,
                'promo_code' => $promo['code'] ?? null,
                'currency' => strtoupper((string) app_config('momo.currency', 'RWF')),
                'phone' => $phone,
                'requested_by_user_id' => (int) Auth::id(),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);

        $this->subscriptions->markPaymentPending((int) $subscription['id'], (int) $plan['id'], $externalRef);

        if ($promo !== null && $amountRwf === 0) {
            $this->paymentProcessor->processSuccessfulTransaction($externalRef, [
                'financialTransactionId' => 'promo-zero-charge',
                'reason' => 'Promo code covered 100% of the plan price.',
                'amount' => $amountRwf,
                'currency' => strtoupper((string) app_config('momo.currency', 'RWF')),
            ]);
            Session::flash('success', 'Promo code applied. Your subscription has been activated without a mobile money charge.');
            redirect('subscriptions/index', ['ref' => $externalRef]);
        }

        try {
            $response = $this->momoApi->requestToPay([
                'reference_id' => $externalRef,
                'external_id' => 'subscription-' . $transactionId,
                'amount_rwf' => $amountRwf,
                'phone' => $phone,
                'payer_message' => 'Marketplace subscription payment',
                'payee_note' => $plan['name'] . ' plan',
            ]);

            $this->transactions->updateGatewayPayload($externalRef, [
                'request_to_pay' => $response,
                'plan_id' => (int) $plan['id'],
                'promo_code_id' => $promo['id'] ?? null,
            ]);
        } catch (Throwable $exception) {
            $this->transactions->updateGatewayPayload($externalRef, [
                'request_error' => $exception->getMessage(),
            ], 'failed');
            Session::flash('error', 'We could not start the MTN MoMo payment request right now. Please review your configuration and try again.');
            redirect('subscriptions/index');
        }

        Session::flash('success', 'MTN MoMo payment request started. Confirm it on the phone, then refresh this page if needed.');
        redirect('subscriptions/index', ['ref' => $externalRef]);
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
            $this->webhookLogs->create($ref !== '' ? $ref : null, 'blocked_secret', $headers, (string) $raw, $requestIp);
            http_response_code(403);

            return 'forbidden';
        }

        if (!$this->ipAllowed($requestIp)) {
            $this->webhookLogs->create($ref !== '' ? $ref : null, 'blocked_ip', $headers, (string) $raw, $requestIp);
            http_response_code(403);

            return 'forbidden-ip';
        }

        if ($ref === '') {
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
        $this->transactions->updateGatewayPayload($ref, [
            'callback' => $payload,
            'raw' => $raw,
            'webhook_log_id' => $webhookLogId,
        ], $mappedStatus === 'successful' ? null : $mappedStatus);

        if ($mappedStatus === 'successful') {
            try {
                $this->paymentProcessor->processSuccessfulTransaction($ref, $payload);
            } catch (Throwable $exception) {
                $this->transactions->updateGatewayPayload($ref, [
                    'callback_validation_error' => $exception->getMessage(),
                ], 'failed');
                http_response_code(400);

                return 'validation-failed';
            }
        }

        return 'ok';
    }

    private function generateReference(): string
    {
        return sprintf(
            'sub-%s-%s',
            date('YmdHis'),
            bin2hex(random_bytes(8))
        );
    }

    private function ipAllowed(string $requestIp): bool
    {
        $allowlist = trim((string) app_config('momo.callback_allowlist', ''));

        if ($allowlist === '') {
            return true;
        }

        $allowedIps = array_values(array_filter(array_map('trim', explode(',', $allowlist))));

        return in_array($requestIp, $allowedIps, true);
    }
}

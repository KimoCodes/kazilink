<?php

declare(strict_types=1);

final class AdminController
{
    private const AD_MEDIA_MAX_BYTES = 15728640;
    private const AD_MEDIA_MIME = [
        'image/jpeg' => ['extension' => 'jpg', 'type' => 'image'],
        'image/png' => ['extension' => 'png', 'type' => 'image'],
        'image/webp' => ['extension' => 'webp', 'type' => 'image'],
        'video/mp4' => ['extension' => 'mp4', 'type' => 'video'],
        'video/webm' => ['extension' => 'webm', 'type' => 'video'],
    ];
    private const THEME_BACKGROUND_MAX_BYTES = 5242880;
    private const THEME_BACKGROUND_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private User $users;
    private Task $tasks;
    private AdminAudit $audit;
    private Ad $ads;
    private HiringAgreement $agreements;
    private Dispute $disputes;
    private Plan $plans;
    private PromoCode $promoCodes;
    private Subscription $subscriptions;
    private MomoTransaction $momoTransactions;
    private SubscriptionPaymentIntent $paymentIntents;
    private SubscriptionPaymentIntentAudit $paymentIntentAudit;
    private NotificationService $notifications;
    private EmailDeliveryService $emailDelivery;
    private AppSetting $settings;
    private SiteSetting $siteSettings;
    private MomoWebhookLog $webhookLogs;

    public function __construct()
    {
        $this->users = new User();
        $this->tasks = new Task();
        $this->audit = new AdminAudit();
        $this->ads = new Ad();
        $this->agreements = new HiringAgreement();
        $this->disputes = new Dispute();
        $this->plans = new Plan();
        $this->promoCodes = new PromoCode();
        $this->subscriptions = new Subscription();
        $this->momoTransactions = new MomoTransaction();
        $this->paymentIntents = new SubscriptionPaymentIntent();
        $this->paymentIntentAudit = new SubscriptionPaymentIntentAudit();
        $this->notifications = new NotificationService();
        $this->emailDelivery = new EmailDeliveryService();
        $this->settings = new AppSetting();
        $this->siteSettings = new SiteSetting();
        $this->webhookLogs = new MomoWebhookLog();
    }

    public function dashboard(): string
    {
        Auth::requireRole('admin');

        return View::render('admin/dashboard', [
            'pageTitle' => 'Admin Dashboard',
            'stats' => [
                'total_users' => $this->users->countAll(),
                'active_clients' => $this->users->countByRole('client', true),
                'active_taskers' => $this->users->countByRole('tasker', true),
                'open_tasks' => $this->tasks->countByStatus('open'),
                'booked_tasks' => $this->tasks->countByStatus('booked'),
                'completed_tasks' => $this->tasks->countByStatus('completed'),
                'pending_agreements' => $this->agreements->countByStatus('pending_acceptance'),
                'accepted_agreements' => $this->agreements->countByStatus('accepted'),
                'open_disputes' => $this->disputes->countByStatus('open'),
                'active_plans' => count(array_filter($this->plans->allForAdmin(), static fn (array $plan): bool => (int) $plan['active'] === 1)),
                'subscriptions_live' => count(array_filter($this->subscriptions->latestForAdmin(200), static fn (array $subscription): bool => in_array((string) $subscription['status'], ['trialing', 'active', 'past_due'], true))),
                'webhook_events' => count($this->webhookLogs->latest(200)),
            ],
            'auditLogs' => $this->audit->latest(15),
            'ads' => $this->ads->activeByPlacement('home', 2),
        ]);
    }

    public function plans(): string
    {
        Auth::requireRole('admin');

        $editId = (int) ($_GET['id'] ?? 0);
        $editingPlan = $editId > 0 ? $this->plans->findById($editId) : null;

        return View::render('admin/plans', [
            'pageTitle' => 'Subscription Plans',
            'plans' => $this->plans->allForAdmin(),
            'editingPlan' => $editingPlan,
            'errors' => [],
            'fieldErrors' => [],
        ]);
    }

    public function savePlan(): string
    {
        Auth::requireRole('admin');
        verifyPostRequest('admin/plans');

        $planId = (int) ($_POST['plan_id'] ?? 0);
        $input = Validator::trim($_POST);
        $slug = preg_replace('/[^a-z0-9\-]+/', '-', mb_strtolower((string) ($input['slug'] ?? ''))) ?? '';
        $slug = trim($slug, '-');
        $name = normalize_whitespace((string) ($input['name'] ?? ''));
        $priceRwf = max(0, (int) ($input['price_rwf'] ?? 0));
        $visibilityLevel = max(1, (int) ($input['visibility_level'] ?? 1));
        $maxApplicationsPerDay = max(1, (int) ($input['max_applications_per_day'] ?? 1));
        $priorityLevel = max(1, (int) ($input['priority_level'] ?? $visibilityLevel));
        $jobAlertDelayMinutes = (int) ($input['job_alert_delay_minutes'] ?? 0);
        $maxActiveJobs = max(1, (int) ($input['max_active_jobs'] ?? 1));
        $commissionDiscount = max(0, min(100, (float) ($input['commission_discount'] ?? 0)));
        $badgeName = normalize_whitespace((string) ($input['badge_name'] ?? ''));
        $active = (string) ($input['active'] ?? '0') === '1';

        $fieldErrors = [];

        if ($slug === '') {
            $fieldErrors['slug'][] = 'Plan slug is required.';
        }

        if ($name === '') {
            $fieldErrors['name'][] = 'Plan name is required.';
        }

        if ($priceRwf < 0) {
            $fieldErrors['price_rwf'][] = 'Price must be zero or more.';
        }

        if ($fieldErrors !== []) {
            return View::render('admin/plans', [
                'pageTitle' => 'Subscription Plans',
                'plans' => $this->plans->allForAdmin(),
                'editingPlan' => array_merge($input, ['id' => $planId, 'slug' => $slug]),
                'errors' => Validator::flattenFieldErrors($fieldErrors),
                'fieldErrors' => $fieldErrors,
            ]);
        }

        $payload = [
            'slug' => $slug,
            'name' => $name,
            'price_rwf' => $priceRwf,
            'visibility_level' => $visibilityLevel,
            'max_applications_per_day' => $maxApplicationsPerDay,
            'priority_level' => $priorityLevel,
            'job_alert_delay_minutes' => $jobAlertDelayMinutes,
            'max_active_jobs' => $maxActiveJobs,
            'commission_discount' => $commissionDiscount,
            'badge_name' => $badgeName,
            'active' => $active,
        ];

        if ($planId > 0) {
            $this->plans->update($planId, $payload);
            $this->audit->log((int) Auth::id(), 'plan', $planId, 'updated', 'Updated subscription plan settings.');
            Session::flash('success', 'Plan updated.');
        } else {
            $createdId = $this->plans->create($payload);
            $this->audit->log((int) Auth::id(), 'plan', $createdId, 'created', 'Created a new subscription plan.');
            Session::flash('success', 'Plan created.');
        }

        redirect('admin/plans');
    }

    public function promos(): string
    {
        Auth::requireRole('admin');

        $editId = (int) ($_GET['id'] ?? 0);
        $editingPromo = $editId > 0 ? $this->promoCodes->findById($editId) : null;

        return View::render('admin/promos', [
            'pageTitle' => 'Promo Codes',
            'promos' => $this->promoCodes->allForAdmin(),
            'users' => $this->users->allForAdmin(),
            'editingPromo' => $editingPromo,
            'editingPromoUserIds' => $editingPromo !== null ? $this->promoCodes->assignedUserIds((int) $editingPromo['id']) : [],
            'errors' => [],
            'fieldErrors' => [],
        ]);
    }

    public function savePromo(): string
    {
        Auth::requireRole('admin');
        verifyPostRequest('admin/promos');

        $promoId = (int) ($_POST['promo_id'] ?? 0);
        $input = Validator::trim($_POST);
        $code = strtoupper(normalize_whitespace((string) ($input['code'] ?? '')));
        $type = (string) ($input['type'] ?? 'percent');
        $amount = max(0, (int) ($input['amount'] ?? 0));
        $maxRedemptions = trim((string) ($input['max_redemptions'] ?? '')) === '' ? null : max(1, (int) $input['max_redemptions']);
        $expiresAt = trim((string) ($input['expires_at'] ?? '')) !== '' ? (string) $input['expires_at'] : null;
        $active = (string) ($input['active'] ?? '0') === '1';
        $targetUserIds = array_map(static fn (mixed $value): int => (int) $value, (array) ($_POST['target_user_ids'] ?? []));

        $fieldErrors = [];

        if ($code === '') {
            $fieldErrors['code'][] = 'Promo code is required.';
        }

        if (!in_array($type, ['percent', 'fixed_rwf'], true)) {
            $fieldErrors['type'][] = 'Choose a valid promo type.';
        }

        if ($amount <= 0) {
            $fieldErrors['amount'][] = 'Promo amount must be greater than zero.';
        }

        if ($type === 'percent' && $amount > 100) {
            $fieldErrors['amount'][] = 'Percent discounts cannot exceed 100.';
        }

        if ($fieldErrors !== []) {
            return View::render('admin/promos', [
                'pageTitle' => 'Promo Codes',
                'promos' => $this->promoCodes->allForAdmin(),
                'users' => $this->users->allForAdmin(),
                'editingPromo' => array_merge($input, ['id' => $promoId]),
                'editingPromoUserIds' => $targetUserIds,
                'errors' => Validator::flattenFieldErrors($fieldErrors),
                'fieldErrors' => $fieldErrors,
            ]);
        }

        $payload = [
            'code' => $code,
            'type' => $type,
            'amount' => $amount,
            'max_redemptions' => $maxRedemptions,
            'expires_at' => $expiresAt,
            'active' => $active,
            'target_user_ids' => $targetUserIds,
        ];

        if ($promoId > 0) {
            $this->promoCodes->update($promoId, $payload);
            $this->audit->log((int) Auth::id(), 'promo_code', $promoId, 'updated', 'Updated promo code and targeting rules.');
            Session::flash('success', 'Promo code updated.');
        } else {
            $createdId = $this->promoCodes->create($payload);
            $this->audit->log((int) Auth::id(), 'promo_code', $createdId, 'created', 'Created promo code.');
            Session::flash('success', 'Promo code created.');
        }

        redirect('admin/promos');
    }

    public function subscriptions(): string
    {
        Auth::requireRole('admin');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pagination = pagination_params($page, 40);

        return View::render('admin/subscriptions', [
            'pageTitle' => 'Subscriptions',
            'subscriptions' => $this->subscriptions->latestForAdmin($pagination['limit'], $pagination['offset']),
            'transactions' => $this->momoTransactions->latestForAdmin($pagination['limit'], $pagination['offset']),
            'pendingPaymentIntents' => $this->paymentIntents->pendingForAdmin($pagination['limit'], $pagination['offset']),
            'recentPaymentIntents' => $this->paymentIntents->recentForAdmin(30),
            'pagination' => pagination_meta($page, $pagination['per_page'], max($this->subscriptions->countForAdmin(), $this->momoTransactions->countForAdmin())),
            'webhookLogs' => $this->webhookLogs->latest(60),
        ]);
    }

    public function emailDelivery(): string
    {
        Auth::requireRole('admin');

        $status = trim((string) ($_GET['status'] ?? ''));
        $eventName = trim((string) ($_GET['event_name'] ?? ''));
        $recipientEmail = trim((string) ($_GET['recipient_email'] ?? ''));

        return View::render('admin/email-delivery', [
            'pageTitle' => 'Email Delivery',
            'filters' => [
                'status' => $status,
                'event_name' => $eventName,
                'recipient_email' => $recipientEmail,
            ],
            'metrics' => $this->emailDelivery->metrics(),
            'emails' => $this->emailDelivery->recentForAdmin([
                'status' => $status,
                'event_name' => $eventName,
                'recipient_email' => $recipientEmail,
            ], 120),
        ]);
    }

    public function resendEmailDelivery(): string
    {
        Auth::requireRole('admin');
        verifyPostRequest('admin/email-delivery');

        $outboxId = (int) ($_POST['outbox_id'] ?? 0);
        if ($outboxId <= 0) {
            Session::flash('error', 'Choose an email record to resend.');
            redirect('admin/email-delivery');
        }

        $resendId = $this->emailDelivery->resend($outboxId, (int) Auth::id());
        if ($resendId === null) {
            Session::flash('error', 'That email could not be queued for resend.');
            redirect('admin/email-delivery');
        }

        $this->audit->log((int) Auth::id(), 'email_outbox', $outboxId, 'resend-requested', 'Queued a safe resend of a failed or skipped email.');
        Session::flash('success', 'Email resend queued. The background worker will deliver it.');
        redirect('admin/email-delivery');
    }

    public function settings(): string
    {
        Auth::requireRole('admin');

        return View::render('admin/settings', [
            'pageTitle' => 'Subscription Settings',
            'settings' => $this->settings->all(),
            'graceDays' => SubscriptionConfig::graceDays(),
        ]);
    }

    public function saveSettings(): string
    {
        Auth::requireRole('admin');
        verifyPostRequest('admin/settings');

        $graceDays = max(0, min(7, (int) ($_POST['subscription_grace_days'] ?? SubscriptionConfig::graceDays())));
        $this->settings->set('subscription_grace_days', (string) $graceDays);
        $this->audit->log((int) Auth::id(), 'setting', 0, 'updated-subscription-grace-days', 'Set subscription grace period to ' . $graceDays . ' days.');
        Session::flash('success', 'Subscription settings updated.');
        redirect('admin/settings');
    }

    public function theme(): string
    {
        Auth::requireRole('admin');

        return View::render('admin/theme', [
            'pageTitle' => 'Theme Manager',
            'themeSettings' => $this->siteSettings->themeSettings(),
            'backgrounds' => $this->siteSettings->allBackgrounds(),
            'siteSettingsReady' => $this->siteSettings->tableExists(),
        ]);
    }

    public function saveTheme(): string
    {
        Auth::requireRole('admin');
        verifyPostRequest('admin/theme');

        if (!$this->siteSettings->tableExists()) {
            Session::flash('error', 'Run the site settings migration first so theme changes can be saved.');
            redirect('admin/theme');
        }

        $action = trim((string) ($_POST['theme_action'] ?? 'save'));
        $defaults = $this->siteSettings->defaults();

        if ($action === 'reset') {
            foreach ([
                'theme_background_color',
                'theme_surface_color',
                'theme_text_color',
                'theme_primary_color',
                'theme_secondary_color',
                'theme_mode',
                'theme_font_preset',
                'theme_spacing_scale',
            ] as $key) {
                $this->siteSettings->set($key, (string) $defaults[$key]);
            }

            $this->audit->log((int) Auth::id(), 'setting', 0, 'reset-theme', 'Reset website theme settings to defaults.');
            Session::flash('success', 'Theme settings reset to defaults.');
            redirect('admin/theme');
        }

        $background = $this->siteSettings->sanitizeColor((string) ($_POST['theme_background_color'] ?? ''), (string) $defaults['theme_background_color']);
        $surface = $this->siteSettings->sanitizeColor((string) ($_POST['theme_surface_color'] ?? ''), (string) $defaults['theme_surface_color']);
        $text = $this->siteSettings->sanitizeColor((string) ($_POST['theme_text_color'] ?? ''), (string) $defaults['theme_text_color']);
        $primary = $this->siteSettings->sanitizeColor((string) ($_POST['theme_primary_color'] ?? ''), (string) $defaults['theme_primary_color']);
        $secondary = $this->siteSettings->sanitizeColor((string) ($_POST['theme_secondary_color'] ?? ''), (string) $defaults['theme_secondary_color']);
        $mode = strtolower(trim((string) ($_POST['theme_mode'] ?? (string) $defaults['theme_mode'])));
        $mode = $mode === 'dark' ? 'dark' : 'light';
        $fontPreset = $this->siteSettings->sanitizeFontPreset((string) ($_POST['theme_font_preset'] ?? (string) $defaults['theme_font_preset']));
        $spacingScale = $this->siteSettings->sanitizeSpacingScale((string) ($_POST['theme_spacing_scale'] ?? (string) $defaults['theme_spacing_scale']));

        $this->siteSettings->set('theme_background_color', $background);
        $this->siteSettings->set('theme_surface_color', $surface);
        $this->siteSettings->set('theme_text_color', $text);
        $this->siteSettings->set('theme_primary_color', $primary);
        $this->siteSettings->set('theme_secondary_color', $secondary);
        $this->siteSettings->set('theme_mode', $mode);
        $this->siteSettings->set('theme_font_preset', $fontPreset);
        $this->siteSettings->set('theme_spacing_scale', $spacingScale);

        $this->audit->log((int) Auth::id(), 'setting', 0, 'updated-theme', 'Updated website colors, typography preset, mode, and spacing scale.');
        Session::flash('success', 'Theme settings updated.');
        redirect('admin/theme');
    }

    public function uploadThemeBackground(): string
    {
        Auth::requireRole('admin');
        verifyPostRequest('admin/theme');

        if (!$this->siteSettings->tableExists()) {
            Session::flash('error', 'Run the site settings migration first so background images can be saved.');
            redirect('admin/theme');
        }

        if (!isset($_FILES['background_image']) || !is_array($_FILES['background_image'])) {
            Session::flash('error', 'Choose an image before uploading.');
            redirect('admin/theme');
        }

        $pageTarget = $this->siteSettings->sanitizePageName((string) ($_POST['page_target'] ?? ''));
        $customPage = $this->siteSettings->sanitizePageName((string) ($_POST['custom_page'] ?? ''));
        $pageName = $pageTarget === 'other' ? $customPage : $pageTarget;

        if ($pageName === '') {
            Session::flash('error', 'Choose a valid page before uploading a background.');
            redirect('admin/theme');
        }

        $upload = $this->processThemeBackgroundUpload($_FILES['background_image'], $pageName);

        if (isset($upload['errors'])) {
            foreach ((array) $upload['errors'] as $message) {
                Session::flash('error', (string) $message);
            }

            redirect('admin/theme');
        }

        $settingKey = 'bg_' . $pageName;
        $oldPath = $this->siteSettings->get($settingKey);

        $this->siteSettings->set($settingKey, (string) $upload['path']);

        if ($oldPath !== null && trim((string) $oldPath) !== '' && $oldPath !== (string) $upload['path']) {
            $oldFilePath = BASE_PATH . '/public/' . ltrim((string) $oldPath, '/');
            if (is_file($oldFilePath)) {
                @unlink($oldFilePath);
            }
        }

        $this->audit->log((int) Auth::id(), 'setting', 0, 'updated-background', 'Updated ' . $settingKey . ' background image.');
        Session::flash('success', 'Background uploaded for ' . $pageName . '.');
        redirect('admin/theme');
    }

    public function resolveMomoTransaction(): string
    {
        Auth::requireRole('admin');
        verifyPostRequest('admin/subscriptions');

        $transactionId = (int) ($_POST['transaction_id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));

        if ($transactionId <= 0 || !in_array($status, ['successful', 'failed'], true) || $notes === '') {
            Session::flash('error', 'Choose a valid transaction, status, and audit note.');
            redirect('admin/subscriptions');
        }

        $transaction = $this->momoTransactions->findById($transactionId);
        if ($transaction === null) {
            Session::flash('error', 'Transaction not found.');
            redirect('admin/subscriptions');
        }

        try {
            $this->momoTransactions->updateGatewayPayload((string) $transaction['external_ref'], [
                'admin_resolution' => [
                    'admin_user_id' => (int) Auth::id(),
                    'status' => $status,
                    'notes' => $notes,
                ],
            ], $status);

            if ($status === 'successful') {
                $metadata = json_decode((string) ($transaction['raw_payload_json'] ?? '{}'), true);
                $planId = (int) ($metadata['plan_id'] ?? 0);

                if ($planId > 0) {
                    $this->subscriptions->activateFromSuccessfulPayment((int) $transaction['user_id'], $planId, (string) $transaction['external_ref']);
                }
            }
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            redirect('admin/subscriptions');
        }

        $this->audit->log((int) Auth::id(), 'momo_transaction', $transactionId, 'manually-marked-' . $status, $notes);
        Session::flash('success', 'Transaction updated with an audit log.');
        redirect('admin/subscriptions');
    }

    public function approveSubscriptionPaymentIntent(): string
    {
        Auth::requireRole('admin');
        verifyPostRequest('admin/subscriptions');

        $intentId = (int) ($_POST['payment_intent_id'] ?? 0);
        $intent = $this->paymentIntents->findById($intentId);

        if ($intent === null) {
            Session::flash('error', 'Payment proof was not found.');
            redirect('admin/subscriptions');
        }

        if ((string) $intent['status'] !== SubscriptionPaymentIntent::STATUS_PENDING_VERIFICATION) {
            Session::flash('error', 'Only pending payment proofs can be approved.');
            redirect('admin/subscriptions');
        }

        if ((int) ($intent['is_late'] ?? 0) === 1) {
            Session::flash('error', 'Late submissions cannot be approved for activation.');
            redirect('admin/subscriptions');
        }

        if (trim((string) ($intent['screenshot_url'] ?? '')) === '') {
            Session::flash('error', 'This payment request has no screenshot proof to review.');
            redirect('admin/subscriptions');
        }

        $reviewedAt = date('Y-m-d H:i:s');
        $this->paymentIntents->approve($intentId, (int) Auth::id(), $reviewedAt);
        $this->paymentIntentAudit->create([
            'payment_intent_id' => $intentId,
            'actor_user_id' => (int) Auth::id(),
            'actor_type' => 'admin',
            'action' => 'approved',
            'from_status' => (string) $intent['status'],
            'to_status' => SubscriptionPaymentIntent::STATUS_APPROVED,
            'reason' => 'Admin approved manual MoMo screenshot proof.',
        ]);
        try {
            $this->notifications->emit('payment_approved', sprintf('payment_approved:%d:%s', $intentId, $reviewedAt), [
                'payment_intent_id' => $intentId,
                'entity_type' => 'payment_intent',
                'entity_id' => $intentId,
                'user_id' => (int) $intent['user_id'],
                'user_email' => (string) ($intent['email'] ?? ''),
                'user_name' => (string) (($intent['full_name'] ?? '') !== '' ? $intent['full_name'] : ($intent['email'] ?? 'User')),
                'plan_id' => (int) $intent['plan_id'],
                'plan_name' => (string) ($intent['plan_name'] ?? 'Plan'),
                'amount_expected_rwf' => (int) ($intent['amount_expected_rwf'] ?? 0),
                'currency' => 'RWF',
                'status' => SubscriptionPaymentIntent::STATUS_APPROVED,
                'reviewed_at' => $reviewedAt,
                'reviewed_by' => (int) Auth::id(),
                'deadline_at' => (string) $intent['deadline_at'],
                'intended_activation_at' => (string) $intent['intended_activation_at'],
                'payment_link' => url_for('subscriptions/index', ['intent' => $intentId]),
            ]);
            $this->notifications->processOutbox(20);
        } catch (Throwable $e) {
            error_log('Notification error during payment approval: ' . $e->getMessage());
        }
        $this->audit->log((int) Auth::id(), 'subscription_payment_intent', $intentId, 'approved', 'Approved manual MoMo screenshot proof.');

        Session::flash('success', 'Payment proof approved. The plan will activate at the intended activation time.');
        redirect('admin/subscriptions');
    }

    public function rejectSubscriptionPaymentIntent(): string
    {
        Auth::requireRole('admin');
        verifyPostRequest('admin/subscriptions');

        $intentId = (int) ($_POST['payment_intent_id'] ?? 0);
        $reason = normalize_whitespace((string) ($_POST['rejection_reason'] ?? ''));
        $intent = $this->paymentIntents->findById($intentId);

        if ($intent === null) {
            Session::flash('error', 'Payment proof was not found.');
            redirect('admin/subscriptions');
        }

        if ((string) $intent['status'] !== SubscriptionPaymentIntent::STATUS_PENDING_VERIFICATION) {
            Session::flash('error', 'Only pending payment proofs can be rejected.');
            redirect('admin/subscriptions');
        }

        if ($reason === '') {
            Session::flash('error', 'Enter a rejection reason so the user knows what to fix.');
            redirect('admin/subscriptions');
        }

        $reviewedAt = date('Y-m-d H:i:s');
        $this->paymentIntents->reject($intentId, (int) Auth::id(), $reviewedAt, $reason);
        $this->paymentIntentAudit->create([
            'payment_intent_id' => $intentId,
            'actor_user_id' => (int) Auth::id(),
            'actor_type' => 'admin',
            'action' => 'rejected',
            'from_status' => (string) $intent['status'],
            'to_status' => SubscriptionPaymentIntent::STATUS_REJECTED,
            'reason' => $reason,
        ]);
        try {
            $this->notifications->emit('payment_rejected', sprintf('payment_rejected:%d:%s', $intentId, $reviewedAt), [
                'payment_intent_id' => $intentId,
                'entity_type' => 'payment_intent',
                'entity_id' => $intentId,
                'user_id' => (int) $intent['user_id'],
                'user_email' => (string) ($intent['email'] ?? ''),
                'user_name' => (string) (($intent['full_name'] ?? '') !== '' ? $intent['full_name'] : ($intent['email'] ?? 'User')),
                'plan_id' => (int) $intent['plan_id'],
                'plan_name' => (string) ($intent['plan_name'] ?? 'Plan'),
                'amount_expected_rwf' => (int) ($intent['amount_expected_rwf'] ?? 0),
                'currency' => 'RWF',
                'status' => SubscriptionPaymentIntent::STATUS_REJECTED,
                'reviewed_at' => $reviewedAt,
                'reviewed_by' => (int) Auth::id(),
                'rejection_reason' => $reason,
                'deadline_at' => (string) $intent['deadline_at'],
                'intended_activation_at' => (string) $intent['intended_activation_at'],
                'payment_link' => url_for('subscriptions/index', ['intent' => $intentId]),
            ]);
            $this->notifications->processOutbox(20);
        } catch (Throwable $e) {
            error_log('Notification error during payment rejection: ' . $e->getMessage());
        }
        $this->audit->log((int) Auth::id(), 'subscription_payment_intent', $intentId, 'rejected', $reason);

        Session::flash('success', 'Payment proof rejected and returned to the user for re-upload.');
        redirect('admin/subscriptions');
    }

    public function messages(): string
    {
        Auth::requireRole('admin');

        $show = trim((string) ($_GET['show'] ?? 'all'));
        $messages = LeadCapture::contactMessages();

        if ($show === 'unreplied') {
            $messages = array_values(array_filter($messages, static fn (array $message): bool => !((bool) ($message['has_replies'] ?? false))));
        } else {
            $show = 'all';
        }

        return View::render('admin/messages', [
            'pageTitle' => 'Contact Messages',
            'messages' => $messages,
            'showFilter' => $show,
        ]);
    }

    public function newsletter(): string
    {
        Auth::requireRole('admin');

        $audience = trim((string) ($_GET['audience'] ?? 'all'));
        $subscriptions = LeadCapture::newsletterSubscriptions();

        if ($audience !== 'all') {
            $subscriptions = array_values(array_filter($subscriptions, static function (array $subscription) use ($audience): bool {
                $payload = is_array($subscription['payload'] ?? null) ? $subscription['payload'] : [];

                return (string) ($payload['audience'] ?? '') === $audience;
            }));
        }

        return View::render('admin/newsletter', [
            'pageTitle' => 'Newsletter Subscribers',
            'subscriptions' => $subscriptions,
            'audienceFilter' => in_array($audience, ['all', 'client', 'tasker', 'partner'], true) ? $audience : 'all',
        ]);
    }

    public function replyMessage(): string
    {
        Auth::requireRole('admin');
        verifyPostRequest('admin/messages');

        $messageId = trim((string) ($_POST['message_id'] ?? ''));
        $toEmail = trim((string) ($_POST['to_email'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $body = trim((string) ($_POST['reply_body'] ?? ''));
        $showFilter = trim((string) ($_POST['show_filter'] ?? 'all'));
        $currentAdmin = Auth::user();

        if ($messageId === '' || $toEmail === '' || $body === '') {
            Session::flash('error', 'Choose a message and enter a reply before sending.');
            redirect('admin/messages', $showFilter === 'unreplied' ? ['show' => 'unreplied'] : []);
        }

        $queued = LeadCapture::deliverAdminReply([
            'to_email' => $toEmail,
            'subject' => $subject,
            'message' => $body,
            'admin_name' => (string) ($currentAdmin['full_name'] ?? $currentAdmin['email'] ?? 'Admin'),
        ]);

        $replyPayload = [
            'original_message_id' => $messageId,
            'to_email' => $toEmail,
            'subject' => $subject,
            'message' => $body,
            'admin_user_id' => (int) Auth::id(),
            'admin_name' => (string) ($currentAdmin['full_name'] ?? $currentAdmin['email'] ?? 'Admin'),
            'delivery_status' => $queued ? 'queued' : 'logged_only',
        ];

        $saved = LeadCapture::append('contact-replies', $replyPayload);

        Session::flash(($saved || $queued) ? 'success' : 'warning', ($saved || $queued)
            ? 'Reply recorded' . ($queued ? ' and queued for delivery.' : ' locally. Email delivery could not be queued.')
            : 'The reply could not be saved or sent.');
        redirect('admin/messages', $showFilter === 'unreplied' ? ['show' => 'unreplied'] : []);
    }

    public function ads(): string
    {
        Auth::requireRole('admin');

        $editId = (int) ($_GET['id'] ?? 0);
        $editingAd = $editId > 0 ? $this->ads->findById($editId) : null;

        return View::render('admin/ads', [
            'pageTitle' => 'Manage Ads',
            'ads' => $this->ads->allForAdmin(),
            'editingAd' => $editingAd,
            'errors' => [],
            'fieldErrors' => [],
        ]);
    }

    public function saveAd(): string
    {
        Auth::requireRole('admin');
        verifyPostRequest('admin/ads');

        $adId = (int) ($_POST['ad_id'] ?? 0);
        $input = Validator::trim($_POST);
        $fieldErrors = Validator::adFields($input);
        $existingAd = $adId > 0 ? $this->ads->findById($adId) : null;
        $mediaPath = (string) ($existingAd['media_path'] ?? '');
        $mediaType = (string) ($existingAd['media_type'] ?? '');

        if ($adId > 0 && $existingAd === null) {
            Session::flash('error', 'Ad not found.');
            redirect('admin/ads');
        }

        if (
            $this->ads->hasPlacementSortOrderConflict(
                trim((string) ($input['placement'] ?? '')),
                (int) ($input['sort_order'] ?? 0),
                $adId
            )
        ) {
            $fieldErrors['sort_order'][] = 'That sort order is already in use for the selected placement.';
        }

        if (isset($_FILES['media']) && ($_FILES['media']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = $this->processAdMediaUpload($_FILES['media']);

            if (isset($uploadResult['errors'])) {
                $fieldErrors['media'] = array_merge($fieldErrors['media'] ?? [], (array) $uploadResult['errors']);
            } else {
                $mediaPath = $uploadResult['path'];
                $mediaType = $uploadResult['type'];
            }
        }

        if ($fieldErrors !== []) {
            $editingAd = $adId > 0
                ? array_merge((array) ($existingAd ?? []), $input, ['id' => $adId, 'media_path' => $mediaPath, 'media_type' => $mediaType])
                : array_merge($input, ['media_path' => $mediaPath, 'media_type' => $mediaType]);

            return View::render('admin/ads', [
                'pageTitle' => 'Manage Ads',
                'ads' => $this->ads->allForAdmin(),
                'editingAd' => $editingAd,
                'errors' => Validator::flattenFieldErrors($fieldErrors),
                'fieldErrors' => $fieldErrors,
            ]);
        }

        $payload = [
            'title' => normalize_whitespace((string) $input['title']),
            'body' => trim((string) $input['body']),
            'media_type' => $mediaType !== '' ? $mediaType : null,
            'media_path' => $mediaPath !== '' ? $mediaPath : null,
            'cta_label' => normalize_whitespace((string) ($input['cta_label'] ?? '')),
            'cta_url' => trim((string) ($input['cta_url'] ?? '')),
            'placement' => trim((string) $input['placement']),
            'sort_order' => (int) ($input['sort_order'] ?? 0),
            'is_active' => isset($input['is_active']) && (string) $input['is_active'] === '1',
        ];

        if ($adId > 0) {
            $ad = $this->ads->findById($adId);

            if ($ad === null) {
                Session::flash('error', 'Ad not found.');
                redirect('admin/ads');
            }

            $this->ads->update($adId, $payload);
            Session::flash('success', 'Ad updated successfully.');
        } else {
            $this->ads->create($payload);
            Session::flash('success', 'Ad created successfully.');
        }

        redirect('admin/ads');
    }

    public function toggleAd(): string
    {
        Auth::requireRole('admin');
        verifyPostRequest('admin/ads');

        $adId = (int) ($_POST['ad_id'] ?? 0);

        if ($adId <= 0) {
            Session::flash('error', 'Ad not found.');
            redirect('admin/ads');
        }

        $ad = $this->ads->findById($adId);

        if ($ad === null) {
            Session::flash('error', 'Ad not found.');
            redirect('admin/ads');
        }

        $this->ads->setActive($adId, !(bool) $ad['is_active']);
        Session::flash('success', 'Ad status updated.');
        redirect('admin/ads');
    }

    private function processAdMediaUpload(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['errors' => ['Media upload failed. Please try again.']];
        }

        if (!is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            return ['errors' => ['Invalid media upload.']];
        }

        if ((int) ($file['size'] ?? 0) > self::AD_MEDIA_MAX_BYTES) {
            return ['errors' => ['Media must be 15MB or smaller.']];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, (string) $file['tmp_name']) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        if ($mime === false || !array_key_exists($mime, self::AD_MEDIA_MIME)) {
            return ['errors' => ['Media must be a JPG, PNG, WebP, MP4, or WebM file.']];
        }

        $config = self::AD_MEDIA_MIME[$mime];
        $filename = sprintf('ad_%s.%s', sha1(uniqid('ad', true) . microtime(true)), $config['extension']);
        $destinationDirectory = BASE_PATH . '/public/uploads/ads';

        if (!is_dir($destinationDirectory) && !mkdir($destinationDirectory, 0755, true) && !is_dir($destinationDirectory)) {
            return ['errors' => ['Unable to save uploaded media.']];
        }

        $destinationPath = $destinationDirectory . '/' . $filename;
        if (!move_uploaded_file((string) $file['tmp_name'], $destinationPath)) {
            return ['errors' => ['Unable to save uploaded media.']];
        }

        return [
            'path' => 'uploads/ads/' . $filename,
            'type' => (string) $config['type'],
        ];
    }

    private function processThemeBackgroundUpload(array $file, string $pageName): array
    {
        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($errorCode !== UPLOAD_ERR_OK) {
            return ['errors' => ['The upload failed. Please choose a valid image and try again.']];
        }

        $size = (int) ($file['size'] ?? 0);

        if ($size <= 0 || $size > self::THEME_BACKGROUND_MAX_BYTES) {
            return ['errors' => ['Background images must be larger than 0 bytes and no more than 5MB.']];
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return ['errors' => ['The uploaded file was not received correctly by the server.']];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = (string) $finfo->file($tmpName);
        $extension = self::THEME_BACKGROUND_MIME[$mimeType] ?? null;

        if ($extension === null) {
            return ['errors' => ['Only JPG, PNG, and WEBP images are allowed for backgrounds.']];
        }

        if (@getimagesize($tmpName) === false) {
            return ['errors' => ['The selected file is not a valid image.']];
        }

        $directory = BASE_PATH . '/public/uploads/site-backgrounds';

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            return ['errors' => ['The background upload directory could not be created.']];
        }

        if (!is_writable($directory)) {
            @chmod($directory, 0777);
        }

        if (!is_writable($directory)) {
            return ['errors' => ['The background upload directory is not writable by the web server.']];
        }

        $filename = sprintf('bg_%s_%s.%s', $pageName, bin2hex(random_bytes(16)), $extension);
        $destination = $directory . '/' . $filename;

        if (!move_uploaded_file($tmpName, $destination)) {
            return ['errors' => ['The uploaded image could not be stored.']];
        }

        return [
            'path' => 'uploads/site-backgrounds/' . $filename,
        ];
    }

    public function users(): string
    {
        Auth::requireRole('admin');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pagination = pagination_params($page, 30);

        return View::render('admin/users', [
            'pageTitle' => 'Manage Users',
            'users' => $this->users->allForAdmin($pagination['limit'], $pagination['offset']),
            'pagination' => pagination_meta($page, $pagination['per_page'], $this->users->countAll()),
        ]);
    }

    public function tasks(): string
    {
        Auth::requireRole('admin');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pagination = pagination_params($page, 30);

        return View::render('admin/tasks', [
            'pageTitle' => 'Manage Tasks',
            'tasks' => $this->tasks->allForAdmin($pagination['limit'], $pagination['offset']),
            'pagination' => pagination_meta($page, $pagination['per_page'], $this->tasks->countAllForAdmin()),
        ]);
    }

    public function agreements(): string
    {
        Auth::requireRole('admin');

        return View::render('admin/agreements', [
            'pageTitle' => 'Hiring Agreements',
            'agreements' => $this->agreements->latestForAdmin(80),
        ]);
    }

    public function disputes(): string
    {
        Auth::requireRole('admin');

        return View::render('admin/disputes', [
            'pageTitle' => 'Disputes',
            'disputes' => $this->disputes->latestForAdmin(80),
        ]);
    }

    public function updateDisputeStatus(): string
    {
        Auth::requireRole('admin');
        verifyPostRequest('admin/disputes');

        $disputeId = (int) ($_POST['dispute_id'] ?? 0);
        $input = Validator::trim($_POST);
        $status = trim((string) ($input['status'] ?? ''));

        if ($disputeId <= 0) {
            Session::flash('error', 'Dispute not found.');
            redirect('admin/disputes');
        }

        $dispute = $this->disputes->findVisibleById($disputeId, (int) Auth::id(), 'admin');

        if ($dispute === null) {
            Session::flash('error', 'Dispute not found.');
            redirect('admin/disputes');
        }

        $fieldErrors = Validator::adminDisputeUpdateFields($input);

        if ($fieldErrors !== []) {
            Session::flash('error', (string) (Validator::flattenFieldErrors($fieldErrors)[0] ?? 'Please review the dispute update form.'));
            redirect('disputes/show', ['id' => $disputeId]);
        }

        try {
            $this->disputes->updateStatus(
                $disputeId,
                $status,
                trim((string) ($input['admin_notes'] ?? '')),
                (int) Auth::id()
            );
            $this->audit->log(
                (int) Auth::id(),
                'dispute',
                $disputeId,
                'status-updated-to-' . $status,
                trim((string) ($input['admin_notes'] ?? '')) !== '' ? trim((string) ($input['admin_notes'] ?? '')) : null
            );
            Session::flash('success', 'Dispute status updated.');
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
        }

        redirect('disputes/show', ['id' => $disputeId]);
    }

    public function toggleUser(): string
    {
        Auth::requireRole('admin');
        verifyPostRequest('admin/users');
        $userId = (int) ($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            Session::flash('error', 'User not found.');
            redirect('admin/users');
        }

        if ($userId === (int) Auth::id()) {
            Session::flash('error', 'You cannot deactivate your own admin account.');
            redirect('admin/users');
        }

        $user = $this->users->findById($userId);

        if ($user === null) {
            Session::flash('error', 'User not found.');
            redirect('admin/users');
        }

        $newStatus = !(bool) $user['is_active'];
        $this->users->setActive($userId, $newStatus);
        $this->audit->log((int) Auth::id(), 'user', $userId, $newStatus ? 'activated' : 'deactivated');

        Session::flash('success', 'User status updated.');
        redirect('admin/users');
    }

    public function toggleTask(): string
    {
        Auth::requireRole('admin');
        verifyPostRequest('admin/tasks');
        $taskId = (int) ($_POST['task_id'] ?? 0);

        if ($taskId <= 0) {
            Session::flash('error', 'Task not found.');
            redirect('admin/tasks');
        }

        $task = $this->tasks->findById($taskId);

        if ($task === null) {
            Session::flash('error', 'Task not found.');
            redirect('admin/tasks');
        }

        $newActive = !(bool) $task['is_active'];
        $this->tasks->setActive($taskId, $newActive);
        $this->audit->log((int) Auth::id(), 'task', $taskId, $newActive ? 'activated' : 'deactivated');

        Session::flash('success', 'Task status updated.');
        redirect('admin/tasks');
    }
}

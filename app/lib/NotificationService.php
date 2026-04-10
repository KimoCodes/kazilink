<?php

declare(strict_types=1);

final class NotificationService
{
    private NotificationEventOutbox $outbox;
    private Notification $notifications;
    private NotificationPreference $preferences;
    private User $users;
    private EmailDeliveryService $emails;

    public function __construct()
    {
        $this->outbox = new NotificationEventOutbox();
        $this->notifications = new Notification();
        $this->preferences = new NotificationPreference();
        $this->users = new User();
        $this->emails = new EmailDeliveryService();
    }

    public function emit(string $eventName, string $idempotencyKey, array $payload): ?string
    {
        return $this->outbox->create($eventName, $idempotencyKey, $payload);
    }

    public function processOutbox(int $limit = 100): int
    {
        $processed = 0;

        foreach ($this->outbox->pending($limit) as $event) {
            $payload = json_decode((string) ($event['payload_json'] ?? '{}'), true);
            $payload = is_array($payload) ? $payload : [];
            $payload['event_id'] = (string) $event['event_id'];
            $this->dispatchEvent((string) $event['event_name'], $payload);
            $this->outbox->markProcessed((int) $event['id']);
            $processed++;
        }

        // Process actual outbound emails synchronously
        $this->deliverQueuedEmails($limit);

        return $processed;
    }

    public function deliverQueuedEmails(int $limit = 50): int
    {
        return $this->emails->processDue($limit);
    }

    private function dispatchEvent(string $eventName, array $payload): void
    {
        $templates = $this->templates($eventName, $payload);
        $eventId = (string) ($payload['event_id'] ?? '');

        foreach ($this->recipientSpecs($eventName, $payload) as $recipient) {
            $prefs = $this->preferences->forUser((int) $recipient['recipient_id']);

            if (($recipient['notify_in_app'] ?? false) && $this->channelEnabled($recipient['recipient_type'], 'in_app', $prefs)) {
                $template = $templates[$recipient['template_key']]['in_app'] ?? null;
                if (is_array($template)) {
                    $this->notifications->create([
                        'recipient_type' => $recipient['recipient_type'],
                        'recipient_id' => $recipient['recipient_id'],
                        'channel' => 'in_app',
                        'title' => $template['title'],
                        'body' => $template['body'],
                        'link_url' => $template['link_url'] ?? null,
                        'event_name' => $eventName,
                        'event_id' => $eventId,
                        'status' => 'unread',
                    ]);
                }
            }

            if (($recipient['notify_email'] ?? false) && $this->channelEnabled($recipient['recipient_type'], 'email', $prefs)) {
                $template = $templates[$recipient['template_key']]['email'] ?? null;
                if (is_array($template)) {
                    $entityType = (string) ($payload['entity_type'] ?? 'event');
                    $entityId = (int) ($payload['entity_id'] ?? ($payload['payment_intent_id'] ?? 0));
                    $this->emails->queue([
                        'event_name' => $eventName,
                        'entity_type' => $entityType,
                        'entity_id' => $entityId,
                        'recipient_email' => (string) ($recipient['email'] ?? ''),
                        'recipient_name' => (string) ($payload['user_name'] ?? ''),
                        'template_name' => (string) ($template['template_name'] ?? 'generic_notification'),
                        'subject' => (string) $template['subject'],
                        'template_data' => (array) ($template['template_data'] ?? []),
                        'idempotency_key' => sprintf('%s:%s:%d:%s', $eventName, $entityType, $entityId, mb_strtolower(trim((string) ($recipient['email'] ?? '')))),
                    ]);
                }
            }
        }
    }

    private function recipientSpecs(string $eventName, array $payload): array
    {
        $specs = [];

        if (in_array($eventName, ['payment_submitted', 'payment_expiring_soon'], true)) {
            foreach ($this->adminRecipients() as $admin) {
                $specs[] = [
                    'recipient_type' => 'admin',
                    'recipient_id' => (int) $admin['id'],
                    'email' => (string) ($admin['email'] ?? ''),
                    'template_key' => 'admin',
                    'notify_in_app' => true,
                    'notify_email' => true,
                ];
            }
        }

        if (!empty($payload['user_id'])) {
            $user = $this->users->findById((int) $payload['user_id']);
            if (is_array($user)) {
                $specs[] = [
                    'recipient_type' => 'user',
                    'recipient_id' => (int) $user['id'],
                    'email' => (string) ($user['email'] ?? ''),
                    'template_key' => 'user',
                    'notify_in_app' => true,
                    'notify_email' => true,
                ];
            }
        }

        return $specs;
    }

    private function adminRecipients(): array
    {
        return array_values(array_filter(
            $this->users->allForAdmin(200, 0),
            static fn (array $user): bool => (string) ($user['role'] ?? '') === 'admin' && (int) ($user['is_active'] ?? 0) === 1
        ));
    }

    private function channelEnabled(string $recipientType, string $channel, array $preferences): bool
    {
        if ($recipientType === 'admin') {
            return $channel === 'in_app'
                ? (int) ($preferences['admin_inapp_enabled'] ?? 1) === 1
                : (int) ($preferences['admin_email_enabled'] ?? 1) === 1;
        }

        return $channel === 'in_app'
            ? (int) ($preferences['user_inapp_enabled'] ?? 1) === 1
            : (int) ($preferences['user_email_enabled'] ?? 1) === 1;
    }

    private function templates(string $eventName, array $payload): array
    {
        $planName = (string) ($payload['plan_name'] ?? 'your plan');
        $amount = moneyRwf((int) ($payload['amount_expected_rwf'] ?? 0));
        $deadline = dateFmt((string) ($payload['deadline_at'] ?? ''));
        $activation = dateFmt((string) ($payload['intended_activation_at'] ?? ''));
        $submittedAt = dateFmt((string) ($payload['submitted_at'] ?? ''));
        $reviewedAt = dateFmt((string) ($payload['reviewed_at'] ?? ''));
        $status = ucfirst(str_replace('_', ' ', (string) ($payload['status'] ?? 'pending_verification')));
        $userName = (string) ($payload['user_name'] ?? $payload['user_email'] ?? 'A user');
        $paymentLink = (string) ($payload['payment_link'] ?? url_for('subscriptions/index', ['intent' => (int) ($payload['payment_intent_id'] ?? 0)]));
        $adminLink = (string) ($payload['admin_review_link'] ?? url_for('admin/subscriptions'));
        $reason = (string) ($payload['rejection_reason'] ?? 'No reason provided.');
        $supportEmail = (string) email_config('support_email', app_config('contact.email', 'support@yourdomain.com'));
        $supportPhone = (string) email_config('support_phone', app_config('contact.phone', '+250 000 000 000'));
        $absolutePaymentLink = $this->absoluteLink(
            (string) ($payload['payment_link'] ?? ''),
            absolute_url('subscriptions/index', ['intent' => (int) ($payload['payment_intent_id'] ?? 0)])
        );
        $absoluteAdminLink = $this->absoluteLink(
            (string) ($payload['admin_review_link'] ?? ''),
            absolute_url('admin/subscriptions')
        );
        $commonTemplateData = [
            'platform_name' => (string) app_config('name', 'Kazilink'),
            'support_email' => $supportEmail,
            'support_phone' => $supportPhone,
            'plan_name' => $planName,
            'amount' => $amount,
            'submitted_at' => $submittedAt,
            'reviewed_at' => $reviewedAt,
            'deadline_at' => $deadline,
            'intended_activation_at' => $activation,
            'user_name' => $userName,
            'payment_link' => $absolutePaymentLink,
            'admin_review_link' => $absoluteAdminLink,
            'rejection_reason' => $reason,
            'status' => $status,
        ];

        return match ($eventName) {
            'payment_submitted' => [
                'admin' => [
                    'in_app' => [
                        'title' => 'Payment submitted for review',
                        'body' => sprintf('%s submitted %s payment proof for %s. Review before %s.', $userName, $planName, $amount, $deadline),
                        'link_url' => $adminLink,
                    ],
                    'email' => [
                        'subject' => sprintf('Payment submitted: %s for %s', $planName, $userName),
                        'body' => "A payment proof has been submitted for review.\n\nUser: {$userName}\nPlan: {$planName}\nAmount: {$amount}\nSubmitted at: {$submittedAt}\nDeadline: {$deadline}\nIntended activation: {$activation}\nStatus: {$status}\n\nReview payment: {$adminLink}",
                        'link_url' => $adminLink,
                        'template_name' => 'payment_submitted_admin',
                        'template_data' => array_merge($commonTemplateData, [
                            'subject' => sprintf('Payment submitted: %s for %s', $planName, $userName),
                        ]),
                    ],
                ],
                'user' => [
                    'in_app' => [
                        'title' => 'Payment submitted successfully',
                        'body' => sprintf('Your %s payment proof for %s is now pending verification.', $planName, $amount),
                        'link_url' => $paymentLink,
                    ],
                    'email' => [
                        'subject' => sprintf('We received your payment proof for %s', $planName),
                        'body' => "Your payment proof has been received.\n\nPlan: {$planName}\nAmount: {$amount}\nSubmitted at: {$submittedAt}\nDeadline: {$deadline}\nIntended activation: {$activation}\nStatus: Pending Verification\n\nTrack payment: {$paymentLink}",
                        'link_url' => $paymentLink,
                        'template_name' => 'payment_submitted_user',
                        'template_data' => array_merge($commonTemplateData, [
                            'subject' => sprintf('We received your payment proof for %s', $planName),
                        ]),
                    ],
                ],
            ],
            'payment_approved' => [
                'user' => [
                    'in_app' => [
                        'title' => 'Payment approved',
                        'body' => sprintf('Your %s payment for %s was approved. Activation is scheduled for %s.', $planName, $amount, $activation),
                        'link_url' => $paymentLink,
                    ],
                    'email' => [
                        'subject' => sprintf('Your %s payment was approved', $planName),
                        'body' => "Your payment has been approved.\n\nPlan: {$planName}\nAmount: {$amount}\nApproved at: {$reviewedAt}\nActivation time: {$activation}\nStatus: Approved\n\nView details: {$paymentLink}",
                        'link_url' => $paymentLink,
                        'template_name' => 'payment_approved',
                        'template_data' => array_merge($commonTemplateData, [
                            'subject' => sprintf('Your %s payment was approved', $planName),
                        ]),
                    ],
                ],
            ],
            'payment_rejected' => [
                'user' => [
                    'in_app' => [
                        'title' => 'Payment rejected',
                        'body' => sprintf('Your %s payment proof was rejected. Reason: %s', $planName, $reason),
                        'link_url' => $paymentLink,
                    ],
                    'email' => [
                        'subject' => sprintf('Your %s payment needs attention', $planName),
                        'body' => "Your payment proof was rejected.\n\nPlan: {$planName}\nAmount: {$amount}\nReviewed at: {$reviewedAt}\nDeadline: {$deadline}\nIntended activation: {$activation}\nStatus: Rejected\nReason: {$reason}\n\nUpload a new proof: {$paymentLink}",
                        'link_url' => $paymentLink,
                        'template_name' => 'payment_rejected',
                        'template_data' => array_merge($commonTemplateData, [
                            'subject' => sprintf('Your %s payment needs attention', $planName),
                        ]),
                    ],
                ],
            ],
            'payment_expiring_soon' => [
                'admin' => [
                    'in_app' => [
                        'title' => 'Payment deadline approaching',
                        'body' => sprintf('%s has a %s payment awaiting action. Deadline is %s.', $userName, $planName, $deadline),
                        'link_url' => $adminLink,
                    ],
                    'email' => [
                        'subject' => sprintf('Payment expiring soon: %s for %s', $planName, $userName),
                        'body' => "A submitted payment is approaching its deadline.\n\nUser: {$userName}\nPlan: {$planName}\nAmount: {$amount}\nCurrent status: {$status}\nDeadline: {$deadline}\nIntended activation: {$activation}\n\nReview now: {$adminLink}",
                        'link_url' => $adminLink,
                        'template_name' => 'generic_notification',
                        'template_data' => array_merge($commonTemplateData, [
                            'subject' => sprintf('Payment expiring soon: %s for %s', $planName, $userName),
                            'message' => "A submitted payment is approaching its deadline.\n\nUser: {$userName}\nPlan: {$planName}\nAmount: {$amount}\nCurrent status: {$status}\nDeadline: {$deadline}\nIntended activation: {$activation}",
                            'link_url' => $absoluteAdminLink,
                            'link_label' => 'Review payment',
                        ]),
                    ],
                ],
                'user' => [
                    'in_app' => [
                        'title' => 'Payment deadline is approaching',
                        'body' => sprintf('Your %s payment for %s expires soon. Deadline: %s.', $planName, $amount, $deadline),
                        'link_url' => $paymentLink,
                    ],
                    'email' => [
                        'subject' => sprintf('Your %s payment deadline is approaching', $planName),
                        'body' => "Your payment request is approaching its deadline.\n\nPlan: {$planName}\nAmount: {$amount}\nCurrent status: {$status}\nDeadline: {$deadline}\nIntended activation: {$activation}\n\nView payment: {$paymentLink}",
                        'link_url' => $paymentLink,
                        'template_name' => 'generic_notification',
                        'template_data' => array_merge($commonTemplateData, [
                            'subject' => sprintf('Your %s payment deadline is approaching', $planName),
                            'message' => "Your payment request is approaching its deadline.\n\nPlan: {$planName}\nAmount: {$amount}\nCurrent status: {$status}\nDeadline: {$deadline}\nIntended activation: {$activation}",
                            'link_url' => $absolutePaymentLink,
                            'link_label' => 'View payment',
                        ]),
                    ],
                ],
            ],
            default => [],
        };
    }

    private function absoluteLink(string $candidate, string $fallback): string
    {
        $candidate = trim($candidate);
        if ($candidate === '') {
            return $fallback;
        }

        if (preg_match('#^https?://#i', $candidate) === 1) {
            return $candidate;
        }

        return $fallback;
    }
}

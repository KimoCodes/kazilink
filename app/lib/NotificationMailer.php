<?php

declare(strict_types=1);

final class NotificationMailer
{
    private EmailService $emails;

    public function __construct()
    {
        $this->emails = new EmailService();
    }

    public function send(array $notification): array
    {
        $recipient = trim((string) ($notification['email'] ?? ''));
        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'reason' => 'invalid_recipient_email', 'retryable' => false];
        }

        $template = trim((string) ($notification['template_name'] ?? ''));
        $templateData = json_decode((string) ($notification['template_data_json'] ?? '{}'), true);
        $templateData = is_array($templateData) ? $templateData : [];
        $templateData['subject'] = (string) ($templateData['subject'] ?? $notification['title'] ?? '');
        $templateData['message'] = (string) ($templateData['message'] ?? $notification['body'] ?? '');
        $templateData['link_url'] = (string) ($templateData['link_url'] ?? $notification['link_url'] ?? '');
        $templateData['recipient_name'] = (string) ($notification['full_name'] ?? '');

        if ($template === '') {
            $template = 'generic_notification';
        }

        if ($this->emails->sendEmail([
            'email' => $recipient,
            'name' => trim((string) ($notification['full_name'] ?? '')),
        ], (string) $notification['title'], $template, $templateData)) {
            return ['ok' => true, 'reason' => null, 'retryable' => false];
        }

        return [
            'ok' => false,
            'reason' => (string) ($this->emails->lastError() ?? 'smtp_send_failed'),
            'retryable' => $this->emails->lastFailureIsRetryable(),
        ];
    }
}

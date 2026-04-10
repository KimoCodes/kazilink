<?php

declare(strict_types=1);

require_once BASE_PATH . '/src/Exception.php';
require_once BASE_PATH . '/src/PHPMailer.php';
require_once BASE_PATH . '/src/SMTP.php';
require_once BASE_PATH . '/app/lib/EmailTemplateCatalog.php';

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

final class EmailService
{
    private array $config;
    private ?string $lastError = null;
    private bool $lastRetryable = false;
    private ?string $lastProviderMessageId = null;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? (require BASE_PATH . '/config/email_config.php');
    }

    public function sendEmail(array|string $to, string $subject, string $template, array $data = []): bool
    {
        $htmlBody = $this->renderTemplate($template, array_merge($data, ['subject' => $subject]));
        $plainBody = $this->toPlainText($htmlBody);

        return ($this->sendRenderedEmail($to, $subject, $htmlBody, $plainBody)['ok'] ?? false) === true;
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function lastFailureIsRetryable(): bool
    {
        return $this->lastRetryable;
    }

    public function lastProviderMessageId(): ?string
    {
        return $this->lastProviderMessageId;
    }

    public function sendRenderedEmail(array|string $to, string $subject, string $htmlBody, ?string $plainBody = null): array
    {
        $this->lastError = null;
        $this->lastRetryable = false;
        $this->lastProviderMessageId = null;

        $recipients = $this->normalizeRecipients($to);
        if ($recipients === []) {
            $this->lastError = 'invalid_recipient_email';

            return ['ok' => false, 'reason' => $this->lastError, 'retryable' => false, 'provider_message_id' => null];
        }

        $sanitizedSubject = $this->sanitizeHeaderValue($subject);
        if ($sanitizedSubject === '') {
            $this->lastError = 'invalid_email_subject';

            return ['ok' => false, 'reason' => $this->lastError, 'retryable' => false, 'provider_message_id' => null];
        }

        if (trim((string) ($this->config['host'] ?? '')) === '' || trim((string) ($this->config['username'] ?? '')) === '' || (string) ($this->config['password'] ?? '') === '') {
            $this->lastError = 'smtp_not_configured';

            return ['ok' => false, 'reason' => $this->lastError, 'retryable' => false, 'provider_message_id' => null];
        }

        if (!(bool) ($this->config['enabled'] ?? true)) {
            $this->lastError = 'email_disabled';

            return ['ok' => false, 'reason' => $this->lastError, 'retryable' => false, 'provider_message_id' => null];
        }

        try {
            $mailer = new PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host = (string) $this->config['host'];
            $mailer->Port = (int) $this->config['port'];
            $mailer->SMTPAuth = true;
            $mailer->Username = (string) $this->config['username'];
            $mailer->Password = (string) $this->config['password'];
            $mailer->Timeout = (int) ($this->config['timeout'] ?? 20);
            $mailer->CharSet = 'UTF-8';
            $mailer->setFrom(
                $this->sanitizeEmail((string) $this->config['from_email']),
                $this->sanitizeHeaderValue((string) ($this->config['from_name'] ?? 'Kazilink'))
            );
            $replyToEmail = $this->sanitizeEmail((string) ($this->config['reply_to_email'] ?? ''));
            if ($replyToEmail !== '' && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
                $mailer->addReplyTo($replyToEmail, $this->sanitizeHeaderValue((string) ($this->config['reply_to_name'] ?? $this->config['from_name'] ?? 'Kazilink')));
            }

            $secure = strtolower((string) ($this->config['secure'] ?? 'tls'));
            if ($secure === 'tls') {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($secure === 'ssl') {
                $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }

            foreach ($recipients as $recipient) {
                $mailer->addAddress($recipient['email'], $recipient['name']);
            }

            $mailer->isHTML(true);
            $mailer->Subject = $sanitizedSubject;
            $mailer->Body = $htmlBody;
            $mailer->AltBody = $plainBody ?? $this->toPlainText($htmlBody);
            $mailer->send();
            $this->lastProviderMessageId = trim((string) $mailer->getLastMessageID()) !== '' ? trim((string) $mailer->getLastMessageID()) : null;

            return ['ok' => true, 'reason' => null, 'retryable' => false, 'provider_message_id' => $this->lastProviderMessageId];
        } catch (PHPMailerException $exception) {
            $this->lastError = mb_substr($exception->getMessage(), 0, 255);
            $this->lastRetryable = $this->isRetryableSmtpError($this->lastError);
            error_log('Email delivery failed: ' . $this->lastError);

            return ['ok' => false, 'reason' => $this->lastError, 'retryable' => $this->lastRetryable, 'provider_message_id' => null];
        } catch (Throwable $exception) {
            $this->lastError = mb_substr($exception->getMessage(), 0, 255);
            $this->lastRetryable = true;
            error_log('Email delivery failed: ' . $this->lastError);

            return ['ok' => false, 'reason' => $this->lastError, 'retryable' => true, 'provider_message_id' => null];
        }
    }

    private function normalizeRecipients(array|string $to): array
    {
        $input = is_array($to) ? $to : [$to];
        $recipients = [];

        foreach ($input as $recipient) {
            if (is_array($recipient)) {
                $email = $this->sanitizeEmail((string) ($recipient['email'] ?? ''));
                $name = $this->sanitizeHeaderValue((string) ($recipient['name'] ?? ''));
            } else {
                $email = $this->sanitizeEmail((string) $recipient);
                $name = '';
            }

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $recipients[$email] = [
                'email' => $email,
                'name' => $name,
            ];
        }

        return array_values($recipients);
    }

    private function renderTemplate(string $template, array $data): string
    {
        return (new EmailTemplateCatalog())->render($template, $data)['html'];
    }

    private function toPlainText(string $html): string
    {
        $decoded = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], PHP_EOL, $html)), ENT_QUOTES, 'UTF-8');
        $decoded = preg_replace("/[\r\n]{3,}/", PHP_EOL . PHP_EOL, $decoded) ?? $decoded;

        return trim($decoded);
    }

    private function sanitizeHeaderValue(string $value): string
    {
        $sanitized = trim(str_replace(["\r", "\n"], ' ', $value));
        $sanitized = preg_replace('/\s+/', ' ', $sanitized) ?? $sanitized;

        return mb_substr($sanitized, 0, 190);
    }

    private function sanitizeEmail(string $email): string
    {
        return trim(str_replace(["\r", "\n"], '', $email));
    }

    public function sendNewsletter(array $data): bool
    {
        $to = $data['to'] ?? '';
        $subject = $data['subject'] ?? '';
        $content = $data['content'] ?? '';
        
        if (empty($to) || empty($subject) || empty($content)) {
            $this->lastError = 'Missing required fields: to, subject, or content';
            return false;
        }
        
        // Create HTML email with newsletter content
        $htmlBody = $this->createNewsletterHtml($subject, $content, $data);
        $plainBody = $this->toPlainText($htmlBody);
        
        $result = $this->sendRenderedEmail($to, $subject, $htmlBody, $plainBody);
        return ($result['ok'] ?? false) === true;
    }

    private function createNewsletterHtml(string $subject, string $content, array $data = []): string
    {
        $baseUrl = $data['base_url'] ?? app_config('base_url', 'http://localhost');
        $siteName = app_config('name', 'Kazilink');
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$subject}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0d9488; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #ffffff; padding: 30px; border: 1px solid #e5e7eb; }
                .footer { background: #f8fafc; padding: 20px; text-align: center; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px; font-size: 12px; color: #6b7280; }
                .footer a { color: #0d9488; text-decoration: none; }
                .unsubscribe { margin-top: 15px; font-size: 11px; }
                .unsubscribe a { color: #ef4444; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>{$siteName}</h1>
                <p>Newsletter</p>
            </div>
            <div class='content'>
                {$content}
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " {$siteName}. All rights reserved.</p>
                <p>This email was sent to you because you subscribed to receive updates from {$siteName}.</p>
                <div class='unsubscribe'>
                    <a href='{$baseUrl}/unsubscribe?email=" . urlencode($to ?? '') . "'>Unsubscribe</a>
                </div>
            </div>
        </body>
        </html>";
    }

    private function isRetryableSmtpError(string $message): bool
    {
        $normalized = strtolower($message);

        foreach (['timed out', 'temporary', 'try again', 'could not connect', 'connection refused', 'server unavailable'] as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }
}

<?php

declare(strict_types=1);

final class LeadCapture
{
    public static function append(string $channel, array $payload): bool
    {
        $directory = self::resolveCaptureDirectory();

        if ($directory === null) {
            return false;
        }

        $safeChannel = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($channel)) ?: 'capture';
        $filePath = $directory . '/' . $safeChannel . '.jsonl';
        $record = [
            'recorded_at' => gmdate(DATE_ATOM),
            'channel' => $safeChannel,
            'route' => current_route(),
            'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'user_id' => Auth::id(),
            'payload' => $payload,
        ];

        $json = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return false;
        }

        return file_put_contents($filePath, $json . PHP_EOL, FILE_APPEND | LOCK_EX) !== false;
    }

    public static function contactMessages(): array
    {
        $messages = self::readChannelRecords('contact');
        $replies = self::readChannelRecords('contact-replies');
        $repliesByMessageId = [];

        foreach ($replies as $reply) {
            $messageId = (string) ($reply['payload']['original_message_id'] ?? '');

            if ($messageId === '') {
                continue;
            }

            $repliesByMessageId[$messageId][] = $reply;
        }

        foreach ($messages as &$message) {
            $messageId = (string) ($message['id'] ?? '');
            $messageReplies = $repliesByMessageId[$messageId] ?? [];
            usort($messageReplies, static function (array $left, array $right): int {
                return strcmp((string) ($right['recorded_at'] ?? ''), (string) ($left['recorded_at'] ?? ''));
            });

            $latestReply = $messageReplies[0] ?? null;
            $latestReplyPayload = is_array($latestReply['payload'] ?? null) ? $latestReply['payload'] : [];
            $latestStatus = trim((string) ($latestReplyPayload['delivery_status'] ?? ''));

            $message['replies'] = $messageReplies;
            $message['has_replies'] = $messageReplies !== [];
            $message['reply_count'] = count($messageReplies);
            $message['latest_reply_at'] = (string) ($latestReply['recorded_at'] ?? '');
            $message['latest_reply_status'] = $latestStatus !== '' ? $latestStatus : ($messageReplies !== [] ? 'logged_only' : 'not_replied');
        }
        unset($message);

        usort($messages, static function (array $left, array $right): int {
            return strcmp((string) ($right['recorded_at'] ?? ''), (string) ($left['recorded_at'] ?? ''));
        });

        return $messages;
    }

    public static function newsletterSubscriptions(): array
    {
        $subscriptions = self::readChannelRecords('newsletter');

        usort($subscriptions, static function (array $left, array $right): int {
            return strcmp((string) ($right['recorded_at'] ?? ''), (string) ($left['recorded_at'] ?? ''));
        });

        return $subscriptions;
    }

    public static function newsletterAlreadySubscribed(string $email): bool
    {
        $normalizedEmail = mb_strtolower(trim($email));

        if ($normalizedEmail === '') {
            return false;
        }

        foreach (self::newsletterSubscriptions() as $subscription) {
            $payload = is_array($subscription['payload'] ?? null) ? $subscription['payload'] : [];
            $existingEmail = mb_strtolower(trim((string) ($payload['email'] ?? '')));

            if ($existingEmail === $normalizedEmail) {
                return true;
            }
        }

        return false;
    }

    public static function deliverContactEmail(array $payload): bool
    {
        $recipient = trim((string) app_config('contact.email', ''));

        if ($recipient === '') {
            return false;
        }

        if (!function_exists('mail')) {
            return false;
        }

        $submitterName = trim((string) ($payload['name'] ?? ''));
        $submitterEmail = trim((string) ($payload['email'] ?? ''));
        $topic = trim((string) ($payload['topic'] ?? 'General inquiry'));
        $company = trim((string) ($payload['company'] ?? ''));
        $message = trim((string) ($payload['message'] ?? ''));

        $subject = '[Kazilink Contact] ' . ($topic !== '' ? $topic : 'New message');
        $bodyLines = [
            'A new contact form submission was received.',
            '',
            'Name: ' . ($submitterName !== '' ? $submitterName : 'Not provided'),
            'Email: ' . ($submitterEmail !== '' ? $submitterEmail : 'Not provided'),
            'Company: ' . ($company !== '' ? $company : 'Not provided'),
            'Topic: ' . ($topic !== '' ? $topic : 'Not provided'),
            'Submitted at: ' . date(DATE_ATOM),
            'Route: ' . current_route(),
            '',
            'Message:',
            $message !== '' ? $message : '(empty)',
        ];

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: Kazilink <no-reply@kazilink.local>',
        ];

        if ($submitterEmail !== '' && filter_var($submitterEmail, FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: ' . $submitterName . ' <' . $submitterEmail . '>';
        }

        return @mail($recipient, $subject, implode(PHP_EOL, $bodyLines), implode(PHP_EOL, $headers));
    }

    public static function deliveryStatusMeta(string $status): array
    {
        return match ($status) {
            'sent' => [
                'label' => 'Email sent',
                'pill_class' => 'pill-success',
                'description' => 'Reply was emailed and logged locally.',
            ],
            'logged_only' => [
                'label' => 'Logged only',
                'pill_class' => 'pill-warning',
                'description' => 'Reply was stored locally, but email delivery was unavailable on this machine.',
            ],
            default => [
                'label' => 'Awaiting reply',
                'pill_class' => 'pill-info',
                'description' => 'No admin reply has been recorded for this message yet.',
            ],
        };
    }

    public static function deliverAdminReply(array $payload): bool
    {
        $recipient = trim((string) ($payload['to_email'] ?? ''));
        $subjectSource = trim((string) ($payload['subject'] ?? ''));
        $message = trim((string) ($payload['message'] ?? ''));
        $adminName = trim((string) ($payload['admin_name'] ?? 'Admin'));
        $businessEmail = trim((string) app_config('contact.email', ''));

        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL) || $message === '') {
            return false;
        }

        if (!function_exists('mail')) {
            return false;
        }

        $subject = $subjectSource !== '' ? 'Re: ' . $subjectSource : 'Reply from ' . app_config('name', 'Kazilink');
        $bodyLines = [
            'Hello,',
            '',
            $message,
            '',
            'Best regards,',
            $adminName,
            app_config('name', 'Kazilink'),
        ];

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . app_config('name', 'Kazilink') . ' <no-reply@kazilink.local>',
        ];

        if ($businessEmail !== '' && filter_var($businessEmail, FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: ' . app_config('name', 'Kazilink') . ' <' . $businessEmail . '>';
        }

        return @mail($recipient, $subject, implode(PHP_EOL, $bodyLines), implode(PHP_EOL, $headers));
    }

    public static function deliverNewsletterNotification(array $payload): bool
    {
        $recipient = trim((string) app_config('contact.email', ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $audience = trim((string) ($payload['audience_label'] ?? $payload['audience'] ?? 'General updates'));
        $sourceRoute = trim((string) ($payload['source_route'] ?? current_route()));

        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL) || $email === '') {
            return false;
        }

        if (!function_exists('mail')) {
            return false;
        }

        $subject = '[Kazilink Newsletter] New subscriber';
        $bodyLines = [
            'A new newsletter subscription was captured.',
            '',
            'Email: ' . $email,
            'Audience: ' . $audience,
            'Source route: ' . ($sourceRoute !== '' ? $sourceRoute : 'Unknown'),
            'Captured at: ' . date(DATE_ATOM),
        ];

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . app_config('name', 'Kazilink') . ' <no-reply@kazilink.local>',
            'Reply-To: ' . app_config('name', 'Kazilink') . ' <' . $recipient . '>',
        ];

        return @mail($recipient, $subject, implode(PHP_EOL, $bodyLines), implode(PHP_EOL, $headers));
    }

    private static function resolveCaptureDirectory(): ?string
    {
        $candidates = self::captureDirectoryCandidates();

        foreach ($candidates as $directory) {
            if (is_dir($directory)) {
                return $directory;
            }

            if (@mkdir($directory, 0775, true) || is_dir($directory)) {
                return $directory;
            }
        }

        return null;
    }

    private static function readChannelRecords(string $channel): array
    {
        $records = [];
        $safeChannel = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($channel)) ?: 'capture';

        foreach (self::channelFileCandidates($safeChannel) as $filePath) {
            if (!is_file($filePath) || !is_readable($filePath)) {
                continue;
            }

            $handle = fopen($filePath, 'rb');
            if ($handle === false) {
                continue;
            }

            $lineNumber = 0;

            while (($line = fgets($handle)) !== false) {
                $lineNumber++;
                $decoded = json_decode(trim($line), true);

                if (!is_array($decoded)) {
                    continue;
                }

                $records[] = [
                    'id' => sha1($filePath . '|' . $lineNumber . '|' . (string) ($decoded['recorded_at'] ?? '')),
                    'source_file' => $filePath,
                    'source_line' => $lineNumber,
                    'recorded_at' => (string) ($decoded['recorded_at'] ?? ''),
                    'channel' => (string) ($decoded['channel'] ?? $safeChannel),
                    'route' => (string) ($decoded['route'] ?? ''),
                    'ip' => (string) ($decoded['ip'] ?? ''),
                    'user_agent' => (string) ($decoded['user_agent'] ?? ''),
                    'user_id' => isset($decoded['user_id']) ? (int) $decoded['user_id'] : null,
                    'payload' => is_array($decoded['payload'] ?? null) ? $decoded['payload'] : [],
                ];
            }

            fclose($handle);
        }

        return $records;
    }

    private static function channelFileCandidates(string $channel): array
    {
        $paths = [];

        foreach (self::captureDirectoryCandidates() as $directory) {
            $paths[] = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $channel . '.jsonl';
        }

        return array_values(array_unique($paths));
    }

    private static function captureDirectoryCandidates(): array
    {
        return [
            storage_path('submissions'),
            BASE_PATH . '/public/uploads/submissions',
            rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'informal-submissions',
        ];
    }
}

<?php

declare(strict_types=1);

final class LeadCapture
{
    public static function append(string $channel, array $payload): bool
    {
        $directory = storage_path('submissions');

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
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
}

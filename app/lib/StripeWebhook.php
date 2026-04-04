<?php

declare(strict_types=1);

final class StripeWebhook
{
    public static function verifyAndDecode(string $payload, string $signatureHeader, string $secret, int $toleranceSeconds = 300): array
    {
        $secret = trim($secret);

        if ($secret === '') {
            throw new RuntimeException('Missing Stripe webhook secret.');
        }

        $parts = [];

        foreach (explode(',', $signatureHeader) as $segment) {
            if (!str_contains($segment, '=')) {
                continue;
            }

            [$key, $value] = explode('=', trim($segment), 2);
            $parts[$key][] = $value;
        }

        $timestamp = isset($parts['t'][0]) ? (int) $parts['t'][0] : 0;
        $signatures = $parts['v1'] ?? [];

        if ($timestamp <= 0 || $signatures === []) {
            throw new RuntimeException('Invalid Stripe signature header.');
        }

        if (abs(time() - $timestamp) > $toleranceSeconds) {
            throw new RuntimeException('Stripe signature timestamp is outside the allowed tolerance.');
        }

        $expectedSignature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        $verified = false;

        foreach ($signatures as $signature) {
            if (hash_equals($expectedSignature, $signature)) {
                $verified = true;
                break;
            }
        }

        if (!$verified) {
            throw new RuntimeException('Stripe signature verification failed.');
        }

        $event = json_decode($payload, true);

        if (!is_array($event)) {
            throw new RuntimeException('Invalid Stripe webhook payload.');
        }

        return $event;
    }
}

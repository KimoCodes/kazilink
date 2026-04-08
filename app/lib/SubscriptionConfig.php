<?php

declare(strict_types=1);

final class SubscriptionConfig
{
    public static function graceDays(): int
    {
        $default = max(0, min(7, (int) app_config('subscriptions.grace_days', 5)));

        try {
            $settings = new AppSetting();
            $value = $settings->get('subscription_grace_days');
            if ($value === null || $value === '') {
                return $default;
            }

            return max(0, min(7, (int) $value));
        } catch (Throwable $exception) {
            error_log('SubscriptionConfig graceDays fallback: ' . $exception->getMessage());

            return $default;
        }
    }
}

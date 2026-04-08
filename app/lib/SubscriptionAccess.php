<?php

declare(strict_types=1);

final class SubscriptionAccess
{
    public static function summaryForUser(int $userId): array
    {
        try {
            $subscriptions = new Subscription();
            $plans = new Plan();
            $subscription = $subscriptions->currentForUser($userId);
            $basicPlan = $plans->getBasicPlan();

            if ($subscription === null) {
                if ($basicPlan === null) {
                    return self::fallbackSummary();
                }

                return self::buildSummary(null, $basicPlan, false, false);
            }

            if ((string) $subscription['status'] === 'active') {
                $subscriptions->markPastDueIfExpired((int) $subscription['id']);
                $subscription = $subscriptions->currentForUser($userId) ?? $subscription;
            }

            return self::buildSummary($subscription, $basicPlan, true, true);
        } catch (Throwable $exception) {
            error_log('Subscription summary fallback: ' . $exception->getMessage());

            return self::fallbackSummary();
        }
    }

    public static function requirePaidAccess(string $reason = 'That action requires an active subscription.'): void
    {
        Auth::requireLogin();

        if (Auth::role() === 'admin') {
            return;
        }

        $summary = self::summaryForUser((int) Auth::id());
        if ($summary['has_access']) {
            return;
        }

        Session::flash('error', $reason);
        redirect('subscriptions/index');
    }

    private static function buildSummary(?array $subscription, ?array $basicPlan, bool $hasSubscription, bool $useExistingPlan): array
    {
        $now = time();
        $graceDays = max(0, min(7, (int) app_config('subscriptions.grace_days', 5)));

        $status = $subscription['status'] ?? 'trialing';
        $trialEndsAt = $subscription['trial_ends_at'] ?? null;
        $periodEndsAt = $subscription['current_period_ends_at'] ?? null;
        $trialActive = $trialEndsAt !== null && strtotime((string) $trialEndsAt) >= $now;
        $activePeriod = $periodEndsAt !== null && strtotime((string) $periodEndsAt) >= $now;
        $graceEndsAt = null;
        $inGrace = false;

        if ($periodEndsAt !== null) {
            $graceEndsAt = date('Y-m-d H:i:s', strtotime((string) $periodEndsAt . ' +' . $graceDays . ' days'));
            $inGrace = !$activePeriod
                && in_array((string) $status, ['active', 'past_due'], true)
                && strtotime((string) $graceEndsAt) >= $now;
        }

        $hasAccess = $trialActive || $activePeriod || $inGrace;
        $effectivePlan = $useExistingPlan && $subscription !== null ? $subscription : $basicPlan;
        $visibilityLevel = $hasAccess
            ? (int) ($effectivePlan['visibility_level'] ?? 1)
            : (int) ($basicPlan['visibility_level'] ?? 1);

        return [
            'has_subscription' => $hasSubscription,
            'has_access' => $hasAccess,
            'in_grace_period' => $inGrace,
            'grace_days' => $graceDays,
            'grace_ends_at' => $graceEndsAt,
            'status' => (string) $status,
            'trial_ends_at' => $trialEndsAt,
            'current_period_ends_at' => $periodEndsAt,
            'momo_reference' => $subscription['momo_reference'] ?? null,
            'plan_id' => (int) ($effectivePlan['plan_id'] ?? $effectivePlan['id'] ?? 0),
            'plan_slug' => (string) ($effectivePlan['plan_slug'] ?? $effectivePlan['slug'] ?? 'basic'),
            'plan_name' => (string) ($effectivePlan['plan_name'] ?? $effectivePlan['name'] ?? 'Basic'),
            'plan_price_rwf' => (int) ($effectivePlan['price_rwf'] ?? 0),
            'visibility_level' => $visibilityLevel,
            'max_applications_per_day' => (int) ($effectivePlan['max_applications_per_day'] ?? 5),
            'priority_level' => (int) ($effectivePlan['priority_level'] ?? $visibilityLevel),
            'job_alert_delay_minutes' => (int) ($effectivePlan['job_alert_delay_minutes'] ?? 10),
            'max_active_jobs' => (int) ($effectivePlan['max_active_jobs'] ?? 1),
            'commission_discount' => (float) ($effectivePlan['commission_discount'] ?? 0),
            'badge_name' => ($effectivePlan['badge_name'] ?? null) !== null ? (string) $effectivePlan['badge_name'] : null,
        ];
    }

    private static function fallbackSummary(): array
    {
        return [
            'has_subscription' => false,
            'has_access' => false,
            'in_grace_period' => false,
            'grace_days' => max(0, min(7, (int) app_config('subscriptions.grace_days', 5))),
            'grace_ends_at' => null,
            'status' => 'trialing',
            'trial_ends_at' => null,
            'current_period_ends_at' => null,
            'momo_reference' => null,
            'plan_id' => 0,
            'plan_slug' => 'basic',
            'plan_name' => 'Basic',
            'plan_price_rwf' => 500,
            'visibility_level' => 1,
            'max_applications_per_day' => 5,
            'priority_level' => 1,
            'job_alert_delay_minutes' => 10,
            'max_active_jobs' => 1,
            'commission_discount' => 0.0,
            'badge_name' => null,
        ];
    }
}

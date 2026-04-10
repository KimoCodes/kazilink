<?php

declare(strict_types=1);

final class PlanFeatureAccess
{
    public static function getUserPlan(int $userId): array
    {
        $summary = SubscriptionAccess::summaryForUser($userId);

        return [
            'plan_id' => (int) ($summary['plan_id'] ?? 0),
            'slug' => (string) ($summary['plan_slug'] ?? 'basic'),
            'name' => (string) ($summary['plan_name'] ?? 'Basic Trial'),
            'price_rwf' => (int) ($summary['plan_price_rwf'] ?? 0),
            'visibility_level' => (int) ($summary['visibility_level'] ?? 1),
            'max_applications_per_day' => (int) ($summary['max_applications_per_day'] ?? 5),
            'priority_level' => (int) ($summary['priority_level'] ?? 1),
            'job_alert_delay_minutes' => (int) ($summary['job_alert_delay_minutes'] ?? 10),
            'max_active_jobs' => (int) ($summary['max_active_jobs'] ?? 1),
            'commission_discount' => (float) ($summary['commission_discount'] ?? 0),
            'badge_name' => ($summary['badge_name'] ?? null) !== null ? (string) $summary['badge_name'] : null,
        ];
    }

    public static function resetDailyLimitsIfNeeded(int $userId): array
    {
        if (!Database::tableExists('user_metrics')) {
            return [
                'user_id' => $userId,
                'daily_applications_count' => PHP_INT_MAX,
                'last_reset_date' => date('Y-m-d'),
                'metrics_available' => false,
            ];
        }

        $db = Database::connection();
        $today = date('Y-m-d');
        $statement = $db->prepare('
            INSERT INTO user_metrics (user_id, daily_applications_count, last_reset_date, created_at, updated_at)
            VALUES (:user_id, 0, :last_reset_date, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                daily_applications_count = CASE
                    WHEN last_reset_date IS NULL OR last_reset_date < VALUES(last_reset_date) THEN 0
                    ELSE daily_applications_count
                END,
                last_reset_date = CASE
                    WHEN last_reset_date IS NULL OR last_reset_date < VALUES(last_reset_date) THEN VALUES(last_reset_date)
                    ELSE last_reset_date
                END,
                updated_at = NOW()
        ');
        $statement->execute([
            'user_id' => $userId,
            'last_reset_date' => $today,
        ]);

        $select = $db->prepare('SELECT * FROM user_metrics WHERE user_id = :user_id LIMIT 1');
        $select->execute(['user_id' => $userId]);
        $metrics = $select->fetch();

        if (is_array($metrics)) {
            $metrics['metrics_available'] = true;

            return $metrics;
        }

        return [
            'user_id' => $userId,
            'daily_applications_count' => 0,
            'last_reset_date' => $today,
            'metrics_available' => true,
        ];
    }

    public static function canApplyToJob(int $userId): array
    {
        $plan = self::getUserPlan($userId);
        $metrics = self::resetDailyLimitsIfNeeded($userId);
        if (($metrics['metrics_available'] ?? true) !== true) {
            return [
                'allowed' => false,
                'message' => 'Application limits are unavailable right now. Please try again later.',
                'remaining' => 0,
                'current_count' => 0,
                'limit' => 0,
                'plan' => $plan,
                'metrics' => $metrics,
            ];
        }
        $count = (int) ($metrics['daily_applications_count'] ?? 0);
        $limit = max(0, (int) ($plan['max_applications_per_day'] ?? 0));
        $allowed = $count < $limit;

        return [
            'allowed' => $allowed,
            'message' => $allowed
                ? ''
                : sprintf(
                    'Your %s limit is used up for today. You can submit %d application%s per day.',
                    (string) ($plan['name'] ?? 'plan'),
                    $limit,
                    $limit === 1 ? '' : 's'
                ),
            'remaining' => max(0, $limit - $count),
            'current_count' => $count,
            'limit' => $limit,
            'plan' => $plan,
            'metrics' => $metrics,
        ];
    }

    public static function incrementApplicationCount(int $userId): void
    {
        self::resetDailyLimitsIfNeeded($userId);

        if (!Database::tableExists('user_metrics')) {
            return;
        }

        $statement = Database::connection()->prepare('
            UPDATE user_metrics
            SET daily_applications_count = daily_applications_count + 1,
                updated_at = NOW()
            WHERE user_id = :user_id
        ');
        $statement->execute(['user_id' => $userId]);
    }

    public static function getJobVisibilityTime(array $plan, ?string $jobCreatedAt = null): ?string
    {
        if ($jobCreatedAt === null || trim($jobCreatedAt) === '') {
            return null;
        }

        $timestamp = strtotime($jobCreatedAt);
        if ($timestamp === false) {
            return null;
        }

        $delayMinutes = (int) ($plan['job_alert_delay_minutes'] ?? 0);

        return date('Y-m-d H:i:s', strtotime(($delayMinutes >= 0 ? '+' : '') . $delayMinutes . ' minutes', $timestamp));
    }

    public static function canCreateTask(int $userId): array
    {
        $plan = self::getUserPlan($userId);
        $taskModel = new Task();
        $activeCount = $taskModel->countOpenActiveByClientId($userId);
        $limit = max(1, (int) ($plan['max_active_jobs'] ?? 1));
        $allowed = $activeCount < $limit;

        return [
            'allowed' => $allowed,
            'message' => $allowed
                ? ''
                : sprintf(
                    'Your %s plan allows %d active job%s at a time. Upgrade to post more jobs.',
                    (string) ($plan['name'] ?? 'plan'),
                    $limit,
                    $limit === 1 ? '' : 's'
                ),
            'active_count' => $activeCount,
            'limit' => $limit,
            'remaining' => max(0, $limit - $activeCount),
            'plan' => $plan,
        ];
    }

    public static function isTaskVisibleForUser(array $task, array $plan): bool
    {
        $visibleAt = self::getJobVisibilityTime($plan, (string) ($task['created_at'] ?? ''));

        if ($visibleAt === null) {
            return true;
        }

        return strtotime($visibleAt) <= time();
    }

    public static function badgeLabel(?array $plan): ?string
    {
        if (!is_array($plan)) {
            return null;
        }

        $badge = trim((string) ($plan['badge_name'] ?? ''));

        return $badge !== '' ? $badge : null;
    }
}

<?php

declare(strict_types=1);

final class EmailRecipient
{
    private PDO $db;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_UNDELIVERABLE = 'undeliverable';
    public const STATUS_SUPPRESSED = 'suppressed';

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findByEmail(string $email): ?array
    {
        if (!Database::tableExists('email_recipients')) {
            return null;
        }

        $statement = $this->db->prepare('SELECT * FROM email_recipients WHERE email = :email LIMIT 1');
        $statement->execute(['email' => mb_strtolower(trim($email))]);
        $record = $statement->fetch();

        return is_array($record) ? $record : null;
    }

    public function ensure(string $email): array
    {
        $normalized = mb_strtolower(trim($email));
        $existing = $this->findByEmail($normalized);
        if ($existing !== null) {
            return $existing;
        }

        $statement = $this->db->prepare('
            INSERT INTO email_recipients (email, status, created_at, updated_at)
            VALUES (:email, :status, NOW(), NOW())
        ');
        $statement->execute([
            'email' => $normalized,
            'status' => self::STATUS_ACTIVE,
        ]);

        return $this->findByEmail($normalized) ?? [
            'email' => $normalized,
            'status' => self::STATUS_ACTIVE,
        ];
    }

    public function markUndeliverable(string $email, string $reason): void
    {
        if (!Database::tableExists('email_recipients')) {
            return;
        }

        $statement = $this->db->prepare('
            INSERT INTO email_recipients (email, status, last_bounce_at, last_failure_reason, created_at, updated_at)
            VALUES (:email, :status, NOW(), :reason, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                last_bounce_at = NOW(),
                last_failure_reason = VALUES(last_failure_reason),
                updated_at = NOW()
        ');
        $statement->execute([
            'email' => mb_strtolower(trim($email)),
            'status' => self::STATUS_UNDELIVERABLE,
            'reason' => mb_substr($reason, 0, 255),
        ]);
    }
}

<?php

declare(strict_types=1);

final class MomoWebhookLog
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(?string $externalRef, string $decision, array $headers, string $payload, string $requestIp): int
    {
        if (!Database::tableExists('momo_webhook_logs')) {
            return 0;
        }

        $statement = $this->db->prepare('
            INSERT INTO momo_webhook_logs (external_ref, request_ip, decision, headers_json, payload_json, created_at)
            VALUES (:external_ref, :request_ip, :decision, :headers_json, :payload_json, NOW())
        ');
        $statement->execute([
            'external_ref' => $externalRef !== '' ? $externalRef : null,
            'request_ip' => $requestIp,
            'decision' => $decision,
            'headers_json' => json_encode($headers, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'payload_json' => $payload,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function latest(int $limit = 100): array
    {
        if (!Database::tableExists('momo_webhook_logs')) {
            return [];
        }

        $statement = $this->db->prepare('
            SELECT *
            FROM momo_webhook_logs
            ORDER BY created_at DESC, id DESC
            LIMIT :limit
        ');
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}

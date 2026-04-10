<?php

declare(strict_types=1);

final class NewsletterCampaign
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare('
            INSERT INTO newsletter_campaigns (
                title, subject, content, audience, status, scheduled_at, created_by
            ) VALUES (
                :title, :subject, :content, :audience, :status, :scheduled_at, :created_by
            )
        ');

        $statement->execute([
            'title' => $data['title'],
            'subject' => $data['subject'],
            'content' => $data['content'],
            'audience' => $data['audience'],
            'status' => $data['status'],
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'created_by' => $data['created_by'],
        ]);

        $campaignId = (int) $this->db->lastInsertId();

        // Initialize stats
        $this->initializeStats($campaignId);

        return $campaignId;
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        foreach (['title', 'subject', 'content', 'audience', 'status', 'scheduled_at'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if ($fields === []) {
            return false;
        }

        $sql = 'UPDATE newsletter_campaigns SET ' . implode(', ', $fields) . ' WHERE id = :id';
        
        $statement = $this->db->prepare($sql);
        $result = $statement->execute($params);

        return $result;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare('
            SELECT c.*, u.email as created_by_email
            FROM newsletter_campaigns c
            LEFT JOIN users u ON u.id = c.created_by
            WHERE c.id = :id
            LIMIT 1
        ');
        
        $statement->execute(['id' => $id]);
        $campaign = $statement->fetch();

        return $campaign ?: null;
    }

    public function all(?string $status = null, int $limit = 25, int $offset = 0): array
    {
        $sql = '
            SELECT c.*, u.email as created_by_email,
                   s.total_subscribers, s.sent_count, s.delivered_count, s.failed_count
            FROM newsletter_campaigns c
            LEFT JOIN users u ON u.id = c.created_by
            LEFT JOIN newsletter_campaign_stats s ON s.campaign_id = c.id
        ';
        
        $params = [];
        
        if ($status !== null) {
            $sql .= ' WHERE c.status = :status';
            $params['status'] = $status;
        }
        
        $sql .= ' ORDER BY c.created_at DESC LIMIT :limit OFFSET :offset';
        
        $statement = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        
        $statement->execute();

        return $statement->fetchAll();
    }

    public function count(?string $status = null): int
    {
        $sql = 'SELECT COUNT(*) AS aggregate FROM newsletter_campaigns';
        $params = [];

        if ($status !== null) {
            $sql .= ' WHERE status = :status';
            $params['status'] = $status;
        }

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }

    public function delete(int $id): bool
    {
        $statement = $this->db->prepare('DELETE FROM newsletter_campaigns WHERE id = :id');
        return $statement->execute(['id' => $id]);
    }

    public function getSubscribersByAudience(string $audience): array
    {
        // Get subscribers from the existing newsletter system
        $subscriptions = LeadCapture::newsletterSubscriptions();
        $subscribers = [];

        foreach ($subscriptions as $subscription) {
            $payload = $subscription['payload'] ?? [];
            $subscriberAudience = $payload['audience'] ?? 'all';
            $email = $payload['email'] ?? '';

            if ($email === '') {
                continue;
            }

            if ($audience === 'all' || $subscriberAudience === $audience) {
                $subscribers[] = [
                    'email' => $email,
                    'audience' => $subscriberAudience,
                    'audience_label' => $payload['audience_label'] ?? ucfirst($subscriberAudience),
                    'source_route' => $payload['source_route'] ?? $subscription['route'] ?? 'unknown',
                ];
            }
        }

        return array_unique($subscribers, SORT_REGULAR);
    }

    public function scheduleCampaign(int $campaignId, ?DateTimeInterface $scheduledAt = null): bool
    {
        $scheduledAt = $scheduledAt ?? new DateTimeImmutable();
        
        $statement = $this->db->prepare('
            UPDATE newsletter_campaigns 
            SET status = :status, scheduled_at = :scheduled_at 
            WHERE id = :id AND status IN (:draft, :failed)
        ');

        return $statement->execute([
            'id' => $campaignId,
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
            'draft' => 'draft',
            'failed' => 'failed',
        ]);
    }

    public function sendCampaign(int $campaignId): array
    {
        $campaign = $this->findById($campaignId);
        
        if ($campaign === null) {
            throw new RuntimeException('Campaign not found');
        }

        if ($campaign['status'] !== 'scheduled') {
            throw new RuntimeException('Campaign is not scheduled for sending');
        }

        // Update status to sending
        $this->update($campaignId, ['status' => 'sending']);

        $subscribers = $this->getSubscribersByAudience($campaign['audience']);
        $results = [
            'total' => count($subscribers),
            'sent' => 0,
            'failed' => 0,
            'errors' => []
        ];

        // Update total subscribers count
        $this->updateStats($campaignId, ['total_subscribers' => $results['total']]);

        foreach ($subscribers as $subscriber) {
            try {
                $deliveryId = $this->createDeliveryRecord($campaignId, $subscriber);
                $sent = $this->sendEmail($campaign, $subscriber);
                
                if ($sent) {
                    $this->updateDeliveryStatus($deliveryId, 'sent');
                    $results['sent']++;
                } else {
                    $this->updateDeliveryStatus($deliveryId, 'failed', 'Email sending failed');
                    $results['failed']++;
                }
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Failed to send to {$subscriber['email']}: " . $e->getMessage();
                Logger::error('Newsletter campaign delivery failed', [
                    'campaign_id' => $campaignId,
                    'subscriber_email' => $subscriber['email'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update final status and stats
        $finalStatus = ($results['failed'] === 0) ? 'sent' : 'sent_with_errors';
        $this->update($campaignId, [
            'status' => $finalStatus,
            'sent_at' => date('Y-m-d H:i:s')
        ]);

        $this->updateStats($campaignId, [
            'sent_count' => $results['sent'],
            'failed_count' => $results['failed'],
        ]);

        Logger::info('Newsletter campaign sent', [
            'campaign_id' => $campaignId,
            'total_subscribers' => $results['total'],
            'sent_count' => $results['sent'],
            'failed_count' => $results['failed'],
        ]);

        return $results;
    }

    private function initializeStats(int $campaignId): void
    {
        $statement = $this->db->prepare('
            INSERT INTO newsletter_campaign_stats (campaign_id, total_subscribers)
            VALUES (:campaign_id, 0)
        ');
        $statement->execute(['campaign_id' => $campaignId]);
    }

    private function updateStats(int $campaignId, array $data): void
    {
        $fields = [];
        $params = ['campaign_id' => $campaignId];

        foreach ($data as $field => $value) {
            $fields[] = "$field = :$field";
            $params[$field] = $value;
        }

        if ($fields === []) {
            return;
        }

        $sql = 'UPDATE newsletter_campaign_stats SET ' . implode(', ', $fields) . ', last_updated_at = NOW() WHERE campaign_id = :campaign_id';
        
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
    }

    private function createDeliveryRecord(int $campaignId, array $subscriber): int
    {
        $statement = $this->db->prepare('
            INSERT INTO newsletter_campaign_deliveries (
                campaign_id, subscriber_email, subscriber_audience, status
            ) VALUES (
                :campaign_id, :subscriber_email, :subscriber_audience, :status
            )
        ');

        $statement->execute([
            'campaign_id' => $campaignId,
            'subscriber_email' => $subscriber['email'],
            'subscriber_audience' => $subscriber['audience'],
            'status' => 'pending',
        ]);

        return (int) $this->db->lastInsertId();
    }

    private function updateDeliveryStatus(int $deliveryId, string $status, ?string $errorMessage = null): void
    {
        $sql = 'UPDATE newsletter_campaign_deliveries SET status = :status';
        $params = ['delivery_id' => $deliveryId, 'status' => $status];

        if ($errorMessage !== null) {
            $sql .= ', error_message = :error_message';
            $params['error_message'] = $errorMessage;
        }

        if ($status === 'sent') {
            $sql .= ', sent_at = NOW()';
        } elseif ($status === 'delivered') {
            $sql .= ', delivered_at = NOW()';
        }

        $sql .= ' WHERE id = :delivery_id';

        $statement = $this->db->prepare($sql);
        $statement->execute($params);
    }

    private function sendEmail(array $campaign, array $subscriber): bool
    {
        try {
            $emailService = new EmailService();
            
            return $emailService->sendNewsletter([
                'to' => $subscriber['email'],
                'subject' => $campaign['subject'],
                'content' => $campaign['content'],
                'campaign_id' => $campaign['id'],
                'subscriber_audience' => $subscriber['audience'],
            ]);
        } catch (Exception $e) {
            Logger::error('Newsletter email sending failed', [
                'campaign_id' => $campaign['id'],
                'subscriber_email' => $subscriber['email'],
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function getCampaignStats(int $campaignId): ?array
    {
        $statement = $this->db->prepare('
            SELECT * FROM newsletter_campaign_stats WHERE campaign_id = :campaign_id
        ');
        $statement->execute(['campaign_id' => $campaignId]);
        
        $stats = $statement->fetch();
        return $stats ?: null;
    }

    public function getDeliveryReport(int $campaignId): array
    {
        $statement = $this->db->prepare('
            SELECT 
                status,
                COUNT(*) as count,
                COUNT(CASE WHEN sent_at IS NOT NULL THEN 1 END) as sent_count,
                COUNT(CASE WHEN delivered_at IS NOT NULL THEN 1 END) as delivered_count
            FROM newsletter_campaign_deliveries 
            WHERE campaign_id = :campaign_id
            GROUP BY status
            ORDER BY status
        ');
        
        $statement->execute(['campaign_id' => $campaignId]);
        return $statement->fetchAll();
    }
}

<?php

declare(strict_types=1);

final class ProductListing
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare('
            INSERT INTO product_listings (
                seller_id, title, description, city, region, country, starting_price, status, is_active, created_at, updated_at
            ) VALUES (
                :seller_id, :title, :description, :city, :region, :country, :starting_price, :status, 1, NOW(), NOW()
            )
        ');
        $statement->execute([
            'seller_id' => $data['seller_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'city' => $data['city'],
            'region' => $data['region'] !== '' ? $data['region'] : null,
            'country' => $data['country'],
            'starting_price' => $data['starting_price'],
            'status' => 'open',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function browseOpen(array $filters, int $limit = 25, int $offset = 0): array
    {
        $sql = '
            SELECT
                l.*,
                seller.email AS seller_email,
                p.full_name AS seller_name,
                COALESCE(plan_visibility.visibility_level, 1) AS seller_visibility_level,
                COALESCE(plan_visibility.name, "Basic") AS seller_plan_name,
                COALESCE((SELECT MAX(amount) FROM product_bids WHERE listing_id = l.id), l.starting_price) AS highest_bid,
                (SELECT COUNT(*) FROM product_bids WHERE listing_id = l.id) AS bid_count
            FROM product_listings l
            INNER JOIN users seller ON seller.id = l.seller_id
            INNER JOIN profiles p ON p.user_id = l.seller_id
            LEFT JOIN subscriptions s ON s.user_id = l.seller_id AND s.id = (SELECT MAX(id) FROM subscriptions WHERE user_id = l.seller_id)
            LEFT JOIN plans plan_visibility ON plan_visibility.id = COALESCE(s.active_plan_id, s.plan_id)
            WHERE l.is_active = 1
              AND l.status = :status
        ';

        $params = ['status' => 'open'];

        if (($filters['q'] ?? '') !== '') {
            $sql .= ' AND (l.title LIKE :q_title OR l.description LIKE :q_description)';
            $params['q_title'] = '%' . $filters['q'] . '%';
            $params['q_description'] = '%' . $filters['q'] . '%';
        }

        if (($filters['city'] ?? '') !== '') {
            $sql .= ' AND l.city LIKE :city';
            $params['city'] = '%' . normalize_whitespace((string) $filters['city']) . '%';
        }

        if (($filters['min_price'] ?? '') !== '') {
            $sql .= ' AND l.starting_price >= :min_price';
            $params['min_price'] = $filters['min_price'];
        }

        if (($filters['max_price'] ?? '') !== '') {
            $sql .= ' AND l.starting_price <= :max_price';
            $params['max_price'] = $filters['max_price'];
        }

        $sort = (string) ($filters['sort'] ?? 'newest');
        switch ($sort) {
            case 'oldest':
                $sql .= ' ORDER BY seller_visibility_level DESC, l.created_at ASC';
                break;
            case 'price_high':
                $sql .= ' ORDER BY seller_visibility_level DESC, highest_bid DESC, l.created_at DESC';
                break;
            case 'price_low':
                $sql .= ' ORDER BY seller_visibility_level DESC, highest_bid ASC, l.created_at DESC';
                break;
            case 'most_bids':
                $sql .= ' ORDER BY seller_visibility_level DESC, bid_count DESC, highest_bid DESC, l.created_at DESC';
                break;
            case 'newest':
            default:
                $sql .= ' ORDER BY seller_visibility_level DESC, l.created_at DESC';
                break;
        }

        $sql .= ' LIMIT :limit OFFSET :offset';

        $statement = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function countBrowseOpen(array $filters): int
    {
        $sql = '
            SELECT COUNT(*) AS aggregate
            FROM product_listings l
            WHERE l.is_active = 1
              AND l.status = :status
        ';

        $params = ['status' => 'open'];

        if (($filters['q'] ?? '') !== '') {
            $sql .= ' AND (l.title LIKE :q_title OR l.description LIKE :q_description)';
            $params['q_title'] = '%' . $filters['q'] . '%';
            $params['q_description'] = '%' . $filters['q'] . '%';
        }

        if (($filters['city'] ?? '') !== '') {
            $sql .= ' AND l.city LIKE :city';
            $params['city'] = '%' . normalize_whitespace((string) $filters['city']) . '%';
        }

        if (($filters['min_price'] ?? '') !== '') {
            $sql .= ' AND l.starting_price >= :min_price';
            $params['min_price'] = $filters['min_price'];
        }

        if (($filters['max_price'] ?? '') !== '') {
            $sql .= ' AND l.starting_price <= :max_price';
            $params['max_price'] = $filters['max_price'];
        }

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return (int) ($statement->fetch()['aggregate'] ?? 0);
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare('
            SELECT
                l.*,
                seller.email AS seller_email,
                p.full_name AS seller_name,
                p.phone AS seller_phone,
                p.city AS seller_profile_city,
                p.region AS seller_profile_region,
                p.country AS seller_profile_country
            FROM product_listings l
            INNER JOIN users seller ON seller.id = l.seller_id
            INNER JOIN profiles p ON p.user_id = l.seller_id
            WHERE l.id = :id
            LIMIT 1
        ');
        $statement->execute(['id' => $id]);
        $listing = $statement->fetch();

        return $listing ?: null;
    }

    public function findOpenById(int $id): ?array
    {
        $statement = $this->db->prepare('
            SELECT
                l.*,
                seller.email AS seller_email,
                p.full_name AS seller_name
            FROM product_listings l
            INNER JOIN users seller ON seller.id = l.seller_id
            INNER JOIN profiles p ON p.user_id = l.seller_id
            WHERE l.id = :id
              AND l.is_active = 1
              AND l.status = :status
            LIMIT 1
        ');
        $statement->execute([
            'id' => $id,
            'status' => 'open',
        ]);
        $listing = $statement->fetch();

        return $listing ?: null;
    }

    public function forSeller(int $sellerId): array
    {
        $statement = $this->db->prepare('
            SELECT
                l.*,
                COALESCE(MAX(pb.amount), l.starting_price) AS highest_bid,
                COUNT(pb.id) AS bid_count
            FROM product_listings l
            LEFT JOIN product_bids pb ON pb.listing_id = l.id
            WHERE l.seller_id = :seller_id
            GROUP BY l.id
            ORDER BY l.created_at DESC
        ');
        $statement->execute(['seller_id' => $sellerId]);

        return $statement->fetchAll();
    }

    public function markSold(int $listingId, int $sellerId): void
    {
        $statement = $this->db->prepare('
            UPDATE product_listings
            SET status = :status, updated_at = NOW()
            WHERE id = :id AND seller_id = :seller_id
        ');
        $statement->execute([
            'status' => 'sold',
            'id' => $listingId,
            'seller_id' => $sellerId,
        ]);
    }
}

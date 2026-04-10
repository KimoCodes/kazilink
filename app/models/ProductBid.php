<?php

declare(strict_types=1);

final class ProductBid
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findForBuyer(int $listingId, int $buyerId): ?array
    {
        $statement = $this->db->prepare('
            SELECT *
            FROM product_bids
            WHERE listing_id = :listing_id AND buyer_id = :buyer_id
            LIMIT 1
        ');
        $statement->execute([
            'listing_id' => $listingId,
            'buyer_id' => $buyerId,
        ]);
        $bid = $statement->fetch();

        return $bid ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare('
            INSERT INTO product_bids (listing_id, buyer_id, amount, message, status, created_at, updated_at)
            VALUES (:listing_id, :buyer_id, :amount, :message, :status, NOW(), NOW())
        ');
        $statement->execute([
            'listing_id' => $data['listing_id'],
            'buyer_id' => $data['buyer_id'],
            'amount' => $data['amount'],
            'message' => $data['message'] !== '' ? $data['message'] : null,
            'status' => 'pending',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function createForBuyerOnOpenListing(array $data): int
    {
        $this->db->beginTransaction();

        try {
            $listingStatement = $this->db->prepare('
                SELECT id, seller_id, status, is_active, starting_price
                FROM product_listings
                WHERE id = :listing_id
                FOR UPDATE
            ');
            $listingStatement->execute(['listing_id' => $data['listing_id']]);
            $listing = $listingStatement->fetch();

            if (!$listing || (string) $listing['status'] !== 'open' || (int) $listing['is_active'] !== 1) {
                throw new RuntimeException('That listing is no longer open for bids.');
            }

            if ((int) $listing['seller_id'] === (int) $data['buyer_id']) {
                throw new RuntimeException('You cannot bid on your own listing.');
            }

            if ((float) $data['amount'] < (float) $listing['starting_price']) {
                throw new RuntimeException('Bid amount must be at least the listing price.');
            }

            $existingStatement = $this->db->prepare('
                SELECT id
                FROM product_bids
                WHERE listing_id = :listing_id AND buyer_id = :buyer_id
                LIMIT 1
                FOR UPDATE
            ');
            $existingStatement->execute([
                'listing_id' => $data['listing_id'],
                'buyer_id' => $data['buyer_id'],
            ]);

            if ($existingStatement->fetch()) {
                throw new RuntimeException('You have already placed a bid on this listing.');
            }

            $insertStatement = $this->db->prepare('
                INSERT INTO product_bids (listing_id, buyer_id, amount, message, status, created_at, updated_at)
                VALUES (:listing_id, :buyer_id, :amount, :message, :status, NOW(), NOW())
            ');
            $insertStatement->execute([
                'listing_id' => $data['listing_id'],
                'buyer_id' => $data['buyer_id'],
                'amount' => $data['amount'],
                'message' => $data['message'] !== '' ? $data['message'] : null,
                'status' => 'pending',
            ]);

            $bidId = (int) $this->db->lastInsertId();
            $this->db->commit();

            return $bidId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            if ($exception instanceof PDOException && $exception->getCode() === '23000') {
                throw new RuntimeException('You have already placed a bid on this listing.', 0, $exception);
            }

            throw $exception;
        }
    }

    public function forSellerListing(int $listingId, int $sellerId): array
    {
        $statement = $this->db->prepare('
            SELECT
                pb.*,
                buyer.email AS buyer_email,
                p.full_name AS buyer_name,
                p.phone AS buyer_phone
            FROM product_bids pb
            INNER JOIN product_listings l ON l.id = pb.listing_id
            INNER JOIN users buyer ON buyer.id = pb.buyer_id
            INNER JOIN profiles p ON p.user_id = pb.buyer_id
            WHERE pb.listing_id = :listing_id
              AND l.seller_id = :seller_id
            ORDER BY
                CASE pb.status
                    WHEN "selected" THEN 0
                    WHEN "pending" THEN 1
                    WHEN "rejected" THEN 2
                    WHEN "withdrawn" THEN 3
                    ELSE 4
                END,
                pb.amount DESC,
                pb.created_at ASC
        ');
        $statement->execute([
            'listing_id' => $listingId,
            'seller_id' => $sellerId,
        ]);

        return $statement->fetchAll();
    }

    public function findSelectableForSeller(int $bidId, int $sellerId): ?array
    {
        $statement = $this->db->prepare('
            SELECT
                pb.*,
                l.seller_id,
                l.status AS listing_status,
                l.is_active AS listing_is_active
            FROM product_bids pb
            INNER JOIN product_listings l ON l.id = pb.listing_id
            WHERE pb.id = :bid_id AND l.seller_id = :seller_id
            LIMIT 1
        ');
        $statement->execute([
            'bid_id' => $bidId,
            'seller_id' => $sellerId,
        ]);
        $bid = $statement->fetch();

        return $bid ?: null;
    }

    public function highestPendingAmountForListing(int $listingId): ?float
    {
        $statement = $this->db->prepare('
            SELECT MAX(amount) AS aggregate
            FROM product_bids
            WHERE listing_id = :listing_id
              AND status = :status
        ');
        $statement->execute([
            'listing_id' => $listingId,
            'status' => 'pending',
        ]);
        $row = $statement->fetch();

        return $row && $row['aggregate'] !== null ? (float) $row['aggregate'] : null;
    }

    public function markSelected(int $bidId): void
    {
        $statement = $this->db->prepare('
            UPDATE product_bids
            SET status = :status, updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'status' => 'selected',
            'id' => $bidId,
        ]);
    }

    public function selectHighestPendingBidForSeller(int $bidId, int $sellerId): int
    {
        $this->db->beginTransaction();

        try {
            $bidStatement = $this->db->prepare('
                SELECT
                    pb.*,
                    l.seller_id,
                    l.status AS listing_status,
                    l.is_active AS listing_is_active
                FROM product_bids pb
                INNER JOIN product_listings l ON l.id = pb.listing_id
                WHERE pb.id = :bid_id AND l.seller_id = :seller_id
                LIMIT 1
                FOR UPDATE
            ');
            $bidStatement->execute([
                'bid_id' => $bidId,
                'seller_id' => $sellerId,
            ]);
            $bid = $bidStatement->fetch();

            if (!$bid) {
                throw new RuntimeException('Bid not found.');
            }

            if ((int) $bid['listing_is_active'] !== 1 || (string) $bid['listing_status'] !== 'open' || (string) $bid['status'] !== 'pending') {
                throw new RuntimeException('Only pending bids on active open listings can be selected.');
            }

            $selectedStatement = $this->db->prepare('
                SELECT id
                FROM product_bids
                WHERE listing_id = :listing_id
                  AND status = :status
                LIMIT 1
                FOR UPDATE
            ');
            $selectedStatement->execute([
                'listing_id' => $bid['listing_id'],
                'status' => 'selected',
            ]);

            if ($selectedStatement->fetch()) {
                throw new RuntimeException('This listing already has a selected buyer.');
            }

            $highestStatement = $this->db->prepare('
                SELECT id, amount
                FROM product_bids
                WHERE listing_id = :listing_id
                  AND status = :status
                ORDER BY amount DESC, created_at ASC, id ASC
                LIMIT 1
                FOR UPDATE
            ');
            $highestStatement->execute([
                'listing_id' => $bid['listing_id'],
                'status' => 'pending',
            ]);
            $highestBid = $highestStatement->fetch();

            if (!$highestBid || (int) $highestBid['id'] !== (int) $bid['id']) {
                throw new RuntimeException('You can only select the highest current bid for this listing.');
            }

            $markSelected = $this->db->prepare('
                UPDATE product_bids
                SET status = :status, updated_at = NOW()
                WHERE id = :id AND status = :pending_status
            ');
            $markSelected->execute([
                'status' => 'selected',
                'id' => $bidId,
                'pending_status' => 'pending',
            ]);

            if ($markSelected->rowCount() !== 1) {
                throw new RuntimeException('Bid selection could not be completed.');
            }

            $rejectOthers = $this->db->prepare('
                UPDATE product_bids
                SET status = :status, updated_at = NOW()
                WHERE listing_id = :listing_id
                  AND id != :selected_bid_id
                  AND status = :pending_status
            ');
            $rejectOthers->execute([
                'status' => 'rejected',
                'listing_id' => $bid['listing_id'],
                'selected_bid_id' => $bidId,
                'pending_status' => 'pending',
            ]);

            $listingUpdate = $this->db->prepare('
                UPDATE product_listings
                SET status = :status, updated_at = NOW()
                WHERE id = :listing_id AND seller_id = :seller_id AND status = :open_status
            ');
            $listingUpdate->execute([
                'status' => 'sold',
                'listing_id' => $bid['listing_id'],
                'seller_id' => $sellerId,
                'open_status' => 'open',
            ]);

            if ($listingUpdate->rowCount() !== 1) {
                throw new RuntimeException('Listing status could not be updated safely.');
            }

            $this->db->commit();

            return (int) $bid['listing_id'];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            if ($exception instanceof PDOException && $exception->getCode() === '23000') {
                throw new RuntimeException('This listing already has a selected buyer.', 0, $exception);
            }

            throw $exception;
        }
    }

    public function rejectOthers(int $listingId, int $selectedBidId): void
    {
        $statement = $this->db->prepare('
            UPDATE product_bids
            SET status = :status, updated_at = NOW()
            WHERE listing_id = :listing_id
              AND id != :selected_bid_id
              AND status = :pending_status
        ');
        $statement->execute([
            'status' => 'rejected',
            'listing_id' => $listingId,
            'selected_bid_id' => $selectedBidId,
            'pending_status' => 'pending',
        ]);
    }

    public function findSelectedForListing(int $listingId): ?array
    {
        $statement = $this->db->prepare('
            SELECT
                pb.*,
                buyer.email AS buyer_email,
                seller.email AS seller_email,
                p_buyer.full_name AS buyer_name,
                p_buyer.phone AS buyer_phone,
                p_buyer.city AS buyer_city,
                p_buyer.region AS buyer_region,
                p_buyer.country AS buyer_country,
                p_seller.full_name AS seller_name,
                p_seller.phone AS seller_phone,
                p_seller.city AS seller_city,
                p_seller.region AS seller_region,
                p_seller.country AS seller_country
            FROM product_bids pb
            INNER JOIN product_listings l ON l.id = pb.listing_id
            INNER JOIN users buyer ON buyer.id = pb.buyer_id
            INNER JOIN users seller ON seller.id = l.seller_id
            INNER JOIN profiles p_buyer ON p_buyer.user_id = pb.buyer_id
            INNER JOIN profiles p_seller ON p_seller.user_id = l.seller_id
            WHERE pb.listing_id = :listing_id
              AND pb.status = :status
            LIMIT 1
        ');
        $statement->execute([
            'listing_id' => $listingId,
            'status' => 'selected',
        ]);
        $bid = $statement->fetch();

        return $bid ?: null;
    }

    public function forBuyer(int $buyerId): array
    {
        $statement = $this->db->prepare('
            SELECT
                pb.*,
                l.title,
                l.city,
                l.country,
                l.status AS listing_status,
                seller.email AS seller_email,
                p.full_name AS seller_name
            FROM product_bids pb
            INNER JOIN product_listings l ON l.id = pb.listing_id
            INNER JOIN users seller ON seller.id = l.seller_id
            INNER JOIN profiles p ON p.user_id = l.seller_id
            WHERE pb.buyer_id = :buyer_id
            ORDER BY pb.created_at DESC
        ');
        $statement->execute(['buyer_id' => $buyerId]);

        return $statement->fetchAll();
    }
}

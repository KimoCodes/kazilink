ALTER TABLE subscriptions
    ADD COLUMN IF NOT EXISTS active_plan_id INT UNSIGNED NULL AFTER plan_id,
    ADD COLUMN IF NOT EXISTS pending_plan_id INT UNSIGNED NULL AFTER active_plan_id;

UPDATE subscriptions
SET active_plan_id = COALESCE(active_plan_id, plan_id)
WHERE active_plan_id IS NULL;

ALTER TABLE product_bids
    ADD COLUMN IF NOT EXISTS selected_listing_lock INT UNSIGNED
        GENERATED ALWAYS AS (CASE WHEN status = 'selected' THEN listing_id ELSE NULL END) STORED;

SET @subscriptions_active_plan_idx = (
    SELECT IF(
        EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'subscriptions' AND index_name = 'idx_subscriptions_active_plan'),
        'SELECT 1',
        'ALTER TABLE subscriptions ADD KEY idx_subscriptions_active_plan (active_plan_id)'
    )
);
PREPARE subscriptions_active_plan_idx_stmt FROM @subscriptions_active_plan_idx;
EXECUTE subscriptions_active_plan_idx_stmt;
DEALLOCATE PREPARE subscriptions_active_plan_idx_stmt;

SET @subscriptions_pending_plan_idx = (
    SELECT IF(
        EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'subscriptions' AND index_name = 'idx_subscriptions_pending_plan'),
        'SELECT 1',
        'ALTER TABLE subscriptions ADD KEY idx_subscriptions_pending_plan (pending_plan_id)'
    )
);
PREPARE subscriptions_pending_plan_idx_stmt FROM @subscriptions_pending_plan_idx;
EXECUTE subscriptions_pending_plan_idx_stmt;
DEALLOCATE PREPARE subscriptions_pending_plan_idx_stmt;

SET @subscriptions_active_plan_fk = (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.table_constraints
            WHERE table_schema = DATABASE()
              AND table_name = 'subscriptions'
              AND constraint_name = 'fk_subscriptions_active_plan'
              AND constraint_type = 'FOREIGN KEY'
        ),
        'SELECT 1',
        'ALTER TABLE subscriptions ADD CONSTRAINT fk_subscriptions_active_plan FOREIGN KEY (active_plan_id) REFERENCES plans(id) ON DELETE RESTRICT'
    )
);
PREPARE subscriptions_active_plan_fk_stmt FROM @subscriptions_active_plan_fk;
EXECUTE subscriptions_active_plan_fk_stmt;
DEALLOCATE PREPARE subscriptions_active_plan_fk_stmt;

SET @subscriptions_pending_plan_fk = (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.table_constraints
            WHERE table_schema = DATABASE()
              AND table_name = 'subscriptions'
              AND constraint_name = 'fk_subscriptions_pending_plan'
              AND constraint_type = 'FOREIGN KEY'
        ),
        'SELECT 1',
        'ALTER TABLE subscriptions ADD CONSTRAINT fk_subscriptions_pending_plan FOREIGN KEY (pending_plan_id) REFERENCES plans(id) ON DELETE SET NULL'
    )
);
PREPARE subscriptions_pending_plan_fk_stmt FROM @subscriptions_pending_plan_fk;
EXECUTE subscriptions_pending_plan_fk_stmt;
DEALLOCATE PREPARE subscriptions_pending_plan_fk_stmt;

SET @product_bids_selected_idx = (
    SELECT IF(
        EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'product_bids' AND index_name = 'uq_product_bids_one_selected'),
        'SELECT 1',
        'ALTER TABLE product_bids ADD UNIQUE KEY uq_product_bids_one_selected (selected_listing_lock)'
    )
);
PREPARE product_bids_selected_idx_stmt FROM @product_bids_selected_idx;
EXECUTE product_bids_selected_idx_stmt;
DEALLOCATE PREPARE product_bids_selected_idx_stmt;

SET @product_bids_listing_status_amount_idx = (
    SELECT IF(
        EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'product_bids' AND index_name = 'idx_product_bids_listing_status_amount'),
        'SELECT 1',
        'ALTER TABLE product_bids ADD KEY idx_product_bids_listing_status_amount (listing_id, status, amount, id)'
    )
);
PREPARE product_bids_listing_status_amount_idx_stmt FROM @product_bids_listing_status_amount_idx;
EXECUTE product_bids_listing_status_amount_idx_stmt;
DEALLOCATE PREPARE product_bids_listing_status_amount_idx_stmt;

SET @tasks_active_status_created_idx = (
    SELECT IF(
        EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'tasks' AND index_name = 'idx_tasks_active_status_created'),
        'SELECT 1',
        'ALTER TABLE tasks ADD KEY idx_tasks_active_status_created (is_active, status, created_at, id)'
    )
);
PREPARE tasks_active_status_created_idx_stmt FROM @tasks_active_status_created_idx;
EXECUTE tasks_active_status_created_idx_stmt;
DEALLOCATE PREPARE tasks_active_status_created_idx_stmt;

SET @tasks_client_status_active_idx = (
    SELECT IF(
        EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'tasks' AND index_name = 'idx_tasks_client_status_active'),
        'SELECT 1',
        'ALTER TABLE tasks ADD KEY idx_tasks_client_status_active (client_id, status, is_active)'
    )
);
PREPARE tasks_client_status_active_idx_stmt FROM @tasks_client_status_active_idx;
EXECUTE tasks_client_status_active_idx_stmt;
DEALLOCATE PREPARE tasks_client_status_active_idx_stmt;

SET @product_listings_active_created_idx = (
    SELECT IF(
        EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'product_listings' AND index_name = 'idx_product_listings_active_created'),
        'SELECT 1',
        'ALTER TABLE product_listings ADD KEY idx_product_listings_active_created (is_active, status, created_at, id)'
    )
);
PREPARE product_listings_active_created_idx_stmt FROM @product_listings_active_created_idx;
EXECUTE product_listings_active_created_idx_stmt;
DEALLOCATE PREPARE product_listings_active_created_idx_stmt;

SET @hiring_agreements_booking_status_idx = (
    SELECT IF(
        EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'hiring_agreements' AND index_name = 'idx_hiring_agreements_booking_status'),
        'SELECT 1',
        'ALTER TABLE hiring_agreements ADD KEY idx_hiring_agreements_booking_status (booking_id, status)'
    )
);
PREPARE hiring_agreements_booking_status_idx_stmt FROM @hiring_agreements_booking_status_idx;
EXECUTE hiring_agreements_booking_status_idx_stmt;
DEALLOCATE PREPARE hiring_agreements_booking_status_idx_stmt;

SET @momo_transactions_user_status_idx = (
    SELECT IF(
        EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'momo_transactions' AND index_name = 'idx_momo_transactions_user_status'),
        'SELECT 1',
        'ALTER TABLE momo_transactions ADD KEY idx_momo_transactions_user_status (user_id, status)'
    )
);
PREPARE momo_transactions_user_status_idx_stmt FROM @momo_transactions_user_status_idx;
EXECUTE momo_transactions_user_status_idx_stmt;
DEALLOCATE PREPARE momo_transactions_user_status_idx_stmt;

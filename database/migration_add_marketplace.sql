CREATE TABLE product_listings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    region VARCHAR(100) NULL,
    country VARCHAR(100) NOT NULL,
    starting_price DECIMAL(10,2) NOT NULL,
    status ENUM('open', 'sold', 'cancelled', 'deactivated') NOT NULL DEFAULT 'open',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_listings_seller
        FOREIGN KEY (seller_id) REFERENCES users(id)
        ON DELETE CASCADE,
    KEY idx_product_listings_status (status),
    KEY idx_product_listings_city (city),
    KEY idx_product_listings_seller (seller_id)
);

CREATE TABLE product_bids (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    listing_id INT UNSIGNED NOT NULL,
    buyer_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    message TEXT NULL,
    status ENUM('pending', 'selected', 'rejected', 'withdrawn') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_bids_listing
        FOREIGN KEY (listing_id) REFERENCES product_listings(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_product_bids_buyer
        FOREIGN KEY (buyer_id) REFERENCES users(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_product_bids_listing_buyer (listing_id, buyer_id),
    KEY idx_product_bids_status (status),
    KEY idx_product_bids_listing (listing_id),
    KEY idx_product_bids_buyer (buyer_id)
);

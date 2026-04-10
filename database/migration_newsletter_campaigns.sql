-- Newsletter Campaigns Migration
-- This migration creates the newsletter_campaigns table for managing email campaigns

CREATE TABLE newsletter_campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    audience ENUM('all', 'client', 'tasker', 'partner') NOT NULL DEFAULT 'all',
    status ENUM('draft', 'scheduled', 'sending', 'sent', 'failed', 'cancelled') NOT NULL DEFAULT 'draft',
    scheduled_at DATETIME NULL,
    sent_at DATETIME NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_newsletter_campaigns_admin
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE CASCADE,
    
    KEY idx_newsletter_campaigns_status (status),
    KEY idx_newsletter_campaigns_audience (audience),
    KEY idx_newsletter_campaigns_scheduled (scheduled_at),
    KEY idx_newsletter_campaigns_created_by (created_by)
);

-- Newsletter Campaign Delivery Tracking
CREATE TABLE newsletter_campaign_deliveries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    subscriber_email VARCHAR(255) NOT NULL,
    subscriber_audience VARCHAR(20) NOT NULL,
    status ENUM('pending', 'sent', 'delivered', 'failed', 'bounced') NOT NULL DEFAULT 'pending',
    sent_at DATETIME NULL,
    delivered_at DATETIME NULL,
    error_message TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_newsletter_deliveries_campaign
        FOREIGN KEY (campaign_id) REFERENCES newsletter_campaigns(id)
        ON DELETE CASCADE,
    
    KEY idx_newsletter_deliveries_campaign (campaign_id),
    KEY idx_newsletter_deliveries_status (status),
    KEY idx_newsletter_deliveries_email (subscriber_email)
);

-- Newsletter Campaign Statistics
CREATE TABLE newsletter_campaign_stats (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    total_subscribers INT UNSIGNED NOT NULL DEFAULT 0,
    sent_count INT UNSIGNED NOT NULL DEFAULT 0,
    delivered_count INT UNSIGNED NOT NULL DEFAULT 0,
    failed_count INT UNSIGNED NOT NULL DEFAULT 0,
    bounced_count INT UNSIGNED NOT NULL DEFAULT 0,
    opened_count INT UNSIGNED NOT NULL DEFAULT 0,
    clicked_count INT UNSIGNED NOT NULL DEFAULT 0,
    last_updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_newsletter_stats_campaign
        FOREIGN KEY (campaign_id) REFERENCES newsletter_campaigns(id)
        ON DELETE CASCADE,
    
    UNIQUE KEY uq_newsletter_stats_campaign (campaign_id)
);

-- Insert sample campaign for testing
INSERT INTO newsletter_campaigns (title, subject, content, audience, status, created_by) VALUES
('Welcome to Kazilink', 'Welcome to Kazilink - Your Local Hiring Platform', '<h1>Welcome to Kazilink!</h1><p>Thank you for joining our community. We are excited to help you connect with reliable local support.</p><p>Best regards,<br>The Kazilink Team</p>', 'all', 'draft', 1);

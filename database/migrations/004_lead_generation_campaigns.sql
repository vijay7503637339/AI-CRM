CREATE TABLE IF NOT EXISTS lead_campaigns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    category VARCHAR(120) DEFAULT NULL,
    location VARCHAR(160) DEFAULT NULL,
    keywords VARCHAR(500) DEFAULT NULL,
    target_count INT UNSIGNED NOT NULL DEFAULT 100,
    pages_per_source TINYINT UNSIGNED NOT NULL DEFAULT 10,
    status ENUM('draft','running','completed','paused') NOT NULL DEFAULT 'draft',
    total_found INT UNSIGNED NOT NULL DEFAULT 0,
    total_imported INT UNSIGNED NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_campaign_status(status),
    INDEX idx_campaign_creator(created_by),
    CONSTRAINT fk_campaign_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS campaign_sources (
    campaign_id BIGINT UNSIGNED NOT NULL,
    source_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (campaign_id, source_id),
    CONSTRAINT fk_campaign_source_campaign FOREIGN KEY (campaign_id) REFERENCES lead_campaigns(id) ON DELETE CASCADE,
    CONSTRAINT fk_campaign_source_source FOREIGN KEY (source_id) REFERENCES prospect_sources(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE web_prospects ADD COLUMN campaign_id BIGINT UNSIGNED DEFAULT NULL AFTER source_id;
ALTER TABLE web_prospects ADD INDEX idx_web_prospect_campaign(campaign_id);
ALTER TABLE web_prospects ADD CONSTRAINT fk_web_prospect_campaign FOREIGN KEY (campaign_id) REFERENCES lead_campaigns(id) ON DELETE SET NULL;

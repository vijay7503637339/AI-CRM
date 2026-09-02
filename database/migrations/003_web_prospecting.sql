CREATE TABLE IF NOT EXISTS prospect_sources (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    seed_url TEXT NOT NULL,
    category VARCHAR(120) DEFAULT NULL,
    location VARCHAR(160) DEFAULT NULL,
    created_by BIGINT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_prospect_source_creator(created_by),
    CONSTRAINT fk_prospect_source_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS scrape_runs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_id BIGINT UNSIGNED DEFAULT NULL,
    seed_url TEXT NOT NULL,
    status ENUM('running','completed','failed') NOT NULL DEFAULT 'running',
    pages_crawled INT UNSIGNED NOT NULL DEFAULT 0,
    prospects_found INT UNSIGNED NOT NULL DEFAULT 0,
    error_message TEXT DEFAULT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    finished_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_scrape_runs_source(source_id),
    CONSTRAINT fk_scrape_run_source FOREIGN KEY (source_id) REFERENCES prospect_sources(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS web_prospects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_id BIGINT UNSIGNED DEFAULT NULL,
    scrape_run_id BIGINT UNSIGNED DEFAULT NULL,
    business_name VARCHAR(190) NOT NULL,
    category VARCHAR(120) DEFAULT NULL,
    website TEXT DEFAULT NULL,
    domain VARCHAR(190) DEFAULT NULL,
    email VARCHAR(190) DEFAULT NULL,
    phone VARCHAR(60) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    city VARCHAR(120) DEFAULT NULL,
    source_url TEXT NOT NULL,
    source_type VARCHAR(80) NOT NULL DEFAULT 'web',
    raw_data JSON DEFAULT NULL,
    status ENUM('new','imported','rejected') NOT NULL DEFAULT 'new',
    lead_id BIGINT UNSIGNED DEFAULT NULL,
    fingerprint CHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_web_prospect_fingerprint(fingerprint),
    INDEX idx_web_prospect_status(status),
    INDEX idx_web_prospect_category(category),
    INDEX idx_web_prospect_city(city),
    INDEX idx_web_prospect_email(email),
    INDEX idx_web_prospect_phone(phone),
    CONSTRAINT fk_web_prospect_source FOREIGN KEY (source_id) REFERENCES prospect_sources(id) ON DELETE SET NULL,
    CONSTRAINT fk_web_prospect_run FOREIGN KEY (scrape_run_id) REFERENCES scrape_runs(id) ON DELETE SET NULL,
    CONSTRAINT fk_web_prospect_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL
) ENGINE=InnoDB;

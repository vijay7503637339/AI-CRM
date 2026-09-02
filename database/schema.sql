CREATE DATABASE IF NOT EXISTS ai_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ai_crm;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','manager','sales') NOT NULL DEFAULT 'sales',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pipeline_stages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    position INT NOT NULL DEFAULT 0,
    probability TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO pipeline_stages (id, name, position, probability) VALUES
(1, 'New', 1, 10),
(2, 'Contacted', 2, 25),
(3, 'Qualified', 3, 50),
(4, 'Proposal', 4, 70),
(5, 'Won', 5, 100),
(6, 'Lost', 6, 0)
ON DUPLICATE KEY UPDATE name = VALUES(name), position = VALUES(position), probability = VALUES(probability);

CREATE TABLE IF NOT EXISTS leads (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id BIGINT UNSIGNED NULL,
    stage_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
    name VARCHAR(150) NOT NULL,
    company VARCHAR(150) NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(30) NULL,
    source VARCHAR(80) NULL,
    estimated_value DECIMAL(12,2) NOT NULL DEFAULT 0,
    ai_score TINYINT UNSIGNED NULL,
    status ENUM('open','won','lost') NOT NULL DEFAULT 'open',
    next_follow_up DATETIME NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_lead_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_lead_stage FOREIGN KEY (stage_id) REFERENCES pipeline_stages(id),
    INDEX idx_leads_stage (stage_id),
    INDEX idx_leads_owner (owner_id),
    INDEX idx_leads_status (status),
    INDEX idx_follow_up (next_follow_up)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS activities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    type ENUM('note','call','email','whatsapp','meeting','task','system') NOT NULL DEFAULT 'note',
    body TEXT NOT NULL,
    due_at DATETIME NULL,
    completed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_activity_lead (lead_id),
    INDEX idx_activity_due (due_at)
) ENGINE=InnoDB;

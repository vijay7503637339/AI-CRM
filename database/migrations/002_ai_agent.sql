USE ai_crm;

CREATE TABLE IF NOT EXISTS ai_runs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    agent VARCHAR(80) NOT NULL,
    input_tokens INT UNSIGNED NULL,
    output_tokens INT UNSIGNED NULL,
    status ENUM('completed','failed') NOT NULL DEFAULT 'completed',
    result_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ai_run_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    CONSTRAINT fk_ai_run_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_ai_runs_lead (lead_id),
    INDEX idx_ai_runs_agent (agent)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lead_ai_insights (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_id BIGINT UNSIGNED NOT NULL,
    score TINYINT UNSIGNED NOT NULL,
    qualification ENUM('hot','warm','cold','unknown') NOT NULL DEFAULT 'unknown',
    summary TEXT NOT NULL,
    next_action TEXT NOT NULL,
    suggested_followup TEXT NULL,
    factors_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_lead_insight (lead_id),
    CONSTRAINT fk_insight_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
) ENGINE=InnoDB;

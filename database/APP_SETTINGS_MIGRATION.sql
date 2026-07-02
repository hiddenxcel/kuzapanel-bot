-- Generic key-value settings table for bot-wide toggles (starting with AI on/off).
-- Safe to run once on production via phpMyAdmin / mysql CLI.

CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO app_settings (setting_key, setting_value) VALUES ('ai_enabled', '1')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

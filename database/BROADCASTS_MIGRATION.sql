-- Adds a broadcasts table to keep history of admin broadcast messages
-- (message text, when sent, how many customers received it, how many succeeded/failed).
-- Safe to run once on production via phpMyAdmin / mysql CLI.

CREATE TABLE IF NOT EXISTS broadcasts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message TEXT NOT NULL,
    recipient_count INT UNSIGNED NOT NULL DEFAULT 0,
    success_count INT UNSIGNED NOT NULL DEFAULT 0,
    failed_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_by VARCHAR(60) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

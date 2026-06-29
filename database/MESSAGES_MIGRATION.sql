-- Adds a messages table so the admin can see a WhatsApp-Web-style chat log
-- per customer. This only starts capturing messages going forward — there is
-- no way to backfill conversations that happened before this table existed.
-- Safe to run once on production via phpMyAdmin / mysql CLI.

CREATE TABLE IF NOT EXISTS messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_phone VARCHAR(20) NOT NULL,
    direction ENUM('in', 'out') NOT NULL,
    message_type VARCHAR(20) NOT NULL DEFAULT 'text',
    body TEXT NOT NULL,
    wa_message_id VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customer_phone (customer_phone, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

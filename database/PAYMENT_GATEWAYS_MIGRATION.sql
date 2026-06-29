-- Adds a payment_gateways table so the admin can manage gateway API keys and
-- per-transaction fee percentages from the admin panel instead of .env.
-- The fee_percent is used to "gross up" the amount charged to the customer,
-- so the gateway's cut comes out of what the customer pays (not KuzaPanel's margin).
-- Safe to run once on production via phpMyAdmin / mysql CLI.

CREATE TABLE IF NOT EXISTS payment_gateways (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(60) NOT NULL,
    api_key VARCHAR(255) NOT NULL DEFAULT '',
    api_secret VARCHAR(255) NOT NULL DEFAULT '',
    fee_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'inactive',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO payment_gateways (code, name, fee_percent, status) VALUES
    ('zenopay', 'ZenoPay', 0.00, 'inactive'),
    ('snippe', 'Snippe', 0.00, 'active'),
    ('harakapay', 'HarakaPay', 5.90, 'inactive')
ON DUPLICATE KEY UPDATE name = VALUES(name);

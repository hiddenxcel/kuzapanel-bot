-- Adds multi-currency support: a per-customer display currency (TZS/KES/UGX)
-- plus a TZS-based exchange-rate table. The bot's canonical storage currency
-- stays TZS everywhere (services.my_price, customers.balance, orders.amount,
-- etc. are unchanged) — this only adds the ability to DISPLAY amounts in a
-- customer's own currency. Safe to run once on production via phpMyAdmin /
-- mysql CLI.

ALTER TABLE customers ADD COLUMN currency VARCHAR(3) NOT NULL DEFAULT 'TZS' AFTER lang;

CREATE TABLE IF NOT EXISTS exchange_rates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    currency VARCHAR(3) NOT NULL UNIQUE,
    rate DECIMAL(16,8) NOT NULL COMMENT 'TZS per 1 unit of currency (e.g. KES row: 1 KES = rate TZS)',
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    is_manual TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'when 1, the refresh cron skips overwriting this row',
    fetched_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Starter rates (TZS per 1 unit) — rough estimates seeded only so the table
-- is never empty between this migration and the first daily refresh cron
-- run (cron/refresh_exchange_rates.php). KES/UGX will self-correct within a
-- day; TZS is pinned (is_manual=1) since it's the base and never converts.
INSERT INTO exchange_rates (currency, rate, is_enabled, is_manual) VALUES
    ('TZS', 1.00000000, 1, 1),
    ('KES', 21.50000000, 1, 0),
    ('UGX', 0.68000000, 1, 0)
ON DUPLICATE KEY UPDATE currency = VALUES(currency);

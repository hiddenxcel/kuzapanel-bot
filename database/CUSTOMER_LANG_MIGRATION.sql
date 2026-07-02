-- Adds a per-customer bot language preference (Swahili default).
-- Run once: mysql -u root kuzapanel_bot < database/CUSTOMER_LANG_MIGRATION.sql

ALTER TABLE customers
    ADD COLUMN lang VARCHAR(5) NOT NULL DEFAULT 'sw' AFTER name;

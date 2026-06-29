-- Lets an admin manually credit/debit a customer's wallet balance from the
-- admin panel (e.g. correcting an error, goodwill credit, etc.), with a
-- record of who did it, when, and why. Separate from `payments` because
-- these are not gateway transactions and must not affect deposit reports.

CREATE TABLE IF NOT EXISTS balance_adjustments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    admin_id INT UNSIGNED NULL,
    amount DECIMAL(12,2) NOT NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_balance_adj_customer FOREIGN KEY (customer_id) REFERENCES customers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

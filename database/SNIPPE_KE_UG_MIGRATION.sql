-- Adds Snippe's Kenya (KES) and Uganda (UGX) mobile-money gateways as
-- separate payment_gateways rows, alongside the existing TZS 'snippe' row.
-- Same Snippe merchant account/api_key serves all three — leave api_key
-- blank here and it falls back to the 'snippe' row's key via
-- PaymentGateway::resolveConfig(); set a different one on these rows only
-- if Snippe issues separate KE/UG credentials later.
--
-- Also adds a per-gateway min_amount (in that gateway's own currency, TZS
-- for 'snippe', KES for 'snippe_ke', UGX for 'snippe_ug') so the minimum
-- mobile-money collection amount is admin-editable from Settings instead of
-- hardcoded in code. Existing rows default to 0 (no minimum enforced beyond
-- what WebhookController's TZ Tigo/default logic already applies).
--
-- Seeded inactive: confirm real fees/minimums with Snippe before activating
-- in the admin panel. Safe to run once on production.

ALTER TABLE payment_gateways ADD COLUMN min_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER fee_percent;

INSERT INTO payment_gateways (code, name, fee_percent, min_amount, status) VALUES
    ('snippe_ke', 'Snippe (Kenya M-Pesa)', 0.00, 0.00, 'inactive'),
    ('snippe_ug', 'Snippe (Uganda Mobile Money)', 0.00, 0.00, 'inactive')
ON DUPLICATE KEY UPDATE name = VALUES(name);

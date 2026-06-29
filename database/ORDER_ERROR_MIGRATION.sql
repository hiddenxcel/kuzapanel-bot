-- Records why an order failed to submit to the provider (e.g. provider out
-- of balance, network error), so the admin can see it and retry ("Tuma
-- Tena") once the issue is fixed, instead of the order sitting silently
-- stuck at status='pending' forever with only a PHP error_log trace.

ALTER TABLE orders ADD COLUMN order_error VARCHAR(255) NULL AFTER provider_order_id;

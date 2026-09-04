-- Splits services.name into name_sw and name_en so a Tanzanian customer
-- sees a Swahili service name (e.g. "Wafuasi wa Instagram") and a Kenyan/
-- Ugandan customer sees the English one, instead of everyone seeing one
-- name regardless of their bot language.
--
-- Existing names are copied into BOTH columns as a starting point — nothing
-- breaks and nothing shows blank; an admin can then go through Services and
-- fill in the other language per service at their own pace.
--
-- Safe to run once on production via phpMyAdmin / mysql CLI.

ALTER TABLE services
    ADD COLUMN name_sw VARCHAR(150) NOT NULL DEFAULT '' AFTER name,
    ADD COLUMN name_en VARCHAR(150) NOT NULL DEFAULT '' AFTER name_sw;

UPDATE services SET name_sw = name, name_en = name WHERE name_sw = '' AND name_en = '';

-- The old single-language column is dropped only after the copy above —
-- every read site in the app has been switched to name_sw/name_en first.
ALTER TABLE services DROP COLUMN name;

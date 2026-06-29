-- Adds manual ordering for services (admin can arrange the order customers
-- see them in on WhatsApp, instead of always alphabetical).
-- Safe to run once on production via phpMyAdmin / mysql CLI.

ALTER TABLE services ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER status;

-- Backfill: preserve today's alphabetical order so nothing visually jumps
-- around until the admin starts reordering.
UPDATE services s
JOIN (
  SELECT id, ROW_NUMBER() OVER (ORDER BY platform, name) AS rn
  FROM services
) ranked ON ranked.id = s.id
SET s.sort_order = ranked.rn;

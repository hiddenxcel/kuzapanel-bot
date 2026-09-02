-- Adds an optional category to services (a sub-grouping within a platform,
-- e.g. Instagram -> Followers / Likes / Views). Customers only see the extra
-- "choose category" step in the bot when a platform actually has more than
-- one category in use.
-- Safe to run once on production via phpMyAdmin / mysql CLI.

ALTER TABLE services ADD COLUMN category VARCHAR(100) NULL AFTER platform;

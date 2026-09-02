-- Seeds the maintenance-mode toggle into the existing app_settings table
-- (see APP_SETTINGS_MIGRATION.sql). Safe to run once on production.
-- Defaults to '0' (off) so existing bot behavior is unaffected until an
-- admin explicitly enables it via the admin panel.

INSERT INTO app_settings (setting_key, setting_value) VALUES ('maintenance_enabled', '0')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

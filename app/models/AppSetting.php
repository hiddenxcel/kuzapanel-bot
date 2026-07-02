<?php

require_once __DIR__ . '/BaseModel.php';

class AppSetting extends BaseModel
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $stmt = self::db()->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();

        return $value !== false ? $value : $default;
    }

    public static function set(string $key, string $value): void
    {
        $stmt = self::db()->prepare(
            'INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute([$key, $value]);
    }

    /**
     * Defaults to true (AI enabled) if the setting has never been saved,
     * matching the bot's behavior before this on/off toggle existed.
     */
    public static function isAiEnabled(): bool
    {
        return self::get('ai_enabled', '1') === '1';
    }
}

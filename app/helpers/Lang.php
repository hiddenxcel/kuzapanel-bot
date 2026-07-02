<?php

require_once __DIR__ . '/Auth.php';

class Lang
{
    private static array $strings = [];
    private static bool $loaded = false;

    public static function load(): void
    {
        Auth::start();

        $locale = self::current();
        self::$strings = require __DIR__ . "/../lang/{$locale}.php";
        self::$loaded = true;
    }

    public static function get(string $key): string
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$strings[$key] ?? $key;
    }

    public static function current(): string
    {
        Auth::start();

        return $_SESSION['admin_locale'] ?? 'sw';
    }

    public static function setLocale(string $locale): void
    {
        Auth::start();
        $_SESSION['admin_locale'] = in_array($locale, ['sw', 'en'], true) ? $locale : 'sw';
    }
}

function t(string $key): string
{
    return Lang::get($key);
}

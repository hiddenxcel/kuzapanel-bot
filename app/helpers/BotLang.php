<?php

/**
 * Per-customer translations for the WhatsApp bot conversation.
 * (Lang.php next to this file is the admin-panel session locale — separate.)
 *
 * Strings live in app/lang/bot/{locale}.php. Placeholders use {name} style
 * and are replaced via strtr with the $vars map passed to get().
 */
class BotLang
{
    public const DEFAULT = 'sw';
    public const SUPPORTED = ['sw', 'en'];

    /** @var array<string, array<string, string>> */
    private static array $strings = [];

    public static function get(string $lang, string $key, array $vars = []): string
    {
        $lang = self::normalize($lang);
        $text = self::strings($lang)[$key] ?? self::strings(self::DEFAULT)[$key] ?? $key;

        return $vars === [] ? $text : strtr($text, $vars);
    }

    public static function normalize(?string $lang): string
    {
        return in_array($lang, self::SUPPORTED, true) ? $lang : self::DEFAULT;
    }

    /** The customer's saved bot language, defaulting to Swahili. */
    public static function forCustomer(?array $customer): string
    {
        return self::normalize($customer['lang'] ?? null);
    }

    private static function strings(string $lang): array
    {
        if (!isset(self::$strings[$lang])) {
            self::$strings[$lang] = require __DIR__ . "/../lang/bot/{$lang}.php";
        }

        return self::$strings[$lang];
    }
}

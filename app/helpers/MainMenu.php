<?php

require_once __DIR__ . '/../services/WhatsAppClient.php';
require_once __DIR__ . '/BotLang.php';

class MainMenu
{
    public static function rows(string $lang = BotLang::DEFAULT): array
    {
        return [
            ['id' => 'main:new_order', 'title' => BotLang::get($lang, 'menu_new_order_title'), 'description' => BotLang::get($lang, 'menu_new_order_desc')],
            ['id' => 'main:topup', 'title' => BotLang::get($lang, 'menu_topup_title'), 'description' => BotLang::get($lang, 'menu_topup_desc')],
            ['id' => 'main:profile', 'title' => BotLang::get($lang, 'menu_profile_title'), 'description' => BotLang::get($lang, 'menu_profile_desc')],
            ['id' => 'main:referral', 'title' => BotLang::get($lang, 'menu_referral_title'), 'description' => BotLang::get($lang, 'menu_referral_desc')],
            ['id' => 'main:track_order', 'title' => BotLang::get($lang, 'menu_track_title'), 'description' => BotLang::get($lang, 'menu_track_desc')],
            ['id' => 'main:support', 'title' => BotLang::get($lang, 'menu_support_title'), 'description' => BotLang::get($lang, 'menu_support_desc')],
            ['id' => 'main:settings', 'title' => BotLang::get($lang, 'menu_settings_title'), 'description' => BotLang::get($lang, 'menu_settings_desc')],
            ['id' => 'main:group', 'title' => BotLang::get($lang, 'menu_group_title'), 'description' => BotLang::get($lang, 'menu_group_desc')],
            ['id' => 'main:website', 'title' => BotLang::get($lang, 'menu_website_title'), 'description' => BotLang::get($lang, 'menu_website_desc')],
        ];
    }

    public static function send(WhatsAppClient $whatsapp, string $phone, ?string $name = null, string $lang = BotLang::DEFAULT): void
    {
        $greetingName = $name !== null && $name !== '' ? $name : BotLang::get($lang, 'default_customer_name');

        $welcome = BotLang::get($lang, 'menu_welcome', [
            '{name_upper}' => mb_strtoupper($greetingName),
            '{name}' => $greetingName,
        ]);

        $whatsapp->sendList($phone, $welcome, BotLang::get($lang, 'btn_open_menu'), BotLang::get($lang, 'menu_header'), self::rows($lang));
    }
}

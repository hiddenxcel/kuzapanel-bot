<?php

require_once __DIR__ . '/../services/WhatsAppClient.php';

class MainMenu
{
    public static function rows(): array
    {
        return [
            ['id' => 'main:new_order', 'title' => '🛒 Weka Oda Mpya', 'description' => 'Followers, likes au views'],
            ['id' => 'main:topup', 'title' => '💰 Weka Pesa', 'description' => 'Mobile Money / Card'],
            ['id' => 'main:profile', 'title' => '👤 Wasifu Wangu', 'description' => 'Salio na matumizi yako'],
            ['id' => 'main:referral', 'title' => '🎁 Mwalike Rafiki', 'description' => 'Pata bonus kwa kila rafiki'],
            ['id' => 'main:track_order', 'title' => '📦 Fuatilia Oda', 'description' => 'Angalia hatua ya oda yako'],
            ['id' => 'main:support', 'title' => '🎧 Huduma Kwa Wateja', 'description' => 'Wasiliana na admin'],
            ['id' => 'main:settings', 'title' => '⚙️ Mipangilio', 'description' => 'Lugha na sarafu'],
            ['id' => 'main:group', 'title' => '👥 KuzaPanel Group', 'description' => 'Jiunge na taarifa zetu'],
            ['id' => 'main:website', 'title' => '🌐 KuzaPanel Website', 'description' => 'Huduma zaidi mtandaoni'],
        ];
    }

    public static function send(WhatsAppClient $whatsapp, string $phone, ?string $name = null): void
    {
        $greetingName = $name !== null && $name !== '' ? $name : 'Mteja';

        $welcome = "👑 *KARIBU, " . mb_strtoupper($greetingName) . "!*\n\n" .
            "Hujambo {$greetingName}! 👋 Karibu.\n\n" .
            "Mimi ni mshirika wako nipo hapa kukusaidia kukuza akaunti zako za mitandao ya kijamii.\n\n" .
            "Tunatoa huduma za haraka, salama, na nafuu kuongeza followers, likes, na views 🚀📈\n\n" .
            "👇 Chagua chaguo hapa chini:";

        $whatsapp->sendList($phone, $welcome, 'Fungua Menyu', 'Main Menu', self::rows());
    }
}

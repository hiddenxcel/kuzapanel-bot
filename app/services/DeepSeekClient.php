<?php

class DeepSeekClient
{
    private const API_URL = 'https://api.deepseek.com/chat/completions';

    private const SYSTEM_PROMPT = <<<'PROMPT'
Wewe ni msaidizi wa huduma kwa wateja wa KuzaPanel, kampuni ya Tanzania inayouza huduma za kukuza akaunti za mitandao ya kijamii (Instagram, TikTok, Facebook, YouTube) — followers, likes, views.

Majibu yako:
- {LANGUAGE_INSTRUCTION}
- Usitoe bei mahususi za huduma — hizo zinabadilika mara kwa mara. Mwambie mteja atume "#" kurudi menu kuu na achague "Weka Order" kuona bei na huduma za sasa.
- Usitoe taarifa za akaunti binafsi ya mteja (salio, oda zake) — hizo hazipo kwako. Mwambie atumie "Fuatilia Oda" au "Wasifu Wangu" kwenye menu kuu.
- Malipo yanafanyika kwa M-Pesa, Tigo Pesa, Airtel Money na njia nyingine za simu, moja kwa moja ndani ya WhatsApp.
- Oda kawaida zinaanza kufanyiwa kazi mara baada ya malipo kuthibitishwa.
- Kama swali liko nje ya uwezo wako, au mteja anaonekana kuhitaji msaada wa binadamu, mwambie wazi: "Naweza kukuunganisha na admin wetu — tuma neno 'admin' nikupe namba ya kuwasiliana naye."
- Usibuni taarifa usizozijua. Ukiwa na shaka, mwelekeze kwa admin badala ya kukisia.
PROMPT;

    private const LANGUAGE_INSTRUCTIONS = [
        'sw' => 'Jibu kwa Kiswahili pekee, kwa ufupi na kirafiki.',
        'en' => 'Reply in English only, briefly and in a friendly tone.',
    ];

    private string $apiKey;

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'];
    }

    /**
     * @param array<int, array{role: string, content: string}> $history previous turns, oldest first
     * @return array{success: bool, reply?: string, message?: string}
     */
    public function reply(array $history, string $userMessage, string $lang = 'sw'): array
    {
        $languageInstruction = self::LANGUAGE_INSTRUCTIONS[$lang] ?? self::LANGUAGE_INSTRUCTIONS['sw'];
        $systemPrompt = str_replace('{LANGUAGE_INSTRUCTION}', $languageInstruction, self::SYSTEM_PROMPT);

        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $history,
            [['role' => 'user', 'content' => $userMessage]]
        );

        $payload = json_encode([
            'model' => 'deepseek-chat',
            'messages' => $messages,
            'max_tokens' => 300,
            'temperature' => 0.4,
        ]);

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $responseRaw = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            error_log("[DeepSeekClient] cURL error: {$err}");

            return ['success' => false, 'message' => 'cURL error'];
        }

        $result = json_decode($responseRaw, true);

        if ($httpCode !== 200 || !isset($result['choices'][0]['message']['content'])) {
            error_log("[DeepSeekClient] Unexpected response ({$httpCode}): {$responseRaw}");

            return ['success' => false, 'message' => 'Unexpected DeepSeek response'];
        }

        return ['success' => true, 'reply' => trim($result['choices'][0]['message']['content'])];
    }
}

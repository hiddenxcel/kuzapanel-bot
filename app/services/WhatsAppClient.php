<?php

class WhatsAppClient
{
    private string $token;
    private string $phoneNumberId;

    public function __construct(array $config)
    {
        $this->token = $config['token'];
        $this->phoneNumberId = $config['phone_number_id'];
    }

    public function sendText(string $to, string $message): bool
    {
        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $message],
        ]);
    }

    private const FOOTER = '© KuzaPanel';

    /**
     * @param array<int, array{id: string, title: string, description?: string}> $rows up to 10 rows
     */
    public function sendList(string $to, string $bodyText, string $buttonText, string $sectionTitle, array $rows): bool
    {
        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'list',
                'body' => ['text' => $bodyText],
                'footer' => ['text' => self::FOOTER],
                'action' => [
                    'button' => $buttonText,
                    'sections' => [
                        [
                            'title' => $sectionTitle,
                            'rows' => array_map(
                                fn (array $row) => [
                                    'id' => $row['id'],
                                    'title' => $row['title'],
                                    'description' => $row['description'] ?? '',
                                ],
                                $rows
                            ),
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param array<int, array{id: string, title: string}> $buttons up to 3 buttons
     */
    public function sendButtons(string $to, string $bodyText, array $buttons): bool
    {
        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $bodyText],
                'footer' => ['text' => self::FOOTER],
                'action' => [
                    'buttons' => array_map(
                        fn (array $button) => [
                            'type' => 'reply',
                            'reply' => ['id' => $button['id'], 'title' => $button['title']],
                        ],
                        $buttons
                    ),
                ],
            ],
        ]);
    }

    public function sendCtaUrl(string $to, string $bodyText, string $buttonText, string $url): bool
    {
        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'cta_url',
                'body' => ['text' => $bodyText],
                'footer' => ['text' => self::FOOTER],
                'action' => [
                    'name' => 'cta_url',
                    'parameters' => [
                        'display_text' => $buttonText,
                        'url' => $url,
                    ],
                ],
            ],
        ]);
    }

    public function sendImage(string $to, string $imageUrl, string $caption = ''): bool
    {
        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'image',
            'image' => array_filter([
                'link' => $imageUrl,
                'caption' => $caption,
            ]),
        ]);
    }

    /**
     * React to a customer's message with an emoji (e.g. ✅, 💰, 🎉).
     */
    public function sendReaction(string $to, string $messageId, string $emoji): bool
    {
        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'reaction',
            'reaction' => [
                'message_id' => $messageId,
                'emoji' => $emoji,
            ],
        ]);
    }

    /**
     * Mark the customer's message as read (blue ticks) and show the typing
     * indicator at the same time. The typing indicator auto-dismisses when the
     * next outbound message is sent, or after ~25s.
     */
    public function markReadWithTyping(string $messageId): bool
    {
        return $this->send([
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
            'typing_indicator' => ['type' => 'text'],
        ]);
    }

    private function send(array $payload): bool
    {
        $url = "https://graph.facebook.com/v22.0/{$this->phoneNumberId}/messages";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->token,
            ],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            error_log("[WhatsAppClient] cURL error: {$err}");

            return false;
        }

        if ($httpCode !== 200) {
            error_log("[WhatsAppClient] Send failed ({$httpCode}): {$body}");

            return false;
        }

        return true;
    }
}

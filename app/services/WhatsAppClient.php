<?php

require_once __DIR__ . '/../models/Message.php';

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

    /**
     * A cheap, read-only Graph API call that confirms both the token is
     * valid AND the configured phone_number_id is reachable with it. Only
     * meant to be called on-demand from the admin health page — never in
     * the hot inbound-message path.
     *
     * @return array{valid: bool, detail: string}
     */
    public function checkTokenValidity(): array
    {
        $url = "https://graph.facebook.com/v22.0/{$this->phoneNumberId}?fields=id,display_phone_number";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ['valid' => false, 'detail' => "cURL error: {$err}"];
        }

        if ($httpCode !== 200) {
            $decoded = json_decode((string) $body, true);
            $detail = $decoded['error']['message'] ?? "HTTP {$httpCode}";

            return ['valid' => false, 'detail' => $detail];
        }

        return ['valid' => true, 'detail' => 'OK'];
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

        $this->logOutgoingMessage($payload);

        return true;
    }

    /**
     * Log a sent message into the chat-log table for the admin inbox, as a
     * human-readable summary. Skips read-receipts/typing (no 'to') and
     * reactions (not a real message). Wrapped in try/catch so a logging
     * failure never blocks message delivery.
     */
    private function logOutgoingMessage(array $payload): void
    {
        if (empty($payload['to']) || $payload['type'] === 'reaction') {
            return;
        }

        try {
            $type = $payload['type'];

            if ($type === 'text') {
                Message::log($payload['to'], 'out', 'text', $payload['text']['body']);

                return;
            }

            if ($type === 'image') {
                Message::log($payload['to'], 'out', 'image', '🖼️ [Picha] ' . ($payload['image']['caption'] ?? ''));

                return;
            }

            if ($type === 'interactive') {
                $interactiveType = $payload['interactive']['type'];
                $bodyText = $payload['interactive']['body']['text'] ?? '';

                $prefix = match ($interactiveType) {
                    'list' => '📋 ',
                    'button' => '🔘 ',
                    'cta_url' => '🔗 ',
                    default => '',
                };

                $suffix = $interactiveType === 'cta_url'
                    ? ' [' . ($payload['interactive']['action']['parameters']['display_text'] ?? '') . ': ' . ($payload['interactive']['action']['parameters']['url'] ?? '') . ']'
                    : '';

                Message::log($payload['to'], 'out', $interactiveType, $prefix . $bodyText . $suffix);
            }
        } catch (\Throwable $e) {
            error_log('[WhatsAppClient] Failed to log outgoing message: ' . $e->getMessage());
        }
    }
}

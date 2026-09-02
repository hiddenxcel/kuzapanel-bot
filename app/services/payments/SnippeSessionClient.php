<?php

/**
 * Snippe mobile money for Kenya (KES) and Uganda (UGX) customers — a
 * different API from the TZS direct-push flow in SnippeClient.php:
 *
 *   - Endpoint: POST /api/v1/sessions (note the "/api" prefix — omitting it
 *     404s, unlike /v1/payments which answers both prefixed and unprefixed).
 *   - Snippe REJECTS any currency other than "TZS" even for KES/UGX
 *     customers — every session request is priced in TZS regardless of the
 *     customer's own currency; Snippe converts automatically at checkout.
 *     $data['amount'] passed to initiate() must already be TZS (the same
 *     $chargeAmount WebhookController computes for direct-push).
 *   - Response is a hosted checkout_url the customer is sent to (a link,
 *     not an immediate USSD push like direct-push) — Snippe collects the
 *     phone and drives the USSD prompt from its own page.
 *   - References come back as "PAY..." (vs. "SN..." for direct-push).
 *   - Session webhook amounts are a bare number, not the {currency, value}
 *     object direct-push sends.
 *
 * Uses the same api_key/webhook_secret config shape as SnippeClient, and
 * the identical HMAC-SHA256 signature scheme on webhooks.
 */
class SnippeSessionClient
{
    private const SESSIONS_URL = 'https://api.snippe.sh/api/v1/sessions';

    private string $apiKey;
    private string $webhookSecret;

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'];
        $this->webhookSecret = $config['webhook_secret'];
    }

    public function initiate(array $data): array
    {
        $payload = [
            'amount' => (int) round($data['amount']),
            'currency' => 'TZS',
            'allowed_methods' => ['mobile_money'],
            'customer' => [
                'name' => trim($data['firstname'] . ' ' . $data['lastname']),
                'phone' => $data['phone'],
                'email' => $data['email'],
            ],
            'webhook_url' => $data['webhook_url'],
            'description' => 'KuzaPanel Wallet Topup',
            'metadata' => [
                'order_id' => $data['order_id'],
            ],
        ];

        $ch = curl_init(self::SESSIONS_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
                'Idempotency-Key: ' . $data['order_id'],
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $responseRaw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ['success' => false, 'message' => "cURL error: {$err}"];
        }

        $result = json_decode($responseRaw, true);

        if (
            isset($result['status']) && $result['status'] === 'success'
            && isset($result['data']['reference'], $result['data']['checkout_url'])
        ) {
            return [
                'success' => true,
                'reference' => $result['data']['reference'],
                'checkout_url' => $result['data']['checkout_url'],
            ];
        }

        return ['success' => false, 'message' => $result['message'] ?? 'Unknown Snippe error'];
    }

    public function checkStatus(string $reference): ?string
    {
        $ch = curl_init(self::SESSIONS_URL . '/' . urlencode($reference));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->apiKey],
            CURLOPT_TIMEOUT => 15,
        ]);

        $responseRaw = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($responseRaw, true);

        return $result['data']['status'] ?? null;
    }

    /** Identical HMAC-SHA256 scheme to SnippeClient's direct-push webhooks. */
    public function verifySignature(string $body, string $signature, string $timestamp): bool
    {
        if (empty($this->webhookSecret)) {
            return true;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', "{$timestamp}.{$body}", $this->webhookSecret);

        return hash_equals($expected, $signature);
    }
}

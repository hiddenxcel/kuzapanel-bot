<?php

class SnippeClient
{
    private const PAYMENTS_URL = 'https://api.snippe.sh/v1/payments';

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
            'payment_type' => 'mobile',
            'phone_number' => $data['phone'],
            'details' => [
                'amount' => (int) round($data['amount']),
                'currency' => 'TZS',
            ],
            'customer' => [
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'email' => $data['email'],
            ],
            'webhook_url' => $data['webhook_url'],
            'metadata' => [
                'order_id' => $data['order_id'],
            ],
        ];

        $ch = curl_init(self::PAYMENTS_URL);
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

        if (isset($result['status']) && $result['status'] === 'success' && isset($result['data']['reference'])) {
            return ['success' => true, 'reference' => $result['data']['reference']];
        }

        return ['success' => false, 'message' => $result['message'] ?? 'Unknown Snippe error'];
    }

    public function checkStatus(string $reference): ?string
    {
        $ch = curl_init(self::PAYMENTS_URL . '/' . urlencode($reference));
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

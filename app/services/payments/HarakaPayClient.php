<?php

class HarakaPayClient
{
    private const COLLECT_URL = 'https://harakapay.net/api/v1/collect';
    private const STATUS_URL = 'https://harakapay.net/api/v1/status/';

    private string $apiKey;

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'];
    }

    /**
     * Initiate a mobile-money collection. Returns ['success'=>bool, 'order_id'=>?string, 'message'=>?string].
     * HarakaPay assigns its own order_id which we must store for later status polling.
     */
    public function initiate(array $data): array
    {
        $fields = [
            'phone' => $data['phone'],
            'amount' => (int) round($data['amount']),
            'description' => $data['description'] ?? 'Balance Topup',
        ];

        if (!empty($data['webhook_url'])) {
            $fields['webhook_url'] = $data['webhook_url'];
        }

        $payload = json_encode($fields);

        $ch = curl_init(self::COLLECT_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-Key: ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => 45,
        ]);

        $responseRaw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ['success' => false, 'message' => "cURL error: {$err}"];
        }

        $result = json_decode($responseRaw, true);

        if (isset($result['success']) && $result['success'] === true && isset($result['order_id'])) {
            return ['success' => true, 'order_id' => (string) $result['order_id']];
        }

        return ['success' => false, 'message' => $result['message'] ?? 'Unknown HarakaPay error'];
    }

    /**
     * Poll payment status. Returns the status string ('completed', 'pending', etc.) or null.
     */
    public function checkStatus(string $orderId): ?string
    {
        $ch = curl_init(self::STATUS_URL . urlencode($orderId));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['X-API-Key: ' . $this->apiKey],
            CURLOPT_TIMEOUT => 20,
        ]);

        $responseRaw = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($responseRaw, true);

        if (isset($result['success']) && $result['success'] === true) {
            return $result['payment']['status'] ?? null;
        }

        return null;
    }

    public function verifyCompleted(string $orderId): bool
    {
        return $this->checkStatus($orderId) === 'completed';
    }
}

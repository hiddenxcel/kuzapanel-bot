<?php

class ZenoPayClient
{
    private const INITIATE_URL = 'https://zenoapi.com/api/payments/mobile_money_tanzania';
    private const STATUS_URL = 'https://zenoapi.com/api/payments/order-status';

    private string $apiKey;

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'];
    }

    public function initiate(array $data): array
    {
        $payload = json_encode([
            'order_id' => $data['order_id'],
            'buyer_email' => $data['buyer_email'],
            'buyer_name' => $data['buyer_name'],
            'buyer_phone' => $data['buyer_phone'],
            'amount' => (int) round($data['amount']),
            'webhook_url' => $data['webhook_url'],
        ]);

        $ch = curl_init(self::INITIATE_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
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

        if (isset($result['status']) && $result['status'] === 'success') {
            return ['success' => true];
        }

        return ['success' => false, 'message' => $result['message'] ?? 'Unknown ZenoPay error'];
    }

    public function checkStatus(string $orderId): ?string
    {
        $ch = curl_init(self::STATUS_URL . '?order_id=' . urlencode($orderId));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);

        $responseRaw = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($responseRaw, true);

        if (
            isset($result['result']) && $result['result'] === 'SUCCESS'
            && isset($result['data'][0]['order_id'])
            && $result['data'][0]['order_id'] === $orderId
        ) {
            return $result['data'][0]['payment_status'];
        }

        return null;
    }

    public function verifyCompleted(string $orderId): bool
    {
        return $this->checkStatus($orderId) === 'COMPLETED';
    }
}

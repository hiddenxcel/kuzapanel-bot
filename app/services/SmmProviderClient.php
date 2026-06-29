<?php

class SmmProviderClient
{
    private string $apiUrl;
    private string $apiKey;

    public function __construct(array $provider)
    {
        $this->apiUrl = $provider['api_url'];
        $this->apiKey = $provider['api_key'];
    }

    public function addOrder(string $providerServiceId, string $link, int $quantity): array
    {
        $result = $this->call([
            'key' => $this->apiKey,
            'action' => 'add',
            'service' => $providerServiceId,
            'link' => $link,
            'quantity' => $quantity,
        ]);

        if ($result === null || isset($result['error'])) {
            return ['success' => false, 'message' => $result['error'] ?? 'Hitilafu ya provider'];
        }

        if (!isset($result['order'])) {
            return ['success' => false, 'message' => 'Provider haikurudisha order ID'];
        }

        return ['success' => true, 'order_id' => (string) $result['order']];
    }

    public function checkStatus(string $providerOrderId): array
    {
        $result = $this->call([
            'key' => $this->apiKey,
            'action' => 'status',
            'order' => $providerOrderId,
        ]);

        if ($result === null || isset($result['error'])) {
            return ['success' => false, 'message' => $result['error'] ?? 'Hitilafu ya provider'];
        }

        return [
            'success' => true,
            'status' => $result['status'] ?? null,
            'start_count' => $result['start_count'] ?? null,
            'remains' => $result['remains'] ?? null,
        ];
    }

    public function checkBalance(): array
    {
        $result = $this->call([
            'key' => $this->apiKey,
            'action' => 'balance',
        ]);

        if ($result === null || isset($result['error'])) {
            return ['success' => false, 'message' => $result['error'] ?? 'Hitilafu ya provider'];
        }

        return ['success' => true, 'balance' => $result['balance'] ?? null, 'currency' => $result['currency'] ?? null];
    }

    private function call(array $data): ?array
    {
        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err || $response === false) {
            error_log("[SmmProviderClient] cURL error: {$err}");

            return null;
        }

        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
            error_log("[SmmProviderClient] Invalid response: {$response}");

            return null;
        }

        return $decoded;
    }
}

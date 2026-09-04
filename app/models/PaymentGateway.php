<?php

require_once __DIR__ . '/BaseModel.php';

class PaymentGateway extends BaseModel
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM payment_gateways WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public static function findByCode(string $code): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM payment_gateways WHERE code = ?');
        $stmt->execute([$code]);

        return $stmt->fetch() ?: null;
    }

    public static function all(): array
    {
        $stmt = self::db()->query('SELECT * FROM payment_gateways ORDER BY name');

        return $stmt->fetchAll();
    }

    public static function update(int $id, array $data): bool
    {
        $stmt = self::db()->prepare(
            'UPDATE payment_gateways SET api_key = ?, api_secret = ?, fee_percent = ?, status = ? WHERE id = ?'
        );

        return $stmt->execute([
            $data['api_key'],
            $data['api_secret'] ?? '',
            $data['fee_percent'],
            $data['status'],
            $id,
        ]);
    }

    /**
     * Gross-up: how much the customer must pay so that, after the gateway's
     * percentage fee is deducted, the desired net amount remains.
     */
    public static function grossUpAmount(float $desiredNetAmount, string $code): float
    {
        $gateway = self::findByCode($code);
        $feePercent = $gateway !== null ? (float) $gateway['fee_percent'] : 0.0;

        if ($feePercent <= 0) {
            return $desiredNetAmount;
        }

        return round($desiredNetAmount / (1 - $feePercent / 100), 0);
    }

    /**
     * Merge .env payment config with admin-managed overrides from the DB.
     * A non-empty api_key/api_secret in the payment_gateways table takes
     * priority; otherwise the .env value is kept as a fallback.
     */
    public static function resolveConfig(string $code, array $envConfig): array
    {
        $gateway = self::findByCode($code);

        if ($gateway === null) {
            return $envConfig;
        }

        if (!empty($gateway['api_key'])) {
            $envConfig['api_key'] = $gateway['api_key'];
        }

        if (!empty($gateway['api_secret'])) {
            $envConfig['webhook_secret'] = $gateway['api_secret'];
        }

        return $envConfig;
    }
}

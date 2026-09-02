<?php

require_once __DIR__ . '/BaseModel.php';

class Payment extends BaseModel
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM payments WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public static function findByTransactionRef(string $ref): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM payments WHERE transaction_ref = ?');
        $stmt->execute([$ref]);

        return $stmt->fetch() ?: null;
    }

    public static function byOrder(int $orderId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC');
        $stmt->execute([$orderId]);

        return $stmt->fetchAll();
    }

    public static function byCustomer(int $customerId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM payments WHERE customer_id = ? ORDER BY created_at DESC');
        $stmt->execute([$customerId]);

        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO payments (type, order_id, customer_id, gateway, transaction_ref, amount, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['type'] ?? 'wallet_topup',
            $data['order_id'] ?? null,
            $data['customer_id'] ?? null,
            $data['gateway'],
            $data['transaction_ref'] ?? null,
            $data['amount'],
            $data['status'] ?? 'pending',
        ]);

        return (int) self::db()->lastInsertId();
    }

    public static function updateStatus(int $id, string $status): bool
    {
        $stmt = self::db()->prepare('UPDATE payments SET status = ? WHERE id = ?');

        return $stmt->execute([$status, $id]);
    }

    /**
     * Atomically claim a payment for confirmation: flips it to 'success' only
     * if it is not already 'success', and reports whether THIS call was the one
     * that did it. The UPDATE itself is the lock — two concurrent webhook
     * deliveries (gateways retry when a 200 is slow) both see 'pending' if the
     * check is a separate SELECT, and the wallet gets credited twice.
     *
     * 'failed' is claimable too: check_orders expires pending payments after an
     * hour, so a webhook that arrives late must still be honoured.
     */
    public static function claimForConfirmation(int $id): bool
    {
        $stmt = self::db()->prepare(
            "UPDATE payments SET status = 'success' WHERE id = ? AND status <> 'success'"
        );
        $stmt->execute([$id]);

        return $stmt->rowCount() === 1;
    }

    /**
     * Mark long-stale pending payments as failed (USSD never completed).
     * Returns the number of rows affected.
     */
    public static function expireStalePending(int $olderThanMinutes = 60): int
    {
        $stmt = self::db()->prepare(
            "UPDATE payments SET status = 'failed'
             WHERE status = 'pending' AND TIMESTAMPDIFF(MINUTE, created_at, NOW()) >= ?"
        );
        $stmt->execute([$olderThanMinutes]);

        return $stmt->rowCount();
    }

    /** Mark any still-pending payments for an order as failed, alongside cancelling the order itself. */
    public static function expirePendingForOrder(int $orderId): int
    {
        $stmt = self::db()->prepare(
            "UPDATE payments SET status = 'failed' WHERE order_id = ? AND status = 'pending'"
        );
        $stmt->execute([$orderId]);

        return $stmt->rowCount();
    }

    public static function pendingByGateway(string $gateway, int $maxAgeMinutes = 60): array
    {
        $stmt = self::db()->prepare(
            "SELECT * FROM payments
             WHERE status = 'pending' AND gateway = ? AND transaction_ref IS NOT NULL
               AND TIMESTAMPDIFF(MINUTE, created_at, NOW()) < ?
             ORDER BY created_at ASC"
        );
        $stmt->execute([$gateway, $maxAgeMinutes]);

        return $stmt->fetchAll();
    }

    public static function depositsForDate(string $date): array
    {
        $stmt = self::db()->prepare(
            "SELECT COUNT(*) AS count, COALESCE(SUM(amount), 0) AS total
             FROM payments
             WHERE status = 'success' AND DATE(created_at) = ?"
        );
        $stmt->execute([$date]);
        $row = $stmt->fetch();

        return ['count' => (int) $row['count'], 'total' => (float) $row['total']];
    }

    public static function setTransactionRef(int $id, string $ref): bool
    {
        $stmt = self::db()->prepare('UPDATE payments SET transaction_ref = ? WHERE id = ?');

        return $stmt->execute([$ref, $id]);
    }
}

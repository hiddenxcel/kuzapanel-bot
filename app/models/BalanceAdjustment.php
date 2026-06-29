<?php

require_once __DIR__ . '/BaseModel.php';

class BalanceAdjustment extends BaseModel
{
    public static function create(int $customerId, ?int $adminId, float $amount, ?string $note): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO balance_adjustments (customer_id, admin_id, amount, note) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$customerId, $adminId, $amount, $note]);

        return (int) self::db()->lastInsertId();
    }

    public static function byCustomer(int $customerId, int $limit = 10): array
    {
        $stmt = self::db()->prepare(
            'SELECT ba.*, a.username AS admin_username
             FROM balance_adjustments ba
             LEFT JOIN admins a ON a.id = ba.admin_id
             WHERE ba.customer_id = ?
             ORDER BY ba.created_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $customerId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}

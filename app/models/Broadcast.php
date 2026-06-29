<?php

require_once __DIR__ . '/BaseModel.php';

class Broadcast extends BaseModel
{
    public static function create(string $message, int $recipientCount, int $successCount, int $failedCount, ?string $createdBy = null): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO broadcasts (message, recipient_count, success_count, failed_count, created_by) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$message, $recipientCount, $successCount, $failedCount, $createdBy]);

        return (int) self::db()->lastInsertId();
    }

    public static function all(int $limit = 50): array
    {
        $stmt = self::db()->prepare('SELECT * FROM broadcasts ORDER BY id DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}

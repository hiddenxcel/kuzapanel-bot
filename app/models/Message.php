<?php

require_once __DIR__ . '/BaseModel.php';

class Message extends BaseModel
{
    public static function log(string $phone, string $direction, string $type, string $body, ?string $waMessageId = null): void
    {
        $stmt = self::db()->prepare(
            'INSERT INTO messages (customer_phone, direction, message_type, body, wa_message_id) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$phone, $direction, $type, $body, $waMessageId]);
    }

    public static function byCustomer(string $phone, int $limit = 200): array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM messages WHERE customer_phone = ? ORDER BY id DESC LIMIT ?'
        );
        $stmt->bindValue(1, $phone, PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_reverse($stmt->fetchAll());
    }

    /**
     * Distinct customers ordered by their most recent message, for the inbox list.
     */
    public static function recentCustomers(int $limit = 50): array
    {
        $stmt = self::db()->prepare(
            'SELECT
                m.customer_phone,
                c.name AS customer_name,
                lm.body AS last_body,
                lm.direction AS last_direction,
                lm.created_at AS last_at
             FROM (
                SELECT customer_phone, MAX(id) AS last_id
                FROM messages
                GROUP BY customer_phone
             ) m
             JOIN messages lm ON lm.id = m.last_id
             LEFT JOIN customers c ON c.phone = m.customer_phone
             ORDER BY lm.created_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Customers who sent an inbound message within the last 24 hours — the
     * only customers WhatsApp Cloud API allows free-form (non-template)
     * messages to right now.
     */
    public static function activeCustomersWithin24h(): array
    {
        $stmt = self::db()->query(
            "SELECT DISTINCT m.customer_phone, c.name AS customer_name
             FROM messages m
             LEFT JOIN customers c ON c.phone = m.customer_phone
             WHERE m.direction = 'in' AND m.created_at >= NOW() - INTERVAL 24 HOUR
             ORDER BY c.name, m.customer_phone"
        );

        return $stmt->fetchAll();
    }
}

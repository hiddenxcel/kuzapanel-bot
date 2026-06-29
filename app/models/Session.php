<?php

require_once __DIR__ . '/BaseModel.php';

class Session extends BaseModel
{
    public static function findByPhone(string $phone): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM sessions WHERE customer_phone = ?');
        $stmt->execute([$phone]);

        $session = $stmt->fetch();

        if (!$session) {
            return null;
        }

        $session['temp_data'] = $session['temp_data'] !== null
            ? json_decode($session['temp_data'], true)
            : [];

        return $session;
    }

    public static function getOrCreate(string $phone): array
    {
        $session = self::findByPhone($phone);

        if ($session !== null) {
            return $session;
        }

        $stmt = self::db()->prepare(
            "INSERT INTO sessions (customer_phone, state, temp_data) VALUES (?, 'IDLE', NULL)"
        );
        $stmt->execute([$phone]);

        return [
            'id' => (int) self::db()->lastInsertId(),
            'customer_phone' => $phone,
            'state' => 'IDLE',
            'temp_data' => [],
        ];
    }

    public static function updateState(string $phone, string $state, array $tempData = []): bool
    {
        $stmt = self::db()->prepare(
            'UPDATE sessions SET state = ?, temp_data = ? WHERE customer_phone = ?'
        );

        return $stmt->execute([
            $state,
            $tempData === [] ? null : json_encode($tempData),
            $phone,
        ]);
    }

    public static function reset(string $phone): bool
    {
        return self::updateState($phone, 'IDLE', []);
    }

    /**
     * A session is "expired" if it's mid-conversation (not IDLE) and has had
     * no activity for longer than $timeoutMinutes. Time difference is computed
     * inside MySQL (against its own updated_at) to avoid PHP/MySQL timezone skew.
     */
    public static function isExpired(array $session, int $timeoutMinutes = 15): bool
    {
        if (($session['state'] ?? 'IDLE') === 'IDLE') {
            return false;
        }

        $stmt = self::db()->prepare(
            'SELECT TIMESTAMPDIFF(MINUTE, updated_at, NOW()) AS mins FROM sessions WHERE customer_phone = ?'
        );
        $stmt->execute([$session['customer_phone']]);
        $row = $stmt->fetch();

        return $row !== false && (int) $row['mins'] >= $timeoutMinutes;
    }
}

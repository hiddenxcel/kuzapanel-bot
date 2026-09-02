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

    /**
     * All mid-conversation sessions that have been idle for at least
     * $timeoutMinutes — used by the cron job to proactively expire and notify
     * customers, instead of waiting for their next inbound message.
     */
    public static function expiredSessions(int $timeoutMinutes = 15): array
    {
        $stmt = self::db()->prepare(
            "SELECT *, TIMESTAMPDIFF(MINUTE, updated_at, NOW()) AS idle_minutes FROM sessions
             WHERE state NOT IN ('IDLE', 'AWAITING_TOPUP_CONFIRMATION')
               AND TIMESTAMPDIFF(MINUTE, updated_at, NOW()) >= ?"
        );
        $stmt->execute([$timeoutMinutes]);

        return $stmt->fetchAll();
    }

    /**
     * Sessions stuck awaiting a payment webhook that never arrived. These are
     * excluded from expiredSessions() (the webhook may legitimately take a few
     * minutes), so they get a longer, separate timeout — after which we assume
     * the USSD was never completed and free the customer.
     */
    public static function expiredTopupSessions(int $timeoutMinutes = 30): array
    {
        $stmt = self::db()->prepare(
            "SELECT *, TIMESTAMPDIFF(MINUTE, updated_at, NOW()) AS idle_minutes FROM sessions
             WHERE state = 'AWAITING_TOPUP_CONFIRMATION'
               AND TIMESTAMPDIFF(MINUTE, updated_at, NOW()) >= ?"
        );
        $stmt->execute([$timeoutMinutes]);

        return $stmt->fetchAll();
    }

    /**
     * Every session currently mid-conversation (any non-IDLE state),
     * regardless of how long it's been idle — for the admin "session viewer"
     * page. Unlike expiredSessions()/expiredTopupSessions(), this has no
     * minimum-idle-time filter: it's "who is active right now", not "who has
     * been stuck for a while".
     */
    public static function allActive(): array
    {
        $stmt = self::db()->query(
            "SELECT s.*, c.name AS customer_name,
                    TIMESTAMPDIFF(MINUTE, s.updated_at, NOW()) AS idle_minutes
             FROM sessions s
             LEFT JOIN customers c ON c.phone = s.customer_phone
             WHERE s.state <> 'IDLE'
             ORDER BY s.updated_at DESC"
        );

        return $stmt->fetchAll();
    }
}

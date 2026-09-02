<?php

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../helpers/CurrencyHelper.php';

class Customer extends BaseModel
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM customers WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public static function findByPhone(string $phone): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM customers WHERE phone = ?');
        $stmt->execute([$phone]);

        return $stmt->fetch() ?: null;
    }

    public static function getOrCreate(string $phone, ?string $name = null): array
    {
        $customer = self::findByPhone($phone);

        if ($customer !== null) {
            if ($name !== null && $name !== '' && $customer['name'] !== $name) {
                self::db()->prepare('UPDATE customers SET name = ? WHERE id = ?')->execute([$name, $customer['id']]);
                $customer['name'] = $name;
            }

            return $customer;
        }

        // New customer: auto-detect country from the WhatsApp phone number's
        // country code, and set both currency and bot language from it
        // (e.g. 254... -> KES + English). Existing customers are never
        // touched by this — only first contact sets these.
        $detected = CurrencyHelper::detectFromPhone($phone);

        $stmt = self::db()->prepare(
            'INSERT INTO customers (phone, name, referral_code, lang, currency) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$phone, $name, self::generateUniqueReferralCode(), $detected['lang'], $detected['currency']]);

        return self::findByPhone($phone);
    }

    /**
     * Paginated, searchable customer list for the admin panel, with each
     * customer's total order count attached.
     */
    public static function search(string $q = '', int $page = 1, int $perPage = 25): array
    {
        $where = '';
        $params = [];

        if ($q !== '') {
            $where = 'WHERE c.phone LIKE ? OR c.name LIKE ? OR c.referral_code LIKE ?';
            $like = '%' . $q . '%';
            $params = [$like, $like, $like];
        }

        $countStmt = self::db()->prepare("SELECT COUNT(*) AS c FROM customers c $where");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetch()['c'];

        $perPage = max(1, $perPage);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $stmt = self::db()->prepare(
            "SELECT c.*, COUNT(o.id) AS order_count
             FROM customers c
             LEFT JOIN orders o ON o.customer_phone = c.phone
             $where
             GROUP BY c.id
             ORDER BY c.created_at DESC
             LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute($params);

        return [
            'rows' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'perPage' => $perPage,
        ];
    }

    public static function findByReferralCode(string $code): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM customers WHERE referral_code = ?');
        $stmt->execute([strtoupper($code)]);

        return $stmt->fetch() ?: null;
    }

    public static function setReferredBy(int $id, int $referrerId): bool
    {
        // Only set if not already set, and never self-refer.
        $stmt = self::db()->prepare(
            'UPDATE customers SET referred_by = ? WHERE id = ? AND referred_by IS NULL AND id <> ?'
        );
        $stmt->execute([$referrerId, $id, $referrerId]);

        return $stmt->rowCount() === 1;
    }

    public static function creditReferralEarning(int $id, float $amount): bool
    {
        $stmt = self::db()->prepare(
            'UPDATE customers SET balance = balance + ?, referral_earnings = referral_earnings + ? WHERE id = ?'
        );

        return $stmt->execute([$amount, $amount, $id]);
    }

    public static function markFirstDepositDone(int $id): bool
    {
        $stmt = self::db()->prepare('UPDATE customers SET first_deposit_done = 1 WHERE id = ?');

        return $stmt->execute([$id]);
    }

    public static function countReferrals(int $id): int
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) AS c FROM customers WHERE referred_by = ?');
        $stmt->execute([$id]);

        return (int) $stmt->fetch()['c'];
    }

    private static function generateUniqueReferralCode(): string
    {
        do {
            $code = 'KP' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            $stmt = self::db()->prepare('SELECT 1 FROM customers WHERE referral_code = ?');
            $stmt->execute([$code]);
        } while ($stmt->fetch() !== false);

        return $code;
    }

    public static function setLang(int $id, string $lang): bool
    {
        $stmt = self::db()->prepare('UPDATE customers SET lang = ? WHERE id = ?');

        return $stmt->execute([$lang, $id]);
    }

    public static function setCurrency(int $id, string $currency): bool
    {
        $stmt = self::db()->prepare('UPDATE customers SET currency = ? WHERE id = ?');

        return $stmt->execute([$currency, $id]);
    }

    public static function setLastPaymentPhone(int $id, string $paymentPhone): bool
    {
        $stmt = self::db()->prepare('UPDATE customers SET last_payment_phone = ? WHERE id = ?');

        return $stmt->execute([$paymentPhone, $id]);
    }

    public static function credit(int $id, float $amount): bool
    {
        $stmt = self::db()->prepare('UPDATE customers SET balance = balance + ? WHERE id = ?');

        return $stmt->execute([$amount, $id]);
    }

    public static function debit(int $id, float $amount): bool
    {
        $stmt = self::db()->prepare(
            'UPDATE customers SET balance = balance - ?, total_spent = total_spent + ? WHERE id = ? AND balance >= ?'
        );
        $stmt->execute([$amount, $amount, $id, $amount]);

        return $stmt->rowCount() === 1;
    }

    /**
     * Manual admin balance adjustment (does NOT touch total_spent — this is
     * not a purchase). $amount may be positive (credit) or negative (debit).
     * Fails (returns false) rather than letting the balance go negative.
     */
    public static function adjustBalance(int $id, float $amount): bool
    {
        $stmt = self::db()->prepare(
            'UPDATE customers SET balance = balance + ? WHERE id = ? AND balance + ? >= 0'
        );
        $stmt->execute([$amount, $id, $amount]);

        return $stmt->rowCount() === 1;
    }
}

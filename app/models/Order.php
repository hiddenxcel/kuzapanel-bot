<?php

require_once __DIR__ . '/BaseModel.php';

class Order extends BaseModel
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public static function byCustomer(string $phone): array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM orders WHERE customer_phone = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$phone]);

        return $stmt->fetchAll();
    }

    public static function all(): array
    {
        $stmt = self::db()->query('SELECT * FROM orders ORDER BY created_at DESC');

        return $stmt->fetchAll();
    }

    public static function byStatus(string $status): array
    {
        $stmt = self::db()->prepare('SELECT * FROM orders WHERE status = ? ORDER BY created_at DESC');
        $stmt->execute([$status]);

        return $stmt->fetchAll();
    }

    /**
     * Paginated, searchable, filterable order list for the admin panel.
     * Joins customer name and service name/platform for display.
     */
    public static function search(array $filters, int $page = 1, int $perPage = 25): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'o.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['payment_status'])) {
            $where[] = 'o.payment_status = ?';
            $params[] = $filters['payment_status'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(o.customer_phone LIKE ? OR o.link LIKE ? OR o.id = ? OR c.name LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = is_numeric($filters['q']) ? (int) $filters['q'] : 0;
            $params[] = $like;
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countStmt = self::db()->prepare(
            "SELECT COUNT(*) AS c FROM orders o
             LEFT JOIN customers c ON c.phone = o.customer_phone
             $whereSql"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetch()['c'];

        $perPage = max(1, $perPage);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $stmt = self::db()->prepare(
            "SELECT o.*, c.name AS customer_name, s.name AS service_name, s.platform AS service_platform
             FROM orders o
             LEFT JOIN customers c ON c.phone = o.customer_phone
             LEFT JOIN services s ON s.id = o.service_id
             $whereSql
             ORDER BY o.created_at DESC
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

    public static function create(array $data): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO orders (customer_phone, service_id, quantity, link, amount, payment_status, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['customer_phone'],
            $data['service_id'],
            $data['quantity'],
            $data['link'],
            $data['amount'],
            $data['payment_status'] ?? 'pending',
            $data['status'] ?? 'pending',
        ]);

        return (int) self::db()->lastInsertId();
    }

    public static function updatePaymentStatus(int $id, string $paymentStatus): bool
    {
        $stmt = self::db()->prepare('UPDATE orders SET payment_status = ? WHERE id = ?');

        return $stmt->execute([$paymentStatus, $id]);
    }

    public static function markPaid(int $id, string $paidFrom): bool
    {
        $stmt = self::db()->prepare("UPDATE orders SET payment_status = 'paid', paid_from = ? WHERE id = ?");

        return $stmt->execute([$paidFrom, $id]);
    }

    public static function updateStatus(int $id, string $status): bool
    {
        $stmt = self::db()->prepare('UPDATE orders SET status = ? WHERE id = ?');

        return $stmt->execute([$status, $id]);
    }

    public static function setProviderOrderId(int $id, string $providerOrderId): bool
    {
        $stmt = self::db()->prepare('UPDATE orders SET provider_order_id = ? WHERE id = ?');

        return $stmt->execute([$providerOrderId, $id]);
    }

    /**
     * Record why a submission to the provider failed (shown in the admin
     * Orders page so it can be retried with the "Tuma Tena" button).
     */
    public static function setError(int $id, ?string $message): bool
    {
        $stmt = self::db()->prepare('UPDATE orders SET order_error = ? WHERE id = ?');

        return $stmt->execute([$message, $id]);
    }

    public static function dashboardStats(): array
    {
        $stmt = self::db()->query(
            "SELECT
                COUNT(*) AS orders_today,
                COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END), 0) AS revenue_today
             FROM orders
             WHERE DATE(created_at) = CURDATE()"
        );
        $today = $stmt->fetch();

        $pending = self::db()->query("SELECT COUNT(*) AS c FROM orders WHERE status = 'pending'")->fetch();
        $processing = self::db()->query("SELECT COUNT(*) AS c FROM orders WHERE status = 'processing'")->fetch();
        $total = self::db()->query('SELECT COUNT(*) AS c FROM orders')->fetch();

        return [
            'orders_today' => (int) $today['orders_today'],
            'revenue_today' => (float) $today['revenue_today'],
            'pending' => (int) $pending['c'],
            'processing' => (int) $processing['c'],
            'total' => (int) $total['c'],
        ];
    }

    /**
     * Sales stats for a specific date (default today). Profit is computed as
     * (my_price - cost_price) per 1000 * quantity, for paid orders only.
     * Pass 'yesterday' style via $date = date('Y-m-d', strtotime('-1 day')).
     */
    public static function statsForDate(string $date): array
    {
        $stmt = self::db()->prepare(
            "SELECT
                COUNT(*) AS orders_count,
                COALESCE(SUM(o.amount), 0) AS revenue,
                COALESCE(SUM((s.my_price - s.cost_price) / 1000 * o.quantity), 0) AS profit
             FROM orders o
             JOIN services s ON o.service_id = s.id
             WHERE o.payment_status = 'paid' AND DATE(o.created_at) = ?"
        );
        $stmt->execute([$date]);
        $row = $stmt->fetch();

        return [
            'orders_count' => (int) $row['orders_count'],
            'revenue' => (float) $row['revenue'],
            'profit' => (float) $row['profit'],
        ];
    }

    /**
     * Top customers by total_spent (for the sales report).
     */
    public static function topCustomers(int $limit = 5): array
    {
        $stmt = self::db()->prepare(
            'SELECT name, phone, total_spent FROM customers WHERE total_spent > 0 ORDER BY total_spent DESC LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Aggregate sales/profit across a date range (inclusive), paid orders only.
     */
    public static function statsForRange(string $fromDate, string $toDate): array
    {
        $stmt = self::db()->prepare(
            "SELECT
                COUNT(*) AS orders_count,
                COALESCE(SUM(o.amount), 0) AS revenue,
                COALESCE(SUM((s.my_price - s.cost_price) / 1000 * o.quantity), 0) AS profit
             FROM orders o
             JOIN services s ON o.service_id = s.id
             WHERE o.payment_status = 'paid' AND DATE(o.created_at) BETWEEN ? AND ?"
        );
        $stmt->execute([$fromDate, $toDate]);
        $row = $stmt->fetch();

        return [
            'orders_count' => (int) $row['orders_count'],
            'revenue' => (float) $row['revenue'],
            'profit' => (float) $row['profit'],
        ];
    }
}

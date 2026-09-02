<?php

require_once __DIR__ . '/BaseModel.php';

class Service extends BaseModel
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM services WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public static function allActive(): array
    {
        $stmt = self::db()->query("SELECT * FROM services WHERE status = 'active' ORDER BY sort_order, platform, name");

        return $stmt->fetchAll();
    }

    public static function activeByPlatform(string $platform): array
    {
        $stmt = self::db()->prepare(
            "SELECT * FROM services WHERE status = 'active' AND platform = ? ORDER BY sort_order, name"
        );
        $stmt->execute([$platform]);

        return $stmt->fetchAll();
    }

    public static function activeByPlatformAndCategory(string $platform, ?string $category): array
    {
        if ($category === null) {
            $stmt = self::db()->prepare(
                "SELECT * FROM services WHERE status = 'active' AND platform = ? AND category IS NULL ORDER BY sort_order, name"
            );
            $stmt->execute([$platform]);

            return $stmt->fetchAll();
        }

        $stmt = self::db()->prepare(
            "SELECT * FROM services WHERE status = 'active' AND platform = ? AND category = ? ORDER BY sort_order, name"
        );
        $stmt->execute([$platform, $category]);

        return $stmt->fetchAll();
    }

    public static function activePlatforms(): array
    {
        $stmt = self::db()->query(
            "SELECT platform FROM services WHERE status = 'active' GROUP BY platform ORDER BY MIN(sort_order)"
        );

        return array_column($stmt->fetchAll(), 'platform');
    }

    /**
     * Distinct categories in use within an active platform, in the order
     * they first appear (by sort_order). NULL/empty categories are excluded —
     * callers check separately whether any uncategorised services exist.
     */
    public static function activeCategoriesForPlatform(string $platform): array
    {
        $stmt = self::db()->prepare(
            "SELECT category FROM services
             WHERE status = 'active' AND platform = ? AND category IS NOT NULL AND category <> ''
             GROUP BY category ORDER BY MIN(sort_order)"
        );
        $stmt->execute([$platform]);

        return array_column($stmt->fetchAll(), 'category');
    }

    /**
     * Whether an active platform has at least one service with no category —
     * used to decide if an "Other" bucket is needed alongside its categories.
     */
    public static function hasUncategorisedActive(string $platform): bool
    {
        $stmt = self::db()->prepare(
            "SELECT 1 FROM services
             WHERE status = 'active' AND platform = ? AND (category IS NULL OR category = '') LIMIT 1"
        );
        $stmt->execute([$platform]);

        return $stmt->fetch() !== false;
    }

    /** Distinct categories across all services (for the admin form's dropdown). */
    public static function allCategories(): array
    {
        $stmt = self::db()->query(
            "SELECT DISTINCT category FROM services WHERE category IS NOT NULL AND category <> '' ORDER BY category"
        );

        return array_column($stmt->fetchAll(), 'category');
    }

    /** Distinct platforms across all services (for the admin form's dropdown). */
    public static function allPlatforms(): array
    {
        $stmt = self::db()->query(
            "SELECT DISTINCT platform FROM services ORDER BY platform"
        );

        return array_column($stmt->fetchAll(), 'platform');
    }

    public static function byProvider(int $providerId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM services WHERE provider_id = ? ORDER BY sort_order, name');
        $stmt->execute([$providerId]);

        return $stmt->fetchAll();
    }

    public static function all(): array
    {
        $stmt = self::db()->query('SELECT * FROM services ORDER BY sort_order, platform, name');

        return $stmt->fetchAll();
    }

    /** Summary counts for the admin page's stat row. */
    public static function stats(): array
    {
        $row = self::db()->query(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'active') AS active,
                SUM(status = 'inactive') AS inactive,
                COUNT(DISTINCT platform) AS platforms
             FROM services"
        )->fetch();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'active' => (int) ($row['active'] ?? 0),
            'inactive' => (int) ($row['inactive'] ?? 0),
            'platforms' => (int) ($row['platforms'] ?? 0),
        ];
    }

    /**
     * Swap sort_order with the previous service in the same platform group.
     */
    public static function moveUp(int $id): bool
    {
        $service = self::find($id);
        if (!$service) {
            return false;
        }

        $stmt = self::db()->prepare(
            'SELECT id, sort_order FROM services WHERE platform = ? AND sort_order < ? ORDER BY sort_order DESC LIMIT 1'
        );
        $stmt->execute([$service['platform'], $service['sort_order']]);
        $prev = $stmt->fetch();
        if (!$prev) {
            return false;
        }

        return self::swapSortOrder($service['id'], $service['sort_order'], $prev['id'], $prev['sort_order']);
    }

    /**
     * Swap sort_order with the next service in the same platform group.
     */
    public static function moveDown(int $id): bool
    {
        $service = self::find($id);
        if (!$service) {
            return false;
        }

        $stmt = self::db()->prepare(
            'SELECT id, sort_order FROM services WHERE platform = ? AND sort_order > ? ORDER BY sort_order ASC LIMIT 1'
        );
        $stmt->execute([$service['platform'], $service['sort_order']]);
        $next = $stmt->fetch();
        if (!$next) {
            return false;
        }

        return self::swapSortOrder($service['id'], $service['sort_order'], $next['id'], $next['sort_order']);
    }

    private static function swapSortOrder(int $idA, int $orderA, int $idB, int $orderB): bool
    {
        $db = self::db();
        $db->beginTransaction();
        $stmt = $db->prepare('UPDATE services SET sort_order = ? WHERE id = ?');
        $stmt->execute([$orderB, $idA]);
        $stmt->execute([$orderA, $idB]);
        $db->commit();

        return true;
    }

    public static function create(array $data): int
    {
        $nextOrder = self::db()->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM services')->fetchColumn();

        $stmt = self::db()->prepare(
            'INSERT INTO services
                (provider_id, provider_service_id, platform, category, name, unit_label, cost_price, my_price,
                 min_quantity, max_quantity, link_instructions, link_instructions_image, status, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['provider_id'],
            $data['provider_service_id'],
            $data['platform'],
            $data['category'] ?? null,
            $data['name'],
            $data['unit_label'] ?? 'Followers',
            $data['cost_price'],
            $data['my_price'],
            $data['min_quantity'] ?? 1,
            $data['max_quantity'] ?? 1,
            $data['link_instructions'] ?? null,
            $data['link_instructions_image'] ?? null,
            $data['status'] ?? 'active',
            $nextOrder,
        ]);

        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $stmt = self::db()->prepare(
            'UPDATE services SET
                provider_id = ?, provider_service_id = ?, platform = ?, category = ?, name = ?, unit_label = ?,
                cost_price = ?, my_price = ?, min_quantity = ?, max_quantity = ?,
                link_instructions = ?, link_instructions_image = ?, status = ?
             WHERE id = ?'
        );

        return $stmt->execute([
            $data['provider_id'],
            $data['provider_service_id'],
            $data['platform'],
            $data['category'] ?? null,
            $data['name'],
            $data['unit_label'],
            $data['cost_price'],
            $data['my_price'],
            $data['min_quantity'],
            $data['max_quantity'],
            $data['link_instructions'] ?? null,
            $data['link_instructions_image'] ?? null,
            $data['status'],
            $id,
        ]);
    }

    public static function delete(int $id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM services WHERE id = ?');

        return $stmt->execute([$id]);
    }

    /** @param int[] $ids */
    public static function bulkSetStatus(array $ids, string $status): int
    {
        if ($ids === []) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = self::db()->prepare("UPDATE services SET status = ? WHERE id IN ($placeholders)");
        $stmt->execute([$status, ...$ids]);

        return $stmt->rowCount();
    }

    /** @param int[] $ids */
    public static function bulkDelete(array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = self::db()->prepare("DELETE FROM services WHERE id IN ($placeholders)");
        $stmt->execute($ids);

        return $stmt->rowCount();
    }

    /**
     * Adjust my_price for the given services by a percentage (positive to
     * raise, negative to lower). cost_price is untouched — this only moves
     * the customer-facing price.
     *
     * @param int[] $ids
     */
    public static function bulkAdjustPrice(array $ids, float $percent): int
    {
        if ($ids === []) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $multiplier = 1 + ($percent / 100);
        $stmt = self::db()->prepare(
            "UPDATE services SET my_price = ROUND(my_price * ?, 4) WHERE id IN ($placeholders)"
        );
        $stmt->execute([$multiplier, ...$ids]);

        return $stmt->rowCount();
    }
}

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

    public static function activePlatforms(): array
    {
        $stmt = self::db()->query(
            "SELECT platform FROM services WHERE status = 'active' GROUP BY platform ORDER BY MIN(sort_order)"
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
                (provider_id, provider_service_id, platform, name, unit_label, cost_price, my_price,
                 min_quantity, max_quantity, link_instructions, link_instructions_image, status, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['provider_id'],
            $data['provider_service_id'],
            $data['platform'],
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
                provider_id = ?, provider_service_id = ?, platform = ?, name = ?, unit_label = ?,
                cost_price = ?, my_price = ?, min_quantity = ?, max_quantity = ?,
                link_instructions = ?, link_instructions_image = ?, status = ?
             WHERE id = ?'
        );

        return $stmt->execute([
            $data['provider_id'],
            $data['provider_service_id'],
            $data['platform'],
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
}

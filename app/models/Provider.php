<?php

require_once __DIR__ . '/BaseModel.php';

class Provider extends BaseModel
{
    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM providers WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public static function allActive(): array
    {
        $stmt = self::db()->query("SELECT * FROM providers WHERE status = 'active' ORDER BY name");

        return $stmt->fetchAll();
    }

    public static function all(): array
    {
        $stmt = self::db()->query('SELECT * FROM providers ORDER BY name');

        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO providers (name, api_url, api_key, status) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['api_url'],
            $data['api_key'],
            $data['status'] ?? 'active',
        ]);

        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $stmt = self::db()->prepare(
            'UPDATE providers SET name = ?, api_url = ?, api_key = ?, status = ? WHERE id = ?'
        );

        return $stmt->execute([
            $data['name'],
            $data['api_url'],
            $data['api_key'],
            $data['status'],
            $id,
        ]);
    }

    public static function delete(int $id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM providers WHERE id = ?');

        return $stmt->execute([$id]);
    }
}

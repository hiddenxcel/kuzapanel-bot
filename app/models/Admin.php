<?php

require_once __DIR__ . '/BaseModel.php';

class Admin extends BaseModel
{
    public static function findByUsername(string $username): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM admins WHERE username = ?');
        $stmt->execute([$username]);

        return $stmt->fetch() ?: null;
    }

    public static function create(string $username, string $password): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO admins (username, password_hash) VALUES (?, ?)'
        );
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);

        return (int) self::db()->lastInsertId();
    }

    public static function verify(string $username, string $password): ?array
    {
        $admin = self::findByUsername($username);

        if ($admin === null || !password_verify($password, $admin['password_hash'])) {
            return null;
        }

        return $admin;
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM admins WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Returns false if the new username is already taken by another admin.
     */
    public static function updateUsername(int $id, string $newUsername): bool
    {
        $existing = self::findByUsername($newUsername);

        if ($existing !== null && (int) $existing['id'] !== $id) {
            return false;
        }

        $stmt = self::db()->prepare('UPDATE admins SET username = ? WHERE id = ?');

        return $stmt->execute([$newUsername, $id]);
    }

    public static function updatePassword(int $id, string $newPassword): bool
    {
        $stmt = self::db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?');

        return $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $id]);
    }
}

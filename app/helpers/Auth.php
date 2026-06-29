<?php

require_once __DIR__ . '/../models/Admin.php';

class Auth
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function attempt(string $username, string $password): bool
    {
        $admin = Admin::verify($username, $password);

        if ($admin === null) {
            return false;
        }

        self::start();
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];

        return true;
    }

    public static function check(): bool
    {
        self::start();

        return isset($_SESSION['admin_id']);
    }

    public static function user(): ?array
    {
        self::start();

        if (!isset($_SESSION['admin_id'])) {
            return null;
        }

        return [
            'id' => $_SESSION['admin_id'],
            'username' => $_SESSION['admin_username'],
        ];
    }

    public static function requireLogin(): void
    {
        self::start();

        if (!self::check()) {
            header('Location: login.php');
            exit;
        }
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
    }
}

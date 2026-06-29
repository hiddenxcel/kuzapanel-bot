<?php
// CLI-only: php database/seed_admin.php <username> <password>

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    die();
}

require_once __DIR__ . '/../app/models/Admin.php';

[$script, $username, $password] = array_pad($argv, 3, null);

if ($username === null || $password === null) {
    echo "Usage: php database/seed_admin.php <username> <password>\n";
    exit(1);
}

if (Admin::findByUsername($username) !== null) {
    echo "Admin '{$username}' already exists.\n";
    exit(1);
}

Admin::create($username, $password);
echo "Admin '{$username}' created.\n";

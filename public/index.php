<?php

require_once __DIR__ . '/../app/controllers/WebhookController.php';

$config = require __DIR__ . '/../config/config.php';
$controller = new WebhookController($config);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $challenge = $controller->verify($_GET);

    if ($challenge !== null) {
        echo $challenge;

        return;
    }

    http_response_code(403);

    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true) ?? [];

    try {
        $controller->handleIncoming($payload);
    } catch (Throwable $e) {
        error_log('[Webhook] ' . $e->getMessage());
    }

    http_response_code(200);
    echo 'OK';

    return;
}

http_response_code(405);

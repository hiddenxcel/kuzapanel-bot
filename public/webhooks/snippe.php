<?php

require_once __DIR__ . '/../../app/services/payments/SnippeClient.php';
require_once __DIR__ . '/../../app/services/WalletTopupHandler.php';
require_once __DIR__ . '/../../app/models/Payment.php';

header('Content-Type: application/json');

$config = require __DIR__ . '/../../config/config.php';

$body = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$timestamp = $_SERVER['HTTP_X_WEBHOOK_TIMESTAMP'] ?? '';

$snippe = new SnippeClient($config['payments']['snippe']);

if (!$snippe->verifySignature($body, $signature, $timestamp)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);

    return;
}

$data = json_decode($body, true);

if (!$data || ($data['type'] ?? null) !== 'payment.completed') {
    echo json_encode(['status' => 'ignored']);

    return;
}

$reference = $data['data']['reference'] ?? null;

if ($reference === null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing reference']);

    return;
}

$payment = Payment::findByTransactionRef($reference);

if ($payment === null) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Payment not found']);

    return;
}

(new WalletTopupHandler($config))->handleConfirmedPayment($payment);

echo json_encode(['status' => 'success']);

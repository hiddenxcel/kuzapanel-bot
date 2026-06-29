<?php

require_once __DIR__ . '/../../app/services/payments/HarakaPayClient.php';
require_once __DIR__ . '/../../app/services/WalletTopupHandler.php';
require_once __DIR__ . '/../../app/models/Payment.php';

header('Content-Type: application/json');

$config = require __DIR__ . '/../../config/config.php';

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data || !isset($data['order_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid payload']);

    return;
}

$orderId = $data['order_id'];
$status = $data['status'] ?? null;

if ($status !== 'completed') {
    echo json_encode(['status' => 'ignored']);

    return;
}

$harakapay = new HarakaPayClient($config['payments']['harakapay']);

if (!$harakapay->verifyCompleted($orderId)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Verification failed']);

    return;
}

$payment = Payment::findByTransactionRef($orderId);

if ($payment === null) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Payment not found']);

    return;
}

(new WalletTopupHandler($config))->handleConfirmedPayment($payment);

echo json_encode(['status' => 'success']);

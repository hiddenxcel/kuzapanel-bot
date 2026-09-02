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

// Direct-push (TZS) webhooks carry type='payment.completed'; the KES/UGX
// Sessions API has been observed to also set data.status='completed' — accept
// either shape rather than assuming only one. Neither shape needs its
// amount read here: the payment's TZS amount is trusted from the payments
// row (set at initiation), not re-parsed from the webhook body.
$eventType = $data['type'] ?? null;
$sessionStatus = $data['data']['status'] ?? null;

if (!$data || ($eventType !== 'payment.completed' && $sessionStatus !== 'completed')) {
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

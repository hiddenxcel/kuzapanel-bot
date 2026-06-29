<?php
// Run every 1-2 minutes via Windows Task Scheduler:
// php c:\xampp\htdocs\kuzapanel\bot\cron\check_harakapay.php
// HarakaPay has no webhook — we poll pending HarakaPay payments and, once the
// provider reports them completed, run the same confirmation logic as the webhook.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    die();
}

require_once __DIR__ . '/../app/models/Payment.php';
require_once __DIR__ . '/../app/services/payments/HarakaPayClient.php';
require_once __DIR__ . '/../app/services/WalletTopupHandler.php';

$config = require __DIR__ . '/../config/config.php';
$client = new HarakaPayClient($config['payments']['harakapay']);
$handler = new WalletTopupHandler($config);

$pending = Payment::pendingByGateway('harakapay', 60);

foreach ($pending as $payment) {
    $status = $client->checkStatus($payment['transaction_ref']);

    if ($status === 'completed') {
        // Re-fetch to avoid double-processing if status already flipped.
        $fresh = Payment::find((int) $payment['id']);
        if ($fresh !== null && $fresh['status'] !== 'success') {
            $handler->handleConfirmedPayment($fresh);
            echo "HarakaPay payment #{$payment['id']} ({$payment['transaction_ref']}) -> completed\n";
        }
    }
}

echo 'Done. Checked ' . count($pending) . " pending HarakaPay payment(s).\n";

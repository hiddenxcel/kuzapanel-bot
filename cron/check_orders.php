<?php
// Run every 5-10 minutes via Windows Task Scheduler:
// php c:\xampp\htdocs\kuzapanel\bot\cron\check_orders.php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    die();
}

require_once __DIR__ . '/../app/models/Order.php';
require_once __DIR__ . '/../app/models/Service.php';
require_once __DIR__ . '/../app/models/Provider.php';
require_once __DIR__ . '/../app/models/Payment.php';
require_once __DIR__ . '/../app/models/Customer.php';
require_once __DIR__ . '/../app/services/SmmProviderClient.php';
require_once __DIR__ . '/../app/services/WhatsAppClient.php';
require_once __DIR__ . '/../app/helpers/BotLang.php';

$config = require __DIR__ . '/../config/config.php';
$whatsapp = new WhatsAppClient($config['whatsapp']);

// Cancel orders still waiting on a gateway payment after 15 minutes — the
// customer likely never completed the USSD prompt. The order was recorded
// up front (payment_status='pending') when payment was initiated, so it's
// safe to cancel outright: no wallet debit has happened yet for it. Both
// the order and its linked payment are marked, and the customer is told
// by name/number so a stray "pesa imekatwa lakini oda haipo" report never
// happens silently.
$stalePendingOrders = Order::stalePendingPayment(15);
foreach ($stalePendingOrders as $staleOrder) {
    Order::updateStatus($staleOrder['id'], 'cancelled');
    Payment::expirePendingForOrder((int) $staleOrder['id']);

    $lang = BotLang::forCustomer(Customer::findByPhone($staleOrder['customer_phone']));
    $whatsapp->sendText(
        $staleOrder['customer_phone'],
        BotLang::get($lang, 'order_payment_expired', ['{number}' => $staleOrder['id']])
    );

    echo "Order #{$staleOrder['id']}: cancelled — payment never completed.\n";
}

// Expire stale pending payments (standalone top-ups: USSD prompts that were
// never completed, with no order attached to notify about).
$expired = Payment::expireStalePending(60);
if ($expired > 0) {
    echo "Expired {$expired} stale pending payment(s).\n";
}

$orders = Order::byStatus('processing');
$providerCache = [];

foreach ($orders as $order) {
    if ($order['provider_order_id'] === null) {
        continue;
    }

    $service = Service::find($order['service_id']);

    if ($service === null) {
        continue;
    }

    $providerId = $service['provider_id'];

    if (!isset($providerCache[$providerId])) {
        $providerCache[$providerId] = Provider::find($providerId);
    }

    $provider = $providerCache[$providerId];

    if ($provider === null) {
        continue;
    }

    $client = new SmmProviderClient($provider);
    $result = $client->checkStatus($order['provider_order_id']);

    if (!$result['success']) {
        echo "Order #{$order['id']}: status check failed — {$result['message']}\n";

        continue;
    }

    $providerStatus = strtolower($result['status'] ?? '');
    $localStatus = match ($providerStatus) {
        'completed' => 'completed',
        'canceled', 'cancelled' => 'cancelled',
        'partial' => 'completed',
        default => 'processing',
    };

    if ($localStatus !== $order['status']) {
        Order::updateStatus($order['id'], $localStatus);
        echo "Order #{$order['id']}: {$order['status']} -> {$localStatus}\n";

        $qty = number_format((int) $order['quantity'], 0);
        $unit = $service['unit_label'];
        $lang = BotLang::forCustomer(Customer::findByPhone($order['customer_phone']));

        // Show the provider's order number; fall back to our internal id only
        // if the order somehow has none.
        $orderNumber = !empty($order['provider_order_id']) ? $order['provider_order_id'] : $order['id'];

        if ($localStatus === 'completed') {
            $whatsapp->sendText(
                $order['customer_phone'],
                BotLang::get($lang, 'order_completed_notify', [
                    '{number}' => $orderNumber,
                    '{service}' => $service['name'],
                    '{qty}' => $qty,
                    '{unit}' => $unit,
                    '{link}' => $order['link'],
                ])
            );
        } elseif ($localStatus === 'cancelled') {
            $whatsapp->sendText(
                $order['customer_phone'],
                BotLang::get($lang, 'order_cancelled_notify', [
                    '{number}' => $orderNumber,
                    '{service}' => $service['name'],
                ])
            );
        }
    }
}

echo "Done. Checked " . count($orders) . " order(s).\n";

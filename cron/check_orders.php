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

// Expire stale pending payments (USSD prompts that were never completed).
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

        if ($localStatus === 'completed') {
            $whatsapp->sendText(
                $order['customer_phone'],
                BotLang::get($lang, 'order_completed_notify', [
                    '{id}' => $order['id'],
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
                    '{id}' => $order['id'],
                    '{service}' => $service['name'],
                ])
            );
        }
    }
}

echo "Done. Checked " . count($orders) . " order(s).\n";

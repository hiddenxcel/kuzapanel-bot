<?php

require_once __DIR__ . '/SmmProviderClient.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/Provider.php';

class OrderFulfillment
{
    public static function submit(int $orderId): void
    {
        $order = Order::find($orderId);

        if ($order === null) {
            return;
        }

        $service = Service::find($order['service_id']);

        if ($service === null) {
            return;
        }

        $provider = Provider::find($service['provider_id']);

        if ($provider === null || $provider['status'] !== 'active') {
            error_log("[OrderFulfillment] Order #{$orderId}: provider unavailable");
            Order::setError($orderId, 'Provider hakuna au amezimwa.');

            return;
        }

        $client = new SmmProviderClient($provider);
        $result = $client->addOrder($service['provider_service_id'], $order['link'], $order['quantity']);

        if (!$result['success']) {
            error_log("[OrderFulfillment] Order #{$orderId} failed: {$result['message']}");
            Order::setError($orderId, $result['message'] ?? 'Hitilafu isiyojulikana kutoka kwa provider.');

            return;
        }

        Order::setProviderOrderId($orderId, $result['order_id']);
        Order::setError($orderId, null);
        Order::updateStatus($orderId, 'processing');
    }
}

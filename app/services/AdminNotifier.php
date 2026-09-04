<?php

require_once __DIR__ . '/WhatsAppClient.php';

class AdminNotifier
{
    private WhatsAppClient $whatsapp;
    private string $adminPhone;

    public function __construct(array $config)
    {
        $this->whatsapp = new WhatsAppClient($config['whatsapp']);
        $this->adminPhone = preg_replace('/[^0-9]/', '', $config['admin']['phone_number'] ?? '');
    }

    public function notify(string $message): void
    {
        if ($this->adminPhone === '') {
            return;
        }

        $this->whatsapp->sendText($this->adminPhone, $message);
    }

    public function newOrder(array $order, array $service, array $customer): void
    {
        // Admin sees both: our internal id (used in the admin panel) and the
        // provider's order number (shown only once the order reaches the provider).
        $providerLine = !empty($order['provider_order_id'])
            ? "🔢 Provider #{$order['provider_order_id']}\n"
            : '';

        $this->notify(
            "🛒 *ODA MPYA*\n\n" .
            "🆔 Oda #{$order['id']}\n" .
            $providerLine .
            "👤 Mteja: " . ($customer['name'] ?? 'Mteja') . " ({$customer['phone']})\n" .
            "🎯 Huduma: {$service['name_sw']}\n" .
            "🔢 Kiasi: " . number_format((int) $order['quantity'], 0) . " {$service['unit_label']}\n" .
            "💰 Gharama: " . number_format((float) $order['amount'], 0) . " TZS\n" .
            "🔗 Link: {$order['link']}"
        );
    }

    public function newDeposit(array $customer, float $amount, string $gateway): void
    {
        $this->notify(
            "💰 *MALIPO MAPYA*\n\n" .
            "👤 Mteja: " . ($customer['name'] ?? 'Mteja') . " ({$customer['phone']})\n" .
            "💵 Kiasi: " . number_format($amount, 0) . " TZS\n" .
            "🏦 Njia: " . ucfirst($gateway)
        );
    }
}

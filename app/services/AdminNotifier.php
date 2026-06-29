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
        $this->notify(
            "🛒 *ODA MPYA*\n\n" .
            "🆔 Oda #{$order['id']}\n" .
            "👤 Mteja: " . ($customer['name'] ?? 'Mteja') . " ({$customer['phone']})\n" .
            "🎯 Huduma: {$service['name']}\n" .
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

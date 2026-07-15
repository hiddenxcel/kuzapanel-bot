<?php

require_once __DIR__ . '/WhatsAppClient.php';
require_once __DIR__ . '/OrderFulfillment.php';
require_once __DIR__ . '/AdminNotifier.php';
require_once __DIR__ . '/../helpers/MainMenu.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/Session.php';
require_once __DIR__ . '/../helpers/BotLang.php';

class WalletTopupHandler
{
    private WhatsAppClient $whatsapp;
    private AdminNotifier $admin;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->whatsapp = new WhatsAppClient($config['whatsapp']);
        $this->admin = new AdminNotifier($config);
    }

    public function handleConfirmedPayment(array $payment): void
    {
        if ($payment['status'] === 'success') {
            return;
        }

        Payment::updateStatus($payment['id'], 'success');
        Customer::credit((int) $payment['customer_id'], (float) $payment['amount']);

        $customer = Customer::find((int) $payment['customer_id']);

        if ($customer === null) {
            return;
        }

        $this->whatsapp->sendText(
            $customer['phone'],
            BotLang::get(BotLang::forCustomer($customer), 'deposit_confirmed', [
                '{amount}' => number_format((float) $payment['amount'], 0),
            ])
        );

        $this->admin->newDeposit($customer, (float) $payment['amount'], $payment['gateway']);

        $this->payReferralBonus($customer, (float) $payment['amount']);

        $this->tryCompletePendingOrder($customer);
    }

    /**
     * On the referred customer's FIRST deposit, pay the referrer a percentage bonus.
     */
    private function payReferralBonus(array $customer, float $depositAmount): void
    {
        if ((int) $customer['first_deposit_done'] === 1) {
            return;
        }

        // Mark first deposit done regardless, so this only ever runs once.
        Customer::markFirstDepositDone((int) $customer['id']);

        if ($customer['referred_by'] === null) {
            return;
        }

        $referrer = Customer::find((int) $customer['referred_by']);

        if ($referrer === null) {
            return;
        }

        $percent = (float) ($this->config['referral']['percent'] ?? 0);
        $bonus = round($depositAmount * ($percent / 100), 0);

        if ($bonus <= 0) {
            return;
        }

        Customer::creditReferralEarning((int) $referrer['id'], $bonus);

        $this->whatsapp->sendText(
            $referrer['phone'],
            BotLang::get(BotLang::forCustomer($referrer), 'referral_bonus', [
                '{bonus}' => number_format($bonus, 0),
            ])
        );
    }

    private function tryCompletePendingOrder(array $customer): void
    {
        $session = Session::findByPhone($customer['phone']);

        if ($session === null || $session['state'] !== 'AWAITING_TOPUP_CONFIRMATION') {
            return;
        }

        $data = $session['temp_data'];
        $freshCustomer = Customer::find((int) $customer['id']);

        if ($freshCustomer === null || (float) $freshCustomer['balance'] < (float) $data['amount']) {
            return;
        }

        if (!Customer::debit((int) $freshCustomer['id'], (float) $data['amount'])) {
            return;
        }

        $orderId = Order::create([
            'customer_phone' => $customer['phone'],
            'service_id' => $data['service_id'],
            'quantity' => $data['quantity'],
            'link' => $data['link'],
            'amount' => $data['amount'],
        ]);

        Order::markPaid($orderId, 'wallet');
        OrderFulfillment::submit($orderId);

        $order = Order::find($orderId);
        $service = Service::find($data['service_id']);
        if ($order !== null && $service !== null) {
            $this->admin->newOrder($order, $service, $customer);
        }

        $lang = BotLang::forCustomer($customer);

        // Show the provider's order number; fall back to our internal id only
        // if the order hasn't reached the provider yet (so it's never blank).
        $orderNumber = !empty($order['provider_order_id']) ? $order['provider_order_id'] : $orderId;
        $providerOrderLine = BotLang::get($lang, 'order_provider_line', ['{provider}' => $orderNumber]);

        $this->whatsapp->sendText(
            $customer['phone'],
            BotLang::get($lang, 'order_received', [
                '{provider_line}' => $providerOrderLine,
            ])
        );

        Session::reset($customer['phone']);
        MainMenu::send($this->whatsapp, $customer['phone'], $customer['name'] ?? null, $lang);
    }
}

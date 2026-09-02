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
require_once __DIR__ . '/../helpers/CurrencyHelper.php';

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
        // Atomically claim the payment. If a concurrent delivery of the same
        // webhook already claimed it, this returns false and we stop — without
        // it the wallet gets credited once per delivery, and gateways do retry
        // whenever a 200 is slow to come back.
        if (!Payment::claimForConfirmation((int) $payment['id'])) {
            return;
        }

        Customer::credit((int) $payment['customer_id'], (float) $payment['amount']);

        $customer = Customer::find((int) $payment['customer_id']);

        if ($customer === null) {
            return;
        }

        $currency = CurrencyHelper::forCustomer($customer);

        $this->whatsapp->sendText(
            $customer['phone'],
            BotLang::get(BotLang::forCustomer($customer), 'deposit_confirmed', [
                '{amount}' => CurrencyHelper::format((float) $payment['amount'], $currency),
                '{currency}' => $currency,
            ])
        );

        $this->admin->newDeposit($customer, (float) $payment['amount'], $payment['gateway']);

        $this->payReferralBonus($customer, (float) $payment['amount']);

        $this->tryCompletePendingOrder($customer, $payment);
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

        $referrerCurrency = CurrencyHelper::forCustomer($referrer);

        $this->whatsapp->sendText(
            $referrer['phone'],
            BotLang::get(BotLang::forCustomer($referrer), 'referral_bonus', [
                '{bonus}' => CurrencyHelper::format($bonus, $referrerCurrency),
                '{currency}' => $referrerCurrency,
            ])
        );
    }

    /**
     * If this payment was for an order (order_id set — see
     * WebhookController::initiatePayment, which records the order up front,
     * before the gateway is even called), complete THAT order now.
     *
     * This deliberately does not look at the customer's conversation session:
     * a stray "#", a session timeout, or simply messaging support while
     * waiting for the USSD prompt would otherwise wipe the pending-order
     * context and leave the wallet credited with no order ever placed. The
     * order row itself — not the session — is the source of truth for what
     * the customer paid for.
     */
    private function tryCompletePendingOrder(array $customer, array $payment): void
    {
        if ($payment['order_id'] === null) {
            return;
        }

        $order = Order::find((int) $payment['order_id']);

        // Already resolved (paid by another route, or expired/cancelled by
        // the stale-payment cron) — nothing to complete.
        if ($order === null || $order['payment_status'] !== 'pending') {
            return;
        }

        $freshCustomer = Customer::find((int) $customer['id']);

        if ($freshCustomer === null || (float) $freshCustomer['balance'] < (float) $order['amount']) {
            return;
        }

        if (!Customer::debit((int) $freshCustomer['id'], (float) $order['amount'])) {
            return;
        }

        Order::markPaid((int) $order['id'], 'wallet');
        OrderFulfillment::submit((int) $order['id']);

        $order = Order::find((int) $order['id']);
        $service = Service::find($order['service_id']);
        if ($order !== null && $service !== null) {
            $this->admin->newOrder($order, $service, $customer);
        }

        $lang = BotLang::forCustomer($customer);

        // Show the provider's order number; fall back to our internal id only
        // if the order hasn't reached the provider yet (so it's never blank).
        $orderNumber = !empty($order['provider_order_id']) ? $order['provider_order_id'] : $order['id'];
        $providerOrderLine = BotLang::get($lang, 'order_provider_line', ['{provider}' => $orderNumber]);

        $this->whatsapp->sendText(
            $customer['phone'],
            BotLang::get($lang, 'order_received', [
                '{provider_line}' => $providerOrderLine,
            ])
        );

        // Only clear the session if it's still sitting on this same payment —
        // the customer may have already moved on to something else, and that
        // in-progress state (e.g. a NEW order they started) must not be wiped.
        $session = Session::findByPhone($customer['phone']);
        if ($session !== null && $session['state'] === 'AWAITING_TOPUP_CONFIRMATION'
            && ($session['temp_data']['order_id'] ?? null) === $order['id']) {
            Session::reset($customer['phone']);
            MainMenu::send($this->whatsapp, $customer['phone'], $customer['name'] ?? null, $lang);
        }
    }
}

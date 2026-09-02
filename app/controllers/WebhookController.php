<?php

require_once __DIR__ . '/../services/WhatsAppClient.php';
require_once __DIR__ . '/../services/payments/ZenoPayClient.php';
require_once __DIR__ . '/../services/payments/SnippeClient.php';
require_once __DIR__ . '/../services/payments/HarakaPayClient.php';
require_once __DIR__ . '/../services/DeepSeekClient.php';
require_once __DIR__ . '/../models/Session.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../models/PaymentGateway.php';
require_once __DIR__ . '/../models/Message.php';
require_once __DIR__ . '/../models/AppSetting.php';
require_once __DIR__ . '/../services/OrderFulfillment.php';
require_once __DIR__ . '/../services/AdminNotifier.php';
require_once __DIR__ . '/../helpers/MainMenu.php';
require_once __DIR__ . '/../helpers/BotLang.php';

class WebhookController
{
    private WhatsAppClient $whatsapp;
    private ZenoPayClient $zenopay;
    private SnippeClient $snippe;
    private HarakaPayClient $harakapay;
    private DeepSeekClient $deepseek;
    private AdminNotifier $admin;
    private array $config;

    /** Per-request cache of each phone's bot language. */
    private array $langCache = [];

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->whatsapp = new WhatsAppClient($config['whatsapp']);
        $this->zenopay = new ZenoPayClient(PaymentGateway::resolveConfig('zenopay', $config['payments']['zenopay']));
        $this->snippe = new SnippeClient(PaymentGateway::resolveConfig('snippe', $config['payments']['snippe']));
        $this->harakapay = new HarakaPayClient(PaymentGateway::resolveConfig('harakapay', $config['payments']['harakapay']));
        $this->deepseek = new DeepSeekClient($config['deepseek']);
        $this->admin = new AdminNotifier($config);
    }

    /**
     * The customer's saved bot language (cached per request).
     */
    private function langFor(string $phone): string
    {
        if (!isset($this->langCache[$phone])) {
            $this->langCache[$phone] = BotLang::forCustomer(Customer::findByPhone($phone));
        }

        return $this->langCache[$phone];
    }

    /** Convenience: translate a key in the caller's language. */
    private function t(string $phone, string $key, array $vars = []): string
    {
        return BotLang::get($this->langFor($phone), $key, $vars);
    }

    public function verify(array $params): ?string
    {
        $mode = $params['hub_mode'] ?? null;
        $token = $params['hub_verify_token'] ?? null;
        $challenge = $params['hub_challenge'] ?? null;

        if ($mode === 'subscribe' && $token === $this->config['whatsapp']['verify_token']) {
            return $challenge;
        }

        return null;
    }

    public function handleIncoming(array $payload): void
    {
        $message = $this->extractMessage($payload);

        if ($message === null) {
            return;
        }

        $phone = $message['from'];

        $this->logIncomingMessage($phone, $message);

        // Maintenance mode: reply with a single notice and stop — no read
        // receipt/typing indicator, no session/state-machine work at all.
        // Checked after logging so the inbound message still shows up in the
        // admin inbox/history.
        if (AppSetting::isMaintenanceEnabled()) {
            $this->whatsapp->sendText($phone, $this->t($phone, 'maintenance_message'));

            return;
        }

        // Mark the incoming message as read (blue ticks) and show "typing…".
        if (!empty($message['message_id'])) {
            $this->whatsapp->markReadWithTyping($message['message_id']);
        }

        Customer::getOrCreate($phone, $message['name'] ?? null);
        $session = Session::getOrCreate($phone);

        // Expire idle mid-conversation sessions (except while awaiting a payment webhook).
        if ($session['state'] !== 'AWAITING_TOPUP_CONFIRMATION' && Session::isExpired($session, 15)) {
            Session::reset($phone);
            $this->whatsapp->sendText($phone, $this->t($phone, 'session_expired'));
            $this->sendMainMenu($phone, $message['name'] ?? null);
            Session::updateState($phone, 'AWAITING_MAIN_MENU');

            return;
        }

        $this->route($phone, $session, $message);
    }

    private function extractMessage(array $payload): ?array
    {
        $value = $payload['entry'][0]['changes'][0]['value'] ?? null;

        if ($value === null || empty($value['messages'])) {
            return null;
        }

        // Only handle messages that arrived on OUR configured business number.
        // (Meta test numbers / other numbers on the same account are ignored.)
        $incomingPhoneId = $value['metadata']['phone_number_id'] ?? null;
        if ($incomingPhoneId !== null && $incomingPhoneId !== $this->config['whatsapp']['phone_number_id']) {
            return null;
        }

        $msg = $value['messages'][0];
        $from = $msg['from'];
        $name = $value['contacts'][0]['profile']['name'] ?? null;
        $messageId = $msg['id'] ?? null;

        if ($msg['type'] === 'text') {
            return ['from' => $from, 'name' => $name, 'message_id' => $messageId, 'type' => 'text', 'text' => trim($msg['text']['body'])];
        }

        if ($msg['type'] === 'interactive') {
            $interactive = $msg['interactive'];

            if ($interactive['type'] === 'list_reply') {
                return ['from' => $from, 'name' => $name, 'message_id' => $messageId, 'type' => 'selection', 'id' => $interactive['list_reply']['id'], 'title' => $interactive['list_reply']['title'] ?? null];
            }

            if ($interactive['type'] === 'button_reply') {
                return ['from' => $from, 'name' => $name, 'message_id' => $messageId, 'type' => 'selection', 'id' => $interactive['button_reply']['id'], 'title' => $interactive['button_reply']['title'] ?? null];
            }
        }

        return ['from' => $from, 'name' => $name, 'message_id' => $messageId, 'type' => 'unsupported'];
    }

    /**
     * Log the customer's inbound message into the chat-log table for the admin inbox.
     * Wrapped in try/catch so a logging failure never breaks message handling.
     */
    private function logIncomingMessage(string $phone, array $message): void
    {
        try {
            if ($message['type'] === 'text') {
                Message::log($phone, 'in', 'text', $message['text'], $message['message_id'] ?? null);

                return;
            }

            if ($message['type'] === 'selection') {
                $body = '[Kabonyeza] ' . ($message['title'] ?? $message['id']);
                Message::log($phone, 'in', 'selection', $body, $message['message_id'] ?? null);
            }
        } catch (\Throwable $e) {
            error_log('[WebhookController] Failed to log incoming message: ' . $e->getMessage());
        }
    }

    private function route(string $phone, array $session, array $message): void
    {
        // Referral capture: a new customer arriving via wa.me link sends "REF <code>".
        if ($message['type'] === 'text' && preg_match('/^REF\s+([A-Za-z0-9]+)/i', trim($message['text']), $m)) {
            $this->captureReferral($phone, $m[1]);
            $this->sendMainMenu($phone, $message['name'] ?? null);
            Session::updateState($phone, 'AWAITING_MAIN_MENU');

            return;
        }

        if ($message['type'] === 'text' && trim($message['text']) === '#') {
            $this->sendMainMenu($phone, $message['name'] ?? null);
            Session::updateState($phone, 'AWAITING_MAIN_MENU');

            return;
        }

        switch ($session['state']) {
            case 'IDLE':
                $this->sendMainMenu($phone, $message['name'] ?? null);
                Session::updateState($phone, 'AWAITING_MAIN_MENU');
                break;

            case 'AWAITING_MAIN_MENU':
                $this->handleMainMenuSelection($phone, $message);
                break;

            case 'AWAITING_LANGUAGE':
                $this->handleLanguageSelection($phone, $message);
                break;

            case 'AWAITING_PLATFORM':
                $this->handlePlatformSelection($phone, $message);
                break;

            case 'AWAITING_CATEGORY':
                $this->handleCategorySelection($phone, $session, $message);
                break;

            case 'AWAITING_SERVICE':
                $this->handleServiceSelection($phone, $session, $message);
                break;

            case 'AWAITING_QUANTITY_CHOICE':
                $this->handleQuantityChoice($phone, $session, $message);
                break;

            case 'AWAITING_QUANTITY':
                $this->handleQuantity($phone, $session, $message);
                break;

            case 'AWAITING_LINK':
                $this->handleLink($phone, $session, $message);
                break;

            case 'AWAITING_ORDER_CONFIRM':
                $this->handleOrderConfirm($phone, $session, $message);
                break;

            case 'AWAITING_TOPUP_DECISION':
                $this->handleTopupDecision($phone, $session, $message);
                break;

            case 'AWAITING_TOPUP_AMOUNT':
                $this->handleTopupAmount($phone, $session, $message);
                break;

            case 'AWAITING_PAYMENT_PHONE_CHOICE':
                $this->handlePaymentPhoneChoice($phone, $session, $message);
                break;

            case 'AWAITING_PAYMENT_PHONE_INPUT':
                $this->handlePaymentPhoneInput($phone, $session, $message);
                break;

            case 'AWAITING_TOPUP_CONFIRMATION':
                $this->handleTopupConfirmation($phone, $session, $message);
                break;

            case 'AWAITING_AI_CHAT':
                $this->handleAiChat($phone, $session, $message);
                break;

            default:
                Session::reset($phone);
                $this->whatsapp->sendText($phone, $this->t($phone, 'route_error'));
        }
    }

    private function sendMainMenu(string $phone, ?string $name = null): void
    {
        MainMenu::send($this->whatsapp, $phone, $name, $this->langFor($phone));
    }

    private const AI_HISTORY_LIMIT = 6;

    private function handleAiChat(string $phone, array $session, array $message): void
    {
        if (!AppSetting::isAiEnabled()) {
            $adminPhone = $this->config['admin']['phone_number'];
            $waNumber = preg_replace('/[^0-9]/', '', $adminPhone);
            $this->whatsapp->sendCtaUrl(
                $phone,
                $this->t($phone, 'ai_unavailable'),
                $this->t($phone, 'btn_contact_admin'),
                'https://wa.me/' . $waNumber
            );
            Session::reset($phone);

            return;
        }

        if ($message['type'] !== 'text' || trim($message['text']) === '') {
            $this->whatsapp->sendText($phone, $this->t($phone, 'ai_ask_text'));

            return;
        }

        $text = trim($message['text']);

        if (strcasecmp($text, 'admin') === 0) {
            $adminPhone = $this->config['admin']['phone_number'];
            $waNumber = preg_replace('/[^0-9]/', '', $adminPhone);
            $this->whatsapp->sendCtaUrl(
                $phone,
                $this->t($phone, 'ai_connect_admin'),
                $this->t($phone, 'btn_contact_admin'),
                'https://wa.me/' . $waNumber
            );
            Session::reset($phone);

            return;
        }

        $history = $session['temp_data']['ai_history'] ?? [];

        $result = $this->deepseek->reply($history, $text, $this->langFor($phone));

        if (!$result['success']) {
            $this->whatsapp->sendText($phone, $this->t($phone, 'ai_error'));

            return;
        }

        $this->whatsapp->sendText($phone, $result['reply']);

        $history[] = ['role' => 'user', 'content' => $text];
        $history[] = ['role' => 'assistant', 'content' => $result['reply']];
        $history = array_slice($history, -self::AI_HISTORY_LIMIT);

        Session::updateState($phone, 'AWAITING_AI_CHAT', ['ai_history' => $history]);
    }

    /**
     * When a customer types free text instead of tapping a button/list option,
     * answer their question with AI (one-off, no history) before repeating the
     * reminder — without touching session state, so their place in the order
     * flow (platform/service/quantity/etc.) is preserved.
     */
    private function respondWithAiFallback(string $phone, array $message, string $reminderText): void
    {
        if (!AppSetting::isAiEnabled() || $message['type'] !== 'text' || trim($message['text']) === '') {
            $this->whatsapp->sendText($phone, $reminderText);

            return;
        }

        $result = $this->deepseek->reply([], trim($message['text']), $this->langFor($phone));

        if ($result['success']) {
            $this->whatsapp->sendText($phone, $result['reply']);
        }

        $this->whatsapp->sendText($phone, $reminderText);
    }

    private function sendPaymentSentMenu(string $phone, string $intro): void
    {
        $lang = $this->langFor($phone);
        $body = BotLang::get($lang, 'payment_sent_menu', ['{intro}' => $intro]);

        $this->whatsapp->sendList($phone, $body, BotLang::get($lang, 'btn_open_menu'), BotLang::get($lang, 'menu_header'), MainMenu::rows($lang));
    }

    private function handleMainMenuSelection(string $phone, array $message): void
    {
        if ($message['type'] !== 'selection' || !str_starts_with($message['id'], 'main:')) {
            $this->respondWithAiFallback($phone, $message, $this->t($phone, 'not_understood_menu'));

            return;
        }

        $choice = substr($message['id'], strlen('main:'));

        switch ($choice) {
            case 'new_order':
                $this->sendPlatformMenu($phone, $message['name'] ?? null);
                Session::updateState($phone, 'AWAITING_PLATFORM');
                break;

            case 'topup':
                $this->whatsapp->sendText($phone, $this->t($phone, 'topup_prompt'));
                Session::updateState($phone, 'AWAITING_TOPUP_AMOUNT');
                break;

            case 'profile':
                $this->sendProfile($phone);
                Session::reset($phone);
                break;

            case 'referral':
                $this->sendReferralInfo($phone);
                Session::reset($phone);
                break;

            case 'track_order':
                $this->sendOrderTracking($phone);
                Session::reset($phone);
                break;

            case 'support':
                if (AppSetting::isAiEnabled()) {
                    $this->whatsapp->sendText($phone, $this->t($phone, 'support_ai_intro'));
                    Session::updateState($phone, 'AWAITING_AI_CHAT');
                } else {
                    $adminPhone = $this->config['admin']['phone_number'];
                    $waNumber = preg_replace('/[^0-9]/', '', $adminPhone);
                    $this->whatsapp->sendCtaUrl(
                        $phone,
                        $this->t($phone, 'support_cta'),
                        $this->t($phone, 'btn_contact_admin'),
                        'https://wa.me/' . $waNumber
                    );
                    Session::reset($phone);
                }
                break;

            case 'settings':
                $this->sendLanguageChooser($phone);
                Session::updateState($phone, 'AWAITING_LANGUAGE');
                break;

            case 'group':
                $this->whatsapp->sendText($phone, $this->t($phone, 'group_info', ['{url}' => $this->config['links']['group_url']]));
                Session::reset($phone);
                break;

            case 'website':
                $this->whatsapp->sendText($phone, $this->t($phone, 'website_info', ['{url}' => $this->config['links']['website_url']]));
                Session::reset($phone);
                break;

            default:
                $this->whatsapp->sendText($phone, $this->t($phone, 'not_understood_menu'));
        }
    }

    private function sendLanguageChooser(string $phone): void
    {
        $lang = $this->langFor($phone);

        $this->whatsapp->sendButtons(
            $phone,
            BotLang::get($lang, 'settings_choose_language'),
            [
                ['id' => 'lang:sw', 'title' => BotLang::get($lang, 'lang_name_sw')],
                ['id' => 'lang:en', 'title' => BotLang::get($lang, 'lang_name_en')],
            ]
        );
    }

    private function handleLanguageSelection(string $phone, array $message): void
    {
        if ($message['type'] !== 'selection' || !str_starts_with($message['id'], 'lang:')) {
            $this->respondWithAiFallback($phone, $message, $this->t($phone, 'settings_press_language'));

            return;
        }

        $chosen = BotLang::normalize(substr($message['id'], strlen('lang:')));

        $customer = Customer::getOrCreate($phone);
        Customer::setLang((int) $customer['id'], $chosen);
        $this->langCache[$phone] = $chosen;

        // Confirm in the newly chosen language, then reopen the menu in it.
        $this->whatsapp->sendText($phone, BotLang::get($chosen, 'language_changed'));
        $this->sendMainMenu($phone, $customer['name'] ?? null);
        Session::updateState($phone, 'AWAITING_MAIN_MENU');
    }

    private function sendProfile(string $phone): void
    {
        $customer = Customer::getOrCreate($phone);

        $this->whatsapp->sendText(
            $phone,
            $this->t($phone, 'profile', [
                '{name}' => $customer['name'] ?? $this->t($phone, 'default_customer_name'),
                '{balance}' => number_format((float) $customer['balance'], 0),
                '{spent}' => number_format((float) $customer['total_spent'], 0),
            ])
        );
    }

    private function captureReferral(string $phone, string $code): void
    {
        $customer = Customer::getOrCreate($phone);

        // Only brand-new customers (no deposit yet, not already referred) can be attributed.
        if ((int) $customer['first_deposit_done'] === 1 || $customer['referred_by'] !== null) {
            return;
        }

        $referrer = Customer::findByReferralCode($code);

        if ($referrer === null || (int) $referrer['id'] === (int) $customer['id']) {
            return;
        }

        Customer::setReferredBy((int) $customer['id'], (int) $referrer['id']);
    }

    private function sendReferralInfo(string $phone): void
    {
        $customer = Customer::getOrCreate($phone);
        $code = $customer['referral_code'];
        $percent = (float) $this->config['referral']['percent'];
        $botNumber = $this->config['whatsapp']['display_number'];

        $inviteText = rawurlencode($this->t($phone, 'referral_invite_text', ['{code}' => $code]));
        $waLink = "https://wa.me/{$botNumber}?text={$inviteText}";

        $referredCount = Customer::countReferrals((int) $customer['id']);

        $this->whatsapp->sendText(
            $phone,
            $this->t($phone, 'referral_info', [
                '{percent}' => $percent,
                '{code}' => $code,
                '{count}' => $referredCount,
                '{earnings}' => number_format((float) $customer['referral_earnings'], 0),
                '{link}' => $waLink,
            ])
        );
    }

    private function sendOrderTracking(string $phone): void
    {
        $orders = Order::byCustomer($phone);

        if ($orders === []) {
            $this->whatsapp->sendText($phone, $this->t($phone, 'track_no_orders'));

            return;
        }

        $lines = [$this->t($phone, 'track_header')];

        foreach (array_slice($orders, 0, 10) as $order) {
            $service = Service::find($order['service_id']);
            $serviceName = $service['name'] ?? $this->t($phone, 'default_service_name');

            // Show the provider's order number; fall back to our internal id
            // only if the order hasn't reached the provider yet.
            $orderNumber = !empty($order['provider_order_id']) ? $order['provider_order_id'] : $order['id'];

            $lines[] = $this->t($phone, 'track_line', [
                '{number}' => $orderNumber,
                '{service}' => $serviceName,
                '{qty}' => $order['quantity'],
                '{amount}' => number_format((float) $order['amount'], 0),
                '{status}' => $order['status'],
            ]);
        }

        $lines[] = $this->t($phone, 'track_footer');

        $this->whatsapp->sendText($phone, implode("\n\n", $lines));
    }

    private function handleTopupAmount(string $phone, array $session, array $message): void
    {
        $amount = (float) preg_replace('/[^0-9.]/', '', $message['text'] ?? '');

        if ($amount < 100) {
            $this->whatsapp->sendText($phone, $this->t($phone, 'topup_invalid'));

            return;
        }

        // Standalone wallet top-up: no pending order, amount is the full top-up amount.
        $this->sendPhoneChoice($phone, ['topup_amount' => $amount]);
    }

    /**
     * Choose gateway automatically by amount.
     * For now: Snippe handles ALL amounts (HarakaPay account not yet API-authorized,
     * ZenoPay disabled). Switch back to HarakaPay for <1000 once its API is enabled:
     *   return $amount < 1000 ? 'harakapay' : 'snippe';
     */
    private function gatewayForAmount(float $amount): string
    {
        return 'snippe';
    }

    /**
     * Show the payment phone choice (saved/last phone shown as a button, or enter another, or cancel).
     * $tempData must carry either 'topup_amount' (standalone) or the full order data + 'amount'.
     */
    private function sendPhoneChoice(string $phone, array $tempData): void
    {
        $customer = Customer::getOrCreate($phone);

        $savedPhone = !empty($customer['last_payment_phone'])
            ? $this->localPhone($customer['last_payment_phone'])
            : $this->localPhone($phone);

        $tempData['payment_phone_suggestion'] = $savedPhone;

        $amount = $tempData['topup_amount'] ?? ($tempData['amount'] ?? 0);

        $this->whatsapp->sendButtons(
            $phone,
            $this->t($phone, 'payment_method', ['{amount}' => number_format((float) $amount, 0)]),
            [
                ['id' => 'pay_phone:saved', 'title' => '📱 ' . $savedPhone],
                ['id' => 'pay_phone:other', 'title' => $this->t($phone, 'btn_other_number')],
                ['id' => 'pay_phone:cancel', 'title' => $this->t($phone, 'btn_cancel')],
            ]
        );

        Session::updateState($phone, 'AWAITING_PAYMENT_PHONE_CHOICE', $tempData);
    }

    private function localPhone(string $whatsappPhone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $whatsappPhone);

        if (str_starts_with($digits, '255')) {
            return '0' . substr($digits, 3);
        }

        return $digits;
    }

    private function handlePaymentPhoneChoice(string $phone, array $session, array $message): void
    {
        if ($message['type'] !== 'selection' || !str_starts_with($message['id'], 'pay_phone:')) {
            $this->respondWithAiFallback($phone, $message, $this->t($phone, 'press_button_reminder'));

            return;
        }

        $choice = substr($message['id'], strlen('pay_phone:'));

        if ($choice === 'cancel') {
            $this->whatsapp->sendText($phone, $this->t($phone, 'cancelled'));
            Session::reset($phone);

            return;
        }

        if ($choice === 'other') {
            $this->whatsapp->sendText($phone, $this->t($phone, 'payment_phone_prompt'));
            Session::updateState($phone, 'AWAITING_PAYMENT_PHONE_INPUT', $session['temp_data']);

            return;
        }

        // 'saved' — use the suggested phone
        $this->initiatePayment($phone, $session['temp_data'], $session['temp_data']['payment_phone_suggestion']);
    }

    private function handlePaymentPhoneInput(string $phone, array $session, array $message): void
    {
        $rawPhone = preg_replace('/[^0-9]/', '', $message['text'] ?? '');

        if (str_starts_with($rawPhone, '0')) {
            $rawPhone = '255' . substr($rawPhone, 1);
        }

        if (strlen($rawPhone) < 11) {
            $this->whatsapp->sendText($phone, $this->t($phone, 'payment_phone_invalid'));

            return;
        }

        $this->initiatePayment($phone, $session['temp_data'], $rawPhone);
    }

    /**
     * While awaiting the payment webhook: a customer message here means they're
     * probably stuck (no USSD arrived, or they gave up). Offer to resend the USSD
     * (re-picking a phone first) or cancel — plus the usual "#" back-to-menu.
     */
    private function handleTopupConfirmation(string $phone, array $session, array $message): void
    {
        if ($message['type'] === 'selection' && str_starts_with($message['id'], 'topup_wait:')) {
            $choice = substr($message['id'], strlen('topup_wait:'));

            if ($choice === 'cancel') {
                $orderId = $session['temp_data']['order_id'] ?? null;
                if ($orderId !== null) {
                    Order::updateStatus($orderId, 'cancelled');
                    Payment::expirePendingForOrder($orderId);
                }

                $this->whatsapp->sendText($phone, $this->t($phone, 'payment_cancelled'));
                Session::reset($phone);

                return;
            }

            if ($choice === 'resend') {
                // Re-pick the payment phone, then re-initiate against the SAME
                // pending order (temp_data still carries its order_id) — this
                // does not create a second order. The stale pending payment
                // record is left to expire on its own (check_orders cron).
                $this->sendPhoneChoice($phone, $session['temp_data']);

                return;
            }
        }

        // Any other message: explain what's happening and offer the two actions.
        $this->whatsapp->sendButtons(
            $phone,
            $this->t($phone, 'topup_choose_action'),
            [
                ['id' => 'topup_wait:resend', 'title' => $this->t($phone, 'btn_resend_ussd')],
                ['id' => 'topup_wait:cancel', 'title' => $this->t($phone, 'btn_cancel_payment')],
            ]
        );
    }

    /**
     * Initiate a mobile-money payment. $rawPhone may be local (07..) or international (2557..).
     * Gateway is auto-selected by amount. On success, sets a state that lets the webhook
     * complete the pending order (if any) or just credit the wallet.
     */
    /**
     * Tigo (Yas) lines require a minimum mobile-money collection of 1000 TZS;
     * other Tanzanian networks (Vodacom, Airtel, Halotel) accept from 200 TZS.
     */
    private const TIGO_PREFIXES = ['071', '065', '067'];
    private const TIGO_MIN_AMOUNT = 1000;
    private const DEFAULT_MIN_AMOUNT = 200;

    private function minAmountForPhone(string $rawPhone): int
    {
        $local = str_starts_with($rawPhone, '255') ? '0' . substr($rawPhone, 3) : $rawPhone;
        $prefix = substr($local, 0, 3);

        return in_array($prefix, self::TIGO_PREFIXES, true) ? self::TIGO_MIN_AMOUNT : self::DEFAULT_MIN_AMOUNT;
    }

    private function initiatePayment(string $phone, array $data, string $payPhone): void
    {
        $rawPhone = preg_replace('/[^0-9]/', '', $payPhone);
        if (str_starts_with($rawPhone, '0')) {
            $rawPhone = '255' . substr($rawPhone, 1);
        }

        $customer = Customer::getOrCreate($phone);
        $customerName = trim($customer['name'] ?? '') ?: $rawPhone;

        $isStandalone = isset($data['topup_amount']);
        $amount = $isStandalone
            ? (float) $data['topup_amount']
            : round((float) $data['amount'] - (float) $customer['balance'], 0);

        $minAmount = $this->minAmountForPhone($rawPhone);
        if ($amount < $minAmount) {
            $this->whatsapp->sendText($phone, $this->t($phone, 'min_amount', ['{min}' => number_format($minAmount, 0)]));
            Session::reset($phone);

            return;
        }

        $gateway = $this->gatewayForAmount($amount);
        $chargeAmount = PaymentGateway::grossUpAmount($amount, $gateway);
        $localRef = 'KZPTOP' . $customer['id'] . time();
        $webhookBase = rtrim($this->config['app']['url'], '/');

        // Order top-up: the order is recorded now, before the gateway is even
        // called, with payment_status still 'pending'. The webhook completes
        // THIS row when payment confirms — it no longer depends on the
        // customer's conversation state, which can be reset by a stray "#"
        // or a session timeout while the USSD prompt is still pending. Without
        // this, a late webhook credited the wallet but silently dropped the
        // order the customer paid for.
        //
        // A "resend USSD" re-enters this method with the same order_id already
        // in $data (see handleTopupConfirmation) — that existing pending order
        // is reused rather than creating a duplicate on every resend.
        $orderId = $data['order_id'] ?? null;
        if (!$isStandalone && $orderId === null) {
            $orderId = Order::create([
                'customer_phone' => $phone,
                'service_id' => $data['service_id'],
                'quantity' => $data['quantity'],
                'link' => $data['link'],
                'amount' => $data['amount'],
            ]);
        }

        $paymentId = Payment::create([
            'type' => 'wallet_topup',
            'order_id' => $orderId,
            'customer_id' => $customer['id'],
            'gateway' => $gateway,
            'transaction_ref' => $localRef,
            'amount' => $amount,
        ]);

        $result = match ($gateway) {
            'harakapay' => $this->harakapay->initiate([
                'phone' => $rawPhone,
                'amount' => $chargeAmount,
                'description' => 'KuzaPanel Wallet Topup - ' . $customerName . ' WhatsApp',
                'webhook_url' => $webhookBase . '/webhooks/harakapay.php',
            ]),
            'zenopay' => $this->zenopay->initiate([
                'order_id' => $localRef,
                'buyer_email' => 'customer@kuzapanel.local',
                'buyer_name' => $customerName . ' WhatsApp',
                'buyer_phone' => $rawPhone,
                'amount' => $chargeAmount,
                'webhook_url' => $webhookBase . '/webhooks/zenopay.php',
            ]),
            default => $this->snippe->initiate([
                'order_id' => $localRef,
                'phone' => $rawPhone,
                'amount' => $chargeAmount,
                'firstname' => $customerName,
                'lastname' => 'WhatsApp',
                'email' => 'customer@kuzapanel.local',
                'webhook_url' => $webhookBase . '/webhooks/snippe.php',
            ]),
        };

        if (!$result['success']) {
            if ($orderId !== null) {
                Order::updateStatus($orderId, 'cancelled');
            }

            $this->whatsapp->sendText($phone, $this->t($phone, 'payment_failed', ['{message}' => $result['message']]));
            Session::reset($phone);

            return;
        }

        $reference = $localRef;
        if ($gateway === 'snippe' && isset($result['reference'])) {
            Payment::setTransactionRef($paymentId, $result['reference']);
            $reference = $result['reference'];
        } elseif ($gateway === 'harakapay' && isset($result['order_id'])) {
            Payment::setTransactionRef($paymentId, $result['order_id']);
            $reference = $result['order_id'];
        }

        Customer::setLastPaymentPhone((int) $customer['id'], $rawPhone);

        if ($isStandalone) {
            $this->sendPaymentSentMenu(
                $phone,
                $this->t($phone, 'topup_sent', [
                    '{charge}' => number_format($chargeAmount, 0),
                    '{reference}' => $reference,
                    '{amount}' => number_format($amount, 0),
                ])
            );

            Session::updateState($phone, 'AWAITING_MAIN_MENU');

            return;
        }

        // Order top-up: keep order data so the webhook can complete it after payment.
        // Send with the resend/cancel buttons up front, so a customer who never
        // gets the USSD prompt can act immediately without messaging us first.
        $this->whatsapp->sendButtons(
            $phone,
            $this->t($phone, 'order_payment_sent', [
                '{charge}' => number_format($chargeAmount, 0),
                '{phone}' => $this->localPhone($rawPhone),
            ]),
            [
                ['id' => 'topup_wait:resend', 'title' => $this->t($phone, 'btn_resend_ussd')],
                ['id' => 'topup_wait:cancel', 'title' => $this->t($phone, 'btn_cancel_payment')],
            ]
        );

        Session::updateState($phone, 'AWAITING_TOPUP_CONFIRMATION', $data + ['order_id' => $orderId]);
    }

    private const PLATFORM_INFO = [
        'Instagram' => ['emoji' => '📸', 'description' => 'Followers, Likes, Views'],
        'TikTok' => ['emoji' => '🎵', 'description' => 'Followers, Likes, Views'],
        'YouTube' => ['emoji' => '▶️', 'description' => 'Subscribe, Views, Likes'],
        'Facebook' => ['emoji' => '👍', 'description' => 'Followers, Likes, Views'],
        'Twitter' => ['emoji' => '🐦', 'description' => 'Followers, Likes, Views'],
        'Telegram' => ['emoji' => '✈️', 'description' => 'Members, Views'],
    ];

    private function platformEmoji(string $platform): string
    {
        return self::PLATFORM_INFO[$platform]['emoji'] ?? '📱';
    }

    private function platformDescription(string $platform, string $phone): string
    {
        return self::PLATFORM_INFO[$platform]['description'] ?? $this->t($phone, 'platform_generic_desc');
    }

    private function sendPlatformMenu(string $phone, ?string $name = null): void
    {
        $platforms = Service::activePlatforms();

        if ($platforms === []) {
            $this->whatsapp->sendText($phone, $this->t($phone, 'no_services'));

            return;
        }

        $greetingName = $name !== null && $name !== '' ? $name : $this->t($phone, 'default_customer_name');

        $rows = array_map(
            fn (string $platform) => [
                'id' => 'platform:' . $platform,
                'title' => $this->platformEmoji($platform) . ' ' . $platform,
                'description' => $this->platformDescription($platform, $phone),
            ],
            array_slice($platforms, 0, 10)
        );

        $this->whatsapp->sendList(
            $phone,
            $this->t($phone, 'platform_menu', ['{name}' => $greetingName]),
            $this->t($phone, 'btn_platforms'),
            'Platforms',
            $rows
        );
    }

    private const MAX_LIST_ROWS = 10;

    private function handlePlatformSelection(string $phone, array $message): void
    {
        if ($message['type'] !== 'selection' || !str_starts_with($message['id'], 'platform:')) {
            $this->respondWithAiFallback($phone, $message, $this->t($phone, 'choose_platform'));

            return;
        }

        $platform = substr($message['id'], strlen('platform:'));
        $services = Service::activeByPlatform($platform);

        if ($services === []) {
            $this->whatsapp->sendText($phone, $this->t($phone, 'no_services_platform'));
            Session::reset($phone);

            return;
        }

        $categories = Service::activeCategoriesForPlatform($platform);
        $hasUncategorised = Service::hasUncategorisedActive($platform);
        $bucketCount = count($categories) + ($hasUncategorised ? 1 : 0);

        // Only worth a category step when it actually narrows things down and
        // still fits in one WhatsApp list (max 10 rows).
        if (count($categories) > 1 && $bucketCount <= self::MAX_LIST_ROWS) {
            $this->sendCategoryMenu($phone, $platform, $categories, $hasUncategorised);
            Session::updateState($phone, 'AWAITING_CATEGORY', ['platform' => $platform]);

            return;
        }

        $this->sendServiceMenu($phone, $platform, null, $services, $message['name'] ?? null);
        Session::updateState($phone, 'AWAITING_SERVICE', ['platform' => $platform]);
    }

    private function sendCategoryMenu(string $phone, string $platform, array $categories, bool $hasUncategorised): void
    {
        $rows = array_map(
            fn (string $category) => [
                'id' => 'category:' . rawurlencode($category),
                'title' => mb_substr($category, 0, 24),
            ],
            $categories
        );

        if ($hasUncategorised) {
            $rows[] = ['id' => 'category:', 'title' => $this->t($phone, 'category_other')];
        }

        $this->whatsapp->sendList(
            $phone,
            $this->t($phone, 'choose_category', ['{platform}' => $platform]),
            $this->t($phone, 'btn_categories'),
            $this->t($phone, 'categories_header'),
            $rows
        );
    }

    private function handleCategorySelection(string $phone, array $session, array $message): void
    {
        if ($message['type'] !== 'selection' || !str_starts_with($message['id'], 'category:')) {
            $this->respondWithAiFallback($phone, $message, $this->t($phone, 'choose_category_reminder'));

            return;
        }

        $platform = $session['temp_data']['platform'];
        $raw = substr($message['id'], strlen('category:'));
        $category = $raw === '' ? null : rawurldecode($raw);

        $services = Service::activeByPlatformAndCategory($platform, $category);

        if ($services === []) {
            $this->whatsapp->sendText($phone, $this->t($phone, 'no_services_platform'));
            Session::reset($phone);

            return;
        }

        $this->sendServiceMenu($phone, $platform, $category, $services, $message['name'] ?? null);
        Session::updateState($phone, 'AWAITING_SERVICE', ['platform' => $platform, 'category' => $category]);
    }

    private function sendServiceMenu(string $phone, string $platform, ?string $category, array $services, ?string $name): void
    {
        $greetingName = $name !== null && $name !== '' ? $name : $this->t($phone, 'default_customer_name');

        $rows = array_map(
            fn (array $service) => [
                'id' => 'service:' . $service['id'],
                'title' => mb_substr($service['name'], 0, 24),
                'description' => $this->t($phone, 'price_per_1000', ['{price}' => number_format((float) $service['my_price'], 0)]),
            ],
            array_slice($services, 0, self::MAX_LIST_ROWS)
        );

        $heading = $category !== null ? $platform . ' · ' . $category : $platform;

        $this->whatsapp->sendList(
            $phone,
            $this->t($phone, 'service_menu', [
                '{platform_upper}' => mb_strtoupper($heading),
                '{platform}' => $heading,
                '{name}' => $greetingName,
            ]),
            $this->t($phone, 'btn_choose_service'),
            'Services',
            $rows
        );
    }

    private function handleServiceSelection(string $phone, array $session, array $message): void
    {
        if ($message['type'] !== 'selection' || !str_starts_with($message['id'], 'service:')) {
            $this->respondWithAiFallback($phone, $message, $this->t($phone, 'choose_service_reminder'));

            return;
        }

        $serviceId = (int) substr($message['id'], strlen('service:'));
        $service = Service::find($serviceId);

        if ($service === null || $service['status'] !== 'active') {
            $this->whatsapp->sendText($phone, $this->t($phone, 'service_unavailable'));
            Session::reset($phone);

            return;
        }

        $this->sendQuantityChoices($phone, $service);

        Session::updateState($phone, 'AWAITING_QUANTITY_CHOICE', $session['temp_data'] + ['service_id' => $serviceId]);
    }

    private const QUANTITY_PACKAGES = [200, 500, 1000, 2000, 5000, 10000, 50000, 100000];

    private function sendQuantityChoices(string $phone, array $service): void
    {
        $packages = array_filter(
            self::QUANTITY_PACKAGES,
            fn (int $qty) => $qty >= $service['min_quantity'] && $qty <= $service['max_quantity']
        );

        $unitLabel = $service['unit_label'];

        $rows = array_map(
            fn (int $qty) => [
                'id' => 'qty_package:' . $qty,
                'title' => ($qty >= 1000 ? number_format($qty / 1000, 0) . 'K' : (string) $qty) . ' ' . $unitLabel,
                'description' => $this->t($phone, 'qty_total', ['{total}' => number_format(($service['my_price'] / 1000) * $qty, 0)]),
            ],
            $packages
        );

        $rows[] = [
            'id' => 'qty_package:custom',
            'title' => $this->t($phone, 'qty_custom_title'),
            'description' => $this->t($phone, 'qty_custom_desc', ['{min}' => $service['min_quantity'], '{max}' => $service['max_quantity']]),
        ];

        $this->whatsapp->sendList(
            $phone,
            $this->t($phone, 'qty_menu', ['{service}' => $service['name']]),
            $this->t($phone, 'btn_packages'),
            'Vifurushi',
            $rows
        );
    }

    private function handleQuantityChoice(string $phone, array $session, array $message): void
    {
        if ($message['type'] !== 'selection' || !str_starts_with($message['id'], 'qty_package:')) {
            $this->respondWithAiFallback($phone, $message, $this->t($phone, 'qty_package_reminder'));

            return;
        }

        $choice = substr($message['id'], strlen('qty_package:'));

        if ($choice === 'custom') {
            $service = Service::find($session['temp_data']['service_id']);

            $this->whatsapp->sendText(
                $phone,
                $this->t($phone, 'qty_custom_prompt', ['{min}' => $service['min_quantity'], '{max}' => $service['max_quantity']])
            );

            Session::updateState($phone, 'AWAITING_QUANTITY', $session['temp_data']);

            return;
        }

        $quantity = (int) $choice;
        $service = Service::find($session['temp_data']['service_id']);

        if ($service === null) {
            $this->whatsapp->sendText($phone, $this->t($phone, 'generic_error'));
            Session::reset($phone);

            return;
        }

        if ($quantity < $service['min_quantity'] || $quantity > $service['max_quantity']) {
            $this->sendQuantityChoices($phone, $service);

            return;
        }

        $this->sendLinkRequest($phone, $service, $quantity);

        Session::updateState($phone, 'AWAITING_LINK', $session['temp_data'] + ['quantity' => $quantity]);
    }

    private function handleQuantity(string $phone, array $session, array $message): void
    {
        $quantity = (int) ($message['text'] ?? 0);
        $service = Service::find($session['temp_data']['service_id']);

        if ($service === null) {
            $this->whatsapp->sendText($phone, $this->t($phone, 'generic_error'));
            Session::reset($phone);

            return;
        }

        if ($quantity < $service['min_quantity'] || $quantity > $service['max_quantity']) {
            $this->whatsapp->sendText(
                $phone,
                $this->t($phone, 'qty_invalid', ['{min}' => $service['min_quantity'], '{max}' => $service['max_quantity']])
            );

            return;
        }

        $this->sendLinkRequest($phone, $service, $quantity);

        Session::updateState($phone, 'AWAITING_LINK', $session['temp_data'] + ['quantity' => $quantity]);
    }

    private const PROFILE_UNIT_LABELS = ['Followers', 'Subscribers'];

    /**
     * Language-neutral link metadata (example URL + illustration image) per
     * platform + type. The step-by-step instructions are translated and pulled
     * from BotLang by key (link_steps_{platform}_{type}).
     */
    private const LINK_META = [
        'Instagram' => [
            'profile' => ['example' => 'https://www.instagram.com/yourname', 'image' => 'instagram_profile.jpg'],
            'post' => ['example' => 'https://www.instagram.com/p/kuza1234', 'image' => 'instagram_post.jpg'],
        ],
        'TikTok' => [
            'profile' => ['example' => 'https://www.tiktok.com/@yourname', 'image' => 'tiktok_profile.jpg'],
            'post' => ['example' => 'https://www.tiktok.com/@yourname/video/123456', 'image' => 'tiktok_post.jpg'],
        ],
        'Facebook' => [
            'profile' => ['example' => 'https://www.facebook.com/yourname', 'image' => 'facebook_profile.jpg'],
            'post' => ['example' => 'https://www.facebook.com/share/123456', 'image' => 'facebook_post.jpg'],
        ],
        'YouTube' => [
            'profile' => ['example' => 'https://www.youtube.com/@yourchannel', 'image' => 'youtube_profile.jpg'],
            'post' => ['example' => 'https://www.youtube.com/watch?v=abc123', 'image' => 'youtube_post.jpg'],
        ],
    ];

    private function getLinkInstructions(string $phone, string $platform, string $unitLabel): array
    {
        $lang = $this->langFor($phone);
        $type = in_array($unitLabel, self::PROFILE_UNIT_LABELS, true) ? 'profile' : 'post';

        $platformKey = null;
        foreach (array_keys(self::LINK_META) as $knownPlatform) {
            if (strcasecmp($knownPlatform, $platform) === 0) {
                $platformKey = $knownPlatform;
                break;
            }
        }

        if ($platformKey === null) {
            return [
                'steps' => BotLang::get($lang, 'link_steps_generic', ['{platform}' => $platform]),
                'example' => 'https://example.com/yourname',
                'image' => null,
            ];
        }

        $meta = self::LINK_META[$platformKey][$type];

        return [
            'steps' => BotLang::get($lang, 'link_steps_' . strtolower($platformKey) . '_' . $type),
            'example' => $meta['example'],
            'image' => $meta['image'],
        ];
    }

    private function sendLinkRequest(string $phone, array $service, int $quantity): void
    {
        $customer = Customer::getOrCreate($phone);
        $name = $customer['name'] ?? $this->t($phone, 'default_customer_name');
        $qtyLabel = ($quantity >= 1000 ? number_format($quantity / 1000, 0) . 'K' : (string) $quantity) . ' ' . $service['unit_label'];

        $instructions = $this->getLinkInstructions($phone, $service['platform'], $service['unit_label']);
        $steps = !empty($service['link_instructions']) ? $service['link_instructions'] : $instructions['steps'];

        $imageUrl = !empty($service['link_instructions_image'])
            ? $service['link_instructions_image']
            : ($instructions['image'] !== null
                ? rtrim($this->config['app']['url'], '/') . '/assets/instructions/' . $instructions['image']
                : null);

        $hasImage = $imageUrl !== null;

        $message = $this->t($phone, 'link_request', [
            '{name}' => $name,
            '{qty}' => $qtyLabel,
            '{image_note}' => $hasImage ? $this->t($phone, 'link_see_image') : '',
            '{steps}' => $steps,
            '{example}' => $instructions['example'],
        ]);

        if ($hasImage) {
            $this->whatsapp->sendImage($phone, $imageUrl, $message);
        } else {
            $this->whatsapp->sendText($phone, $message);
        }
    }

    private function handleLink(string $phone, array $session, array $message): void
    {
        $link = trim($message['text'] ?? '');

        if (!filter_var($link, FILTER_VALIDATE_URL)) {
            $this->whatsapp->sendText($phone, $this->t($phone, 'link_invalid'));

            return;
        }

        if (!empty($message['message_id'])) {
            $this->whatsapp->sendReaction($phone, $message['message_id'], '✅');
        }

        $service = Service::find($session['temp_data']['service_id']);
        $quantity = $session['temp_data']['quantity'];
        $amount = round(($service['my_price'] / 1000) * $quantity, 2);

        $tempData = $session['temp_data'] + ['link' => $link, 'amount' => $amount];

        $this->whatsapp->sendButtons(
            $phone,
            $this->t($phone, 'order_confirm', [
                '{platform}' => $tempData['platform'],
                '{service}' => $service['name'],
                '{link}' => $link,
                '{qty}' => $quantity,
                '{amount}' => number_format($amount, 0),
            ]),
            [
                ['id' => 'order_confirm:yes', 'title' => $this->t($phone, 'btn_yes_continue')],
                ['id' => 'order_confirm:no', 'title' => $this->t($phone, 'btn_no_cancel')],
            ]
        );

        Session::updateState($phone, 'AWAITING_ORDER_CONFIRM', $tempData);
    }

    private function handleOrderConfirm(string $phone, array $session, array $message): void
    {
        if ($message['type'] !== 'selection' || !str_starts_with($message['id'], 'order_confirm:')) {
            $this->respondWithAiFallback($phone, $message, $this->t($phone, 'press_button_reminder'));

            return;
        }

        $choice = substr($message['id'], strlen('order_confirm:'));

        if ($choice === 'no') {
            $this->whatsapp->sendText($phone, $this->t($phone, 'order_cancelled'));
            Session::reset($phone);

            return;
        }

        $data = $session['temp_data'];
        $customer = Customer::getOrCreate($phone);

        if ((float) $customer['balance'] >= (float) $data['amount']) {
            $this->completeOrderFromWallet($phone, $customer, $data);

            return;
        }

        $shortfall = round((float) $data['amount'] - (float) $customer['balance'], 0);

        $this->whatsapp->sendButtons(
            $phone,
            $this->t($phone, 'low_balance', ['{shortfall}' => number_format($shortfall, 0)]),
            [
                ['id' => 'topup_decision:yes', 'title' => $this->t($phone, 'btn_yes_pay')],
                ['id' => 'topup_decision:no', 'title' => $this->t($phone, 'btn_no_cancel_plain')],
            ]
        );

        Session::updateState($phone, 'AWAITING_TOPUP_DECISION', $data);
    }

    private function completeOrderFromWallet(string $phone, array $customer, array $data): void
    {
        if (!Customer::debit((int) $customer['id'], (float) $data['amount'])) {
            $this->whatsapp->sendText($phone, $this->t($phone, 'wallet_debit_error'));
            Session::reset($phone);

            return;
        }

        $orderId = Order::create([
            'customer_phone' => $phone,
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

        // Show the provider's order number; fall back to our internal id only
        // if the order hasn't reached the provider yet (so it's never blank).
        $orderNumber = !empty($order['provider_order_id']) ? $order['provider_order_id'] : $orderId;
        $providerOrderLine = $this->t($phone, 'order_provider_line', ['{provider}' => $orderNumber]);

        $this->whatsapp->sendText(
            $phone,
            $this->t($phone, 'order_received', [
                '{provider_line}' => $providerOrderLine,
            ])
        );

        Session::reset($phone);
        MainMenu::send($this->whatsapp, $phone, $customer['name'] ?? null, $this->langFor($phone));
    }

    private function handleTopupDecision(string $phone, array $session, array $message): void
    {
        if ($message['type'] !== 'selection' || !str_starts_with($message['id'], 'topup_decision:')) {
            $this->respondWithAiFallback($phone, $message, $this->t($phone, 'press_button_reminder'));

            return;
        }

        $choice = substr($message['id'], strlen('topup_decision:'));

        if ($choice === 'no') {
            $this->whatsapp->sendText($phone, $this->t($phone, 'order_cancelled'));
            Session::reset($phone);

            return;
        }

        // Order top-up: carry the full order data; sendPhoneChoice computes the shortfall.
        $this->sendPhoneChoice($phone, $session['temp_data']);
    }
}

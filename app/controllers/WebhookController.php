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
require_once __DIR__ . '/../services/OrderFulfillment.php';
require_once __DIR__ . '/../services/AdminNotifier.php';
require_once __DIR__ . '/../helpers/MainMenu.php';

class WebhookController
{
    private WhatsAppClient $whatsapp;
    private ZenoPayClient $zenopay;
    private SnippeClient $snippe;
    private HarakaPayClient $harakapay;
    private DeepSeekClient $deepseek;
    private AdminNotifier $admin;
    private array $config;

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

        // Mark the incoming message as read (blue ticks) and show "typing…".
        if (!empty($message['message_id'])) {
            $this->whatsapp->markReadWithTyping($message['message_id']);
        }

        Customer::getOrCreate($phone, $message['name'] ?? null);
        $session = Session::getOrCreate($phone);

        // Expire idle mid-conversation sessions (except while awaiting a payment webhook).
        if ($session['state'] !== 'AWAITING_TOPUP_CONFIRMATION' && Session::isExpired($session, 15)) {
            Session::reset($phone);
            $this->whatsapp->sendText(
                $phone,
                "⏰ Muda wa mazungumzo yako umepita kwa kukaa kimya.\n\n" .
                "Hakuna wasiwasi — tunaanza upya! 👇"
            );
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
                return ['from' => $from, 'name' => $name, 'message_id' => $messageId, 'type' => 'selection', 'id' => $interactive['list_reply']['id']];
            }

            if ($interactive['type'] === 'button_reply') {
                return ['from' => $from, 'name' => $name, 'message_id' => $messageId, 'type' => 'selection', 'id' => $interactive['button_reply']['id']];
            }
        }

        return ['from' => $from, 'name' => $name, 'message_id' => $messageId, 'type' => 'unsupported'];
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

            case 'AWAITING_PLATFORM':
                $this->handlePlatformSelection($phone, $message);
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
                $this->whatsapp->sendText(
                    $phone,
                    'Tunasubiri uthibitisho wa malipo yako. Tutakutumia ujumbe mara malipo yatakapokamilika.'
                );
                break;

            case 'AWAITING_AI_CHAT':
                $this->handleAiChat($phone, $session, $message);
                break;

            default:
                Session::reset($phone);
                $this->whatsapp->sendText($phone, "Samahani, hitilafu imetokea. Tuma '#' kuanza upya.");
        }
    }

    private function sendMainMenu(string $phone, ?string $name = null): void
    {
        MainMenu::send($this->whatsapp, $phone, $name);
    }

    private const AI_HISTORY_LIMIT = 6;

    private function handleAiChat(string $phone, array $session, array $message): void
    {
        if ($message['type'] !== 'text' || trim($message['text']) === '') {
            $this->whatsapp->sendText($phone, 'Tafadhali andika swali lako kwa maandishi.');

            return;
        }

        $text = trim($message['text']);

        if (strcasecmp($text, 'admin') === 0) {
            $adminPhone = $this->config['admin']['phone_number'];
            $waNumber = preg_replace('/[^0-9]/', '', $adminPhone);
            $this->whatsapp->sendCtaUrl(
                $phone,
                "Sawa, bonyeza kitufe hapa chini kuongea na admin wetu moja kwa moja.",
                'Wasiliana Na Admin',
                'https://wa.me/' . $waNumber
            );
            Session::reset($phone);

            return;
        }

        $history = $session['temp_data']['ai_history'] ?? [];

        $result = $this->deepseek->reply($history, $text);

        if (!$result['success']) {
            $this->whatsapp->sendText(
                $phone,
                "⚠️ Samahani, sijaweza kupata jibu kwa sasa.\n\n" .
                "Tuma neno *admin* kuongea na binadamu, au \"#\" kurudi menu kuu."
            );

            return;
        }

        $this->whatsapp->sendText($phone, $result['reply']);

        $history[] = ['role' => 'user', 'content' => $text];
        $history[] = ['role' => 'assistant', 'content' => $result['reply']];
        $history = array_slice($history, -self::AI_HISTORY_LIMIT);

        Session::updateState($phone, 'AWAITING_AI_CHAT', ['ai_history' => $history]);
    }

    private function sendPaymentSentMenu(string $phone, string $intro): void
    {
        $body = $intro . "\n\n" .
            "👇 Chagua huduma kuanza:";

        $this->whatsapp->sendList($phone, $body, 'Fungua Menyu', 'Main Menu', MainMenu::rows());
    }

    private function handleMainMenuSelection(string $phone, array $message): void
    {
        if ($message['type'] !== 'selection' || !str_starts_with($message['id'], 'main:')) {
            $this->whatsapp->sendText(
                $phone,
                "🤔 Samahani, sijaelewa.\n\n" .
                "Tafadhali bonyeza *Fungua Menyu* hapo juu kuchagua, au tuma *#* kuona menu kuu."
            );

            return;
        }

        $choice = substr($message['id'], strlen('main:'));

        switch ($choice) {
            case 'new_order':
                $this->sendPlatformMenu($phone, $message['name'] ?? null);
                Session::updateState($phone, 'AWAITING_PLATFORM');
                break;

            case 'topup':
                $this->whatsapp->sendText(
                    $phone,
                    "💰 *WEKA PESA*\n\n" .
                    "Tafadhali andika kiasi unachotaka kuweka kwenye salio lako (TZS).\n\n" .
                    "📌 Mfano: 5000\n\n" .
                    "Tuma \"#\" kurudi menu kuu."
                );
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
                $this->whatsapp->sendText(
                    $phone,
                    "🎧 *Huduma Kwa Wateja*\n\n" .
                    "Habari! Mimi ni msaidizi wa AI wa KuzaPanel. Niulize swali lolote kuhusu huduma zetu, malipo, au jinsi ya kuagiza.\n\n" .
                    "💡 Ukihitaji kuongea na admin moja kwa moja, tuma neno *admin*.\n" .
                    "_Tuma \"#\" wakati wowote kurudi menu kuu._"
                );
                Session::updateState($phone, 'AWAITING_AI_CHAT');
                break;

            case 'settings':
                $this->whatsapp->sendText($phone, '⚙️ Mipangilio (lugha na sarafu) inakuja hivi karibuni. Tuma "#" kurudi.');
                Session::reset($phone);
                break;

            case 'group':
                $this->whatsapp->sendText(
                    $phone,
                    "👥 *KuzaPanel Group*\n\nJiunge na group letu kupata taarifa za huduma zetu na maelekezo:\n{$this->config['links']['group_url']}"
                );
                Session::reset($phone);
                break;

            case 'website':
                $this->whatsapp->sendText(
                    $phone,
                    "🌐 *KuzaPanel Website*\n\nKupata huduma nyingi zaidi na kwa haraka na salama, tembelea tovuti yetu:\n{$this->config['links']['website_url']}"
                );
                Session::reset($phone);
                break;

            default:
                $this->whatsapp->sendText(
                $phone,
                "🤔 Samahani, sijaelewa.\n\n" .
                "Tafadhali bonyeza *Fungua Menyu* hapo juu kuchagua, au tuma *#* kuona menu kuu."
            );
        }
    }

    private function sendProfile(string $phone): void
    {
        $customer = Customer::getOrCreate($phone);

        $this->whatsapp->sendText(
            $phone,
            "👤 *WASIFU WANGU*\n\n" .
            "Jina: " . ($customer['name'] ?? 'Mteja') . "\n" .
            "💰 Salio: " . number_format((float) $customer['balance'], 0) . " TZS\n" .
            "📊 Jumla Umetumia: " . number_format((float) $customer['total_spent'], 0) . " TZS\n\n" .
            "Tuma \"#\" kurudi kwenye menu kuu."
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

        $inviteText = rawurlencode("Habari! Nataka kukuza akaunti yangu ya mitandao. Tumia code yangu: REF {$code}");
        $waLink = "https://wa.me/{$botNumber}?text={$inviteText}";

        $referredCount = Customer::countReferrals((int) $customer['id']);

        $this->whatsapp->sendText(
            $phone,
            "🎁 *MWALIKE RAFIKI, PATA BONUS*\n\n" .
            "Mwalike rafiki yako atumie KuzaPanel. Rafiki akiweka pesa mara ya kwanza, " .
            "wewe utapata *{$percent}%* ya kiasi alichoweka moja kwa moja kwenye salio lako! 💰\n\n" .
            "🔑 Code yako: *{$code}*\n" .
            "👥 Marafiki uliowaalika: {$referredCount}\n" .
            "💵 Umechuma jumla: " . number_format((float) $customer['referral_earnings'], 0) . " TZS\n\n" .
            "📲 Shiriki link hii na marafiki:\n{$waLink}\n\n" .
            "Tuma \"#\" kurudi menu kuu."
        );
    }

    private function sendOrderTracking(string $phone): void
    {
        $orders = Order::byCustomer($phone);

        if ($orders === []) {
            $this->whatsapp->sendText($phone, '📦 Hauna oda yoyote bado. Tuma "#" kuanza oda mpya.');

            return;
        }

        $lines = ["📦 *FUATILIA ODA*\n"];

        foreach (array_slice($orders, 0, 10) as $order) {
            $service = Service::find($order['service_id']);
            $serviceName = $service['name'] ?? 'Huduma';

            $lines[] = "🆔 Oda #{$order['id']} — {$serviceName}\n" .
                "   Kiasi: {$order['quantity']} | Gharama: " . number_format((float) $order['amount'], 0) . " TZS\n" .
                "   Status: {$order['status']}";
        }

        $lines[] = "\nTuma \"#\" kurudi kwenye menu kuu.";

        $this->whatsapp->sendText($phone, implode("\n\n", $lines));
    }

    private function handleTopupAmount(string $phone, array $session, array $message): void
    {
        $amount = (float) preg_replace('/[^0-9.]/', '', $message['text'] ?? '');

        if ($amount < 100) {
            $this->whatsapp->sendText(
                $phone,
                "⚠️ Kiasi sio sahihi.\n\n" .
                "Tafadhali andika kiasi unachotaka kuweka kwa namba.\n📌 Mfano: 5000"
            );

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
            "💳 *NJIA YA MALIPO*\n" .
            "💰 Unahitaji: " . number_format((float) $amount, 0) . " TZS\n\n" .
            "Utalipa " . number_format((float) $amount, 0) . " TZS.\n\n" .
            "Unataka kulipia kwa namba gani?",
            [
                ['id' => 'pay_phone:saved', 'title' => '📱 ' . $savedPhone],
                ['id' => 'pay_phone:other', 'title' => '✏️ Namba Nyingine'],
                ['id' => 'pay_phone:cancel', 'title' => '❌ Sitisha'],
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
            $this->whatsapp->sendText(
                $phone,
                "⚠️ Tafadhali bonyeza moja ya vitufe hapo juu 👆\n\n" .
                "_Tuma \"#\" kurudi mwanzo_"
            );

            return;
        }

        $choice = substr($message['id'], strlen('pay_phone:'));

        if ($choice === 'cancel') {
            $this->whatsapp->sendText($phone, 'Imesitishwa. Tuma "#" kuanza upya.');
            Session::reset($phone);

            return;
        }

        if ($choice === 'other') {
            $this->whatsapp->sendText(
                $phone,
                "📱 *NAMBA YA MALIPO*\n\n" .
                "Tafadhali andika namba ya simu utakayolipia (Mobile Money).\n\n" .
                "📌 Mfano: 0712345678\n\n" .
                "Tuma \"#\" kurudi menu kuu."
            );
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
            $this->whatsapp->sendText(
                $phone,
                "⚠️ Namba sio sahihi.\n\n" .
                "Tafadhali andika namba kamili ya simu (Mobile Money).\n📌 Mfano: 0712345678"
            );

            return;
        }

        $this->initiatePayment($phone, $session['temp_data'], $rawPhone);
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
            $this->whatsapp->sendText(
                $phone,
                "⚠️ Kiwango cha chini cha malipo kwa namba hii ni " . number_format($minAmount, 0) . " TZS.\n\n" .
                "Tafadhali jaribu tena na kiasi cha juu zaidi, au tumia namba ya mtandao mwingine.\n\n" .
                "Tuma \"#\" kuanza upya."
            );
            Session::reset($phone);

            return;
        }

        $gateway = $this->gatewayForAmount($amount);
        $chargeAmount = PaymentGateway::grossUpAmount($amount, $gateway);
        $localRef = 'KZPTOP' . $customer['id'] . time();
        $webhookBase = rtrim($this->config['app']['url'], '/');

        $paymentId = Payment::create([
            'type' => 'wallet_topup',
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
            $this->whatsapp->sendText($phone, "Samahani, malipo hayakuanzishwa: {$result['message']}\nTuma \"#\" kuanza upya.");
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
                "🪙 Tumetuma ombi la malipo!\n\n" .
                "📲 Angalia simu yako (USSD push) na ingiza PIN kuthibitisha *" . number_format($chargeAmount, 0) . " TZS*.\n\n" .
                "🆔 Kumbukumbu: {$reference}\n\n" .
                "💰 Salio lako litaongezwa kwa " . number_format($amount, 0) . " TZS moja kwa moja malipo yatakapothibitishwa."
            );

            Session::updateState($phone, 'AWAITING_MAIN_MENU');

            return;
        }

        // Order top-up: keep order data so the webhook can complete it after payment.
        $this->whatsapp->sendText(
            $phone,
            "✅ *OMBI LIMETUMWA*\n\n" .
            "Kiasi cha kulipa: *" . number_format($chargeAmount, 0) . " TZS*\n" .
            "Namba: " . $this->localPhone($rawPhone) . "\n\n" .
            "📲 Angalia simu yako — utapokea ujumbe wa kuthibitisha malipo (USSD). Weka PIN yako kukamilisha.\n\n" .
            "🚀 Oda yako itakamilika moja kwa moja malipo yatakapothibitishwa.\n\n" .
            "© KuzaPanel"
        );

        Session::updateState($phone, 'AWAITING_TOPUP_CONFIRMATION', $data);
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

    private function platformDescription(string $platform): string
    {
        return self::PLATFORM_INFO[$platform]['description'] ?? 'Huduma mbalimbali';
    }

    private function sendPlatformMenu(string $phone, ?string $name = null): void
    {
        $platforms = Service::activePlatforms();

        if ($platforms === []) {
            $this->whatsapp->sendText($phone, 'Samahani, hakuna huduma zinazopatikana kwa sasa.');

            return;
        }

        $greetingName = $name !== null && $name !== '' ? $name : 'Mteja';

        $rows = array_map(
            fn (string $platform) => [
                'id' => 'platform:' . $platform,
                'title' => $this->platformEmoji($platform) . ' ' . $platform,
                'description' => $this->platformDescription($platform),
            ],
            array_slice($platforms, 0, 10)
        );

        $this->whatsapp->sendList(
            $phone,
            "📱 *CHAGUA MTANDAO*\n" .
            "Sawa {$greetingName}, tukuze akaunti yako! 🚀\n\n" .
            "Unataka kukuza mtandao gani leo?",
            'Mitandao',
            'Platforms',
            $rows
        );
    }

    private function handlePlatformSelection(string $phone, array $message): void
    {
        if ($message['type'] !== 'selection' || !str_starts_with($message['id'], 'platform:')) {
            $this->whatsapp->sendText($phone, 'Tafadhali chagua platform kutoka kwenye orodha.');

            return;
        }

        $platform = substr($message['id'], strlen('platform:'));
        $services = Service::activeByPlatform($platform);

        if ($services === []) {
            $this->whatsapp->sendText($phone, 'Samahani, hakuna huduma za platform hii kwa sasa. Tuma "#" kuanza upya.');
            Session::reset($phone);

            return;
        }

        $greetingName = ($message['name'] ?? null) !== null && $message['name'] !== '' ? $message['name'] : 'Mteja';

        $rows = array_map(
            fn (array $service) => [
                'id' => 'service:' . $service['id'],
                'title' => mb_substr($service['name'], 0, 24),
                'description' => number_format((float) $service['my_price'], 0) . ' TZS kwa 1000',
            ],
            array_slice($services, 0, 10)
        );

        $this->whatsapp->sendList(
            $phone,
            "🎯 *CHAGUA HUDUMA ZA " . mb_strtoupper($platform) . "*\n" .
            "Chaguo nzuri {$greetingName}! Umechagua {$platform}.\n\n" .
            "Unahitaji nini haswa kwa akaunti yako? 👇",
            'Chagua Huduma',
            'Services',
            $rows
        );

        Session::updateState($phone, 'AWAITING_SERVICE', ['platform' => $platform]);
    }

    private function handleServiceSelection(string $phone, array $session, array $message): void
    {
        if ($message['type'] !== 'selection' || !str_starts_with($message['id'], 'service:')) {
            $this->whatsapp->sendText(
                $phone,
                "⚠️ Tafadhali chagua huduma kutoka kwenye orodha hapo juu 👆\n\n" .
                "_Tuma \"#\" kurudi mwanzo_"
            );

            return;
        }

        $serviceId = (int) substr($message['id'], strlen('service:'));
        $service = Service::find($serviceId);

        if ($service === null || $service['status'] !== 'active') {
            $this->whatsapp->sendText($phone, 'Huduma hii haipatikani tena. Tuma "#" kuanza upya.');
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
                'description' => 'Jumla: ' . number_format(($service['my_price'] / 1000) * $qty, 0) . ' TZS',
            ],
            $packages
        );

        $rows[] = [
            'id' => 'qty_package:custom',
            'title' => '✏️ Au Andika Kiasi Chako',
            'description' => "Min: {$service['min_quantity']} | Max: {$service['max_quantity']}",
        ];

        $this->whatsapp->sendList(
            $phone,
            "🔢 *CHAGUA KIASI*\n" .
            "Unataka kuongeza {$service['name']} ngapi?\n\n" .
            "Chagua kifurushi 👇",
            'Vifurushi',
            'Vifurushi',
            $rows
        );
    }

    private function handleQuantityChoice(string $phone, array $session, array $message): void
    {
        if ($message['type'] !== 'selection' || !str_starts_with($message['id'], 'qty_package:')) {
            $this->whatsapp->sendText(
                $phone,
                "⚠️ Tafadhali chagua kifurushi kutoka kwenye orodha hapo juu 👆\n\n" .
                "_Tuma \"#\" kurudi mwanzo_"
            );

            return;
        }

        $choice = substr($message['id'], strlen('qty_package:'));

        if ($choice === 'custom') {
            $service = Service::find($session['temp_data']['service_id']);

            $this->whatsapp->sendText(
                $phone,
                "Tafadhali andika idadi (kati ya {$service['min_quantity']} na {$service['max_quantity']}) unayotaka:"
            );

            Session::updateState($phone, 'AWAITING_QUANTITY', $session['temp_data']);

            return;
        }

        $quantity = (int) $choice;
        $service = Service::find($session['temp_data']['service_id']);

        if ($service === null) {
            $this->whatsapp->sendText($phone, 'Hitilafu imetokea. Tuma "#" kuanza upya.');
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
            $this->whatsapp->sendText($phone, 'Hitilafu imetokea. Tuma "#" kuanza upya.');
            Session::reset($phone);

            return;
        }

        if ($quantity < $service['min_quantity'] || $quantity > $service['max_quantity']) {
            $this->whatsapp->sendText(
                $phone,
                "Idadi sio sahihi. Tafadhali andika namba kati ya {$service['min_quantity']} na {$service['max_quantity']}:"
            );

            return;
        }

        $this->sendLinkRequest($phone, $service, $quantity);

        Session::updateState($phone, 'AWAITING_LINK', $session['temp_data'] + ['quantity' => $quantity]);
    }

    private const PROFILE_UNIT_LABELS = ['Followers', 'Subscribers'];

    private const LINK_INSTRUCTIONS = [
        'Instagram' => [
            'profile' => [
                'steps' => "1️⃣ Fungua profile yako ya Instagram\n2️⃣ Bonyeza Share profile\n3️⃣ Chagua Copy link\n4️⃣ Bandika (paste) link hapa chini",
                'example' => 'https://www.instagram.com/yourname',
                'image' => 'instagram_profile.jpg',
            ],
            'post' => [
                'steps' => "1️⃣ Fungua post yako ya Instagram\n2️⃣ Bonyeza ikoni ya share (✈️)\n3️⃣ Chagua Copy link\n4️⃣ Bandika (paste) link hapa chini",
                'example' => 'https://www.instagram.com/p/kuza1234',
                'image' => 'instagram_post.jpg',
            ],
        ],
        'TikTok' => [
            'profile' => [
                'steps' => "1️⃣ Fungua account yako ya TikTok\n2️⃣ Bonyeza ikoni ya share juu\n3️⃣ Chagua Copy link\n4️⃣ Bandika (paste) link hapa chini",
                'example' => 'https://www.tiktok.com/@yourname',
                'image' => 'tiktok_profile.jpg',
            ],
            'post' => [
                'steps' => "1️⃣ Fungua post yako ya TikTok\n2️⃣ Bonyeza ikoni ya share (✈️)\n3️⃣ Chagua Copy link\n4️⃣ Bandika (paste) link hapa chini",
                'example' => 'https://www.tiktok.com/@yourname/video/123456',
                'image' => 'tiktok_post.jpg',
            ],
        ],
        'Facebook' => [
            'profile' => [
                'steps' => "1️⃣ Fungua Account yako ya Facebook\n2️⃣ Bonyeza vidoti vitatu\n3️⃣ Chagua Copy link\n4️⃣ Bandika (paste) link hapa chini",
                'example' => 'https://www.facebook.com/yourname',
                'image' => 'facebook_profile.jpg',
            ],
            'post' => [
                'steps' => "1️⃣ Fungua post yako ya Facebook\n2️⃣ Bonyeza ikoni ya share\n3️⃣ Chagua Copy link\n4️⃣ Bandika (paste) link hapa chini",
                'example' => 'https://www.facebook.com/share/123456',
                'image' => 'facebook_post.jpg',
            ],
        ],
        'YouTube' => [
            'profile' => [
                'steps' => "1️⃣ Fungua profile yako ya YouTube\n2️⃣ Bonyeza Share profile\n3️⃣ Chagua Copy link\n4️⃣ Bandika (paste) link hapa chini",
                'example' => 'https://www.youtube.com/@yourchannel',
                'image' => 'youtube_profile.jpg',
            ],
            'post' => [
                'steps' => "1️⃣ Fungua post yako ya YouTube\n2️⃣ Bonyeza ikoni ya share (✈️)\n3️⃣ Chagua Copy link\n4️⃣ Bandika (paste) link hapa chini",
                'example' => 'https://www.youtube.com/watch?v=abc123',
                'image' => 'youtube_post.jpg',
            ],
        ],
    ];

    private function getLinkInstructions(string $platform, string $unitLabel): array
    {
        $type = in_array($unitLabel, self::PROFILE_UNIT_LABELS, true) ? 'profile' : 'post';

        $default = [
            'steps' => "1️⃣ Fungua akaunti/post yako ya {$platform}\n2️⃣ Bonyeza ikoni ya share\n3️⃣ Chagua Copy link\n4️⃣ Bandika (paste) link hapa chini",
            'example' => 'https://example.com/yourname',
            'image' => null,
        ];

        $platformKey = null;
        foreach (array_keys(self::LINK_INSTRUCTIONS) as $knownPlatform) {
            if (strcasecmp($knownPlatform, $platform) === 0) {
                $platformKey = $knownPlatform;
                break;
            }
        }

        if ($platformKey === null) {
            return $default;
        }

        return self::LINK_INSTRUCTIONS[$platformKey][$type] ?? $default;
    }

    private function sendLinkRequest(string $phone, array $service, int $quantity): void
    {
        $customer = Customer::getOrCreate($phone);
        $name = $customer['name'] ?? 'Mteja';
        $qtyLabel = ($quantity >= 1000 ? number_format($quantity / 1000, 0) . 'K' : (string) $quantity) . ' ' . $service['unit_label'];

        $instructions = $this->getLinkInstructions($service['platform'], $service['unit_label']);
        $steps = !empty($service['link_instructions']) ? $service['link_instructions'] : $instructions['steps'];

        $imageUrl = !empty($service['link_instructions_image'])
            ? $service['link_instructions_image']
            : ($instructions['image'] !== null
                ? rtrim($this->config['app']['url'], '/') . '/assets/instructions/' . $instructions['image']
                : null);

        $hasImage = $imageUrl !== null;

        $message =
            "🔗 *TUMA LINK YAKO*\n\n" .
            "Habari {$name}, unaagiza {$qtyLabel}.\n\n" .
            ($hasImage ? "👉 Angalia picha hapo juu kuona format sahihi.\n\n" : '') .
            "📱 Hatua:\n{$steps}\n\n" .
            "📌 Mfano: {$instructions['example']}\n\n" .
            "Tuma \"#\" kurudi menu kuu.";

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
            $this->whatsapp->sendText(
                $phone,
                "⚠️ Link uliyotuma sio sahihi.\n\n" .
                "Tafadhali tuma link kamili inayoanza na http:// au https://\n" .
                "📌 Mfano: https://instagram.com/jinaako\n\n" .
                "_Tuma \"#\" kurudi mwanzo_"
            );

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
            "✅ *UTHIBITISHO WA MWISHO*\n" .
            "Tafadhali hakiki maelezo ya oda yako:\n\n" .
            "📡 Mtandao: {$tempData['platform']}\n" .
            "🎯 Huduma: {$service['name']}\n" .
            "🔗 Link: {$link}\n" .
            "🔢 Kiasi: {$quantity}\n\n" .
            "💰 Gharama: " . number_format($amount, 0) . " TZS\n\n" .
            "Je, tuendelee na oda hii?",
            [
                ['id' => 'order_confirm:yes', 'title' => 'Ndio, Endelea 🚀'],
                ['id' => 'order_confirm:no', 'title' => 'Hapana, Sitisha ❌'],
            ]
        );

        Session::updateState($phone, 'AWAITING_ORDER_CONFIRM', $tempData);
    }

    private function handleOrderConfirm(string $phone, array $session, array $message): void
    {
        if ($message['type'] !== 'selection' || !str_starts_with($message['id'], 'order_confirm:')) {
            $this->whatsapp->sendText(
                $phone,
                "⚠️ Tafadhali bonyeza moja ya vitufe hapo juu 👆\n\n" .
                "_Tuma \"#\" kurudi mwanzo_"
            );

            return;
        }

        $choice = substr($message['id'], strlen('order_confirm:'));

        if ($choice === 'no') {
            $this->whatsapp->sendText($phone, 'Oda imesitishwa. Tuma "#" kuanza upya.');
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
            "⚠️ *SALIO DOGO*\n" .
            "Salio Halitoshi\n\n" .
            "Unahitaji ziada ya: " . number_format($shortfall, 0) . " TZS\n\n" .
            "Je, unataka kulipa sasa ili kukamilisha oda?",
            [
                ['id' => 'topup_decision:yes', 'title' => 'Ndio, Lipa Sasa 💳'],
                ['id' => 'topup_decision:no', 'title' => 'Hapana, Sitisha'],
            ]
        );

        Session::updateState($phone, 'AWAITING_TOPUP_DECISION', $data);
    }

    private function completeOrderFromWallet(string $phone, array $customer, array $data): void
    {
        if (!Customer::debit((int) $customer['id'], (float) $data['amount'])) {
            $this->whatsapp->sendText($phone, 'Samahani, hitilafu imetokea wakati wa kutumia salio. Tuma "#" kuanza upya.');
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

        $providerOrderLine = !empty($order['provider_order_id'])
            ? "🔢 Kuzapanel #{$order['provider_order_id']}\n"
            : '';

        $this->whatsapp->sendText(
            $phone,
            "🎉 *Oda Imepokelewa Kikamilifu!*\n\n" .
            "🆔 Namba: #{$orderId}\n" .
            $providerOrderLine .
            "⏳ Tumeanza kuifanyia kazi mara moja! 🚀\n\n" .
            "Unaweza kuifuatilia kwa kutumia kitufe cha 'Fuatilia Oda' kwenye menu kuu.\n\n" .
            "_Tuma # kurudi menu kuu_"
        );

        Session::reset($phone);
        MainMenu::send($this->whatsapp, $phone, $customer['name'] ?? null);
    }

    private function handleTopupDecision(string $phone, array $session, array $message): void
    {
        if ($message['type'] !== 'selection' || !str_starts_with($message['id'], 'topup_decision:')) {
            $this->whatsapp->sendText(
                $phone,
                "⚠️ Tafadhali bonyeza moja ya vitufe hapo juu 👆\n\n" .
                "_Tuma \"#\" kurudi mwanzo_"
            );

            return;
        }

        $choice = substr($message['id'], strlen('topup_decision:'));

        if ($choice === 'no') {
            $this->whatsapp->sendText($phone, 'Oda imesitishwa. Tuma "#" kuanza upya.');
            Session::reset($phone);

            return;
        }

        // Order top-up: carry the full order data; sendPhoneChoice computes the shortfall.
        $this->sendPhoneChoice($phone, $session['temp_data']);
    }
}

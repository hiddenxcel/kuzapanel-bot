<?php
// Run every 1-2 minutes via Windows Task Scheduler / cPanel cron:
// php c:\xampp\htdocs\kuzapanel\bot\cron\expire_sessions.php
// Proactively expires mid-conversation sessions that have been idle for 15+
// minutes, notifying the customer immediately instead of waiting for their
// next inbound message to trigger the expiry check in WebhookController.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    die();
}

require_once __DIR__ . '/../app/models/Session.php';
require_once __DIR__ . '/../app/models/Customer.php';
require_once __DIR__ . '/../app/services/WhatsAppClient.php';
require_once __DIR__ . '/../app/helpers/MainMenu.php';
require_once __DIR__ . '/../app/helpers/BotLang.php';

$config = require __DIR__ . '/../config/config.php';
$whatsapp = new WhatsAppClient($config['whatsapp']);

$expired = Session::expiredSessions(15);

$notified = 0;
$silentlyReset = 0;

foreach ($expired as $session) {
    $phone = $session['customer_phone'];
    $idleMinutes = (int) $session['idle_minutes'];

    // WhatsApp only allows free-form messages within 24h of the customer's
    // last inbound message. Beyond that, sending would just fail at Meta's
    // end — so silently reset those without attempting delivery.
    if ($idleMinutes >= 24 * 60) {
        Session::reset($phone);
        $silentlyReset++;
        echo "Silently reset (>24h idle) for {$phone}\n";

        continue;
    }

    $lang = BotLang::forCustomer(Customer::findByPhone($phone));
    $whatsapp->sendText($phone, BotLang::get($lang, 'session_expired'));
    MainMenu::send($whatsapp, $phone, null, $lang);

    // Reset to IDLE (NOT AWAITING_MAIN_MENU). expiredSessions() excludes IDLE,
    // so this stops the cron from picking the same session up again and
    // re-sending the expiry notice every single minute. The customer's next
    // inbound message re-opens the menu normally (the IDLE case does that).
    Session::reset($phone);

    $notified++;
    echo "Expired session for {$phone}\n";
}

echo "Done. Notified {$notified}, silently reset {$silentlyReset} (>24h idle).\n";

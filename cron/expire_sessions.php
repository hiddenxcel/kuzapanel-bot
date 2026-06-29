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
require_once __DIR__ . '/../app/services/WhatsAppClient.php';
require_once __DIR__ . '/../app/helpers/MainMenu.php';

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

    $whatsapp->sendText(
        $phone,
        "⏰ Muda wa mazungumzo yako umepita kwa kukaa kimya.\n\n" .
        "Hakuna wasiwasi — tunaanza upya! 👇"
    );
    MainMenu::send($whatsapp, $phone);

    Session::updateState($phone, 'AWAITING_MAIN_MENU');

    $notified++;
    echo "Expired session for {$phone}\n";
}

echo "Done. Notified {$notified}, silently reset {$silentlyReset} (>24h idle).\n";

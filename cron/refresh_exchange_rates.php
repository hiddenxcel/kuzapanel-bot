<?php
// Run once a day via Windows Task Scheduler / cPanel cron:
// php c:\xampp\htdocs\kuzapanel\bot\cron\refresh_exchange_rates.php
//
// Refreshes the KES/UGX rows in exchange_rates from a free, no-key exchange
// rate API. TZS is the bot's base currency and its row is never touched
// (pinned is_manual=1). A row with is_manual=1 (an admin-entered override)
// is also skipped, matching the same "manual wins" rule.
//
// On any failure (network error, malformed response), existing rates are
// left untouched — a stale rate is far less harmful than a page/reply that
// fails to send because of a currency lookup.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    die();
}

require_once __DIR__ . '/../app/lib/DB.php';

$config = require __DIR__ . '/../config/config.php';
$db = DB::connect($config['db']);

$ch = curl_init('https://open.er-api.com/v6/latest/USD');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_CONNECTTIMEOUT => 10,
]);
$body = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo "cURL error fetching exchange rates: {$err}\n";
    exit(1);
}

$data = json_decode($body, true);

if (!is_array($data) || ($data['result'] ?? null) !== 'success' || empty($data['rates']['TZS']) || empty($data['rates']['KES']) || empty($data['rates']['UGX'])) {
    echo "Unexpected exchange rate response, leaving existing rates untouched.\n";
    exit(1);
}

// The API gives "1 USD = X units" for every currency. The bot's base is
// TZS, not USD, so cross-divide through USD to get "1 KES = ? TZS" etc.
$usdToTzs = (float) $data['rates']['TZS'];
$targets = [
    'KES' => $usdToTzs / (float) $data['rates']['KES'],
    'UGX' => $usdToTzs / (float) $data['rates']['UGX'],
];

foreach ($targets as $currency => $tzsPerUnit) {
    $stmt = $db->prepare('SELECT is_manual FROM exchange_rates WHERE currency = ?');
    $stmt->execute([$currency]);
    $row = $stmt->fetch();

    if ($row !== false && (int) $row['is_manual'] === 1) {
        echo "{$currency}: skipped (manual override).\n";

        continue;
    }

    $update = $db->prepare('UPDATE exchange_rates SET rate = ?, fetched_at = NOW() WHERE currency = ?');
    $update->execute([round($tzsPerUnit, 8), $currency]);

    echo "{$currency}: updated to " . round($tzsPerUnit, 4) . " TZS.\n";
}

echo "Done.\n";

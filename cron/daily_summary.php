<?php
// Run once a day (00:00) via Windows Task Scheduler:
// php c:\xampp\htdocs\kuzapanel\bot\cron\daily_summary.php
// Sends the previous day's sales summary to the admin via WhatsApp.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    die();
}

require_once __DIR__ . '/../app/models/Order.php';
require_once __DIR__ . '/../app/models/Payment.php';
require_once __DIR__ . '/../app/services/AdminNotifier.php';

$config = require __DIR__ . '/../config/config.php';
$admin = new AdminNotifier($config);

$date = date('Y-m-d', strtotime('yesterday'));

$sales = Order::statsForDate($date);
$deposits = Payment::depositsForDate($date);

$message =
    "📊 *MUHTASARI WA SIKU*\n" .
    "📅 {$date}\n\n" .
    "🛒 Orders: {$sales['orders_count']}\n" .
    "💰 Mauzo: " . number_format($sales['revenue'], 0) . " TZS\n" .
    "📈 Faida: " . number_format($sales['profit'], 0) . " TZS\n\n" .
    "💵 Deposits: {$deposits['count']} (" . number_format($deposits['total'], 0) . " TZS)\n\n" .
    "© KuzaPanel";

$admin->notify($message);

echo "Daily summary for {$date} sent.\n";

<?php

require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/models/Order.php';
require_once __DIR__ . '/../../app/models/Payment.php';

Auth::requireLogin();

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$weekStart = date('Y-m-d', strtotime('-6 days'));
$monthStart = date('Y-m-01');

$todayStats = Order::statsForDate($today);
$yesterdayStats = Order::statsForDate($yesterday);
$weekStats = Order::statsForRange($weekStart, $today);
$monthStats = Order::statsForRange($monthStart, $today);

$depositsToday = Payment::depositsForDate($today);

$topCustomers = Order::topCustomers(5);

$pageTitle = 'Ripoti ya Mauzo';
$activeNav = 'reports';
require __DIR__ . '/includes/layout_header.php';

function fmt(float $n): string
{
    return number_format($n, 0) . ' TZS';
}
?>

<div class="stat-grid">
    <div class="stat-card">
        <div class="label">Mauzo Leo</div>
        <div class="value"><?= number_format($todayStats['revenue'], 0) ?></div>
        <div class="label">Faida: <?= fmt($todayStats['profit']) ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Mauzo Jana</div>
        <div class="value"><?= number_format($yesterdayStats['revenue'], 0) ?></div>
        <div class="label">Faida: <?= fmt($yesterdayStats['profit']) ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Mauzo Wiki (siku 7)</div>
        <div class="value"><?= number_format($weekStats['revenue'], 0) ?></div>
        <div class="label">Faida: <?= fmt($weekStats['profit']) ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Mauzo Mwezi</div>
        <div class="value"><?= number_format($monthStats['revenue'], 0) ?></div>
        <div class="label">Faida: <?= fmt($monthStats['profit']) ?></div>
    </div>
</div>

<div class="card">
    <h3 style="margin-top:0;">Muhtasari wa Orders (zilizolipiwa)</h3>
    <table>
        <tr><th>Kipindi</th><th>Orders</th><th>Mauzo</th><th>Faida</th></tr>
        <tr><td>Leo</td><td><?= $todayStats['orders_count'] ?></td><td><?= fmt($todayStats['revenue']) ?></td><td><?= fmt($todayStats['profit']) ?></td></tr>
        <tr><td>Jana</td><td><?= $yesterdayStats['orders_count'] ?></td><td><?= fmt($yesterdayStats['revenue']) ?></td><td><?= fmt($yesterdayStats['profit']) ?></td></tr>
        <tr><td>Wiki (siku 7)</td><td><?= $weekStats['orders_count'] ?></td><td><?= fmt($weekStats['revenue']) ?></td><td><?= fmt($weekStats['profit']) ?></td></tr>
        <tr><td>Mwezi huu</td><td><?= $monthStats['orders_count'] ?></td><td><?= fmt($monthStats['revenue']) ?></td><td><?= fmt($monthStats['profit']) ?></td></tr>
    </table>
</div>

<div class="card">
    <h3 style="margin-top:0;">Deposits Leo</h3>
    <table>
        <tr><td>Idadi ya deposits</td><td><?= $depositsToday['count'] ?></td></tr>
        <tr><td>Jumla ya deposits</td><td><?= fmt($depositsToday['total']) ?></td></tr>
    </table>
</div>

<div class="card">
    <h3 style="margin-top:0;">Wateja Bora (kwa matumizi)</h3>
    <table>
        <tr><th>#</th><th>Jina</th><th>Namba</th><th>Jumla Ametumia</th></tr>
        <?php foreach ($topCustomers as $i => $c): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($c['name'] ?? 'Mteja') ?></td>
            <td><?= htmlspecialchars($c['phone']) ?></td>
            <td><?= fmt((float) $c['total_spent']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($topCustomers === []): ?>
        <tr><td colspan="4">Hakuna data bado.</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

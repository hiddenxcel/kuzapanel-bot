<?php

require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/helpers/Lang.php';
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

$pageTitle = t('reports.title');
$activeNav = 'reports';
require __DIR__ . '/includes/layout_header.php';

function fmt(float $n): string
{
    return number_format($n, 0) . ' TZS';
}
?>

<div class="stat-grid">
    <div class="stat-card">
        <div class="label"><?= t('reports.sales_today') ?></div>
        <div class="value"><?= number_format($todayStats['revenue'], 0) ?></div>
        <div class="label"><?= t('reports.profit') ?> <?= fmt($todayStats['profit']) ?></div>
    </div>
    <div class="stat-card">
        <div class="label"><?= t('reports.sales_yesterday') ?></div>
        <div class="value"><?= number_format($yesterdayStats['revenue'], 0) ?></div>
        <div class="label"><?= t('reports.profit') ?> <?= fmt($yesterdayStats['profit']) ?></div>
    </div>
    <div class="stat-card">
        <div class="label"><?= t('reports.sales_week') ?></div>
        <div class="value"><?= number_format($weekStats['revenue'], 0) ?></div>
        <div class="label"><?= t('reports.profit') ?> <?= fmt($weekStats['profit']) ?></div>
    </div>
    <div class="stat-card">
        <div class="label"><?= t('reports.sales_month') ?></div>
        <div class="value"><?= number_format($monthStats['revenue'], 0) ?></div>
        <div class="label"><?= t('reports.profit') ?> <?= fmt($monthStats['profit']) ?></div>
    </div>
</div>

<div class="card">
    <h3 style="margin-top:0;"><?= t('reports.orders_summary') ?></h3>
    <table>
        <tr><th><?= t('reports.col_period') ?></th><th><?= t('reports.col_orders') ?></th><th><?= t('reports.col_sales') ?></th><th><?= t('reports.col_profit') ?></th></tr>
        <tr><td><?= t('reports.today') ?></td><td><?= $todayStats['orders_count'] ?></td><td><?= fmt($todayStats['revenue']) ?></td><td><?= fmt($todayStats['profit']) ?></td></tr>
        <tr><td><?= t('reports.yesterday') ?></td><td><?= $yesterdayStats['orders_count'] ?></td><td><?= fmt($yesterdayStats['revenue']) ?></td><td><?= fmt($yesterdayStats['profit']) ?></td></tr>
        <tr><td><?= t('reports.this_week') ?></td><td><?= $weekStats['orders_count'] ?></td><td><?= fmt($weekStats['revenue']) ?></td><td><?= fmt($weekStats['profit']) ?></td></tr>
        <tr><td><?= t('reports.this_month') ?></td><td><?= $monthStats['orders_count'] ?></td><td><?= fmt($monthStats['revenue']) ?></td><td><?= fmt($monthStats['profit']) ?></td></tr>
    </table>
</div>

<div class="card">
    <h3 style="margin-top:0;"><?= t('reports.deposits_today') ?></h3>
    <table>
        <tr><td><?= t('reports.deposits_count') ?></td><td><?= $depositsToday['count'] ?></td></tr>
        <tr><td><?= t('reports.deposits_total') ?></td><td><?= fmt($depositsToday['total']) ?></td></tr>
    </table>
</div>

<div class="card">
    <h3 style="margin-top:0;"><?= t('reports.top_customers') ?></h3>
    <table>
        <tr><th><?= t('reports.col_rank') ?></th><th><?= t('reports.col_name') ?></th><th><?= t('reports.col_phone') ?></th><th><?= t('reports.col_total_spent') ?></th></tr>
        <?php foreach ($topCustomers as $i => $c): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($c['name'] ?? 'Mteja') ?></td>
            <td><?= htmlspecialchars($c['phone']) ?></td>
            <td><?= fmt((float) $c['total_spent']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($topCustomers === []): ?>
        <tr><td colspan="4"><?= t('reports.no_data') ?></td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

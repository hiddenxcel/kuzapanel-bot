<?php

require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/models/Order.php';
require_once __DIR__ . '/../../app/models/Provider.php';
require_once __DIR__ . '/../../app/models/Service.php';

Auth::requireLogin();

$stats = Order::dashboardStats();
$providerCount = count(Provider::all());
$serviceCount = count(Service::all());

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/includes/layout_header.php';
?>

<div class="stat-grid">
    <div class="stat-card">
        <div class="label"><?= t('dashboard.orders_today') ?></div>
        <div class="value"><?= $stats['orders_today'] ?></div>
    </div>
    <div class="stat-card">
        <div class="label"><?= t('dashboard.revenue_today') ?></div>
        <div class="value"><?= number_format($stats['revenue_today'], 2) ?></div>
    </div>
    <div class="stat-card">
        <div class="label"><?= t('dashboard.pending_orders') ?></div>
        <div class="value"><?= $stats['pending'] ?></div>
    </div>
    <div class="stat-card">
        <div class="label"><?= t('dashboard.processing_orders') ?></div>
        <div class="value"><?= $stats['processing'] ?></div>
    </div>
</div>

<div class="card">
    <h3 style="margin-top:0;"><?= t('dashboard.summary') ?></h3>
    <table>
        <tr><td><?= t('dashboard.total_orders') ?></td><td><?= $stats['total'] ?></td></tr>
        <tr><td><?= t('dashboard.providers') ?></td><td><?= $providerCount ?></td></tr>
        <tr><td><?= t('dashboard.services') ?></td><td><?= $serviceCount ?></td></tr>
    </table>
</div>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

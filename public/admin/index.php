<?php

require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/helpers/Lang.php';
require_once __DIR__ . '/../../app/models/Order.php';
require_once __DIR__ . '/../../app/models/Provider.php';
require_once __DIR__ . '/../../app/models/Service.php';
require_once __DIR__ . '/../../app/models/AppSetting.php';

Auth::requireLogin();

$stats = Order::dashboardStats();
$providerCount = count(Provider::all());
$serviceCount = count(Service::all());
$aiEnabled = AppSetting::isAiEnabled();
$maintenanceEnabled = AppSetting::isMaintenanceEnabled();

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

<div class="card">
    <h3 style="margin-top:0;"><?= t('dashboard.bot_status') ?></h3>
    <div style="display:flex;gap:16px;flex-wrap:wrap;">
        <a href="settings.php" style="text-decoration:none;flex:1;min-width:180px;">
            <div class="mini-stat" style="cursor:pointer;">
                <div class="icon <?= $maintenanceEnabled ? 'inactive' : 'active' ?>"><i class="fa-solid fa-power-off"></i></div>
                <div><div class="num" style="font-size:14px;color:var(--text);"><?= $maintenanceEnabled ? t('health.maintenance_on') : t('health.maintenance_off') ?></div><div class="lbl"><?= t('health.maintenance_label') ?></div></div>
            </div>
        </a>
        <a href="settings.php" style="text-decoration:none;flex:1;min-width:180px;">
            <div class="mini-stat" style="cursor:pointer;">
                <div class="icon <?= $aiEnabled ? 'active' : 'inactive' ?>"><i class="fa-solid fa-robot"></i></div>
                <div><div class="num" style="font-size:14px;color:var(--text);"><?= $aiEnabled ? t('health.ai_on') : t('health.ai_off') ?></div><div class="lbl"><?= t('health.ai_label') ?></div></div>
            </div>
        </a>
        <a href="health.php" style="text-decoration:none;flex:1;min-width:180px;">
            <div class="mini-stat" style="cursor:pointer;">
                <div class="icon total"><i class="fa-solid fa-heart-pulse"></i></div>
                <div><div class="num" style="font-size:14px;color:var(--text);"><?= t('dashboard.view_health') ?></div><div class="lbl"><?= t('health.title') ?></div></div>
            </div>
        </a>
    </div>
</div>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

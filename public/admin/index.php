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
        <div class="label">Orders Leo</div>
        <div class="value"><?= $stats['orders_today'] ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Mapato Leo (TZS)</div>
        <div class="value"><?= number_format($stats['revenue_today'], 2) ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Pending Orders</div>
        <div class="value"><?= $stats['pending'] ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Processing Orders</div>
        <div class="value"><?= $stats['processing'] ?></div>
    </div>
</div>

<div class="card">
    <h3 style="margin-top:0;">Muhtasari wa Mfumo</h3>
    <table>
        <tr><td>Jumla ya Orders</td><td><?= $stats['total'] ?></td></tr>
        <tr><td>Providers</td><td><?= $providerCount ?></td></tr>
        <tr><td>Services</td><td><?= $serviceCount ?></td></tr>
    </table>
</div>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

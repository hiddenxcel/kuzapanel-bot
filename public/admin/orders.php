<?php

require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/models/Order.php';
require_once __DIR__ . '/../../app/services/OrderFulfillment.php';

Auth::requireLogin();

$success = null;
$error = null;

require_once __DIR__ . '/../../app/helpers/Lang.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    Order::updateStatus((int) $_POST['id'], $_POST['status']);
    $success = 'Order #' . (int) $_POST['id'] . ' ' . t('orders.updated');
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'resend') {
    $resendId = (int) $_POST['id'];
    OrderFulfillment::submit($resendId);
    $fresh = Order::find($resendId);

    if ($fresh !== null && $fresh['provider_order_id'] !== null) {
        $success = "Order #{$resendId} " . t('orders.resend_success');
    } else {
        $error = "Order #{$resendId} " . t('orders.resend_failed') . ' ' . ($fresh['order_error'] ?? t('orders.unknown_error'));
    }
}

$filters = [
    'status' => $_GET['status'] ?? '',
    'payment_status' => $_GET['payment_status'] ?? '',
    'q' => trim($_GET['q'] ?? ''),
];
$page = max(1, (int) ($_GET['page'] ?? 1));

$result = Order::search($filters, $page, 20);

$statusOptions = ['pending', 'processing', 'completed', 'cancelled'];
$paymentOptions = ['pending', 'paid', 'failed'];

$pageTitle = t('orders.title');
$activeNav = 'orders';
require __DIR__ . '/includes/layout_header.php';
?>

<?php if ($success !== null): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error !== null): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="get" class="toolbar">
    <input type="text" name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="<?= t('orders.search_label') ?>">
    <select name="status">
        <option value=""><?= t('orders.order_status') ?>: <?= t('orders.all') ?></option>
        <?php foreach ($statusOptions as $opt): ?>
            <option value="<?= $opt ?>" <?= $filters['status'] === $opt ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="payment_status">
        <option value=""><?= t('orders.payment') ?>: <?= t('orders.all') ?></option>
        <?php foreach ($paymentOptions as $opt): ?>
            <option value="<?= $opt ?>" <?= $filters['payment_status'] === $opt ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> <?= t('orders.filter') ?></button>
    <?php if ($filters['q'] !== '' || $filters['status'] !== '' || $filters['payment_status'] !== ''): ?>
        <a href="orders.php" class="btn btn-secondary"><?= t('orders.clear_filters') ?></a>
    <?php endif; ?>
</form>

<div class="card">
    <h3 style="margin-top:0;"><?= t('orders.list_title') ?> <span style="color:var(--text-soft);font-weight:500;font-size:13px;">(<?= $result['total'] ?> <?= t('orders.total_suffix') ?>)</span></h3>
    <table>
        <tr>
            <th><?= t('orders.col_id') ?></th><th><?= t('orders.col_customer') ?></th><th><?= t('orders.col_service') ?></th><th><?= t('orders.col_qty') ?></th><th><?= t('orders.col_link') ?></th><th><?= t('orders.col_price') ?></th><th><?= t('orders.col_payment') ?></th><th><?= t('orders.col_provider_order_id') ?></th><th><?= t('orders.col_status') ?></th><th><?= t('orders.col_date') ?></th><th><?= t('orders.col_action') ?></th>
        </tr>
        <?php foreach ($result['rows'] as $o): ?>
        <tr>
            <td>#<?= $o['id'] ?></td>
            <td>
                <?= htmlspecialchars($o['customer_name'] ?: '—') ?><br>
                <span style="color:var(--text-soft);font-size:12px;"><?= htmlspecialchars($o['customer_phone']) ?></span>
            </td>
            <td>
                <?= htmlspecialchars($o['service_name'] ?? '—') ?><br>
                <span style="color:var(--text-soft);font-size:12px;"><?= htmlspecialchars($o['service_platform'] ?? '') ?></span>
            </td>
            <td><?= $o['quantity'] ?></td>
            <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                <a href="<?= htmlspecialchars($o['link']) ?>" target="_blank" title="<?= htmlspecialchars($o['link']) ?>"><?= htmlspecialchars($o['link']) ?></a>
            </td>
            <td><?= number_format((float) $o['amount'], 2) ?></td>
            <td><span class="badge badge-<?= $o['payment_status'] === 'paid' ? 'active' : ($o['payment_status'] === 'failed' ? 'inactive' : 'pending') ?>"><?= ucfirst($o['payment_status']) ?></span></td>
            <td style="white-space:nowrap;">
                <?= !empty($o['provider_order_id']) ? '#' . htmlspecialchars($o['provider_order_id']) : '<span style="color:var(--text-soft);">—</span>' ?>
            </td>
            <td>
                <form class="inline" method="post" style="display:flex;gap:6px;">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" value="<?= $o['id'] ?>">
                    <select name="status" style="padding:6px 8px;font-size:12.5px;width:auto;">
                        <?php foreach ($statusOptions as $opt): ?>
                            <option value="<?= $opt ?>" <?= $o['status'] === $opt ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-secondary" style="padding:6px 10px;"><?= t('orders.ok') ?></button>
                </form>
                <?php if (!empty($o['order_error']) && empty($o['provider_order_id'])): ?>
                <div style="margin-top:8px;padding:6px 8px;background:var(--red-soft);border-radius:8px;max-width:220px;">
                    <div style="color:var(--red);font-size:11.5px;font-weight:600;margin-bottom:6px;">
                        <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($o['order_error']) ?>
                    </div>
                    <form class="inline" method="post">
                        <input type="hidden" name="action" value="resend">
                        <input type="hidden" name="id" value="<?= $o['id'] ?>">
                        <button type="submit" class="btn btn-primary" style="padding:6px 10px;font-size:12px;">
                            <i class="fa-solid fa-rotate-right"></i> <?= t('orders.resend') ?>
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </td>
            <td style="white-space:nowrap;color:var(--text-soft);font-size:12.5px;"><?= htmlspecialchars($o['created_at']) ?></td>
            <td>
                <a href="customers.php?q=<?= urlencode($o['customer_phone']) ?>" class="btn btn-secondary"><?= t('orders.customer_btn') ?></a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if ($result['rows'] === []): ?>
        <tr><td colspan="11"><?= t('orders.no_results') ?></td></tr>
        <?php endif; ?>
    </table>

    <?php if ($result['totalPages'] > 1): ?>
    <div style="display:flex;gap:6px;justify-content:center;margin-top:18px;">
        <?php for ($p = 1; $p <= $result['totalPages']; $p++): ?>
            <?php
                $qs = $_GET;
                $qs['page'] = $p;
            ?>
            <a href="?<?= htmlspecialchars(http_build_query($qs)) ?>"
               class="btn <?= $p === $result['page'] ? 'btn-primary' : 'btn-secondary' ?>"
               style="padding:7px 13px;"><?= $p ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

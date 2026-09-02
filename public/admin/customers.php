<?php

require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/helpers/Lang.php';
require_once __DIR__ . '/../../app/models/Customer.php';
require_once __DIR__ . '/../../app/models/BalanceAdjustment.php';

Auth::requireLogin();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'adjust_balance') {
    $customerId = (int) $_POST['id'];
    $rawAmount = (float) ($_POST['amount'] ?? 0);
    $direction = $_POST['direction'] ?? 'credit';
    $note = trim($_POST['note'] ?? '') ?: null;
    $signedAmount = $direction === 'debit' ? -abs($rawAmount) : abs($rawAmount);

    if ($rawAmount <= 0) {
        $error = t('customers.fill_valid_amount');
    } elseif (!Customer::adjustBalance($customerId, $signedAmount)) {
        $error = t('customers.adjust_failed');
    } else {
        BalanceAdjustment::create($customerId, Auth::user()['id'] ?? null, $signedAmount, $note);
        $success = ($direction === 'debit' ? t('customers.debited') : t('customers.credited'));
    }
}

$q = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));

$result = Customer::search($q, $page, 25);

$adjusting = null;
$adjustingHistory = [];
if (isset($_GET['adjust'])) {
    $adjusting = Customer::find((int) $_GET['adjust']);
    if ($adjusting !== null) {
        $adjustingHistory = BalanceAdjustment::byCustomer((int) $adjusting['id']);
    }
}

$pageTitle = t('customers.title');
$activeNav = 'customers';
require __DIR__ . '/includes/layout_header.php';
?>

<?php if ($error !== null): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success !== null): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="card">
    <form method="get" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div class="form-group" style="margin-bottom:0;flex:1;min-width:220px;">
            <label><?= t('customers.search_label') ?></label>
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="mfano: 255712345678">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> <?= t('customers.search') ?></button>
        <?php if ($q !== ''): ?>
            <a href="customers.php" class="btn btn-secondary"><?= t('customers.clear') ?></a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <h3 style="margin-top:0;"><?= t('customers.list_title') ?> <span style="color:var(--text-soft);font-weight:500;font-size:13px;">(<?= $result['total'] ?> <?= t('customers.total_suffix') ?>)</span></h3>
    <table>
        <tr>
            <th><?= t('customers.col_id') ?></th><th><?= t('customers.col_name') ?></th><th><?= t('customers.col_phone') ?></th><th><?= t('customers.col_balance') ?></th><th><?= t('customers.col_total_spent') ?></th><th><?= t('customers.col_referral_code') ?></th><th><?= t('customers.col_orders') ?></th><th><?= t('customers.col_joined') ?></th><th><?= t('customers.col_action') ?></th>
        </tr>
        <?php foreach ($result['rows'] as $c): ?>
        <tr>
            <td>#<?= $c['id'] ?></td>
            <td><?= htmlspecialchars($c['name'] ?: '—') ?></td>
            <td><?= htmlspecialchars($c['phone']) ?></td>
            <td><?= number_format((float) $c['balance'], 2) ?></td>
            <td><?= number_format((float) $c['total_spent'], 2) ?></td>
            <td><code><?= htmlspecialchars($c['referral_code'] ?: '—') ?></code></td>
            <td><?= (int) $c['order_count'] ?></td>
            <td style="white-space:nowrap;color:var(--text-soft);font-size:12.5px;"><?= htmlspecialchars($c['created_at']) ?></td>
            <td style="white-space:nowrap;">
                <a href="orders.php?q=<?= urlencode($c['phone']) ?>" class="btn btn-secondary"><?= t('customers.orders_btn') ?></a>
                <a href="#" class="btn btn-secondary balance-btn" data-id="<?= $c['id'] ?>" data-name="<?= htmlspecialchars($c['name'] ?: $c['phone']) ?>" data-balance="<?= number_format((float) $c['balance'], 2) ?>"><i class="fa-solid fa-coins"></i> <?= t('customers.balance_btn') ?></a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if ($result['rows'] === []): ?>
        <tr><td colspan="9"><?= t('customers.no_results') ?></td></tr>
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

<!-- ==== Balance adjustment modal ==== -->
<div class="modal-backdrop" id="balanceModal">
    <div class="modal-box" style="max-width:520px;">
        <div class="modal-head">
            <h3 id="balanceModalTitle">💰 <?= t('customers.adjust_balance_title') ?></h3>
            <button type="button" class="modal-close" id="balanceModalCloseBtn"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p id="balanceModalBalance" style="margin-top:-8px;color:var(--text-soft);font-size:13px;"></p>
            <form method="post" id="balanceForm">
                <input type="hidden" name="action" value="adjust_balance">
                <input type="hidden" name="id" id="balanceFormId" value="">
                <div class="form-group">
                    <label><?= t('customers.type') ?></label>
                    <select name="direction">
                        <option value="credit"><?= t('customers.credit') ?></option>
                        <option value="debit"><?= t('customers.debit') ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label><?= t('customers.amount') ?></label>
                    <input type="number" name="amount" min="1" step="0.01" required>
                </div>
                <div class="form-group">
                    <label><?= t('customers.note_optional') ?></label>
                    <input type="text" name="note" placeholder="<?= t('customers.note_placeholder') ?>">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> <?= t('customers.execute') ?></button>
            </form>

            <?php if ($adjusting !== null && $adjustingHistory !== []): ?>
            <h3 style="margin-top:22px;font-size:14px;"><?= t('customers.recent_adjustments') ?></h3>
            <table>
                <tr><th><?= t('customers.col_amount') ?></th><th><?= t('customers.col_note') ?></th><th><?= t('customers.col_admin') ?></th><th><?= t('customers.col_date') ?></th></tr>
                <?php foreach ($adjustingHistory as $h): ?>
                <tr>
                    <td style="color:<?= $h['amount'] >= 0 ? 'var(--green)' : 'var(--red)' ?>;font-weight:700;">
                        <?= $h['amount'] >= 0 ? '+' : '' ?><?= number_format((float) $h['amount'], 2) ?>
                    </td>
                    <td><?= htmlspecialchars($h['note'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($h['admin_username'] ?? '—') ?></td>
                    <td style="white-space:nowrap;color:var(--text-soft);font-size:12.5px;"><?= htmlspecialchars($h['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('balanceModal');
    var title = document.getElementById('balanceModalTitle');
    var balanceLine = document.getElementById('balanceModalBalance');
    var formId = document.getElementById('balanceFormId');

    function openFor(id, name, balance) {
        title.textContent = '💰 ' + <?= json_encode(t('customers.adjust_balance_title')) ?> + ' — ' + name;
        balanceLine.textContent = <?= json_encode(t('customers.current_balance')) ?> + ' ' + balance + ' TZS';
        formId.value = id;
        modal.classList.add('open');
    }

    document.querySelectorAll('.balance-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            openFor(btn.dataset.id, btn.dataset.name, btn.dataset.balance);
        });
    });

    document.getElementById('balanceModalCloseBtn').addEventListener('click', function () { modal.classList.remove('open'); });
    modal.addEventListener('click', function (e) {
        if (e.target === modal) modal.classList.remove('open');
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') modal.classList.remove('open');
    });

    <?php if ($adjusting !== null): ?>
    openFor(
        <?= json_encode((string) $adjusting['id']) ?>,
        <?= json_encode($adjusting['name'] ?: $adjusting['phone']) ?>,
        <?= json_encode(number_format((float) $adjusting['balance'], 2)) ?>
    );
    <?php endif; ?>
})();
</script>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

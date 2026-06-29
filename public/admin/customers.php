<?php

require_once __DIR__ . '/../../app/helpers/Auth.php';
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
        $error = 'Tafadhali jaza kiasi sahihi (zaidi ya 0).';
    } elseif (!Customer::adjustBalance($customerId, $signedAmount)) {
        $error = 'Imeshindikana — labda salio la mteja halitoshi kutoa kiasi hicho.';
    } else {
        BalanceAdjustment::create($customerId, Auth::user()['id'] ?? null, $signedAmount, $note);
        $success = ($direction === 'debit' ? 'Salio limetolewa.' : 'Salio limeongezwa.');
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

$pageTitle = 'Customers';
$activeNav = 'customers';
require __DIR__ . '/includes/layout_header.php';
?>

<?php if ($error !== null): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success !== null): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($adjusting !== null): ?>
<div class="card">
    <h3 style="margin-top:0;">💰 Rekebisha Salio — <?= htmlspecialchars($adjusting['name'] ?: $adjusting['phone']) ?></h3>
    <p style="margin-top:-8px;color:var(--text-soft);font-size:13px;">
        Salio la sasa: <strong style="color:var(--text);"><?= number_format((float) $adjusting['balance'], 2) ?> TZS</strong>
    </p>
    <form method="post" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <input type="hidden" name="action" value="adjust_balance">
        <input type="hidden" name="id" value="<?= $adjusting['id'] ?>">
        <div class="form-group" style="margin-bottom:0;width:160px;">
            <label>Aina</label>
            <select name="direction">
                <option value="credit">➕ Ongeza</option>
                <option value="debit">➖ Toa</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;width:160px;">
            <label>Kiasi (TZS)</label>
            <input type="number" name="amount" min="1" step="0.01" required>
        </div>
        <div class="form-group" style="margin-bottom:0;flex:1;min-width:220px;">
            <label>Maelezo (hiari)</label>
            <input type="text" name="note" placeholder="mfano: Marekebisho ya makosa ya malipo">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Tekeleza</button>
        <a href="customers.php" class="btn btn-secondary">Ghairi</a>
    </form>

    <?php if ($adjustingHistory !== []): ?>
    <h3 style="margin-top:22px;font-size:14px;">Historia ya Marekebisho ya Mwisho</h3>
    <table>
        <tr><th>Kiasi</th><th>Maelezo</th><th>Admin</th><th>Tarehe</th></tr>
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
<?php endif; ?>

<div class="card">
    <form method="get" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div class="form-group" style="margin-bottom:0;flex:1;min-width:220px;">
            <label>Tafuta (phone, jina, au referral code)</label>
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="mfano: 255712345678">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Tafuta</button>
        <?php if ($q !== ''): ?>
            <a href="customers.php" class="btn btn-secondary">Futa</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <h3 style="margin-top:0;">Orodha ya Wateja <span style="color:var(--text-soft);font-weight:500;font-size:13px;">(<?= $result['total'] ?> jumla)</span></h3>
    <table>
        <tr>
            <th>ID</th><th>Jina</th><th>Phone</th><th>Balance</th><th>Total Spent</th><th>Referral Code</th><th>Orders</th><th>Tarehe ya kujiunga</th><th>Action</th>
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
                <a href="orders.php?q=<?= urlencode($c['phone']) ?>" class="btn btn-secondary">Orders</a>
                <a href="customers.php?adjust=<?= $c['id'] ?>" class="btn btn-secondary"><i class="fa-solid fa-coins"></i> Salio</a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if ($result['rows'] === []): ?>
        <tr><td colspan="9">Hakuna wateja wanaolingana.</td></tr>
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

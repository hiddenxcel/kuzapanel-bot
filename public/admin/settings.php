<?php

require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/models/PaymentGateway.php';

Auth::requireLogin();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $data = [
        'api_key' => trim($_POST['api_key'] ?? ''),
        'api_secret' => trim($_POST['api_secret'] ?? ''),
        'fee_percent' => (float) ($_POST['fee_percent'] ?? 0),
        'status' => $_POST['status'] ?? 'inactive',
    ];

    if ($id <= 0) {
        $error = 'Gateway sio sahihi.';
    } else {
        PaymentGateway::update($id, $data);
        $success = 'Mipangilio ya malipo imesasishwa.';
    }
}

$gateways = PaymentGateway::all();

$pageTitle = 'Mipangilio ya Malipo';
$activeNav = 'settings';
require __DIR__ . '/includes/layout_header.php';
?>

<?php if ($error !== null): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success !== null): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php foreach ($gateways as $g): ?>
<div class="card">
    <h3 style="margin-top:0;"><?= htmlspecialchars($g['name']) ?> <span class="badge badge-<?= $g['status'] ?>"><?= $g['status'] ?></span></h3>
    <form method="post">
        <input type="hidden" name="id" value="<?= $g['id'] ?>">
        <div class="form-group">
            <label>API Key</label>
            <input type="text" name="api_key" value="<?= htmlspecialchars($g['api_key']) ?>">
        </div>
        <div class="form-group">
            <label>API Secret (kama ipo)</label>
            <input type="text" name="api_secret" value="<?= htmlspecialchars($g['api_secret']) ?>">
        </div>
        <div class="form-group">
            <label>Ada ya Gateway (%) — kiwango wanachokata kwa kila muamala</label>
            <input type="number" name="fee_percent" step="0.01" min="0" max="100" value="<?= htmlspecialchars((string) $g['fee_percent']) ?>">
            <small style="color: var(--text-soft);">Mfumo utamtaka mteja alipe zaidi ya kiasi anachochagua, ili baada ya ada hii kukatwa, kiasi alichokusudia kibaki kamili.</small>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="active" <?= $g['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $g['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Hifadhi</button>
    </form>
</div>
<?php endforeach; ?>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

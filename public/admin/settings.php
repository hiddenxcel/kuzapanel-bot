<?php

require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/models/PaymentGateway.php';
require_once __DIR__ . '/../../app/models/Admin.php';

Auth::requireLogin();

$error = null;
$success = null;
$accountError = null;
$accountSuccess = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_gateway') {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_account') {
    $adminId = (int) Auth::user()['id'];
    $newUsername = trim($_POST['username'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $admin = Admin::find($adminId);

    if ($newUsername === '') {
        $accountError = 'Jina la mtumiaji haliwezi kuwa tupu.';
    } elseif ($admin === null || !password_verify($currentPassword, $admin['password_hash'])) {
        $accountError = 'Password ya sasa sio sahihi.';
    } elseif ($newPassword !== '' && $newPassword !== $confirmPassword) {
        $accountError = 'Password mpya na uthibitisho wake hazifanani.';
    } elseif ($newPassword !== '' && strlen($newPassword) < 6) {
        $accountError = 'Password mpya lazima iwe na herufi/namba angalau 6.';
    } elseif (!Admin::updateUsername($adminId, $newUsername)) {
        $accountError = 'Jina hilo la mtumiaji linatumika tayari.';
    } else {
        if ($newPassword !== '') {
            Admin::updatePassword($adminId, $newPassword);
        }

        $_SESSION['admin_username'] = $newUsername;
        $accountSuccess = 'Akaunti yako imesasishwa.';
    }
}

$gateways = PaymentGateway::all();
$currentAdmin = Admin::find((int) Auth::user()['id']);

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

<div class="card">
    <h3 style="margin-top:0;">👤 Akaunti Yangu</h3>
    <?php if ($accountError !== null): ?>
        <div class="alert alert-error"><?= htmlspecialchars($accountError) ?></div>
    <?php endif; ?>
    <?php if ($accountSuccess !== null): ?>
        <div class="alert alert-success"><?= htmlspecialchars($accountSuccess) ?></div>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="action" value="update_account">
        <div class="form-group">
            <label>Jina la Mtumiaji (Username)</label>
            <input type="text" name="username" value="<?= htmlspecialchars($currentAdmin['username']) ?>" required>
        </div>
        <div class="form-group">
            <label>Password ya Sasa</label>
            <input type="password" name="current_password" required placeholder="Thibitisha kwa password yako ya sasa">
        </div>
        <div class="form-group">
            <label>Password Mpya (acha tupu kama hautaki kubadilisha)</label>
            <input type="password" name="new_password" minlength="6" placeholder="Angalau herufi/namba 6">
        </div>
        <div class="form-group">
            <label>Thibitisha Password Mpya</label>
            <input type="password" name="confirm_password" minlength="6">
        </div>
        <button type="submit" class="btn btn-primary">Hifadhi Akaunti</button>
    </form>
</div>

<?php foreach ($gateways as $g): ?>
<div class="card">
    <h3 style="margin-top:0;"><?= htmlspecialchars($g['name']) ?> <span class="badge badge-<?= $g['status'] ?>"><?= $g['status'] ?></span></h3>
    <form method="post">
        <input type="hidden" name="action" value="update_gateway">
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

<?php

require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/helpers/Lang.php';
require_once __DIR__ . '/../../app/models/PaymentGateway.php';
require_once __DIR__ . '/../../app/models/Admin.php';
require_once __DIR__ . '/../../app/models/AppSetting.php';

Auth::requireLogin();

$error = null;
$success = null;
$accountError = null;
$accountSuccess = null;
$aiSuccess = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_ai_setting') {
    $enabled = ($_POST['ai_enabled'] ?? '0') === '1';
    AppSetting::set('ai_enabled', $enabled ? '1' : '0');
    $aiSuccess = $enabled ? t('settings.ai_enabled_msg') : t('settings.ai_disabled_msg');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_gateway') {
    $id = (int) ($_POST['id'] ?? 0);
    $data = [
        'api_key' => trim($_POST['api_key'] ?? ''),
        'api_secret' => trim($_POST['api_secret'] ?? ''),
        'fee_percent' => (float) ($_POST['fee_percent'] ?? 0),
        'status' => $_POST['status'] ?? 'inactive',
    ];

    if ($id <= 0) {
        $error = t('settings.gateway_invalid');
    } else {
        PaymentGateway::update($id, $data);
        $success = t('settings.gateway_updated');
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
        $accountError = t('settings.username_empty');
    } elseif ($admin === null || !password_verify($currentPassword, $admin['password_hash'])) {
        $accountError = t('settings.wrong_password');
    } elseif ($newPassword !== '' && $newPassword !== $confirmPassword) {
        $accountError = t('settings.password_mismatch');
    } elseif ($newPassword !== '' && strlen($newPassword) < 6) {
        $accountError = t('settings.password_too_short');
    } elseif (!Admin::updateUsername($adminId, $newUsername)) {
        $accountError = t('settings.username_taken');
    } else {
        if ($newPassword !== '') {
            Admin::updatePassword($adminId, $newPassword);
        }

        $_SESSION['admin_username'] = $newUsername;
        $accountSuccess = t('settings.account_updated');
    }
}

$gateways = PaymentGateway::all();
$currentAdmin = Admin::find((int) Auth::user()['id']);
$aiEnabled = AppSetting::isAiEnabled();

$pageTitle = t('settings.title');
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
    <h3 style="margin-top:0;"><?= t('settings.ai_title') ?> <span class="badge badge-<?= $aiEnabled ? 'active' : 'inactive' ?>"><?= $aiEnabled ? t('settings.ai_active') : t('settings.ai_inactive') ?></span></h3>
    <?php if ($aiSuccess !== null): ?>
        <div class="alert alert-success"><?= htmlspecialchars($aiSuccess) ?></div>
    <?php endif; ?>
    <p style="margin-top:-8px;color:var(--text-soft);font-size:13px;">
        <?= t('settings.ai_hint') ?>
    </p>
    <form method="post">
        <input type="hidden" name="action" value="update_ai_setting">
        <div class="form-group">
            <label><?= t('settings.status') ?></label>
            <select name="ai_enabled">
                <option value="1" <?= $aiEnabled ? 'selected' : '' ?>><?= t('settings.ai_option_active') ?></option>
                <option value="0" <?= !$aiEnabled ? 'selected' : '' ?>><?= t('settings.ai_option_inactive') ?></option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><?= t('settings.save') ?></button>
    </form>
</div>

<div class="card">
    <h3 style="margin-top:0;"><?= t('settings.account_title') ?></h3>
    <?php if ($accountError !== null): ?>
        <div class="alert alert-error"><?= htmlspecialchars($accountError) ?></div>
    <?php endif; ?>
    <?php if ($accountSuccess !== null): ?>
        <div class="alert alert-success"><?= htmlspecialchars($accountSuccess) ?></div>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="action" value="update_account">
        <div class="form-group">
            <label><?= t('settings.username') ?></label>
            <input type="text" name="username" value="<?= htmlspecialchars($currentAdmin['username']) ?>" required>
        </div>
        <div class="form-group">
            <label><?= t('settings.current_password') ?></label>
            <input type="password" name="current_password" required placeholder="<?= t('settings.current_password_placeholder') ?>">
        </div>
        <div class="form-group">
            <label><?= t('settings.new_password') ?></label>
            <input type="password" name="new_password" minlength="6" placeholder="<?= t('settings.new_password_placeholder') ?>">
        </div>
        <div class="form-group">
            <label><?= t('settings.confirm_password') ?></label>
            <input type="password" name="confirm_password" minlength="6">
        </div>
        <button type="submit" class="btn btn-primary"><?= t('settings.save_account') ?></button>
    </form>
</div>

<?php foreach ($gateways as $g): ?>
<div class="card">
    <h3 style="margin-top:0;"><?= htmlspecialchars($g['name']) ?> <span class="badge badge-<?= $g['status'] ?>"><?= $g['status'] ?></span></h3>
    <form method="post">
        <input type="hidden" name="action" value="update_gateway">
        <input type="hidden" name="id" value="<?= $g['id'] ?>">
        <div class="form-group">
            <label><?= t('settings.api_key') ?></label>
            <input type="text" name="api_key" value="<?= htmlspecialchars($g['api_key']) ?>">
        </div>
        <div class="form-group">
            <label><?= t('settings.api_secret') ?></label>
            <input type="text" name="api_secret" value="<?= htmlspecialchars($g['api_secret']) ?>">
        </div>
        <div class="form-group">
            <label><?= t('settings.fee_percent') ?></label>
            <input type="number" name="fee_percent" step="0.01" min="0" max="100" value="<?= htmlspecialchars((string) $g['fee_percent']) ?>">
            <small style="color: var(--text-soft);"><?= t('settings.fee_percent_hint') ?></small>
        </div>
        <div class="form-group">
            <label><?= t('providers.status') ?></label>
            <select name="status">
                <option value="active" <?= $g['status'] === 'active' ? 'selected' : '' ?>><?= t('providers.active') ?></option>
                <option value="inactive" <?= $g['status'] === 'inactive' ? 'selected' : '' ?>><?= t('providers.inactive') ?></option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><?= t('settings.save') ?></button>
    </form>
</div>
<?php endforeach; ?>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

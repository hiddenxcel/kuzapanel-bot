<?php

require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/helpers/Lang.php';
require_once __DIR__ . '/../../app/models/Session.php';

Auth::requireLogin();

$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    $phone = trim($_POST['phone'] ?? '');
    if ($phone !== '') {
        Session::reset($phone);
        $success = t('sessions.reset_success');
    }
}

$sessions = Session::allActive();

$pageTitle = t('sessions.title');
$activeNav = 'sessions';
require __DIR__ . '/includes/layout_header.php';
?>

<?php if ($success !== null): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="mini-stats">
    <div class="mini-stat"><div class="icon total"><i class="fa-solid fa-user-clock"></i></div><div><div class="num"><?= count($sessions) ?></div><div class="lbl"><?= t('sessions.active_count') ?></div></div></div>
</div>

<div class="card">
    <h3 style="margin-top:0;"><?= t('sessions.title') ?></h3>
    <table>
        <tr>
            <th><?= t('sessions.col_phone') ?></th>
            <th><?= t('sessions.col_name') ?></th>
            <th><?= t('sessions.col_state') ?></th>
            <th><?= t('sessions.col_idle') ?></th>
            <th><?= t('sessions.col_updated') ?></th>
            <th><?= t('sessions.col_action') ?></th>
        </tr>
        <?php foreach ($sessions as $s): ?>
        <?php
            $idle = (int) $s['idle_minutes'];
            $idleClass = $idle >= 15 ? 'badge-inactive' : ($idle >= 10 ? 'badge-pending' : 'badge-active');
        ?>
        <tr>
            <td><?= htmlspecialchars($s['customer_phone']) ?></td>
            <td><?= htmlspecialchars($s['customer_name'] ?? '—') ?></td>
            <td><span class="badge badge-pending"><?= htmlspecialchars($s['state']) ?></span></td>
            <td><span class="badge <?= $idleClass ?>"><?= $idle ?> <?= t('sessions.minutes_short') ?></span></td>
            <td><?= htmlspecialchars($s['updated_at']) ?></td>
            <td>
                <form class="inline" method="post" onsubmit="return confirm('<?= t('sessions.reset_confirm') ?>');">
                    <input type="hidden" name="action" value="reset">
                    <input type="hidden" name="phone" value="<?= htmlspecialchars($s['customer_phone']) ?>">
                    <button type="submit" class="btn btn-secondary"><?= t('sessions.reset') ?></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if ($sessions === []): ?>
        <tr><td colspan="6"><?= t('sessions.no_active') ?></td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

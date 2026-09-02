<?php

require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/helpers/Lang.php';
require_once __DIR__ . '/../../app/models/Message.php';
require_once __DIR__ . '/../../app/models/Broadcast.php';
require_once __DIR__ . '/../../app/services/WhatsAppClient.php';

Auth::requireLogin();

$error = null;
$success = null;

$activeCustomers = Message::activeCustomersWithin24h();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_broadcast') {
    $text = trim($_POST['message'] ?? '');

    if ($text === '') {
        $error = t('broadcast.empty_message');
    } elseif ($activeCustomers === []) {
        $error = t('broadcast.no_active_error');
    } else {
        set_time_limit(120);

        $config = require __DIR__ . '/../../config/config.php';
        $whatsapp = new WhatsAppClient($config['whatsapp']);

        $successCount = 0;
        $failedCount = 0;

        foreach ($activeCustomers as $customer) {
            if ($whatsapp->sendText($customer['customer_phone'], $text)) {
                $successCount++;
            } else {
                $failedCount++;
            }
        }

        Broadcast::create($text, count($activeCustomers), $successCount, $failedCount, Auth::user()['username'] ?? null);

        $success = t('broadcast.sent_summary') . " {$successCount} " . t('broadcast.succeeded') . " {$failedCount} " . t('broadcast.failed_word');
        $activeCustomers = Message::activeCustomersWithin24h();
    }
}

$history = Broadcast::all();

$pageTitle = t('broadcast.title');
$activeNav = 'broadcast';
require __DIR__ . '/includes/layout_header.php';
?>

<?php if ($error !== null): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success !== null): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="mini-stats">
    <div class="mini-stat"><div class="icon active"><i class="fa-solid fa-user-check"></i></div><div><div class="num"><?= count($activeCustomers) ?></div><div class="lbl"><?= t('broadcast.active_now') ?></div></div></div>
    <div class="mini-stat"><div class="icon total"><i class="fa-solid fa-bullhorn"></i></div><div><div class="num"><?= count($history) ?></div><div class="lbl"><?= t('broadcast.history') ?></div></div></div>
</div>

<div class="card">
    <h3 style="margin-top:0;"><?= t('broadcast.send_title') ?></h3>
    <div class="alert" style="background:var(--amber-soft);color:var(--amber);">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>
            <?= t('broadcast.window_notice_1') ?> <strong><?= t('broadcast.window_notice_2') ?></strong><?= t('broadcast.window_notice_3') ?>
            <strong><?= count($activeCustomers) ?> <?= t('broadcast.window_notice_4') ?></strong>.
            <?= t('broadcast.window_notice_5') ?>
        </span>
    </div>
    <form method="post">
        <input type="hidden" name="action" value="send_broadcast">
        <div class="form-group">
            <label><?= t('broadcast.message_label') ?></label>
            <textarea name="message" rows="5" required placeholder="<?= t('broadcast.message_placeholder') ?>"></textarea>
        </div>
        <button type="submit" class="btn btn-primary" <?= $activeCustomers === [] ? 'disabled' : '' ?>>
            <i class="fa-solid fa-paper-plane"></i> <?= t('broadcast.send_to') ?> <?= count($activeCustomers) ?> <?= t('broadcast.customers_suffix') ?>
        </button>
    </form>
</div>

<div class="card">
    <h3 style="margin-top:0;"><?= t('broadcast.active_now') ?> (<?= count($activeCustomers) ?>)</h3>
    <table>
        <tr><th><?= t('broadcast.col_name') ?></th><th><?= t('broadcast.col_phone') ?></th></tr>
        <?php foreach ($activeCustomers as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c['customer_name'] ?: '—') ?></td>
            <td><?= htmlspecialchars($c['customer_phone']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($activeCustomers === []): ?>
        <tr><td colspan="2"><?= t('broadcast.no_active') ?></td></tr>
        <?php endif; ?>
    </table>
</div>

<div class="card">
    <h3 style="margin-top:0;"><?= t('broadcast.history') ?></h3>
    <table>
        <tr><th><?= t('broadcast.col_message') ?></th><th><?= t('broadcast.col_targets') ?></th><th><?= t('broadcast.col_success') ?></th><th><?= t('broadcast.col_failed') ?></th><th><?= t('broadcast.col_admin') ?></th><th><?= t('broadcast.col_date') ?></th></tr>
        <?php foreach ($history as $b): ?>
        <tr>
            <td style="max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($b['message']) ?>">
                <?= htmlspecialchars(mb_substr($b['message'], 0, 60)) ?><?= mb_strlen($b['message']) > 60 ? '…' : '' ?>
            </td>
            <td><?= (int) $b['recipient_count'] ?></td>
            <td style="color:var(--green);font-weight:700;"><?= (int) $b['success_count'] ?></td>
            <td style="color:<?= $b['failed_count'] > 0 ? 'var(--red)' : 'var(--text-soft)' ?>;font-weight:700;"><?= (int) $b['failed_count'] ?></td>
            <td><?= htmlspecialchars($b['created_by'] ?: '—') ?></td>
            <td style="white-space:nowrap;color:var(--text-soft);font-size:12.5px;"><?= htmlspecialchars($b['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($history === []): ?>
        <tr><td colspan="6"><?= t('broadcast.no_history') ?></td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

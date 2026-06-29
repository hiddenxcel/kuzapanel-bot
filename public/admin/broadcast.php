<?php

require_once __DIR__ . '/../../app/helpers/Auth.php';
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
        $error = 'Tafadhali andika ujumbe wa tangazo.';
    } elseif ($activeCustomers === []) {
        $error = 'Hakuna mteja aliye-active kwa sasa (saa 24 zilizopita).';
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

        $success = "Tangazo limetumwa: {$successCount} zimefanikiwa, {$failedCount} zimeshindikana.";
        $activeCustomers = Message::activeCustomersWithin24h();
    }
}

$history = Broadcast::all();

$pageTitle = 'Matangazo';
$activeNav = 'broadcast';
require __DIR__ . '/includes/layout_header.php';
?>

<?php if ($error !== null): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success !== null): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="card">
    <h3 style="margin-top:0;">📢 Tuma Tangazo</h3>
    <p style="margin-top:-8px;color:var(--text-soft);font-size:13px;">
        ⚠️ WhatsApp inaruhusu ujumbe wa moja kwa moja (bila template) tu kwa wateja walio-active
        ndani ya <strong>saa 24</strong> zilizopita. Kwa sasa hii itafika kwa
        <strong style="color:var(--text);"><?= count($activeCustomers) ?> wateja</strong>.
        Kufikia wateja wote (waliolala), tunahitaji WhatsApp Message Template iliyoidhinishwa na Meta — hatua tofauti.
    </p>
    <form method="post">
        <input type="hidden" name="action" value="send_broadcast">
        <div class="form-group">
            <label>Ujumbe wa Tangazo</label>
            <textarea name="message" rows="5" required placeholder="Andika tangazo lako hapa..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary" <?= $activeCustomers === [] ? 'disabled' : '' ?>>
            <i class="fa-solid fa-paper-plane"></i> Tuma kwa <?= count($activeCustomers) ?> Wateja
        </button>
    </form>
</div>

<div class="card">
    <h3 style="margin-top:0;">Wateja Walio-Active Sasa (<?= count($activeCustomers) ?>)</h3>
    <table>
        <tr><th>Jina</th><th>Phone</th></tr>
        <?php foreach ($activeCustomers as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c['customer_name'] ?: '—') ?></td>
            <td><?= htmlspecialchars($c['customer_phone']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($activeCustomers === []): ?>
        <tr><td colspan="2">Hakuna mteja aliye-active kwa sasa.</td></tr>
        <?php endif; ?>
    </table>
</div>

<div class="card">
    <h3 style="margin-top:0;">Historia ya Matangazo</h3>
    <table>
        <tr><th>Ujumbe</th><th>Walengwa</th><th>Mafanikio</th><th>Yameshindikana</th><th>Admin</th><th>Tarehe</th></tr>
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
        <tr><td colspan="6">Hakuna matangazo yaliyotumwa bado.</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

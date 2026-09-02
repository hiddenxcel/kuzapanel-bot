<?php

require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/helpers/Lang.php';
require_once __DIR__ . '/../../app/models/AppSetting.php';
require_once __DIR__ . '/../../app/models/Message.php';
require_once __DIR__ . '/../../app/services/WhatsAppClient.php';

Auth::requireLogin();

$config = require __DIR__ . '/../../config/config.php';

$aiEnabled = AppSetting::isAiEnabled();
$maintenanceEnabled = AppSetting::isMaintenanceEnabled();
$messagesToday = Message::countForDate(date('Y-m-d'));
$lastInboundAt = Message::lastInboundAt();

$whatsapp = new WhatsAppClient($config['whatsapp']);
$tokenCheck = $whatsapp->checkTokenValidity();

$pageTitle = t('health.title');
$activeNav = 'health';
require __DIR__ . '/includes/layout_header.php';
?>

<div class="mini-stats">
    <div class="mini-stat">
        <div class="icon <?= $tokenCheck['valid'] ? 'active' : 'inactive' ?>"><i class="fa-solid fa-key"></i></div>
        <div>
            <div class="num" style="font-size:14px;"><?= $tokenCheck['valid'] ? t('health.token_valid') : t('health.token_invalid') ?></div>
            <div class="lbl"><?= t('health.token_label') ?></div>
        </div>
    </div>
    <div class="mini-stat">
        <div class="icon <?= $maintenanceEnabled ? 'inactive' : 'active' ?>"><i class="fa-solid fa-power-off"></i></div>
        <div>
            <div class="num" style="font-size:14px;"><?= $maintenanceEnabled ? t('health.maintenance_on') : t('health.maintenance_off') ?></div>
            <div class="lbl"><?= t('health.maintenance_label') ?></div>
        </div>
    </div>
    <div class="mini-stat">
        <div class="icon <?= $aiEnabled ? 'active' : 'inactive' ?>"><i class="fa-solid fa-robot"></i></div>
        <div>
            <div class="num" style="font-size:14px;"><?= $aiEnabled ? t('health.ai_on') : t('health.ai_off') ?></div>
            <div class="lbl"><?= t('health.ai_label') ?></div>
        </div>
    </div>
    <div class="mini-stat">
        <div class="icon total"><i class="fa-solid fa-comments"></i></div>
        <div>
            <div class="num"><?= $messagesToday ?></div>
            <div class="lbl"><?= t('health.messages_today_label') ?></div>
        </div>
    </div>
</div>

<div class="card">
    <h3 style="margin-top:0;"><?= t('health.details_title') ?></h3>
    <table>
        <tr>
            <td><?= t('health.token_label') ?></td>
            <td>
                <span class="badge <?= $tokenCheck['valid'] ? 'badge-active' : 'badge-inactive' ?>"><?= $tokenCheck['valid'] ? t('health.token_valid') : t('health.token_invalid') ?></span>
                <?php if (!$tokenCheck['valid']): ?>
                    <span style="color:var(--text-soft);font-size:12.5px;"> — <?= htmlspecialchars($tokenCheck['detail']) ?></span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td><?= t('health.maintenance_label') ?></td>
            <td><span class="badge <?= $maintenanceEnabled ? 'badge-inactive' : 'badge-active' ?>"><?= $maintenanceEnabled ? t('health.maintenance_on') : t('health.maintenance_off') ?></span></td>
        </tr>
        <tr>
            <td><?= t('health.ai_label') ?></td>
            <td><span class="badge <?= $aiEnabled ? 'badge-active' : 'badge-inactive' ?>"><?= $aiEnabled ? t('health.ai_on') : t('health.ai_off') ?></span></td>
        </tr>
        <tr>
            <td><?= t('health.messages_today_label') ?></td>
            <td><?= $messagesToday ?></td>
        </tr>
        <tr>
            <td><?= t('health.last_inbound_label') ?></td>
            <td><?= $lastInboundAt !== null ? htmlspecialchars($lastInboundAt) : t('health.no_messages_yet') ?></td>
        </tr>
    </table>
</div>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

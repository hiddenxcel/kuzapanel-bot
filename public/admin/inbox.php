<?php

require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/models/Message.php';
require_once __DIR__ . '/../../app/models/Customer.php';

Auth::requireLogin();

$customers = Message::recentCustomers();

$activePhone = trim($_GET['phone'] ?? '');
$activeCustomer = null;
$thread = [];

if ($activePhone !== '') {
    $activeCustomer = Customer::findByPhone($activePhone);
    $thread = Message::byCustomer($activePhone);
}

$pageTitle = 'Inbox';
$activeNav = 'inbox';
require __DIR__ . '/includes/layout_header.php';
?>

<style>
    .inbox-wrap { display:flex; gap:0; border:1px solid var(--border); border-radius:var(--radius); background:var(--card); overflow:hidden; height:calc(100vh - 160px); min-height:480px; }
    .inbox-list { width:300px; flex-shrink:0; border-right:1px solid var(--border); overflow-y:auto; }
    .inbox-list a { display:block; padding:14px 16px; border-bottom:1px solid var(--border); text-decoration:none; color:var(--text); transition:background .12s ease; }
    .inbox-list a:hover { background:#fafbff; }
    .inbox-list a.active { background:var(--primary-soft); }
    .inbox-list .il-name { font-weight:700; font-size:13.5px; margin-bottom:2px; }
    .inbox-list .il-phone { font-size:11.5px; color:var(--text-soft); margin-bottom:4px; }
    .inbox-list .il-snippet { font-size:12px; color:var(--text-soft); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .inbox-list .il-empty { padding:24px 16px; color:var(--text-soft); font-size:13px; }

    .inbox-thread { flex:1; display:flex; flex-direction:column; min-width:0; }
    .inbox-thread-header { padding:14px 18px; border-bottom:1px solid var(--border); font-weight:700; font-size:14px; }
    .inbox-thread-header .th-phone { color:var(--text-soft); font-weight:500; font-size:12.5px; }
    .inbox-thread-body { flex:1; overflow-y:auto; padding:18px; background:#f9fafc; display:flex; flex-direction:column; gap:10px; }
    .inbox-thread-empty { display:flex; align-items:center; justify-content:center; flex:1; color:var(--text-soft); font-size:13.5px; }

    .msg-bubble { max-width:62%; padding:9px 13px; border-radius:14px; font-size:13.5px; line-height:1.5; white-space:pre-wrap; word-break:break-word; }
    .msg-bubble.in { align-self:flex-start; background:#fff; border:1px solid var(--border); border-bottom-left-radius:4px; }
    .msg-bubble.out { align-self:flex-end; background:var(--green-soft); border-bottom-right-radius:4px; }
    .msg-time { font-size:10.5px; color:var(--text-soft); margin-top:4px; display:block; text-align:right; }

    @media (max-width:900px) {
        .inbox-wrap { flex-direction:column; height:auto; }
        .inbox-list { width:100%; border-right:none; border-bottom:1px solid var(--border); max-height:280px; }
        .inbox-thread { min-height:420px; }
    }
</style>

<div class="inbox-wrap">
    <div class="inbox-list">
        <?php if ($customers === []): ?>
            <div class="il-empty">Hakuna mazungumzo bado.</div>
        <?php endif; ?>
        <?php foreach ($customers as $c): ?>
            <a href="inbox.php?phone=<?= urlencode($c['customer_phone']) ?>" class="<?= $c['customer_phone'] === $activePhone ? 'active' : '' ?>">
                <div class="il-name"><?= htmlspecialchars($c['customer_name'] ?: $c['customer_phone']) ?></div>
                <div class="il-phone"><?= htmlspecialchars($c['customer_phone']) ?></div>
                <div class="il-snippet"><?= $c['last_direction'] === 'out' ? '↪ ' : '' ?><?= htmlspecialchars($c['last_body']) ?></div>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="inbox-thread">
        <?php if ($activeCustomer !== null || $activePhone !== ''): ?>
            <div class="inbox-thread-header">
                <?= htmlspecialchars($activeCustomer['name'] ?? $activePhone) ?>
                <span class="th-phone">· <?= htmlspecialchars($activePhone) ?></span>
            </div>
            <div class="inbox-thread-body">
                <?php if ($thread === []): ?>
                    <div class="inbox-thread-empty">Hakuna ujumbe bado kwa mteja huyu.</div>
                <?php endif; ?>
                <?php foreach ($thread as $m): ?>
                    <div class="msg-bubble <?= $m['direction'] ?>">
                        <?= nl2br(htmlspecialchars($m['body'])) ?>
                        <span class="msg-time"><?= htmlspecialchars($m['created_at']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="inbox-thread-empty">Chagua mteja upande wa kushoto kuona mazungumzo.</div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

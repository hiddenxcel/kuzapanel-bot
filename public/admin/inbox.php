<?php

require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/helpers/Lang.php';
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

/**
 * Compact, locale-agnostic "time ago" for chat timestamps.
 * Returns a short label (e.g. "5m", "3h", "Jul 12") from a datetime string.
 */
function inbox_time_ago(?string $datetime): string
{
    if ($datetime === null || $datetime === '') {
        return '';
    }

    $ts = strtotime($datetime);
    if ($ts === false) {
        return htmlspecialchars($datetime);
    }

    $diff = time() - $ts;

    if ($diff < 60) {
        return t('inbox.now');
    }
    if ($diff < 3600) {
        return floor($diff / 60) . 'm';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . 'h';
    }
    // Compare calendar days, not elapsed hours: a message sent at 23:00 is
    // "Jana" at 01:00 the next morning, not "1d".
    $daysAgo = (int) (new DateTime(date('Y-m-d')))->diff(new DateTime(date('Y-m-d', $ts)))->days;
    if ($daysAgo === 1) {
        return t('inbox.yesterday');
    }
    if ($daysAgo < 7) {
        return $daysAgo . 'd';
    }

    return date('M j', $ts);
}

/** Day heading for the thread (Today / Yesterday / date). */
function inbox_day_label(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '';
    }

    $day = date('Y-m-d', $ts);
    if ($day === date('Y-m-d')) {
        return t('inbox.today');
    }
    if ($day === date('Y-m-d', strtotime('-1 day'))) {
        return t('inbox.yesterday');
    }

    return date('M j, Y', $ts);
}

/** Two-letter initials from a name or phone number. */
function inbox_initials(string $label): string
{
    $label = trim($label);
    if ($label === '') {
        return '?';
    }
    if (ctype_digit(str_replace([' ', '+'], '', $label))) {
        // Phone number — use the last two digits.
        $digits = preg_replace('/\D/', '', $label);
        return strtoupper(substr($digits, -2)) ?: '#';
    }
    $parts = preg_split('/\s+/', $label);
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
    }
    return strtoupper(substr($label, 0, 2));
}

/** Deterministic avatar hue from a string, so each customer keeps one colour. */
function inbox_hue(string $seed): int
{
    return crc32($seed) % 360;
}

$pageTitle = t('inbox.title');
$activeNav = 'inbox';
require __DIR__ . '/includes/layout_header.php';

$hasActive = ($activeCustomer !== null || $activePhone !== '');
$activeName = $activeCustomer['name'] ?? '';
$activeDisplay = $activeName !== '' ? $activeName : $activePhone;
?>

<style>
    /* ===== Inbox shell ===== */
    .inbox-wrap {
        display: flex;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        background: var(--card);
        overflow: hidden;
        height: calc(100vh - 150px);
        min-height: 520px;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }

    /* ===== Conversation list ===== */
    .inbox-list { width: 330px; flex-shrink: 0; border-right: 1px solid var(--border); display: flex; flex-direction: column; min-width: 0; }
    .il-head { padding: 16px 18px 12px; border-bottom: 1px solid var(--border); }
    .il-head .il-title { font-size: 15px; font-weight: 800; letter-spacing: -0.01em; display: flex; align-items: center; gap: 8px; }
    .il-head .il-title .il-count { font-size: 11px; font-weight: 700; color: var(--primary); background: var(--primary-soft); border-radius: 999px; padding: 2px 9px; }
    .il-search { position: relative; margin-top: 12px; }
    .il-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-soft); font-size: 12.5px; pointer-events: none; }
    .il-search input {
        width: 100%; padding: 9px 12px 9px 33px;
        border: 1px solid var(--border); border-radius: 10px;
        font-size: 13px; font-family: inherit; background: #fcfcfe;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .il-search input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); background: #fff; }

    .il-scroll { overflow-y: auto; flex: 1; }
    .il-item {
        display: flex; align-items: center; gap: 12px;
        padding: 12px 16px; border-bottom: 1px solid var(--border);
        text-decoration: none; color: var(--text);
        transition: background .12s ease;
    }
    .il-item:hover { background: #fafbff; }
    .il-item.active { background: var(--primary-soft); }
    .il-item.active .il-name { color: var(--primary-dark); }

    .il-avatar {
        width: 42px; height: 42px; border-radius: 50%; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; font-weight: 700; color: #fff; letter-spacing: 0.02em;
    }
    .il-body { min-width: 0; flex: 1; }
    .il-row1 { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; }
    .il-name { font-weight: 700; font-size: 13.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .il-time { font-size: 11px; color: var(--text-soft); flex-shrink: 0; font-weight: 600; }
    .il-snippet { font-size: 12.5px; color: var(--text-soft); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
    .il-snippet .il-out-tick { color: var(--green); font-weight: 700; }
    .il-empty { padding: 40px 20px; color: var(--text-soft); font-size: 13px; text-align: center; }
    .il-empty i { display: block; font-size: 26px; margin-bottom: 10px; opacity: .5; }

    /* ===== Thread ===== */
    .inbox-thread { flex: 1; display: flex; flex-direction: column; min-width: 0; background: #f9fafc; }
    .it-head {
        display: flex; align-items: center; gap: 12px;
        padding: 12px 18px; border-bottom: 1px solid var(--border); background: var(--card);
    }
    .it-back {
        display: none; align-items: center; justify-content: center;
        width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
        border: 1px solid var(--border); background: var(--card); color: var(--text);
        text-decoration: none; font-size: 14px;
    }
    .it-back:hover { background: var(--primary-soft); color: var(--primary); }
    .it-avatar {
        width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 13.5px; font-weight: 700; color: #fff;
    }
    .it-meta { min-width: 0; }
    .it-name { font-weight: 700; font-size: 14.5px; letter-spacing: -0.01em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .it-phone { font-size: 12px; color: var(--text-soft); font-weight: 500; }

    .it-body { flex: 1; overflow-y: auto; padding: 20px 18px; display: flex; flex-direction: column; gap: 3px; }

    .day-sep { align-self: center; margin: 12px 0 8px; }
    .day-sep span { background: #e9edf5; color: var(--text-soft); font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 999px; letter-spacing: 0.02em; }

    .msg-bubble {
        max-width: 66%; padding: 9px 13px 7px; border-radius: 16px;
        font-size: 13.5px; line-height: 1.5; white-space: pre-wrap; word-break: break-word;
        position: relative; box-shadow: 0 1px 1px rgba(15,23,42,0.05); margin-top: 4px;
    }
    .msg-bubble.in { align-self: flex-start; background: #fff; border-bottom-left-radius: 5px; }
    .msg-bubble.out { align-self: flex-end; background: #d9fdd3; border-bottom-right-radius: 5px; }
    /* Consecutive bubbles from the same side hug closer */
    .msg-bubble.in + .msg-bubble.in,
    .msg-bubble.out + .msg-bubble.out { margin-top: 2px; }
    .msg-meta { display: flex; align-items: center; gap: 4px; justify-content: flex-end; margin-top: 3px; }
    .msg-time { font-size: 10px; color: #8696a0; }
    .msg-bubble.out .msg-time { color: #667781; }
    .msg-tick { font-size: 10px; color: #53bdeb; }

    .it-empty { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-soft); font-size: 13.5px; text-align: center; padding: 24px; }
    .it-empty .it-empty-icon {
        width: 72px; height: 72px; border-radius: 50%; background: var(--primary-soft); color: var(--primary);
        display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 16px;
    }
    .it-empty .it-empty-title { font-size: 16px; font-weight: 700; color: var(--text); margin-bottom: 6px; }

    /* ===== Responsive: list ⇄ thread swap ===== */
    @media (max-width: 780px) {
        .inbox-wrap { height: calc(100vh - 130px); }
        .inbox-list { width: 100%; }
        .inbox-thread { width: 100%; }
        /* When a conversation is open, show only the thread; otherwise only the list. */
        .inbox-wrap[data-view="thread"] .inbox-list { display: none; }
        .inbox-wrap[data-view="list"] .inbox-thread { display: none; }
        .it-back { display: flex; }
        .msg-bubble { max-width: 82%; }
    }
</style>

<div class="inbox-wrap" data-view="<?= $hasActive ? 'thread' : 'list' ?>">
    <div class="inbox-list">
        <div class="il-head">
            <div class="il-title">
                <?= t('inbox.conversations') ?>
                <?php if ($customers !== []): ?><span class="il-count"><?= count($customers) ?></span><?php endif; ?>
            </div>
            <div class="il-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="inboxSearch" placeholder="<?= htmlspecialchars(t('inbox.search_placeholder')) ?>" autocomplete="off">
            </div>
        </div>

        <div class="il-scroll" id="inboxScroll">
            <?php if ($customers === []): ?>
                <div class="il-empty"><i class="fa-regular fa-comments"></i><?= t('inbox.no_conversations') ?></div>
            <?php endif; ?>

            <?php foreach ($customers as $c):
                $name = $c['customer_name'] ?: $c['customer_phone'];
                $hue = inbox_hue($c['customer_phone']);
            ?>
                <a href="inbox.php?phone=<?= urlencode($c['customer_phone']) ?>"
                   class="il-item <?= $c['customer_phone'] === $activePhone ? 'active' : '' ?>"
                   data-search="<?= htmlspecialchars(strtolower($name . ' ' . $c['customer_phone'])) ?>">
                    <div class="il-avatar" style="background: hsl(<?= $hue ?>, 55%, 52%)"><?= htmlspecialchars(inbox_initials($name)) ?></div>
                    <div class="il-body">
                        <div class="il-row1">
                            <span class="il-name"><?= htmlspecialchars($name) ?></span>
                            <span class="il-time"><?= htmlspecialchars(inbox_time_ago($c['last_at'] ?? null)) ?></span>
                        </div>
                        <div class="il-snippet">
                            <?php if ($c['last_direction'] === 'out'): ?><span class="il-out-tick">✓ </span><?php endif; ?>
                            <?= htmlspecialchars($c['last_body']) ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>

            <div class="il-empty" id="inboxNoResults" style="display:none;">
                <i class="fa-solid fa-magnifying-glass"></i><?= t('inbox.no_results') ?>
            </div>
        </div>
    </div>

    <div class="inbox-thread">
        <?php if ($hasActive): ?>
            <?php $hue = inbox_hue($activePhone); ?>
            <div class="it-head">
                <a href="inbox.php" class="it-back" aria-label="<?= htmlspecialchars(t('inbox.back')) ?>"><i class="fa-solid fa-arrow-left"></i></a>
                <div class="it-avatar" style="background: hsl(<?= $hue ?>, 55%, 52%)"><?= htmlspecialchars(inbox_initials($activeDisplay)) ?></div>
                <div class="it-meta">
                    <div class="it-name"><?= htmlspecialchars($activeDisplay) ?></div>
                    <div class="it-phone"><?= htmlspecialchars($activePhone) ?></div>
                </div>
            </div>

            <div class="it-body" id="threadBody">
                <?php if ($thread === []): ?>
                    <div class="it-empty">
                        <div class="it-empty-icon"><i class="fa-regular fa-comment-dots"></i></div>
                        <div class="it-empty-title"><?= t('inbox.no_messages') ?></div>
                    </div>
                <?php else: ?>
                    <?php $lastDay = null; ?>
                    <?php foreach ($thread as $m): ?>
                        <?php $day = inbox_day_label($m['created_at']); ?>
                        <?php if ($day !== $lastDay): $lastDay = $day; ?>
                            <div class="day-sep"><span><?= htmlspecialchars($day) ?></span></div>
                        <?php endif; ?>
                        <div class="msg-bubble <?= $m['direction'] === 'out' ? 'out' : 'in' ?>">
                            <?= nl2br(htmlspecialchars($m['body'])) ?>
                            <div class="msg-meta">
                                <span class="msg-time"><?= htmlspecialchars(date('H:i', strtotime($m['created_at']))) ?></span>
                                <?php if ($m['direction'] === 'out'): ?><span class="msg-tick"><i class="fa-solid fa-check-double"></i></span><?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="it-empty">
                <div class="it-empty-icon"><i class="fa-regular fa-comments"></i></div>
                <div class="it-empty-title"><?= t('inbox.select_customer') ?></div>
                <div><?= t('inbox.select_hint') ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Live filter of the conversation list.
    (function () {
        var search = document.getElementById('inboxSearch');
        var noResults = document.getElementById('inboxNoResults');
        if (!search) return;

        search.addEventListener('input', function () {
            var q = this.value.trim().toLowerCase();
            var items = document.querySelectorAll('.il-item');
            var visible = 0;
            items.forEach(function (el) {
                var match = el.getAttribute('data-search').indexOf(q) !== -1;
                el.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            if (noResults) noResults.style.display = (visible === 0 && items.length > 0) ? '' : 'none';
        });
    })();

    // Keep the thread scrolled to the latest message.
    (function () {
        var body = document.getElementById('threadBody');
        if (body) body.scrollTop = body.scrollHeight;
    })();
</script>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

<?php

require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/helpers/Lang.php';
require_once __DIR__ . '/../../app/models/Service.php';
require_once __DIR__ . '/../../app/models/Provider.php';

Auth::requireLogin();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $unitLabel = $_POST['unit_label'] ?? '';
        if ($unitLabel === 'custom') {
            $unitLabel = trim($_POST['unit_label_custom'] ?? '');
        }

        $platform = $_POST['platform'] ?? '';
        if ($platform === 'custom') {
            $platform = trim($_POST['platform_custom'] ?? '');
        }

        $category = $_POST['category'] ?? '';
        if ($category === 'custom') {
            $category = trim($_POST['category_custom'] ?? '');
        }

        $data = [
            'provider_id' => (int) ($_POST['provider_id'] ?? 0),
            'provider_service_id' => trim($_POST['provider_service_id'] ?? ''),
            'platform' => trim($platform),
            'category' => trim($category) ?: null,
            'name_sw' => trim($_POST['name_sw'] ?? ''),
            'name_en' => trim($_POST['name_en'] ?? ''),
            'unit_label' => $unitLabel,
            'cost_price' => $_POST['cost_price'] ?? '',
            'my_price' => $_POST['my_price'] ?? '',
            'min_quantity' => (int) ($_POST['min_quantity'] ?? 1),
            'max_quantity' => (int) ($_POST['max_quantity'] ?? 1),
            'link_instructions' => trim($_POST['link_instructions'] ?? '') ?: null,
            'link_instructions_image' => trim($_POST['link_instructions_image'] ?? '') ?: null,
            'status' => $_POST['status'] ?? 'active',
        ];

        if (
            $data['provider_id'] === 0 || $data['provider_service_id'] === ''
            || $data['platform'] === '' || ($data['name_sw'] === '' && $data['name_en'] === '') || $data['unit_label'] === ''
            || $data['cost_price'] === '' || $data['my_price'] === ''
        ) {
            $error = t('services.fill_all');
        } elseif ($action === 'create') {
            Service::create($data);
            $success = t('services.added');
        } else {
            Service::update((int) $_POST['id'], $data);
            $success = t('services.updated');
        }
    } elseif ($action === 'delete') {
        Service::delete((int) $_POST['id']);
        $success = t('services.deleted');
    } elseif ($action === 'move_up') {
        Service::moveUp((int) $_POST['id']);
    } elseif ($action === 'move_down') {
        Service::moveDown((int) $_POST['id']);
    } elseif ($action === 'bulk_activate' || $action === 'bulk_deactivate') {
        $ids = array_map('intval', $_POST['ids'] ?? []);
        $status = $action === 'bulk_activate' ? 'active' : 'inactive';
        $count = Service::bulkSetStatus($ids, $status);
        $success = t('services.bulk_status_done') . " ({$count})";
    } elseif ($action === 'bulk_delete') {
        $ids = array_map('intval', $_POST['ids'] ?? []);
        $count = Service::bulkDelete($ids);
        $success = t('services.bulk_delete_done') . " ({$count})";
    } elseif ($action === 'bulk_price_adjust') {
        $ids = array_map('intval', $_POST['ids'] ?? []);
        $percent = (float) ($_POST['percent'] ?? 0);
        if ($percent === 0.0 || $ids === []) {
            $error = t('services.bulk_price_invalid');
        } else {
            $count = Service::bulkAdjustPrice($ids, $percent);
            $success = t('services.bulk_price_done') . " ({$count})";
        }
    }
}

$services = Service::all();
$providers = Provider::all();
$platforms = Service::allPlatforms();
$categories = Service::allCategories();
$stats = Service::stats();
$editing = null;

if (isset($_GET['edit'])) {
    $editing = Service::find((int) $_GET['edit']);
}

$providerNames = [];
foreach ($providers as $p) {
    $providerNames[$p['id']] = $p['name'];
}

// Group services by platform, then by category, preserving sort_order.
$grouped = [];
foreach ($services as $s) {
    $platform = $s['platform'];
    $cat = ($s['category'] !== null && $s['category'] !== '') ? $s['category'] : '__none__';
    $grouped[$platform][$cat][] = $s;
}

$pageTitle = t('services.title');
$activeNav = 'services';
require __DIR__ . '/includes/layout_header.php';
?>

<style>
    /* ---- Platform / category grouping (unique to this page) ---- */
    .platform-section { margin-bottom: 22px; }
    .platform-head { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
    .platform-head h3 { margin: 0; font-size: 16px; font-weight: 800; }
    .platform-head .count { font-size: 12px; color: var(--text-soft); font-weight: 600; background: #f1f2f8; padding: 3px 10px; border-radius: 999px; }
    .category-label { font-size: 12px; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: .04em; margin: 14px 0 8px; display: flex; align-items: center; gap: 6px; }
    .category-label::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--primary); }
</style>

<?php if ($error !== null): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success !== null): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="mini-stats">
    <div class="mini-stat"><div class="icon total"><i class="fa-solid fa-layer-group"></i></div><div><div class="num"><?= $stats['total'] ?></div><div class="lbl"><?= t('services.stat_total') ?></div></div></div>
    <div class="mini-stat"><div class="icon active"><i class="fa-solid fa-circle-check"></i></div><div><div class="num"><?= $stats['active'] ?></div><div class="lbl"><?= t('services.stat_active') ?></div></div></div>
    <div class="mini-stat"><div class="icon inactive"><i class="fa-solid fa-circle-xmark"></i></div><div><div class="num"><?= $stats['inactive'] ?></div><div class="lbl"><?= t('services.stat_inactive') ?></div></div></div>
    <div class="mini-stat"><div class="icon platforms"><i class="fa-solid fa-hashtag"></i></div><div><div class="num"><?= $stats['platforms'] ?></div><div class="lbl"><?= t('services.stat_platforms') ?></div></div></div>
</div>

<div class="toolbar">
    <input type="text" id="serviceSearch" placeholder="<?= t('services.search_placeholder') ?>">
    <select id="platformFilter">
        <option value=""><?= t('services.filter_all_platforms') ?></option>
        <?php foreach ($platforms as $p): ?>
            <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
        <?php endforeach; ?>
    </select>
    <select id="statusFilter">
        <option value=""><?= t('services.filter_all_status') ?></option>
        <option value="active"><?= t('providers.active') ?></option>
        <option value="inactive"><?= t('providers.inactive') ?></option>
    </select>
    <div class="spacer"></div>
    <button type="button" class="btn btn-primary" id="openAddModal"><i class="fa-solid fa-plus"></i> <?= t('services.add') ?></button>
</div>

<?php if ($providers === []): ?>
    <div class="card">
        <p><?= t('services.add_provider_first') ?> <a href="providers.php"><?= t('services.add_provider_link') ?></a> <?= t('services.add_provider_suffix') ?></p>
    </div>
<?php endif; ?>

<p style="color:var(--text-soft);font-size:13px;margin:-8px 0 16px;"><?= t('services.reorder_hint') ?></p>

<div id="servicesRoot">
<?php foreach ($grouped as $platform => $cats): ?>
    <?php $platformCount = array_sum(array_map('count', $cats)); ?>
    <div class="platform-section">
        <div class="platform-head">
            <h3><?= htmlspecialchars($platform) ?></h3>
            <span class="count"><?= $platformCount ?> <?= t('services.services_word') ?></span>
        </div>
        <?php foreach ($cats as $catKey => $items): ?>
            <?php if ($catKey !== '__none__'): ?>
                <div class="category-label"><?= htmlspecialchars($catKey) ?></div>
            <?php endif; ?>
            <div class="item-grid">
                <?php foreach ($items as $s):
                    $globalIndex = null;
                    foreach ($services as $idx => $svc) { if ($svc['id'] === $s['id']) { $globalIndex = $idx; break; } }
                    $canMoveUp = $globalIndex > 0 && $services[$globalIndex - 1]['platform'] === $s['platform'];
                    $canMoveDown = $globalIndex < count($services) - 1 && $services[$globalIndex + 1]['platform'] === $s['platform'];
                ?>
                <div class="item-card <?= $s['status'] === 'inactive' ? 'inactive' : '' ?>"
                     data-id="<?= $s['id'] ?>"
                     data-platform="<?= htmlspecialchars($s['platform']) ?>"
                     data-status="<?= $s['status'] ?>"
                     data-search="<?= htmlspecialchars(mb_strtolower($s['name_sw'] . ' ' . $s['name_en'] . ' ' . $s['platform'] . ' ' . ($s['category'] ?? ''))) ?>">
                    <div class="item-card-top">
                        <input type="checkbox" class="item-card-check" data-id="<?= $s['id'] ?>">
                        <div class="item-card-title">#<?= $s['id'] ?> — <?= htmlspecialchars($s['name_sw']) ?> <span style="color:var(--text-soft);font-weight:500;">/ <?= htmlspecialchars($s['name_en']) ?></span></div>
                        <span class="badge badge-<?= $s['status'] ?>"><?= $s['status'] ?></span>
                    </div>
                    <div class="item-card-meta">
                        <span><?= htmlspecialchars($providerNames[$s['provider_id']] ?? '—') ?></span>
                        <span><?= htmlspecialchars($s['unit_label']) ?></span>
                        <span><?= $s['min_quantity'] ?>–<?= $s['max_quantity'] ?></span>
                    </div>
                    <div class="item-card-price"><?= number_format((float) $s['my_price'], 2) ?> <small><?= t('services.per_1000_short') ?></small></div>
                    <div class="item-card-bottom">
                        <div class="item-card-actions">
                            <form class="inline" method="post"><input type="hidden" name="action" value="move_up"><input type="hidden" name="id" value="<?= $s['id'] ?>"><button type="submit" <?= $canMoveUp ? '' : 'disabled' ?> title="<?= t('services.col_order') ?>"><i class="fa-solid fa-arrow-up"></i></button></form>
                            <form class="inline" method="post"><input type="hidden" name="action" value="move_down"><input type="hidden" name="id" value="<?= $s['id'] ?>"><button type="submit" <?= $canMoveDown ? '' : 'disabled' ?> title="<?= t('services.col_order') ?>"><i class="fa-solid fa-arrow-down"></i></button></form>
                        </div>
                        <div class="item-card-actions">
                            <a href="#" class="edit-btn" data-id="<?= $s['id'] ?>" title="<?= t('services.edit') ?>"><i class="fa-solid fa-pen"></i></a>
                            <form class="inline" method="post" onsubmit="return confirm('<?= t('services.delete_confirm') ?>');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <button type="submit" class="del" title="<?= t('services.delete') ?>"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
<?php if ($services === []): ?>
    <div class="card"><?= t('services.no_services') ?></div>
<?php endif; ?>
</div>
<p id="noResultsMsg"><?= t('services.no_results') ?></p>

<div class="bulk-bar" id="bulkBar">
    <span class="count" id="bulkCount">0 <?= t('services.selected') ?></span>
    <div class="actions">
        <button type="button" class="b-activate" id="bulkActivate"><i class="fa-solid fa-circle-check"></i> <?= t('services.bulk_activate') ?></button>
        <button type="button" class="b-deactivate" id="bulkDeactivate"><i class="fa-solid fa-circle-xmark"></i> <?= t('services.bulk_deactivate') ?></button>
        <button type="button" class="b-price" id="bulkPrice"><i class="fa-solid fa-tag"></i> <?= t('services.bulk_price') ?></button>
        <button type="button" class="b-delete" id="bulkDelete"><i class="fa-solid fa-trash"></i> <?= t('services.bulk_delete') ?></button>
        <button type="button" class="b-clear" id="bulkClear"><?= t('services.bulk_clear') ?></button>
    </div>
</div>

<!-- Hidden forms submitted by JS for bulk actions -->
<form method="post" id="bulkForm" style="display:none;">
    <input type="hidden" name="action" id="bulkFormAction" value="">
    <input type="hidden" name="percent" id="bulkFormPercent" value="">
    <span id="bulkFormIds"></span>
</form>

<!-- ==== Add/Edit modal ==== -->
<div class="modal-backdrop" id="serviceModal">
    <div class="modal-box">
        <div class="modal-head">
            <h3 id="modalTitle"><?= t('services.add_title') ?></h3>
            <button type="button" class="modal-close" id="modalCloseBtn"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="post" id="serviceForm">
            <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>" id="formAction">
            <input type="hidden" name="id" value="<?= $editing['id'] ?? '' ?>" id="formId">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group full">
                        <label><?= t('services.provider') ?></label>
                        <select name="provider_id" required>
                            <option value=""><?= t('services.choose_provider') ?></option>
                            <?php foreach ($providers as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ($editing['provider_id'] ?? null) == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?= t('services.provider_service_id') ?></label>
                        <input type="text" name="provider_service_id" value="<?= htmlspecialchars($editing['provider_service_id'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label><?= t('services.name_sw') ?></label>
                        <input type="text" name="name_sw" value="<?= htmlspecialchars($editing['name_sw'] ?? '') ?>" data-maxlen="24">
                        <p style="font-size:11.5px;color:var(--text-soft);margin:4px 0 0;"><?= t('services.name_whatsapp_hint') ?></p>
                    </div>
                    <div class="form-group">
                        <label><?= t('services.name_en') ?></label>
                        <input type="text" name="name_en" value="<?= htmlspecialchars($editing['name_en'] ?? '') ?>" data-maxlen="24">
                        <p style="font-size:11.5px;color:var(--text-soft);margin:4px 0 0;"><?= t('services.name_whatsapp_hint') ?></p>
                    </div>
                    <?php
                        $currentPlatform = $editing['platform'] ?? '';
                        $isCustomPlatform = $currentPlatform !== '' && !in_array($currentPlatform, $platforms, true);
                    ?>
                    <div class="form-group">
                        <label><?= t('services.platform') ?></label>
                        <select name="platform" id="platform_select" required onchange="document.getElementById('platform_custom_wrap').style.display = this.value === 'custom' ? 'block' : 'none';">
                            <option value=""<?= $currentPlatform === '' ? ' selected' : '' ?>><?= t('services.choose_platform') ?></option>
                            <?php foreach ($platforms as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>" <?= $currentPlatform === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                            <?php endforeach; ?>
                            <option value="custom" <?= $isCustomPlatform ? 'selected' : '' ?>><?= t('services.platform_other') ?></option>
                        </select>
                    </div>
                    <div class="form-group" id="platform_custom_wrap" style="<?= $isCustomPlatform ? '' : 'display:none;' ?>">
                        <label><?= t('services.platform_custom') ?></label>
                        <input type="text" name="platform_custom" value="<?= $isCustomPlatform ? htmlspecialchars($currentPlatform) : '' ?>" placeholder="<?= t('services.platform') ?>">
                    </div>
                    <?php
                        $currentCategory = $editing['category'] ?? '';
                        $isCustomCategory = $currentCategory !== '' && !in_array($currentCategory, $categories, true);
                    ?>
                    <div class="form-group">
                        <label><?= t('services.category') ?></label>
                        <select name="category" id="category_select" onchange="document.getElementById('category_custom_wrap').style.display = this.value === 'custom' ? 'block' : 'none';">
                            <option value=""<?= $currentCategory === '' ? ' selected' : '' ?>><?= t('services.category_none') ?></option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>" <?= $currentCategory === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                            <option value="custom" <?= $isCustomCategory ? 'selected' : '' ?>><?= t('services.category_other') ?></option>
                        </select>
                    </div>
                    <div class="form-group" id="category_custom_wrap" style="<?= $isCustomCategory ? '' : 'display:none;' ?>">
                        <label><?= t('services.category_custom') ?></label>
                        <input type="text" name="category_custom" value="<?= $isCustomCategory ? htmlspecialchars($currentCategory) : '' ?>" placeholder="<?= t('services.category') ?>">
                    </div>
                    <?php
                        $unitOptions = [
                            'Followers', 'Likes', 'Views', 'Subscribers', 'Comments', 'Shares',
                            'Saves', 'Reactions', 'Plays', 'Members', 'Reposts', 'Retweets',
                            'Votes', 'Ratings', 'Reviews',
                            'Views + Likes', 'Views + Save', 'Views + Share',
                        ];
                        $currentUnit = $editing['unit_label'] ?? 'Followers';
                        $isCustomUnit = !in_array($currentUnit, $unitOptions, true);
                    ?>
                    <div class="form-group">
                        <label><?= t('services.unit_label') ?></label>
                        <select name="unit_label" id="unit_label_select" onchange="document.getElementById('unit_label_custom_wrap').style.display = this.value === 'custom' ? 'block' : 'none';">
                            <?php foreach ($unitOptions as $opt): ?>
                                <option value="<?= $opt ?>" <?= $currentUnit === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                            <option value="custom" <?= $isCustomUnit ? 'selected' : '' ?>><?= t('services.unit_other') ?></option>
                        </select>
                    </div>
                    <div class="form-group" id="unit_label_custom_wrap" style="<?= $isCustomUnit ? '' : 'display:none;' ?>">
                        <label><?= t('services.unit_label_custom') ?></label>
                        <input type="text" name="unit_label_custom" value="<?= $isCustomUnit ? htmlspecialchars($currentUnit) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label><?= t('services.cost_price') ?></label>
                        <input type="number" step="0.0001" name="cost_price" value="<?= htmlspecialchars($editing['cost_price'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label><?= t('services.my_price') ?></label>
                        <input type="number" step="0.0001" name="my_price" value="<?= htmlspecialchars($editing['my_price'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label><?= t('services.min_quantity') ?></label>
                        <input type="number" name="min_quantity" value="<?= htmlspecialchars($editing['min_quantity'] ?? '1') ?>" required>
                    </div>
                    <div class="form-group">
                        <label><?= t('services.max_quantity') ?></label>
                        <input type="number" name="max_quantity" value="<?= htmlspecialchars($editing['max_quantity'] ?? '1') ?>" required>
                    </div>
                    <div class="form-group full">
                        <label><?= t('services.link_instructions') ?></label>
                        <textarea name="link_instructions" rows="4" placeholder="<?= t('services.link_instructions_placeholder') ?>"><?= htmlspecialchars($editing['link_instructions'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group full">
                        <label><?= t('services.link_image') ?></label>
                        <input type="text" name="link_instructions_image" value="<?= htmlspecialchars($editing['link_instructions_image'] ?? '') ?>" placeholder="<?= t('services.link_image_placeholder') ?>">
                    </div>
                    <div class="form-group">
                        <label><?= t('services.status') ?></label>
                        <select name="status">
                            <option value="active" <?= ($editing['status'] ?? 'active') === 'active' ? 'selected' : '' ?>><?= t('providers.active') ?></option>
                            <option value="inactive" <?= ($editing['status'] ?? '') === 'inactive' ? 'selected' : '' ?>><?= t('providers.inactive') ?></option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="submit" class="btn btn-primary" id="modalSubmitBtn"><?= $editing ? t('services.update') : t('services.add') ?></button>
                <button type="button" class="btn btn-secondary" id="modalCancelBtn"><?= t('services.cancel') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- ==== Bulk price adjust modal ==== -->
<div class="modal-backdrop" id="priceModal">
    <div class="modal-box" style="max-width:420px;">
        <div class="modal-head">
            <h3><?= t('services.bulk_price') ?></h3>
            <button type="button" class="modal-close" id="priceModalCloseBtn"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label><?= t('services.bulk_price_label') ?></label>
                <input type="number" step="0.1" id="pricePercentInput" placeholder="<?= t('services.bulk_price_placeholder') ?>">
                <p style="font-size:12px;color:var(--text-soft);margin-top:8px;"><?= t('services.bulk_price_hint') ?></p>
            </div>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-primary" id="priceConfirmBtn"><?= t('services.apply') ?></button>
            <button type="button" class="btn btn-secondary" id="priceCancelBtn"><?= t('services.cancel') ?></button>
        </div>
    </div>
</div>

<script>
(function () {
    // ---- Server-provided service data for the edit modal ----
    var servicesData = <?= json_encode(array_values($services)) ?>;

    // ---- Filters ----
    var search = document.getElementById('serviceSearch');
    var platformFilter = document.getElementById('platformFilter');
    var statusFilter = document.getElementById('statusFilter');
    var cards = Array.from(document.querySelectorAll('.item-card'));
    var noResultsMsg = document.getElementById('noResultsMsg');

    function updateSectionVisibility() {
        document.querySelectorAll('.platform-section').forEach(function (section) {
            var visibleCards = section.querySelectorAll('.item-card:not([style*="display: none"])');
            section.style.display = visibleCards.length === 0 ? 'none' : '';
        });
        document.querySelectorAll('.category-label').forEach(function (label) {
            var grid = label.nextElementSibling;
            var visibleCards = grid ? grid.querySelectorAll('.item-card:not([style*="display: none"])') : [];
            var hide = visibleCards.length === 0;
            label.style.display = hide ? 'none' : '';
            if (grid) grid.style.display = hide ? 'none' : '';
        });
    }

    function applyFilters() {
        var q = search.value.trim().toLowerCase();
        var platform = platformFilter.value;
        var status = statusFilter.value;
        var visibleCount = 0;

        cards.forEach(function (card) {
            var matchesSearch = q === '' || card.dataset.search.indexOf(q) !== -1;
            var matchesPlatform = platform === '' || card.dataset.platform === platform;
            var matchesStatus = status === '' || card.dataset.status === status;
            var visible = matchesSearch && matchesPlatform && matchesStatus;
            card.style.display = visible ? '' : 'none';
            if (visible) visibleCount++;
        });

        updateSectionVisibility();
        noResultsMsg.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    search.addEventListener('input', applyFilters);
    platformFilter.addEventListener('change', applyFilters);
    statusFilter.addEventListener('change', applyFilters);

    // ---- Selection + bulk bar ----
    var bulkBar = document.getElementById('bulkBar');
    var bulkCount = document.getElementById('bulkCount');
    var checkboxes = Array.from(document.querySelectorAll('.item-card-check'));

    function selectedIds() {
        return checkboxes.filter(function (cb) { return cb.checked; }).map(function (cb) { return cb.dataset.id; });
    }

    function refreshBulkBar() {
        var ids = selectedIds();
        checkboxes.forEach(function (cb) {
            var card = cb.closest('.item-card');
            card.classList.toggle('selected', cb.checked);
        });
        if (ids.length > 0) {
            bulkBar.classList.add('show');
            bulkCount.textContent = ids.length + ' <?= t('services.selected') ?>';
        } else {
            bulkBar.classList.remove('show');
        }
    }

    checkboxes.forEach(function (cb) { cb.addEventListener('change', refreshBulkBar); });

    document.getElementById('bulkClear').addEventListener('click', function () {
        checkboxes.forEach(function (cb) { cb.checked = false; });
        refreshBulkBar();
    });

    function submitBulk(action, percent) {
        var ids = selectedIds();
        if (ids.length === 0) return;

        var form = document.getElementById('bulkForm');
        document.getElementById('bulkFormAction').value = action;
        document.getElementById('bulkFormPercent').value = percent || '';

        var idsContainer = document.getElementById('bulkFormIds');
        idsContainer.innerHTML = '';
        ids.forEach(function (id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            idsContainer.appendChild(input);
        });

        form.submit();
    }

    document.getElementById('bulkActivate').addEventListener('click', function () {
        submitBulk('bulk_activate');
    });
    document.getElementById('bulkDeactivate').addEventListener('click', function () {
        submitBulk('bulk_deactivate');
    });
    document.getElementById('bulkDelete').addEventListener('click', function () {
        if (confirm('<?= t('services.bulk_delete_confirm') ?>')) {
            submitBulk('bulk_delete');
        }
    });

    // ---- Bulk price modal ----
    var priceModal = document.getElementById('priceModal');
    document.getElementById('bulkPrice').addEventListener('click', function () {
        priceModal.classList.add('open');
    });
    document.getElementById('priceModalCloseBtn').addEventListener('click', function () { priceModal.classList.remove('open'); });
    document.getElementById('priceCancelBtn').addEventListener('click', function () { priceModal.classList.remove('open'); });
    document.getElementById('priceConfirmBtn').addEventListener('click', function () {
        var percent = document.getElementById('pricePercentInput').value;
        if (!percent || parseFloat(percent) === 0) return;
        submitBulk('bulk_price_adjust', percent);
    });

    // ---- Add/Edit modal ----
    var serviceModal = document.getElementById('serviceModal');
    var modalTitle = document.getElementById('modalTitle');
    var formAction = document.getElementById('formAction');
    var formId = document.getElementById('formId');
    var modalSubmitBtn = document.getElementById('modalSubmitBtn');
    var form = document.getElementById('serviceForm');

    function openModalForCreate() {
        form.reset();
        formAction.value = 'create';
        formId.value = '';
        modalTitle.textContent = <?= json_encode(t('services.add_title')) ?>;
        modalSubmitBtn.textContent = <?= json_encode(t('services.add')) ?>;
        document.getElementById('platform_custom_wrap').style.display = 'none';
        document.getElementById('category_custom_wrap').style.display = 'none';
        document.getElementById('unit_label_custom_wrap').style.display = 'none';
        serviceModal.classList.add('open');
    }

    function openModalForEdit(id) {
        var svc = servicesData.find(function (s) { return String(s.id) === String(id); });
        if (!svc) return;

        form.reset();
        formAction.value = 'update';
        formId.value = svc.id;
        modalTitle.textContent = <?= json_encode(t('services.edit_title')) ?>;
        modalSubmitBtn.textContent = <?= json_encode(t('services.update')) ?>;

        form.provider_id.value = svc.provider_id;
        form.provider_service_id.value = svc.provider_service_id;
        form.name_sw.value = svc.name_sw;
        form.name_en.value = svc.name_en;
        form.cost_price.value = svc.cost_price;
        form.my_price.value = svc.my_price;
        form.min_quantity.value = svc.min_quantity;
        form.max_quantity.value = svc.max_quantity;
        form.link_instructions.value = svc.link_instructions || '';
        form.link_instructions_image.value = svc.link_instructions_image || '';
        form.status.value = svc.status;

        setSelectOrCustom('platform_select', 'platform_custom_wrap', 'platform_custom', svc.platform);
        setSelectOrCustom('category_select', 'category_custom_wrap', 'category_custom', svc.category || '');
        setSelectOrCustom('unit_label_select', 'unit_label_custom_wrap', 'unit_label_custom', svc.unit_label);

        serviceModal.classList.add('open');
    }

    function setSelectOrCustom(selectId, wrapId, customName, value) {
        var select = document.getElementById(selectId);
        var wrap = document.getElementById(wrapId);
        var customInput = form.querySelector('[name=' + customName + ']');
        var hasOption = Array.from(select.options).some(function (o) { return o.value === value; });

        if (value === '' && selectId === 'category_select') {
            select.value = '';
            wrap.style.display = 'none';
            return;
        }

        if (hasOption) {
            select.value = value;
            wrap.style.display = 'none';
        } else {
            select.value = 'custom';
            wrap.style.display = 'block';
            if (customInput) customInput.value = value;
        }
    }

    document.getElementById('openAddModal').addEventListener('click', openModalForCreate);
    document.querySelectorAll('.edit-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            openModalForEdit(btn.dataset.id);
        });
    });
    document.getElementById('modalCloseBtn').addEventListener('click', function () { serviceModal.classList.remove('open'); });
    document.getElementById('modalCancelBtn').addEventListener('click', function () { serviceModal.classList.remove('open'); });

    [serviceModal, priceModal].forEach(function (backdrop) {
        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop) backdrop.classList.remove('open');
        });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            serviceModal.classList.remove('open');
            priceModal.classList.remove('open');
        }
    });

    <?php if ($editing): ?>
    openModalForEdit(<?= (int) $editing['id'] ?>);
    <?php endif; ?>
})();
</script>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

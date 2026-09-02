<?php

require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/helpers/Lang.php';
require_once __DIR__ . '/../../app/models/Provider.php';

Auth::requireLogin();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'api_url' => trim($_POST['api_url'] ?? ''),
            'api_key' => trim($_POST['api_key'] ?? ''),
            'status' => $_POST['status'] ?? 'active',
        ];

        if ($data['name'] === '' || $data['api_url'] === '' || $data['api_key'] === '') {
            $error = t('providers.fill_all');
        } elseif ($action === 'create') {
            Provider::create($data);
            $success = t('providers.added');
        } else {
            Provider::update((int) $_POST['id'], $data);
            $success = t('providers.updated');
        }
    } elseif ($action === 'delete') {
        Provider::delete((int) $_POST['id']);
        $success = t('providers.deleted');
    }
}

$providers = Provider::all();
$stats = Provider::stats();
$editing = null;

if (isset($_GET['edit'])) {
    $editing = Provider::find((int) $_GET['edit']);
}

$pageTitle = t('providers.title');
$activeNav = 'providers';
require __DIR__ . '/includes/layout_header.php';
?>

<?php if ($error !== null): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success !== null): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="mini-stats">
    <div class="mini-stat"><div class="icon total"><i class="fa-solid fa-server"></i></div><div><div class="num"><?= $stats['total'] ?></div><div class="lbl"><?= t('providers.stat_total') ?></div></div></div>
    <div class="mini-stat"><div class="icon active"><i class="fa-solid fa-circle-check"></i></div><div><div class="num"><?= $stats['active'] ?></div><div class="lbl"><?= t('providers.active') ?></div></div></div>
    <div class="mini-stat"><div class="icon inactive"><i class="fa-solid fa-circle-xmark"></i></div><div><div class="num"><?= $stats['inactive'] ?></div><div class="lbl"><?= t('providers.inactive') ?></div></div></div>
</div>

<div class="toolbar">
    <input type="text" id="providerSearch" placeholder="<?= t('providers.search_placeholder') ?>">
    <select id="statusFilter">
        <option value=""><?= t('services.filter_all_status') ?></option>
        <option value="active"><?= t('providers.active') ?></option>
        <option value="inactive"><?= t('providers.inactive') ?></option>
    </select>
    <div class="spacer"></div>
    <button type="button" class="btn btn-primary" id="openAddModal"><i class="fa-solid fa-plus"></i> <?= t('providers.add') ?></button>
</div>

<div class="item-grid" id="providersGrid">
    <?php foreach ($providers as $p): ?>
    <div class="item-card <?= $p['status'] === 'inactive' ? 'inactive' : '' ?>"
         data-id="<?= $p['id'] ?>"
         data-status="<?= $p['status'] ?>"
         data-search="<?= htmlspecialchars(mb_strtolower($p['name'] . ' ' . $p['api_url'])) ?>">
        <div class="item-card-top">
            <div class="item-card-title">#<?= $p['id'] ?> — <?= htmlspecialchars($p['name']) ?></div>
            <span class="badge badge-<?= $p['status'] ?>"><?= $p['status'] ?></span>
        </div>
        <div class="item-card-meta">
            <span style="max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($p['api_url']) ?></span>
        </div>
        <div class="item-card-bottom">
            <div></div>
            <div class="item-card-actions">
                <a href="#" class="edit-btn" data-id="<?= $p['id'] ?>" title="<?= t('providers.edit') ?>"><i class="fa-solid fa-pen"></i></a>
                <form class="inline" method="post" onsubmit="return confirm('<?= t('providers.delete_confirm') ?>');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button type="submit" class="del" title="<?= t('providers.delete') ?>"><i class="fa-solid fa-trash"></i></button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php if ($providers === []): ?>
    <div class="card"><?= t('providers.no_providers') ?></div>
<?php endif; ?>
<p id="noResultsMsg"><?= t('services.no_results') ?></p>

<!-- ==== Add/Edit modal ==== -->
<div class="modal-backdrop" id="providerModal">
    <div class="modal-box">
        <div class="modal-head">
            <h3 id="modalTitle"><?= t('providers.add_title') ?></h3>
            <button type="button" class="modal-close" id="modalCloseBtn"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="post" id="providerForm">
            <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>" id="formAction">
            <input type="hidden" name="id" value="<?= $editing['id'] ?? '' ?>" id="formId">
            <div class="modal-body">
                <div class="form-group">
                    <label><?= t('providers.name') ?></label>
                    <input type="text" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label><?= t('providers.api_url') ?></label>
                    <input type="text" name="api_url" value="<?= htmlspecialchars($editing['api_url'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label><?= t('providers.api_key') ?></label>
                    <input type="text" name="api_key" value="<?= htmlspecialchars($editing['api_key'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label><?= t('providers.status') ?></label>
                    <select name="status">
                        <option value="active" <?= ($editing['status'] ?? 'active') === 'active' ? 'selected' : '' ?>><?= t('providers.active') ?></option>
                        <option value="inactive" <?= ($editing['status'] ?? '') === 'inactive' ? 'selected' : '' ?>><?= t('providers.inactive') ?></option>
                    </select>
                </div>
            </div>
            <div class="modal-foot">
                <button type="submit" class="btn btn-primary" id="modalSubmitBtn"><?= $editing ? t('providers.update') : t('providers.add') ?></button>
                <button type="button" class="btn btn-secondary" id="modalCancelBtn"><?= t('providers.cancel') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var providersData = <?= json_encode(array_values($providers)) ?>;

    var search = document.getElementById('providerSearch');
    var statusFilter = document.getElementById('statusFilter');
    var cards = Array.from(document.querySelectorAll('#providersGrid .item-card'));
    var noResultsMsg = document.getElementById('noResultsMsg');

    function applyFilters() {
        var q = search.value.trim().toLowerCase();
        var status = statusFilter.value;
        var visibleCount = 0;

        cards.forEach(function (card) {
            var matchesSearch = q === '' || card.dataset.search.indexOf(q) !== -1;
            var matchesStatus = status === '' || card.dataset.status === status;
            var visible = matchesSearch && matchesStatus;
            card.style.display = visible ? '' : 'none';
            if (visible) visibleCount++;
        });

        noResultsMsg.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    search.addEventListener('input', applyFilters);
    statusFilter.addEventListener('change', applyFilters);

    var modal = document.getElementById('providerModal');
    var modalTitle = document.getElementById('modalTitle');
    var formAction = document.getElementById('formAction');
    var formId = document.getElementById('formId');
    var modalSubmitBtn = document.getElementById('modalSubmitBtn');
    var form = document.getElementById('providerForm');

    function openModalForCreate() {
        form.reset();
        formAction.value = 'create';
        formId.value = '';
        modalTitle.textContent = <?= json_encode(t('providers.add_title')) ?>;
        modalSubmitBtn.textContent = <?= json_encode(t('providers.add')) ?>;
        modal.classList.add('open');
    }

    function openModalForEdit(id) {
        var p = providersData.find(function (x) { return String(x.id) === String(id); });
        if (!p) return;

        form.reset();
        formAction.value = 'update';
        formId.value = p.id;
        modalTitle.textContent = <?= json_encode(t('providers.edit_title')) ?>;
        modalSubmitBtn.textContent = <?= json_encode(t('providers.update')) ?>;

        form.name.value = p.name;
        form.api_url.value = p.api_url;
        form.api_key.value = p.api_key;
        form.status.value = p.status;

        modal.classList.add('open');
    }

    document.getElementById('openAddModal').addEventListener('click', openModalForCreate);
    document.querySelectorAll('.edit-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            openModalForEdit(btn.dataset.id);
        });
    });
    document.getElementById('modalCloseBtn').addEventListener('click', function () { modal.classList.remove('open'); });
    document.getElementById('modalCancelBtn').addEventListener('click', function () { modal.classList.remove('open'); });
    modal.addEventListener('click', function (e) {
        if (e.target === modal) modal.classList.remove('open');
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') modal.classList.remove('open');
    });

    <?php if ($editing): ?>
    openModalForEdit(<?= (int) $editing['id'] ?>);
    <?php endif; ?>
})();
</script>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

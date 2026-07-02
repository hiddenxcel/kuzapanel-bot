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

        $data = [
            'provider_id' => (int) ($_POST['provider_id'] ?? 0),
            'provider_service_id' => trim($_POST['provider_service_id'] ?? ''),
            'platform' => trim($_POST['platform'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
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
            || $data['platform'] === '' || $data['name'] === '' || $data['unit_label'] === ''
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
    }
}

$services = Service::all();
$providers = Provider::all();
$editing = null;

if (isset($_GET['edit'])) {
    $editing = Service::find((int) $_GET['edit']);
}

$providerNames = [];
foreach ($providers as $p) {
    $providerNames[$p['id']] = $p['name'];
}

$pageTitle = t('services.title');
$activeNav = 'services';
require __DIR__ . '/includes/layout_header.php';
?>

<?php if ($error !== null): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success !== null): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="card">
    <h3 style="margin-top:0;"><?= $editing ? t('services.edit_title') : t('services.add_title') ?></h3>
    <?php if ($providers === []): ?>
        <p><?= t('services.add_provider_first') ?> <a href="providers.php"><?= t('services.add_provider_link') ?></a> <?= t('services.add_provider_suffix') ?></p>
    <?php else: ?>
    <form method="post">
        <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?= $editing['id'] ?>">
        <?php endif; ?>
        <div class="form-group">
            <label><?= t('services.provider') ?></label>
            <select name="provider_id" required>
                <option value=""><?= t('services.choose_provider') ?></option>
                <?php foreach ($providers as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= ($editing['provider_id'] ?? null) == $p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label><?= t('services.provider_service_id') ?></label>
            <input type="text" name="provider_service_id" value="<?= htmlspecialchars($editing['provider_service_id'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label><?= t('services.platform') ?></label>
            <input type="text" name="platform" value="<?= htmlspecialchars($editing['platform'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label><?= t('services.name') ?></label>
            <input type="text" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required>
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
        <div class="form-group">
            <label><?= t('services.link_instructions') ?></label>
            <textarea name="link_instructions" rows="6" placeholder="<?= t('services.link_instructions_placeholder') ?>"><?= htmlspecialchars($editing['link_instructions'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
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
        <button type="submit" class="btn btn-primary"><?= $editing ? t('services.update') : t('services.add') ?></button>
        <?php if ($editing): ?>
            <a href="services.php" class="btn btn-secondary"><?= t('services.cancel') ?></a>
        <?php endif; ?>
    </form>
    <?php endif; ?>
</div>

<div class="card">
    <h3 style="margin-top:0;"><?= t('services.list_title') ?></h3>
    <p style="margin-top:-10px;color:var(--text-soft);font-size:13px;">
        <?= t('services.reorder_hint') ?>
    </p>
    <table>
        <tr>
            <th><?= t('services.col_order') ?></th><th><?= t('services.col_id') ?></th><th><?= t('services.col_provider') ?></th><th><?= t('services.col_platform') ?></th><th><?= t('services.col_name') ?></th><th><?= t('services.col_unit') ?></th><th><?= t('services.col_price') ?></th><th><?= t('services.col_minmax') ?></th><th><?= t('services.col_status') ?></th><th><?= t('services.col_action') ?></th>
        </tr>
        <?php $count = count($services); ?>
        <?php for ($i = 0; $i < $count; $i++): $s = $services[$i]; ?>
        <?php
            $canMoveUp = $i > 0 && $services[$i - 1]['platform'] === $s['platform'];
            $canMoveDown = $i < $count - 1 && $services[$i + 1]['platform'] === $s['platform'];
        ?>
        <tr>
            <td>
                <div style="display:flex;gap:4px;">
                    <form class="inline" method="post">
                        <input type="hidden" name="action" value="move_up">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button type="submit" class="btn btn-secondary" style="padding:6px 9px;<?= $canMoveUp ? '' : 'opacity:.35;cursor:default;' ?>" <?= $canMoveUp ? '' : 'disabled' ?>><i class="fa-solid fa-arrow-up"></i></button>
                    </form>
                    <form class="inline" method="post">
                        <input type="hidden" name="action" value="move_down">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button type="submit" class="btn btn-secondary" style="padding:6px 9px;<?= $canMoveDown ? '' : 'opacity:.35;cursor:default;' ?>" <?= $canMoveDown ? '' : 'disabled' ?>><i class="fa-solid fa-arrow-down"></i></button>
                    </form>
                </div>
            </td>
            <td>#<?= $s['id'] ?></td>
            <td><?= htmlspecialchars($providerNames[$s['provider_id']] ?? '—') ?></td>
            <td><?= htmlspecialchars($s['platform']) ?></td>
            <td><?= htmlspecialchars($s['name']) ?></td>
            <td><?= htmlspecialchars($s['unit_label']) ?></td>
            <td><?= number_format((float) $s['my_price'], 2) ?></td>
            <td><?= $s['min_quantity'] ?> / <?= $s['max_quantity'] ?></td>
            <td><span class="badge badge-<?= $s['status'] ?>"><?= $s['status'] ?></span></td>
            <td>
                <a href="services.php?edit=<?= $s['id'] ?>" class="btn btn-secondary"><?= t('services.edit') ?></a>
                <form class="inline" method="post" onsubmit="return confirm('<?= t('services.delete_confirm') ?>');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <button type="submit" class="btn btn-danger"><?= t('services.delete') ?></button>
                </form>
            </td>
        </tr>
        <?php endfor; ?>
        <?php if ($services === []): ?>
        <tr><td colspan="10"><?= t('services.no_services') ?></td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

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

<div class="card">
    <h3 style="margin-top:0;"><?= $editing ? t('providers.edit_title') : t('providers.add_title') ?></h3>
    <form method="post">
        <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?= $editing['id'] ?>">
        <?php endif; ?>
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
        <button type="submit" class="btn btn-primary"><?= $editing ? t('providers.update') : t('providers.add') ?></button>
        <?php if ($editing): ?>
            <a href="providers.php" class="btn btn-secondary"><?= t('providers.cancel') ?></a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <h3 style="margin-top:0;"><?= t('providers.list_title') ?></h3>
    <table>
        <tr>
            <th><?= t('providers.col_id') ?></th><th><?= t('providers.col_name') ?></th><th><?= t('providers.col_api_url') ?></th><th><?= t('providers.col_status') ?></th><th><?= t('providers.col_action') ?></th>
        </tr>
        <?php foreach ($providers as $p): ?>
        <tr>
            <td>#<?= $p['id'] ?></td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= htmlspecialchars($p['api_url']) ?></td>
            <td><span class="badge badge-<?= $p['status'] ?>"><?= $p['status'] ?></span></td>
            <td>
                <a href="providers.php?edit=<?= $p['id'] ?>" class="btn btn-secondary"><?= t('providers.edit') ?></a>
                <form class="inline" method="post" onsubmit="return confirm('<?= t('providers.delete_confirm') ?>');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button type="submit" class="btn btn-danger"><?= t('providers.delete') ?></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if ($providers === []): ?>
        <tr><td colspan="5"><?= t('providers.no_providers') ?></td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

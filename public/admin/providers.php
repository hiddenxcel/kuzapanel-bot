<?php

require_once __DIR__ . '/../../app/helpers/Auth.php';
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
            $error = 'Tafadhali jaza taarifa zote.';
        } elseif ($action === 'create') {
            Provider::create($data);
            $success = 'Provider imeongezwa.';
        } else {
            Provider::update((int) $_POST['id'], $data);
            $success = 'Provider imesasishwa.';
        }
    } elseif ($action === 'delete') {
        Provider::delete((int) $_POST['id']);
        $success = 'Provider imefutwa.';
    }
}

$providers = Provider::all();
$editing = null;

if (isset($_GET['edit'])) {
    $editing = Provider::find((int) $_GET['edit']);
}

$pageTitle = 'Providers';
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
    <h3 style="margin-top:0;"><?= $editing ? 'Hariri Provider' : 'Ongeza Provider Mpya' ?></h3>
    <form method="post">
        <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?= $editing['id'] ?>">
        <?php endif; ?>
        <div class="form-group">
            <label>Jina</label>
            <input type="text" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>API URL</label>
            <input type="text" name="api_url" value="<?= htmlspecialchars($editing['api_url'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>API Key</label>
            <input type="text" name="api_key" value="<?= htmlspecialchars($editing['api_key'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="active" <?= ($editing['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($editing['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><?= $editing ? 'Sasisha' : 'Ongeza' ?></button>
        <?php if ($editing): ?>
            <a href="providers.php" class="btn btn-secondary">Ghairi</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <h3 style="margin-top:0;">Orodha ya Providers</h3>
    <table>
        <tr>
            <th>ID</th><th>Jina</th><th>API URL</th><th>Status</th><th>Action</th>
        </tr>
        <?php foreach ($providers as $p): ?>
        <tr>
            <td>#<?= $p['id'] ?></td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= htmlspecialchars($p['api_url']) ?></td>
            <td><span class="badge badge-<?= $p['status'] ?>"><?= $p['status'] ?></span></td>
            <td>
                <a href="providers.php?edit=<?= $p['id'] ?>" class="btn btn-secondary">Hariri</a>
                <form class="inline" method="post" onsubmit="return confirm('Una uhakika unataka kufuta?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button type="submit" class="btn btn-danger">Futa</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if ($providers === []): ?>
        <tr><td colspan="5">Hakuna providers bado.</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>

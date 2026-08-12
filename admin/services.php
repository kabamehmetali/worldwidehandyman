<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_or_die();
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $stmt = db()->prepare('SELECT image_path FROM services WHERE id = ?');
        $stmt->execute([$id]);
        if ($row = $stmt->fetch()) {
            delete_upload($row['image_path']);
        }
        db()->prepare('DELETE FROM services WHERE id = ?')->execute([$id]);
        flash_set('success', 'Service deleted.');
    } elseif ($action === 'toggle') {
        db()->prepare('UPDATE services SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
    } elseif ($action === 'move_up' || $action === 'move_down') {
        move_row('services', $id, $action === 'move_up' ? 'up' : 'down');
    }
    redirect('admin/services.php');
}

$adminTitle = 'Services';
require __DIR__ . '/includes/header.php';

$services = db()->query('SELECT * FROM services ORDER BY sort_order ASC, id ASC')->fetchAll();
?>

<div class="admin-card">
    <div class="card-heading">
        <h5>Services (<?= count($services) ?>)</h5>
        <a class="btn btn-gold" href="<?= esc(base_url('admin/service-form.php')) ?>"><i class="fa-solid fa-plus me-2"></i>Add Service</a>
    </div>
    <?php if ($services): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th style="width:70px;">Order</th><th>Icon</th><th>Service</th><th>Visible</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($services as $i => $s): ?>
                    <tr class="<?= $s['is_active'] ? '' : 'opacity-50' ?>">
                        <td>
                            <form class="d-inline" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="action" value="move_up"><button class="btn btn-sm btn-light" <?= $i === 0 ? 'disabled' : '' ?> title="Move up"><i class="fa-solid fa-chevron-up"></i></button></form>
                            <form class="d-inline" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="action" value="move_down"><button class="btn btn-sm btn-light" <?= $i === count($services) - 1 ? 'disabled' : '' ?> title="Move down"><i class="fa-solid fa-chevron-down"></i></button></form>
                        </td>
                        <td><span class="icon-preview"><i class="<?= esc($s['icon']) ?>"></i></span></td>
                        <td>
                            <strong><?= esc($s['title']) ?></strong><br>
                            <small class="text-muted"><?= esc(mb_substr($s['short_desc'], 0, 90)) ?><?= mb_strlen($s['short_desc']) > 90 ? '…' : '' ?></small>
                        </td>
                        <td>
                            <form method="post"><?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                <input type="hidden" name="action" value="toggle">
                                <button class="btn btn-sm <?= $s['is_active'] ? 'btn-success' : 'btn-outline-secondary' ?>" title="Toggle visibility">
                                    <i class="fa-solid <?= $s['is_active'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                                </button>
                            </form>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= esc(base_url('admin/service-form.php?id=' . (int) $s['id'])) ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                            <form class="d-inline" method="post" data-confirm="Delete this service permanently?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted mb-0">No services yet — add your first one.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

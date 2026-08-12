<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_or_die();
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        db()->prepare('DELETE FROM faqs WHERE id = ?')->execute([$id]);
        flash_set('success', 'FAQ deleted.');
    } elseif ($action === 'toggle') {
        db()->prepare('UPDATE faqs SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
    } elseif ($action === 'move_up' || $action === 'move_down') {
        move_row('faqs', $id, $action === 'move_up' ? 'up' : 'down');
    }
    redirect('admin/faqs.php');
}

$adminTitle = 'FAQs';
require __DIR__ . '/includes/header.php';

$faqs = db()->query('SELECT * FROM faqs ORDER BY sort_order ASC, id ASC')->fetchAll();
?>

<div class="admin-card">
    <div class="card-heading">
        <h5>FAQs (<?= count($faqs) ?>)</h5>
        <a class="btn btn-gold" href="<?= esc(base_url('admin/faq-form.php')) ?>"><i class="fa-solid fa-plus me-2"></i>Add FAQ</a>
    </div>
    <?php if ($faqs): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th style="width:70px;">Order</th><th>Question</th><th>Visible</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($faqs as $i => $f): ?>
                    <tr class="<?= $f['is_active'] ? '' : 'opacity-50' ?>">
                        <td>
                            <form class="d-inline" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $f['id'] ?>"><input type="hidden" name="action" value="move_up"><button class="btn btn-sm btn-light" <?= $i === 0 ? 'disabled' : '' ?>><i class="fa-solid fa-chevron-up"></i></button></form>
                            <form class="d-inline" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $f['id'] ?>"><input type="hidden" name="action" value="move_down"><button class="btn btn-sm btn-light" <?= $i === count($faqs) - 1 ? 'disabled' : '' ?>><i class="fa-solid fa-chevron-down"></i></button></form>
                        </td>
                        <td>
                            <strong><?= esc($f['question']) ?></strong><br>
                            <small class="text-muted"><?= esc(mb_substr($f['answer'], 0, 100)) ?><?= mb_strlen($f['answer']) > 100 ? '…' : '' ?></small>
                        </td>
                        <td>
                            <form method="post"><?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                                <input type="hidden" name="action" value="toggle">
                                <button class="btn btn-sm <?= $f['is_active'] ? 'btn-success' : 'btn-outline-secondary' ?>"><i class="fa-solid <?= $f['is_active'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i></button>
                            </form>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= esc(base_url('admin/faq-form.php?id=' . (int) $f['id'])) ?>"><i class="fa-solid fa-pen"></i></a>
                            <form class="d-inline" method="post" data-confirm="Delete this FAQ permanently?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted mb-0">No FAQs yet.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

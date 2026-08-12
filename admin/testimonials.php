<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_or_die();
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        db()->prepare('DELETE FROM testimonials WHERE id = ?')->execute([$id]);
        flash_set('success', 'Testimonial deleted.');
    } elseif ($action === 'toggle') {
        db()->prepare('UPDATE testimonials SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
    } elseif ($action === 'move_up' || $action === 'move_down') {
        move_row('testimonials', $id, $action === 'move_up' ? 'up' : 'down');
    }
    redirect('admin/testimonials.php');
}

$adminTitle = 'Testimonials';
require __DIR__ . '/includes/header.php';

$testimonials = db()->query('SELECT * FROM testimonials ORDER BY sort_order ASC, id ASC')->fetchAll();
?>

<div class="admin-card">
    <div class="card-heading">
        <h5>Testimonials (<?= count($testimonials) ?>)</h5>
        <a class="btn btn-gold" href="<?= esc(base_url('admin/testimonial-form.php')) ?>"><i class="fa-solid fa-plus me-2"></i>Add Testimonial</a>
    </div>
    <?php if ($testimonials): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th style="width:70px;">Order</th><th>Author</th><th>Quote</th><th>Rating</th><th>Visible</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($testimonials as $i => $t): ?>
                    <tr class="<?= $t['is_active'] ? '' : 'opacity-50' ?>">
                        <td>
                            <form class="d-inline" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $t['id'] ?>"><input type="hidden" name="action" value="move_up"><button class="btn btn-sm btn-light" <?= $i === 0 ? 'disabled' : '' ?>><i class="fa-solid fa-chevron-up"></i></button></form>
                            <form class="d-inline" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $t['id'] ?>"><input type="hidden" name="action" value="move_down"><button class="btn btn-sm btn-light" <?= $i === count($testimonials) - 1 ? 'disabled' : '' ?>><i class="fa-solid fa-chevron-down"></i></button></form>
                        </td>
                        <td><strong><?= esc($t['author']) ?></strong><br><small class="text-muted"><?= esc($t['location']) ?></small></td>
                        <td class="text-truncate" style="max-width: 340px;"><?= esc($t['quote_text']) ?></td>
                        <td><span class="text-warning"><?php for ($s = 0; $s < (int) $t['rating']; $s++): ?><i class="fa-solid fa-star"></i><?php endfor; ?></span></td>
                        <td>
                            <form method="post"><?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                                <input type="hidden" name="action" value="toggle">
                                <button class="btn btn-sm <?= $t['is_active'] ? 'btn-success' : 'btn-outline-secondary' ?>"><i class="fa-solid <?= $t['is_active'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i></button>
                            </form>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= esc(base_url('admin/testimonial-form.php?id=' . (int) $t['id'])) ?>"><i class="fa-solid fa-pen"></i></a>
                            <form class="d-inline" method="post" data-confirm="Delete this testimonial permanently?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted mb-0">No testimonials yet.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

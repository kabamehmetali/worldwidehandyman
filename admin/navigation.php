<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_or_die();
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        db()->prepare('DELETE FROM nav_links WHERE id = ?')->execute([$id]);
        flash_set('success', 'Navigation link removed.');
    } elseif ($action === 'toggle') {
        db()->prepare('UPDATE nav_links SET is_visible = 1 - is_visible WHERE id = ?')->execute([$id]);
    } elseif ($action === 'move_up' || $action === 'move_down') {
        move_row('nav_links', $id, $action === 'move_up' ? 'up' : 'down');
    }
    redirect('admin/navigation.php');
}

$adminTitle = 'Navigation';
require __DIR__ . '/includes/header.php';

$links = db()->query('SELECT * FROM nav_links ORDER BY sort_order ASC, id ASC')->fetchAll();
?>

<div class="admin-card">
    <div class="card-heading">
        <h5>Menu Links (<?= count($links) ?>)</h5>
        <a class="btn btn-gold" href="<?= esc(base_url('admin/nav-form.php')) ?>"><i class="fa-solid fa-plus me-2"></i>Add Link</a>
    </div>
    <p class="text-muted small">These links appear in the site header (in this order) and in the footer's Quick Links. Hidden links stay here but disappear from the site. "Button style" renders the link as the gold call-to-action button.</p>
    <?php if ($links): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th style="width:70px;">Order</th><th>Label</th><th>URL</th><th>Style</th><th>Visible</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($links as $i => $l): ?>
                    <tr class="<?= $l['is_visible'] ? '' : 'opacity-50' ?>">
                        <td>
                            <form class="d-inline" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $l['id'] ?>"><input type="hidden" name="action" value="move_up"><button class="btn btn-sm btn-light" <?= $i === 0 ? 'disabled' : '' ?>><i class="fa-solid fa-chevron-up"></i></button></form>
                            <form class="d-inline" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $l['id'] ?>"><input type="hidden" name="action" value="move_down"><button class="btn btn-sm btn-light" <?= $i === count($links) - 1 ? 'disabled' : '' ?>><i class="fa-solid fa-chevron-down"></i></button></form>
                        </td>
                        <td><strong><?= esc($l['label']) ?></strong><?= $l['open_new_tab'] ? ' <i class="fa-solid fa-arrow-up-right-from-square fa-xs text-muted" title="Opens in new tab"></i>' : '' ?></td>
                        <td><code class="small"><?= esc($l['url']) ?></code></td>
                        <td><?= $l['is_cta'] ? '<span class="badge text-bg-warning">Button</span>' : '<span class="badge text-bg-light">Link</span>' ?></td>
                        <td>
                            <form method="post"><?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                                <input type="hidden" name="action" value="toggle">
                                <button class="btn btn-sm <?= $l['is_visible'] ? 'btn-success' : 'btn-outline-secondary' ?>" title="<?= $l['is_visible'] ? 'Visible — click to hide' : 'Hidden — click to show' ?>">
                                    <i class="fa-solid <?= $l['is_visible'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                                </button>
                            </form>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= esc(base_url('admin/nav-form.php?id=' . (int) $l['id'])) ?>"><i class="fa-solid fa-pen"></i></a>
                            <form class="d-inline" method="post" data-confirm="Remove this link from the menu?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted mb-0">The menu is empty — add a link.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

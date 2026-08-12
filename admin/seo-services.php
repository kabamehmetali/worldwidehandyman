<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_or_die();
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        db()->prepare('DELETE FROM seo_services WHERE id = ?')->execute([$id]);
        flash_set('success', 'Service page deleted (its service × city pages went with it).');
    } elseif ($action === 'toggle') {
        db()->prepare('UPDATE seo_services SET is_published = 1 - is_published WHERE id = ?')->execute([$id]);
    } elseif ($action === 'toggle_pillar') {
        db()->prepare('UPDATE seo_services SET is_pillar = 1 - is_pillar WHERE id = ?')->execute([$id]);
    } elseif ($action === 'move_up' || $action === 'move_down') {
        move_row('seo_services', $id, $action === 'move_up' ? 'up' : 'down');
    }
    redirect('admin/seo-services.php');
}

$adminTitle = 'SEO Service Pages';
require __DIR__ . '/includes/header.php';

$services = db()->query(
    'SELECT s.*, (SELECT COUNT(*) FROM seo_service_locations sl WHERE sl.service_id = s.id) AS combo_count
       FROM seo_services s ORDER BY s.sort_order ASC, s.id ASC'
)->fetchAll();
$published = count(array_filter($services, static fn ($s) => (int) $s['is_published'] === 1));
?>

<div class="admin-card">
    <div class="card-heading">
        <h5>Service Pages (<?= count($services) ?> — <?= $published ?> live)</h5>
        <a class="btn btn-gold" href="<?= esc(base_url('admin/seo-service-form.php')) ?>"><i class="fa-solid fa-plus me-2"></i>Add Service Page</a>
    </div>
    <p class="text-muted">
        Each row publishes a page at <code>/services/&lt;slug&gt;</code>.
        <strong>Pillar</strong> services are the ones that also get their own service × city pages.
        These are separate from Content → Services, which only controls the homepage cards.
    </p>

    <?php if ($services): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr>
                    <th style="width:70px;">Order</th><th>Icon</th><th>Service</th>
                    <th>Pillar</th><th>City pages</th><th>Live</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($services as $i => $s): ?>
                    <tr class="<?= $s['is_published'] ? '' : 'opacity-50' ?>">
                        <td>
                            <form class="d-inline" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="action" value="move_up"><button class="btn btn-sm btn-light" <?= $i === 0 ? 'disabled' : '' ?> title="Move up"><i class="fa-solid fa-chevron-up"></i></button></form>
                            <form class="d-inline" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="action" value="move_down"><button class="btn btn-sm btn-light" <?= $i === count($services) - 1 ? 'disabled' : '' ?> title="Move down"><i class="fa-solid fa-chevron-down"></i></button></form>
                        </td>
                        <td><span class="icon-preview"><i class="<?= esc($s['icon']) ?>"></i></span></td>
                        <td>
                            <strong><?= esc($s['name']) ?></strong><br>
                            <small class="text-muted">/services/<?= esc($s['slug']) ?></small>
                        </td>
                        <td>
                            <form method="post"><?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                <input type="hidden" name="action" value="toggle_pillar">
                                <button class="btn btn-sm <?= $s['is_pillar'] ? 'btn-warning' : 'btn-outline-secondary' ?>" title="Toggle pillar service">
                                    <i class="fa-solid <?= $s['is_pillar'] ? 'fa-star' : 'fa-star-half-stroke' ?>"></i>
                                </button>
                            </form>
                        </td>
                        <td>
                            <?php if ((int) $s['combo_count'] > 0): ?>
                                <a href="<?= esc(base_url('admin/seo-combos.php?service=' . (int) $s['id'])) ?>"><?= (int) $s['combo_count'] ?> pages</a>
                            <?php else: ?>
                                <small class="text-muted">—</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post"><?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                <input type="hidden" name="action" value="toggle">
                                <button class="btn btn-sm <?= $s['is_published'] ? 'btn-success' : 'btn-outline-secondary' ?>" title="Toggle published">
                                    <i class="fa-solid <?= $s['is_published'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                                </button>
                            </form>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= esc(base_url('services/' . $s['slug'])) ?>" target="_blank" rel="noopener" title="View page"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= esc(base_url('admin/seo-service-form.php?id=' . (int) $s['id'])) ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                            <form class="d-inline" method="post" data-confirm="Delete this service page and all of its service × city pages?">
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
        <p class="text-muted mb-0">No service pages yet — add your first one, or import the generated content with <code>sql/seo-seed.sql</code>.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

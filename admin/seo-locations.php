<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_or_die();
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        db()->prepare('DELETE FROM seo_locations WHERE id = ?')->execute([$id]);
        flash_set('success', 'Location page deleted (its service × city pages went with it).');
    } elseif ($action === 'toggle') {
        db()->prepare('UPDATE seo_locations SET is_published = 1 - is_published WHERE id = ?')->execute([$id]);
    } elseif ($action === 'move_up' || $action === 'move_down') {
        move_row('seo_locations', $id, $action === 'move_up' ? 'up' : 'down');
    }
    redirect('admin/seo-locations.php');
}

$adminTitle = 'SEO Locations';
require __DIR__ . '/includes/header.php';

$locations = db()->query(
    'SELECT l.*, (SELECT COUNT(*) FROM seo_service_locations sl WHERE sl.location_id = l.id) AS combo_count
       FROM seo_locations l ORDER BY l.sort_order ASC, l.id ASC'
)->fetchAll();
$published = count(array_filter($locations, static fn ($l) => (int) $l['is_published'] === 1));
?>

<div class="admin-card">
    <div class="card-heading">
        <h5>Location Pages (<?= count($locations) ?> — <?= $published ?> live)</h5>
        <a class="btn btn-gold" href="<?= esc(base_url('admin/seo-location-form.php')) ?>"><i class="fa-solid fa-plus me-2"></i>Add Location</a>
    </div>
    <p class="text-muted">
        Each row publishes a page at <code>/handyman/&lt;slug&gt;</code> targeting “handyman &lt;city&gt;” searches.
        <strong>Tier 1</strong> marks the major cities that also get their own service × city pages.
    </p>

    <?php if ($locations): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr>
                    <th style="width:70px;">Order</th><th>Location</th><th>Region</th>
                    <th>Tier</th><th>Sub-pages</th><th>Live</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($locations as $i => $l): ?>
                    <tr class="<?= $l['is_published'] ? '' : 'opacity-50' ?>">
                        <td>
                            <form class="d-inline" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $l['id'] ?>"><input type="hidden" name="action" value="move_up"><button class="btn btn-sm btn-light" <?= $i === 0 ? 'disabled' : '' ?> title="Move up"><i class="fa-solid fa-chevron-up"></i></button></form>
                            <form class="d-inline" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $l['id'] ?>"><input type="hidden" name="action" value="move_down"><button class="btn btn-sm btn-light" <?= $i === count($locations) - 1 ? 'disabled' : '' ?> title="Move down"><i class="fa-solid fa-chevron-down"></i></button></form>
                        </td>
                        <td>
                            <strong><?= esc($l['name']) ?></strong><br>
                            <small class="text-muted">/handyman/<?= esc($l['slug']) ?></small>
                        </td>
                        <td><small><?= esc($l['region']) ?></small></td>
                        <td>
                            <span class="badge <?= (int) $l['tier'] === 1 ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                                Tier <?= (int) $l['tier'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ((int) $l['combo_count'] > 0): ?>
                                <a href="<?= esc(base_url('admin/seo-combos.php?location=' . (int) $l['id'])) ?>"><?= (int) $l['combo_count'] ?> pages</a>
                            <?php else: ?>
                                <small class="text-muted">—</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post"><?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                                <input type="hidden" name="action" value="toggle">
                                <button class="btn btn-sm <?= $l['is_published'] ? 'btn-success' : 'btn-outline-secondary' ?>" title="Toggle published">
                                    <i class="fa-solid <?= $l['is_published'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                                </button>
                            </form>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= esc(base_url('handyman/' . $l['slug'])) ?>" target="_blank" rel="noopener" title="View page"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= esc(base_url('admin/seo-location-form.php?id=' . (int) $l['id'])) ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                            <form class="d-inline" method="post" data-confirm="Delete this location page and all of its service × city pages?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted mb-0">No location pages yet — add your first one, or import the generated content with <code>sql/seo-seed.sql</code>.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

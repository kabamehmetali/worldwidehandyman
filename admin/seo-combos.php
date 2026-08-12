<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_or_die();
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        db()->prepare('DELETE FROM seo_service_locations WHERE id = ?')->execute([$id]);
        flash_set('success', 'Page deleted.');
    } elseif ($action === 'toggle') {
        db()->prepare('UPDATE seo_service_locations SET is_published = 1 - is_published WHERE id = ?')->execute([$id]);
    }
    redirect('admin/seo-combos.php' . (isset($_POST['back']) ? '?' . $_POST['back'] : ''));
}

$filterService  = (int) ($_GET['service'] ?? 0);
$filterLocation = (int) ($_GET['location'] ?? 0);

$where = [];
$params = [];
if ($filterService > 0) {
    $where[] = 'sl.service_id = ?';
    $params[] = $filterService;
}
if ($filterLocation > 0) {
    $where[] = 'sl.location_id = ?';
    $params[] = $filterLocation;
}

$stmt = db()->prepare(
    'SELECT sl.*, s.name AS service_name, s.slug AS service_slug,
            l.name AS location_name, l.slug AS location_slug
       FROM seo_service_locations sl
       JOIN seo_services s  ON s.id = sl.service_id
       JOIN seo_locations l ON l.id = sl.location_id'
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
    . ' ORDER BY s.sort_order ASC, l.sort_order ASC'
);
$stmt->execute($params);
$combos = $stmt->fetchAll();

$services  = db()->query('SELECT id, name FROM seo_services ORDER BY sort_order')->fetchAll();
$locations = db()->query('SELECT id, name FROM seo_locations ORDER BY sort_order')->fetchAll();
$backQuery = http_build_query(array_filter(['service' => $filterService, 'location' => $filterLocation]));

$adminTitle = 'Service × City Pages';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-card">
    <div class="card-heading">
        <h5>Service × City Pages (<?= count($combos) ?><?= ($filterService || $filterLocation) ? ' filtered' : '' ?>)</h5>
        <a class="btn btn-gold" href="<?= esc(base_url('admin/seo-combo-form.php')) ?>"><i class="fa-solid fa-plus me-2"></i>Add Page</a>
    </div>
    <p class="text-muted">
        These publish at <code>/services/&lt;service&gt;/&lt;city&gt;</code>. A page exists only where a row exists here —
        nothing is auto-generated from every possible pairing, which is what keeps Google from seeing a wall of
        near-identical pages. Each one needs copy that is genuinely specific to that service in that city.
    </p>

    <form class="row g-2 align-items-end mb-4" method="get">
        <div class="col-md-4">
            <label class="form-label" for="f-service">Filter by service</label>
            <select class="form-select" id="f-service" name="service">
                <option value="">All services</option>
                <?php foreach ($services as $s): ?>
                    <option value="<?= (int) $s['id'] ?>"<?= $filterService === (int) $s['id'] ? ' selected' : '' ?>><?= esc($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="f-location">Filter by city</label>
            <select class="form-select" id="f-location" name="location">
                <option value="">All cities</option>
                <?php foreach ($locations as $l): ?>
                    <option value="<?= (int) $l['id'] ?>"<?= $filterLocation === (int) $l['id'] ? ' selected' : '' ?>><?= esc($l['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <button class="btn btn-navy" type="submit"><i class="fa-solid fa-filter me-1"></i>Filter</button>
            <a class="btn btn-outline-secondary ms-1" href="<?= esc(base_url('admin/seo-combos.php')) ?>">Reset</a>
        </div>
    </form>

    <?php if ($combos): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Page</th><th>URL</th><th>Live</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($combos as $c): ?>
                    <tr class="<?= $c['is_published'] ? '' : 'opacity-50' ?>">
                        <td>
                            <strong><?= esc($c['service_name']) ?></strong>
                            <span class="text-muted">in</span>
                            <strong><?= esc($c['location_name']) ?></strong><br>
                            <small class="text-muted"><?= esc(mb_substr($c['meta_title'], 0, 70)) ?></small>
                        </td>
                        <td><small class="text-muted">/services/<?= esc($c['service_slug']) ?>/<?= esc($c['location_slug']) ?></small></td>
                        <td>
                            <form method="post"><?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="back" value="<?= esc($backQuery) ?>">
                                <button class="btn btn-sm <?= $c['is_published'] ? 'btn-success' : 'btn-outline-secondary' ?>" title="Toggle published">
                                    <i class="fa-solid <?= $c['is_published'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                                </button>
                            </form>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= esc(base_url('services/' . $c['service_slug'] . '/' . $c['location_slug'])) ?>" target="_blank" rel="noopener" title="View page"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= esc(base_url('admin/seo-combo-form.php?id=' . (int) $c['id'])) ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                            <form class="d-inline" method="post" data-confirm="Delete this page?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <input type="hidden" name="back" value="<?= esc($backQuery) ?>">
                                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted mb-0">No pages match. <a href="<?= esc(base_url('admin/seo-combo-form.php')) ?>">Add one</a>.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

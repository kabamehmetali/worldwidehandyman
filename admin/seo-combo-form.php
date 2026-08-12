<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$row = [
    'service_id' => 0, 'location_id' => 0, 'h1' => '', 'meta_title' => '',
    'meta_description' => '', 'intro' => '', 'local_angle' => '',
    'common_jobs' => '', 'faq_q' => '', 'faq_a' => '', 'is_published' => 1,
];

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM seo_service_locations WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash_set('error', 'Page not found.');
        redirect('admin/seo-combos.php');
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_or_die();

    $row['service_id']  = (int) ($_POST['service_id'] ?? 0);
    $row['location_id'] = (int) ($_POST['location_id'] ?? 0);
    foreach (['h1', 'meta_title', 'meta_description', 'intro', 'local_angle',
              'common_jobs', 'faq_q', 'faq_a'] as $field) {
        $row[$field] = trim((string) ($_POST[$field] ?? ''));
    }
    $row['is_published'] = isset($_POST['is_published']) ? 1 : 0;

    if ($row['service_id'] <= 0 || $row['location_id'] <= 0) {
        $errors[] = 'Pick both a service and a city.';
    } else {
        $stmt = db()->prepare('SELECT id FROM seo_service_locations WHERE service_id = ? AND location_id = ? AND id != ?');
        $stmt->execute([$row['service_id'], $row['location_id'], $id]);
        if ($stmt->fetch()) {
            $errors[] = 'A page for that service and city already exists.';
        }
    }
    if (trim($row['intro']) === '') {
        $errors[] = 'The intro cannot be empty — a page with no unique copy should not be published.';
    }

    if (!$errors) {
        $params = [
            $row['service_id'], $row['location_id'], mb_substr($row['h1'], 0, 200),
            mb_substr($row['meta_title'], 0, 200), mb_substr($row['meta_description'], 0, 255),
            $row['intro'], $row['local_angle'], $row['common_jobs'],
            mb_substr($row['faq_q'], 0, 300), $row['faq_a'], $row['is_published'],
        ];
        if ($id > 0) {
            $params[] = $id;
            db()->prepare(
                'UPDATE seo_service_locations SET service_id=?, location_id=?, h1=?, meta_title=?,
                    meta_description=?, intro=?, local_angle=?, common_jobs=?, faq_q=?, faq_a=?,
                    is_published=? WHERE id=?'
            )->execute($params);
            flash_set('success', 'Page updated.');
        } else {
            db()->prepare(
                'INSERT INTO seo_service_locations (service_id, location_id, h1, meta_title,
                    meta_description, intro, local_angle, common_jobs, faq_q, faq_a, is_published)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)'
            )->execute($params);
            flash_set('success', 'Page created.');
        }
        redirect('admin/seo-combos.php');
    }
}

$services  = db()->query('SELECT id, name, slug FROM seo_services ORDER BY is_pillar DESC, sort_order')->fetchAll();
$locations = db()->query('SELECT id, name, slug, tier FROM seo_locations ORDER BY tier, sort_order')->fetchAll();

$adminTitle = $id > 0 ? 'Edit Service × City Page' : 'Add Service × City Page';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-card">
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?= esc($error) ?></div>
    <?php endforeach; ?>

    <div class="alert alert-info">
        <strong>Write only what is unique to this pairing.</strong> The full service guide and the general
        city page are already published separately and are linked from this page — repeating them here creates
        duplicate content that competes with your own URLs. Every sentence below should be one that would be
        wrong or pointless for a different city or a different service.
    </div>

    <form method="post">
        <?= csrf_field() ?>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label" for="c-service">Service *</label>
                <select class="form-select" id="c-service" name="service_id" required>
                    <option value="">Choose a service…</option>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"<?= (int) $row['service_id'] === (int) $s['id'] ? ' selected' : '' ?>>
                            <?= esc($s['name']) ?> (<?= esc($s['slug']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="c-location">City *</label>
                <select class="form-select" id="c-location" name="location_id" required>
                    <option value="">Choose a city…</option>
                    <?php foreach ($locations as $l): ?>
                        <option value="<?= (int) $l['id'] ?>"<?= (int) $row['location_id'] === (int) $l['id'] ? ' selected' : '' ?>>
                            <?= esc($l['name']) ?> (tier <?= (int) $l['tier'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <h6 class="text-uppercase text-muted mb-3" style="letter-spacing:.08em;">Search Listing</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label" for="c-mt">Meta Title <small class="text-muted fw-normal">(30–50 characters)</small></label>
                <input class="form-control" type="text" id="c-mt" name="meta_title" maxlength="200" value="<?= esc($row['meta_title']) ?>" data-counter="c-mt-count">
                <div class="form-text"><span id="c-mt-count"><?= mb_strlen($row['meta_title']) ?></span> characters</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="c-h1">H1 Heading</label>
                <input class="form-control" type="text" id="c-h1" name="h1" maxlength="200" value="<?= esc($row['h1']) ?>">
            </div>
            <div class="col-12">
                <label class="form-label" for="c-md">Meta Description <small class="text-muted fw-normal">(140–158 characters)</small></label>
                <textarea class="form-control" id="c-md" name="meta_description" rows="2" maxlength="255" data-counter="c-md-count"><?= esc($row['meta_description']) ?></textarea>
                <div class="form-text"><span id="c-md-count"><?= mb_strlen($row['meta_description']) ?></span> characters</div>
            </div>
        </div>

        <h6 class="text-uppercase text-muted mb-3" style="letter-spacing:.08em;">Unique Copy</h6>
        <div class="row g-3 mb-4">
            <div class="col-12">
                <label class="form-label" for="c-intro">Intro * <small class="text-muted fw-normal">(70–110 words, plain text, blank line between paragraphs)</small></label>
                <textarea class="form-control" id="c-intro" name="intro" rows="5" required><?= esc($row['intro']) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="c-angle">Local Angle <small class="text-muted fw-normal">(60–95 words — the real technical or practical reality of this job in this city)</small></label>
                <textarea class="form-control" id="c-angle" name="local_angle" rows="4"><?= esc($row['local_angle']) ?></textarea>
                <div class="form-text">This is the field that stops the pages looking templated. Housing stock, wall construction, building access, what actually goes wrong here.</div>
            </div>
            <div class="col-12">
                <label class="form-label" for="c-jobs">Typical Calls <small class="text-muted fw-normal">(one per line)</small></label>
                <textarea class="form-control" id="c-jobs" name="common_jobs" rows="5"><?= esc($row['common_jobs']) ?></textarea>
            </div>
            <div class="col-md-5">
                <label class="form-label" for="c-fq">FAQ Question</label>
                <input class="form-control" type="text" id="c-fq" name="faq_q" maxlength="300" value="<?= esc($row['faq_q']) ?>">
            </div>
            <div class="col-md-7">
                <label class="form-label" for="c-fa">FAQ Answer <small class="text-muted fw-normal">(45–80 words)</small></label>
                <textarea class="form-control" id="c-fa" name="faq_a" rows="3"><?= esc($row['faq_a']) ?></textarea>
            </div>
        </div>

        <div class="d-flex align-items-center gap-4 border-top pt-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="c-pub" name="is_published" value="1"<?= $row['is_published'] ? ' checked' : '' ?>>
                <label class="form-check-label" for="c-pub">Published</label>
            </div>
            <div class="ms-auto">
                <a class="btn btn-outline-secondary" href="<?= esc(base_url('admin/seo-combos.php')) ?>">Cancel</a>
                <button class="btn btn-gold ms-2" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i><?= $id > 0 ? 'Save Changes' : 'Create Page' ?></button>
            </div>
        </div>
    </form>
</div>

<script>
(function () {
    'use strict';
    document.querySelectorAll('[data-counter]').forEach(function (field) {
        var out = document.getElementById(field.getAttribute('data-counter'));
        field.addEventListener('input', function () { out.textContent = field.value.length; });
    });
}());
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

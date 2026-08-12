<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$row = [
    'slug' => '', 'name' => '', 'region' => '', 'tier' => 2,
    'latitude' => '', 'longitude' => '', 'postal_prefixes' => '',
    'neighbourhoods' => '', 'landmarks' => '', 'nearby' => '',
    'meta_title' => '', 'meta_description' => '', 'h1' => '',
    'intro' => '', 'body_html' => '', 'local_notes' => '', 'common_jobs' => '',
    'faqs_json' => '[]', 'hero_image' => '', 'is_published' => 1,
];

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM seo_locations WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash_set('error', 'Location not found.');
        redirect('admin/seo-locations.php');
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_or_die();

    foreach (['name', 'region', 'postal_prefixes', 'neighbourhoods', 'landmarks',
              'nearby', 'meta_title', 'meta_description', 'h1', 'intro',
              'body_html', 'local_notes', 'common_jobs'] as $field) {
        $row[$field] = trim((string) ($_POST[$field] ?? ''));
    }
    $row['tier']         = (int) ($_POST['tier'] ?? 2) === 1 ? 1 : 2;
    $row['latitude']     = trim((string) ($_POST['latitude'] ?? ''));
    $row['longitude']    = trim((string) ($_POST['longitude'] ?? ''));
    $row['is_published'] = isset($_POST['is_published']) ? 1 : 0;
    $row['hero_image']   = $row['hero_image'] ?? '';

    // FAQs arrive as parallel question/answer arrays
    $faqs = [];
    $qs = $_POST['faq_q'] ?? [];
    $as = $_POST['faq_a'] ?? [];
    foreach ($qs as $i => $q) {
        $q = trim((string) $q);
        $a = trim((string) ($as[$i] ?? ''));
        if ($q !== '' && $a !== '') {
            $faqs[] = ['q' => $q, 'a' => $a];
        }
    }
    $row['faqs_json'] = json_encode($faqs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $slugInput = trim((string) ($_POST['slug'] ?? ''));
    $newSlug = mb_substr(slugify($slugInput !== '' ? $slugInput : $row['name']), 0, 120);

    if ($row['name'] === '') {
        $errors[] = 'Please enter the location name.';
    }
    $stmt = db()->prepare('SELECT id FROM seo_locations WHERE slug = ? AND id != ?');
    $stmt->execute([$newSlug, $id]);
    if ($stmt->fetch()) {
        $errors[] = 'Another location already uses the URL slug "' . $newSlug . '".';
    }
    if ($row['latitude'] !== '' && !is_numeric($row['latitude'])) {
        $errors[] = 'Latitude must be a number (or left blank).';
    }
    if ($row['longitude'] !== '' && !is_numeric($row['longitude'])) {
        $errors[] = 'Longitude must be a number (or left blank).';
    }

    if (!$errors) {
        $row['slug'] = $newSlug;
        $params = [
            $row['slug'], $row['name'], $row['region'], $row['tier'],
            $row['latitude'] === '' ? null : (float) $row['latitude'],
            $row['longitude'] === '' ? null : (float) $row['longitude'],
            $row['postal_prefixes'], $row['neighbourhoods'], $row['landmarks'],
            $row['nearby'], mb_substr($row['meta_title'], 0, 200),
            mb_substr($row['meta_description'], 0, 255), mb_substr($row['h1'], 0, 200),
            $row['intro'], $row['body_html'], $row['local_notes'],
            $row['common_jobs'], $row['faqs_json'], $row['hero_image'], $row['is_published'],
        ];
        if ($id > 0) {
            $params[] = $id;
            db()->prepare(
                'UPDATE seo_locations SET slug=?, name=?, region=?, tier=?, latitude=?, longitude=?,
                    postal_prefixes=?, neighbourhoods=?, landmarks=?, nearby=?, meta_title=?,
                    meta_description=?, h1=?, intro=?, body_html=?, local_notes=?, common_jobs=?,
                    faqs_json=?, hero_image=?, is_published=? WHERE id=?'
            )->execute($params);
            flash_set('success', 'Location page updated.');
        } else {
            $params[] = (int) db()->query('SELECT COALESCE(MAX(sort_order),0) FROM seo_locations')->fetchColumn() + 1;
            db()->prepare(
                'INSERT INTO seo_locations (slug, name, region, tier, latitude, longitude,
                    postal_prefixes, neighbourhoods, landmarks, nearby, meta_title, meta_description,
                    h1, intro, body_html, local_notes, common_jobs, faqs_json, hero_image,
                    is_published, sort_order)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute($params);
            flash_set('success', 'Location page created.');
        }
        redirect('admin/seo-locations.php');
    }
}

$faqRows = seo_json_list($row['faqs_json']);
if (!$faqRows) {
    $faqRows = [['q' => '', 'a' => '']];
}
$allLocations = db()->query('SELECT slug, name FROM seo_locations ORDER BY name')->fetchAll();

$adminTitle = $id > 0 ? 'Edit Location — ' . $row['name'] : 'Add Location';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-card">
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?= esc($error) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <?= csrf_field() ?>

        <h6 class="text-uppercase text-muted mb-3" style="letter-spacing:.08em;">Identity</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label" for="l-name">Location Name *</label>
                <input class="form-control" type="text" id="l-name" name="name" maxlength="120" required value="<?= esc($row['name']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="l-slug">URL Slug</label>
                <div class="input-group">
                    <span class="input-group-text">/handyman/</span>
                    <input class="form-control" type="text" id="l-slug" name="slug" maxlength="120" value="<?= esc($row['slug']) ?>">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="l-region">Region</label>
                <input class="form-control" type="text" id="l-region" name="region" maxlength="80" value="<?= esc($row['region']) ?>" placeholder="York Region">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="l-tier">Tier</label>
                <select class="form-select" id="l-tier" name="tier">
                    <option value="1"<?= (int) $row['tier'] === 1 ? ' selected' : '' ?>>1 — major city</option>
                    <option value="2"<?= (int) $row['tier'] !== 1 ? ' selected' : '' ?>>2 — neighbourhood</option>
                </select>
            </div>
        </div>

        <h6 class="text-uppercase text-muted mb-3" style="letter-spacing:.08em;">Search Listing</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label" for="l-mt">Meta Title <small class="text-muted fw-normal">(aim for 30–50 characters — “ | <?= esc(setting('site_name')) ?>” is added automatically)</small></label>
                <input class="form-control" type="text" id="l-mt" name="meta_title" maxlength="200" value="<?= esc($row['meta_title']) ?>" data-counter="l-mt-count">
                <div class="form-text"><span id="l-mt-count"><?= mb_strlen($row['meta_title']) ?></span> characters</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="l-h1">H1 Heading</label>
                <input class="form-control" type="text" id="l-h1" name="h1" maxlength="200" value="<?= esc($row['h1']) ?>">
            </div>
            <div class="col-12">
                <label class="form-label" for="l-md">Meta Description <small class="text-muted fw-normal">(aim for 140–158 characters)</small></label>
                <textarea class="form-control" id="l-md" name="meta_description" rows="2" maxlength="255" data-counter="l-md-count"><?= esc($row['meta_description']) ?></textarea>
                <div class="form-text"><span id="l-md-count"><?= mb_strlen($row['meta_description']) ?></span> characters</div>
            </div>
        </div>

        <h6 class="text-uppercase text-muted mb-3" style="letter-spacing:.08em;">Local Detail</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label" for="l-lat">Latitude</label>
                <input class="form-control" type="text" id="l-lat" name="latitude" value="<?= esc((string) $row['latitude']) ?>" placeholder="43.7615">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="l-lng">Longitude</label>
                <input class="form-control" type="text" id="l-lng" name="longitude" value="<?= esc((string) $row['longitude']) ?>" placeholder="-79.4111">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="l-pc">Postal Code Prefixes</label>
                <input class="form-control" type="text" id="l-pc" name="postal_prefixes" maxlength="160" value="<?= esc($row['postal_prefixes']) ?>" placeholder="M2M, M2N, M2R">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="l-hoods">Neighbourhoods <small class="text-muted fw-normal">(one per line)</small></label>
                <textarea class="form-control" id="l-hoods" name="neighbourhoods" rows="6"><?= esc($row['neighbourhoods']) ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="l-marks">Landmarks <small class="text-muted fw-normal">(one per line)</small></label>
                <textarea class="form-control" id="l-marks" name="landmarks" rows="6"><?= esc($row['landmarks']) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="l-near">Nearby Locations <small class="text-muted fw-normal">(comma-separated slugs — these become the “also serving” links)</small></label>
                <input class="form-control" type="text" id="l-near" name="nearby" maxlength="500" value="<?= esc($row['nearby']) ?>" list="loc-slugs">
                <datalist id="loc-slugs">
                    <?php foreach ($allLocations as $opt): ?>
                        <option value="<?= esc($opt['slug']) ?>"><?= esc($opt['name']) ?></option>
                    <?php endforeach; ?>
                </datalist>
            </div>
        </div>

        <h6 class="text-uppercase text-muted mb-3" style="letter-spacing:.08em;">Page Copy</h6>
        <div class="row g-3 mb-4">
            <div class="col-12">
                <label class="form-label" for="l-intro">Intro <small class="text-muted fw-normal">(plain text — leave a blank line between paragraphs)</small></label>
                <textarea class="form-control" id="l-intro" name="intro" rows="5"><?= esc($row['intro']) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="l-notes">Local Note <small class="text-muted fw-normal">(one paragraph, shown in the gold callout box)</small></label>
                <textarea class="form-control" id="l-notes" name="local_notes" rows="3"><?= esc($row['local_notes']) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="l-jobs">Common Jobs Here <small class="text-muted fw-normal">(one per line)</small></label>
                <textarea class="form-control" id="l-jobs" name="common_jobs" rows="6"><?= esc($row['common_jobs']) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="l-body">Main Body <small class="text-muted fw-normal">(HTML — only &lt;h2&gt; &lt;h3&gt; &lt;p&gt; &lt;ul&gt; &lt;li&gt; &lt;strong&gt; are rendered)</small></label>
                <textarea class="form-control font-monospace" id="l-body" name="body_html" rows="16" style="font-size:.85rem;"><?= esc($row['body_html']) ?></textarea>
                <div class="form-text">Aim for 450–650 words of genuinely local detail. Thin or duplicated copy is what gets these pages ignored by Google.</div>
            </div>
        </div>

        <h6 class="text-uppercase text-muted mb-3" style="letter-spacing:.08em;">FAQs <small class="text-muted fw-normal text-lowercase">(marked up as FAQ structured data)</small></h6>
        <div id="faq-rows" class="mb-3">
            <?php foreach ($faqRows as $faq): ?>
                <div class="row g-2 mb-2 faq-row">
                    <div class="col-md-5"><input class="form-control" type="text" name="faq_q[]" placeholder="Question" value="<?= esc($faq['q'] ?? '') ?>"></div>
                    <div class="col-md-6"><textarea class="form-control" name="faq_a[]" rows="2" placeholder="Answer"><?= esc($faq['a'] ?? '') ?></textarea></div>
                    <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100 faq-remove" title="Remove"><i class="fa-solid fa-trash"></i></button></div>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mb-4" id="faq-add"><i class="fa-solid fa-plus me-1"></i>Add FAQ</button>

        <div class="d-flex align-items-center gap-4 border-top pt-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="l-pub" name="is_published" value="1"<?= $row['is_published'] ? ' checked' : '' ?>>
                <label class="form-check-label" for="l-pub">Published</label>
            </div>
            <div class="ms-auto">
                <a class="btn btn-outline-secondary" href="<?= esc(base_url('admin/seo-locations.php')) ?>">Cancel</a>
                <button class="btn btn-gold ms-2" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i><?= $id > 0 ? 'Save Changes' : 'Create Location' ?></button>
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
    var wrap = document.getElementById('faq-rows');
    document.getElementById('faq-add').addEventListener('click', function () {
        var row = wrap.querySelector('.faq-row').cloneNode(true);
        row.querySelectorAll('input, textarea').forEach(function (f) { f.value = ''; });
        wrap.appendChild(row);
    });
    wrap.addEventListener('click', function (e) {
        var btn = e.target.closest('.faq-remove');
        if (btn && wrap.querySelectorAll('.faq-row').length > 1) { btn.closest('.faq-row').remove(); }
    });
}());
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$row = [
    'slug' => '', 'name' => '', 'h1' => '', 'icon' => 'fa-solid fa-screwdriver-wrench',
    'keywords' => '', 'is_pillar' => 0, 'meta_title' => '', 'meta_description' => '',
    'intro' => '', 'body_html' => '', 'jobs' => '', 'process_json' => '[]',
    'pricing_notes' => '', 'faqs_json' => '[]', 'related' => '', 'hero_image' => '',
    'is_published' => 1,
];

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM seo_services WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash_set('error', 'Service page not found.');
        redirect('admin/seo-services.php');
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_or_die();

    foreach (['name', 'h1', 'icon', 'keywords', 'meta_title', 'meta_description',
              'intro', 'body_html', 'jobs', 'pricing_notes', 'related'] as $field) {
        $row[$field] = trim((string) ($_POST[$field] ?? ''));
    }
    $row['is_pillar']    = isset($_POST['is_pillar']) ? 1 : 0;
    $row['is_published'] = isset($_POST['is_published']) ? 1 : 0;
    $row['hero_image']   = $row['hero_image'] ?? '';

    $faqs = [];
    foreach (($_POST['faq_q'] ?? []) as $i => $q) {
        $q = trim((string) $q);
        $a = trim((string) ($_POST['faq_a'][$i] ?? ''));
        if ($q !== '' && $a !== '') {
            $faqs[] = ['q' => $q, 'a' => $a];
        }
    }
    $row['faqs_json'] = json_encode($faqs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $steps = [];
    foreach (($_POST['step_title'] ?? []) as $i => $t) {
        $t = trim((string) $t);
        $x = trim((string) ($_POST['step_text'][$i] ?? ''));
        if ($t !== '' && $x !== '') {
            $steps[] = ['title' => $t, 'text' => $x];
        }
    }
    $row['process_json'] = json_encode($steps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $slugInput = trim((string) ($_POST['slug'] ?? ''));
    $newSlug = mb_substr(slugify($slugInput !== '' ? $slugInput : $row['name']), 0, 120);

    if ($row['name'] === '') {
        $errors[] = 'Please enter the service name.';
    }
    $stmt = db()->prepare('SELECT id FROM seo_services WHERE slug = ? AND id != ?');
    $stmt->execute([$newSlug, $id]);
    if ($stmt->fetch()) {
        $errors[] = 'Another service already uses the URL slug "' . $newSlug . '".';
    }

    if (!$errors) {
        $row['slug'] = $newSlug;
        $params = [
            $row['slug'], $row['name'], mb_substr($row['h1'], 0, 200), $row['icon'],
            mb_substr($row['keywords'], 0, 600), $row['is_pillar'],
            mb_substr($row['meta_title'], 0, 200), mb_substr($row['meta_description'], 0, 255),
            $row['intro'], $row['body_html'], $row['jobs'], $row['process_json'],
            $row['pricing_notes'], $row['faqs_json'], mb_substr($row['related'], 0, 500),
            $row['hero_image'], $row['is_published'],
        ];
        if ($id > 0) {
            $params[] = $id;
            db()->prepare(
                'UPDATE seo_services SET slug=?, name=?, h1=?, icon=?, keywords=?, is_pillar=?,
                    meta_title=?, meta_description=?, intro=?, body_html=?, jobs=?, process_json=?,
                    pricing_notes=?, faqs_json=?, related=?, hero_image=?, is_published=? WHERE id=?'
            )->execute($params);
            flash_set('success', 'Service page updated.');
        } else {
            $params[] = (int) db()->query('SELECT COALESCE(MAX(sort_order),0) FROM seo_services')->fetchColumn() + 1;
            db()->prepare(
                'INSERT INTO seo_services (slug, name, h1, icon, keywords, is_pillar, meta_title,
                    meta_description, intro, body_html, jobs, process_json, pricing_notes,
                    faqs_json, related, hero_image, is_published, sort_order)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute($params);
            flash_set('success', 'Service page created.');
        }
        redirect('admin/seo-services.php');
    }
}

$faqRows = seo_json_list($row['faqs_json']) ?: [['q' => '', 'a' => '']];
$stepRows = seo_json_list($row['process_json']) ?: [['title' => '', 'text' => '']];
$allServices = db()->query('SELECT slug, name FROM seo_services ORDER BY name')->fetchAll();

$adminTitle = $id > 0 ? 'Edit Service Page — ' . $row['name'] : 'Add Service Page';
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
                <label class="form-label" for="s-name">Service Name *</label>
                <input class="form-control" type="text" id="s-name" name="name" maxlength="150" required value="<?= esc($row['name']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="s-slug">URL Slug</label>
                <div class="input-group">
                    <span class="input-group-text">/services/</span>
                    <input class="form-control" type="text" id="s-slug" name="slug" maxlength="120" value="<?= esc($row['slug']) ?>">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="s-icon">Font Awesome Icon</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="<?= esc($row['icon']) ?>" id="icon-preview"></i></span>
                    <input class="form-control" type="text" id="s-icon" name="icon" maxlength="80" value="<?= esc($row['icon']) ?>">
                </div>
            </div>
        </div>

        <h6 class="text-uppercase text-muted mb-3" style="letter-spacing:.08em;">Search Listing</h6>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label" for="s-mt">Meta Title <small class="text-muted fw-normal">(30–50 characters — the site name is added automatically)</small></label>
                <input class="form-control" type="text" id="s-mt" name="meta_title" maxlength="200" value="<?= esc($row['meta_title']) ?>" data-counter="s-mt-count">
                <div class="form-text"><span id="s-mt-count"><?= mb_strlen($row['meta_title']) ?></span> characters</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="s-h1">H1 Heading</label>
                <input class="form-control" type="text" id="s-h1" name="h1" maxlength="200" value="<?= esc($row['h1']) ?>">
            </div>
            <div class="col-12">
                <label class="form-label" for="s-md">Meta Description <small class="text-muted fw-normal">(140–158 characters)</small></label>
                <textarea class="form-control" id="s-md" name="meta_description" rows="2" maxlength="255" data-counter="s-md-count"><?= esc($row['meta_description']) ?></textarea>
                <div class="form-text"><span id="s-md-count"><?= mb_strlen($row['meta_description']) ?></span> characters</div>
            </div>
            <div class="col-12">
                <label class="form-label" for="s-kw">Target Keywords <small class="text-muted fw-normal">(comma-separated — for your own reference when writing the copy)</small></label>
                <input class="form-control" type="text" id="s-kw" name="keywords" maxlength="600" value="<?= esc($row['keywords']) ?>">
            </div>
        </div>

        <h6 class="text-uppercase text-muted mb-3" style="letter-spacing:.08em;">Page Copy</h6>
        <div class="row g-3 mb-4">
            <div class="col-12">
                <label class="form-label" for="s-intro">Intro <small class="text-muted fw-normal">(plain text — leave a blank line between paragraphs)</small></label>
                <textarea class="form-control" id="s-intro" name="intro" rows="5"><?= esc($row['intro']) ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="s-jobs">What's Included <small class="text-muted fw-normal">(one per line)</small></label>
                <textarea class="form-control" id="s-jobs" name="jobs" rows="8"><?= esc($row['jobs']) ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="s-price">What Affects the Price <small class="text-muted fw-normal">(one paragraph — never quote figures)</small></label>
                <textarea class="form-control" id="s-price" name="pricing_notes" rows="8"><?= esc($row['pricing_notes']) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="s-body">Main Body <small class="text-muted fw-normal">(HTML — only &lt;h2&gt; &lt;h3&gt; &lt;p&gt; &lt;ul&gt; &lt;li&gt; &lt;strong&gt; are rendered)</small></label>
                <textarea class="form-control font-monospace" id="s-body" name="body_html" rows="18" style="font-size:.85rem;"><?= esc($row['body_html']) ?></textarea>
                <div class="form-text">Aim for 550–800 words. Explaining <em>how</em> the work is done is what makes these pages rank.</div>
            </div>
            <div class="col-12">
                <label class="form-label" for="s-rel">Related Services <small class="text-muted fw-normal">(comma-separated slugs)</small></label>
                <input class="form-control" type="text" id="s-rel" name="related" maxlength="500" value="<?= esc($row['related']) ?>" list="svc-slugs">
                <datalist id="svc-slugs">
                    <?php foreach ($allServices as $opt): ?>
                        <option value="<?= esc($opt['slug']) ?>"><?= esc($opt['name']) ?></option>
                    <?php endforeach; ?>
                </datalist>
            </div>
        </div>

        <h6 class="text-uppercase text-muted mb-3" style="letter-spacing:.08em;">Process Steps <small class="text-muted fw-normal text-lowercase">(marked up as HowTo structured data)</small></h6>
        <div id="step-rows" class="mb-3">
            <?php foreach ($stepRows as $step): ?>
                <div class="row g-2 mb-2 step-row">
                    <div class="col-md-3"><input class="form-control" type="text" name="step_title[]" placeholder="Step title" value="<?= esc($step['title'] ?? '') ?>"></div>
                    <div class="col-md-8"><textarea class="form-control" name="step_text[]" rows="2" placeholder="What happens in this step"><?= esc($step['text'] ?? '') ?></textarea></div>
                    <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100 step-remove" title="Remove"><i class="fa-solid fa-trash"></i></button></div>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mb-4" id="step-add"><i class="fa-solid fa-plus me-1"></i>Add Step</button>

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

        <div class="d-flex align-items-center gap-4 border-top pt-3 flex-wrap">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="s-pub" name="is_published" value="1"<?= $row['is_published'] ? ' checked' : '' ?>>
                <label class="form-check-label" for="s-pub">Published</label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="s-pillar" name="is_pillar" value="1"<?= $row['is_pillar'] ? ' checked' : '' ?>>
                <label class="form-check-label" for="s-pillar">Pillar service (gets its own service × city pages)</label>
            </div>
            <div class="ms-auto">
                <a class="btn btn-outline-secondary" href="<?= esc(base_url('admin/seo-services.php')) ?>">Cancel</a>
                <button class="btn btn-gold ms-2" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i><?= $id > 0 ? 'Save Changes' : 'Create Service Page' ?></button>
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
    var icon = document.getElementById('s-icon');
    var preview = document.getElementById('icon-preview');
    icon.addEventListener('input', function () { preview.className = icon.value; });

    [['faq-rows', 'faq-add', '.faq-row', '.faq-remove'],
     ['step-rows', 'step-add', '.step-row', '.step-remove']].forEach(function (set) {
        var wrap = document.getElementById(set[0]);
        document.getElementById(set[1]).addEventListener('click', function () {
            var row = wrap.querySelector(set[2]).cloneNode(true);
            row.querySelectorAll('input, textarea').forEach(function (f) { f.value = ''; });
            wrap.appendChild(row);
        });
        wrap.addEventListener('click', function (e) {
            var btn = e.target.closest(set[3]);
            if (btn && wrap.querySelectorAll(set[2]).length > 1) { btn.closest(set[2]).remove(); }
        });
    });
}());
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

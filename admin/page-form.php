<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$page = ['slug' => '', 'title' => '', 'meta_description' => '', 'content' => '', 'is_published' => 1];

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM pages WHERE id = ?');
    $stmt->execute([$id]);
    $page = $stmt->fetch();
    if (!$page) {
        flash_set('error', 'Page not found.');
        redirect('admin/pages.php');
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_or_die();

    $page['title'] = trim($_POST['title'] ?? '');
    $slugInput = trim($_POST['slug'] ?? '');
    $page['meta_description'] = mb_substr(trim($_POST['meta_description'] ?? ''), 0, 255);
    $page['content'] = $_POST['content'] ?? '';
    $page['is_published'] = isset($_POST['is_published']) ? 1 : 0;
    $addToNav = isset($_POST['add_to_nav']);

    if ($page['title'] === '' || mb_strlen($page['title']) > 200) {
        $errors[] = 'Please enter a title (max 200 characters).';
    }

    $newSlug = rtrim(mb_substr(slugify($slugInput !== '' ? $slugInput : $page['title']), 0, 120), '-');
    if ($newSlug === '') {
        $newSlug = 'page';
    }
    $stmt = db()->prepare('SELECT id FROM pages WHERE slug = ? AND id != ?');
    $stmt->execute([$newSlug, $id]);
    if ($stmt->fetch()) {
        $errors[] = 'Another page already uses the URL slug "' . $newSlug . '" — pick a different one.';
    }

    if (!$errors) {
        $oldSlug = $page['slug'];
        $page['slug'] = $newSlug;
        if ($id > 0) {
            db()->prepare('UPDATE pages SET slug = ?, title = ?, meta_description = ?, content = ?, is_published = ? WHERE id = ?')
                ->execute([$page['slug'], $page['title'], $page['meta_description'], $page['content'], $page['is_published'], $id]);
            if ($oldSlug !== '' && $oldSlug !== $page['slug']) {
                db()->prepare('UPDATE nav_links SET url = ? WHERE url IN (?, ?, ?)')
                    ->execute([
                        'pages/' . $page['slug'],
                        'pages/' . $oldSlug,
                        'page?slug=' . $oldSlug,
                        'page.php?slug=' . $oldSlug,
                    ]);
            }
            flash_set('success', 'Page updated.');
        } else {
            db()->prepare('INSERT INTO pages (slug, title, meta_description, content, is_published) VALUES (?, ?, ?, ?, ?)')
                ->execute([$page['slug'], $page['title'], $page['meta_description'], $page['content'], $page['is_published']]);
            flash_set('success', 'Page created.');
        }

        if ($addToNav) {
            $navUrl = 'pages/' . $page['slug'];
            $stmt = db()->prepare('SELECT id FROM nav_links WHERE url IN (?, ?, ?)');
            $stmt->execute([
                $navUrl,
                'page?slug=' . $page['slug'],
                'page.php?slug=' . $page['slug'],
            ]);
            if (!$stmt->fetch()) {
                // Insert just before the CTA button(s) so the gold button stays last
                $ctaMin = db()->query('SELECT MIN(sort_order) FROM nav_links WHERE is_cta = 1')->fetchColumn();
                if ($ctaMin !== false && $ctaMin !== null) {
                    db()->prepare('UPDATE nav_links SET sort_order = sort_order + 1 WHERE sort_order >= ?')
                        ->execute([(int) $ctaMin]);
                    $sort = (int) $ctaMin;
                } else {
                    $sort = (int) db()->query('SELECT COALESCE(MAX(sort_order), 0) FROM nav_links')->fetchColumn() + 1;
                }
                db()->prepare('INSERT INTO nav_links (label, url, sort_order, is_visible) VALUES (?, ?, ?, 1)')
                    ->execute([mb_substr($page['title'], 0, 100), $navUrl, $sort]);
                flash_set('success', 'Navigation link added — reorder it under Navigation.');
            }
        }
        redirect('admin/pages.php');
    }
}

$adminTitle = $id > 0 ? 'Edit Page' : 'Create Page';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-card">
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?= esc($error) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-7">
                <label class="form-label" for="p-title">Page Title *</label>
                <input class="form-control" type="text" id="p-title" name="title" maxlength="200" required value="<?= esc($page['title']) ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label" for="p-slug">URL Slug <span class="text-muted fw-normal">(auto from title if empty)</span></label>
                <div class="input-group">
                    <span class="input-group-text">pages/</span>
                    <input class="form-control" type="text" id="p-slug" name="slug" maxlength="120" value="<?= esc($page['slug']) ?>">
                </div>
            </div>
            <div class="col-12">
                <label class="form-label" for="p-meta">Meta Description <span class="text-muted fw-normal">(for search engines)</span></label>
                <input class="form-control" type="text" id="p-meta" name="meta_description" maxlength="255" value="<?= esc($page['meta_description']) ?>">
            </div>
            <div class="col-12">
                <label class="form-label" for="p-content">Content (HTML allowed)</label>
                <textarea class="form-control font-monospace" id="p-content" name="content" rows="16" style="font-size: .85rem;"><?= esc($page['content']) ?></textarea>
                <div class="form-text">
                    You can use full HTML and Bootstrap 5 classes. Useful patterns:
                    <code>&lt;p class="lead"&gt;intro&lt;/p&gt;</code>,
                    <code>&lt;div class="row g-3"&gt;&lt;div class="col-md-6"&gt;…&lt;/div&gt;&lt;/div&gt;</code>,
                    <code>&lt;div class="area-card"&gt;…&lt;/div&gt;</code>
                </div>
            </div>
            <div class="col-12 d-flex gap-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="p-published" name="is_published" value="1"<?= $page['is_published'] ? ' checked' : '' ?>>
                    <label class="form-check-label" for="p-published">Published</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="p-nav" name="add_to_nav" value="1">
                    <label class="form-check-label" for="p-nav">Add a link to this page in the navigation menu</label>
                </div>
            </div>
            <div class="col-12">
                <button class="btn btn-gold" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i><?= $id > 0 ? 'Save Changes' : 'Create Page' ?></button>
                <a class="btn btn-outline-secondary ms-2" href="<?= esc(base_url('admin/pages.php')) ?>">Cancel</a>
            </div>
        </div>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$link = ['label' => '', 'url' => '', 'is_visible' => 1, 'open_new_tab' => 0, 'is_cta' => 0];

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM nav_links WHERE id = ?');
    $stmt->execute([$id]);
    $link = $stmt->fetch();
    if (!$link) {
        flash_set('error', 'Navigation link not found.');
        redirect('admin/navigation.php');
    }
}

// Suggestions for the URL dropdown
$builtinTargets = [
    'index.php' => 'Home', 'services.php' => 'Services', 'about.php' => 'About',
    'gallery.php' => 'Gallery', 'faq.php' => 'FAQ', 'contact.php' => 'Contact', 'quote.php' => 'Get a Quote',
];
$customPages = db()->query('SELECT slug, title FROM pages ORDER BY title ASC')->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_or_die();

    $link['label'] = trim($_POST['label'] ?? '');
    $link['url'] = trim($_POST['url'] ?? '');
    $link['is_visible'] = isset($_POST['is_visible']) ? 1 : 0;
    $link['open_new_tab'] = isset($_POST['open_new_tab']) ? 1 : 0;
    $link['is_cta'] = isset($_POST['is_cta']) ? 1 : 0;

    if ($link['label'] === '' || mb_strlen($link['label']) > 100) {
        $errors[] = 'Please enter a label (max 100 characters).';
    }
    if ($link['url'] === '' || mb_strlen($link['url']) > 255) {
        $errors[] = 'Please enter a URL — a site page like services.php or a full link like https://example.com';
    }

    if (!$errors) {
        if ($id > 0) {
            db()->prepare('UPDATE nav_links SET label = ?, url = ?, is_visible = ?, open_new_tab = ?, is_cta = ? WHERE id = ?')
                ->execute([$link['label'], $link['url'], $link['is_visible'], $link['open_new_tab'], $link['is_cta'], $id]);
            flash_set('success', 'Navigation link updated.');
        } else {
            $max = (int) db()->query('SELECT COALESCE(MAX(sort_order), 0) FROM nav_links')->fetchColumn();
            db()->prepare('INSERT INTO nav_links (label, url, sort_order, is_visible, open_new_tab, is_cta) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$link['label'], $link['url'], $max + 1, $link['is_visible'], $link['open_new_tab'], $link['is_cta']]);
            flash_set('success', 'Navigation link added.');
        }
        redirect('admin/navigation.php');
    }
}

$adminTitle = $id > 0 ? 'Edit Navigation Link' : 'Add Navigation Link';
require __DIR__ . '/includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="admin-card">
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger"><?= esc($error) ?></div>
            <?php endforeach; ?>

            <form method="post">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="n-label">Label *</label>
                        <input class="form-control" type="text" id="n-label" name="label" maxlength="100" required value="<?= esc($link['label']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="n-url">URL *</label>
                        <input class="form-control" type="text" id="n-url" name="url" maxlength="255" required value="<?= esc($link['url']) ?>" list="url-suggestions" placeholder="services.php or https://…">
                        <datalist id="url-suggestions">
                            <?php foreach ($builtinTargets as $url => $label): ?>
                                <option value="<?= esc($url) ?>"><?= esc($label) ?></option>
                            <?php endforeach; ?>
                            <?php foreach ($customPages as $p): ?>
                                <option value="page.php?slug=<?= esc($p['slug']) ?>"><?= esc($p['title']) ?> (custom page)</option>
                            <?php endforeach; ?>
                        </datalist>
                        <div class="form-text">Pick a suggestion or type any internal page / external https:// link.</div>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="n-visible" name="is_visible" value="1"<?= $link['is_visible'] ? ' checked' : '' ?>>
                            <label class="form-check-label" for="n-visible">Visible in menu</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="n-newtab" name="open_new_tab" value="1"<?= $link['open_new_tab'] ? ' checked' : '' ?>>
                            <label class="form-check-label" for="n-newtab">Open in new tab</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="n-cta" name="is_cta" value="1"<?= $link['is_cta'] ? ' checked' : '' ?>>
                            <label class="form-check-label" for="n-cta">Button style (gold call-to-action)</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-gold" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i><?= $id > 0 ? 'Save Changes' : 'Add Link' ?></button>
                        <a class="btn btn-outline-secondary ms-2" href="<?= esc(base_url('admin/navigation.php')) ?>">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

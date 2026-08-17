<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_or_die();
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $stmt = db()->prepare('SELECT slug FROM pages WHERE id = ?');
        $stmt->execute([$id]);
        if ($row = $stmt->fetch()) {
            // remove any nav links pointing at this page
            db()->prepare('DELETE FROM nav_links WHERE url IN (?, ?, ?)')->execute([
                'pages/' . $row['slug'],
                'page?slug=' . $row['slug'],
                'page.php?slug=' . $row['slug'],
            ]);
        }
        db()->prepare('DELETE FROM pages WHERE id = ?')->execute([$id]);
        flash_set('success', 'Page deleted (and its navigation link, if any).');
    } elseif ($action === 'toggle') {
        db()->prepare('UPDATE pages SET is_published = 1 - is_published WHERE id = ?')->execute([$id]);
    }
    redirect('admin/pages.php');
}

$adminTitle = 'Pages';
require __DIR__ . '/includes/header.php';

$pages = db()->query('SELECT * FROM pages ORDER BY title ASC')->fetchAll();
?>

<div class="admin-card">
    <div class="card-heading">
        <h5>Custom Pages (<?= count($pages) ?>)</h5>
        <a class="btn btn-gold" href="<?= esc(base_url('admin/page-form.php')) ?>"><i class="fa-solid fa-plus me-2"></i>Create Page</a>
    </div>
    <p class="text-muted small">Built-in pages (Home, Services, About, Gallery, FAQ, Contact, Get a Quote) are managed through <a href="<?= esc(base_url('admin/settings.php')) ?>">Settings</a> and the content sections. Pages you create here get their own URL and can be added to the navigation.</p>
    <?php if ($pages): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Title</th><th>URL</th><th>Updated</th><th>Published</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($pages as $p): ?>
                    <tr class="<?= $p['is_published'] ? '' : 'opacity-50' ?>">
                        <td><strong><?= esc($p['title']) ?></strong></td>
                        <td><a href="<?= esc(custom_page_url($p['slug'])) ?>" target="_blank" rel="noopener"><code>pages/<?= esc($p['slug']) ?></code> <i class="fa-solid fa-arrow-up-right-from-square fa-xs"></i></a></td>
                        <td><small><?= esc(date('M j, Y', strtotime($p['updated_at']))) ?></small></td>
                        <td>
                            <form method="post"><?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                <input type="hidden" name="action" value="toggle">
                                <button class="btn btn-sm <?= $p['is_published'] ? 'btn-success' : 'btn-outline-secondary' ?>"><i class="fa-solid <?= $p['is_published'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i></button>
                            </form>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= esc(base_url('admin/page-form.php?id=' . (int) $p['id'])) ?>"><i class="fa-solid fa-pen"></i></a>
                            <form class="d-inline" method="post" data-confirm="Delete this page permanently? Its navigation link will be removed too.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted mb-0">No custom pages yet.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_or_die();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'upload' && !empty($_FILES['images'])) {
        $uploaded = 0;
        $failed = [];
        $max = (int) db()->query('SELECT COALESCE(MAX(sort_order), 0) FROM gallery')->fetchColumn();
        $files = $_FILES['images'];
        foreach ($files['name'] as $i => $name) {
            if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ];
            [$path, $error] = handle_image_upload($file, 'gallery', 'gallery');
            if ($path === null) {
                $failed[] = $name . ' (' . $error . ')';
            } else {
                $title = trim(pathinfo($name, PATHINFO_FILENAME));
                db()->prepare('INSERT INTO gallery (title, image_path, sort_order) VALUES (?, ?, ?)')
                    ->execute([mb_substr($title, 0, 150), $path, ++$max]);
                $uploaded++;
            }
        }
        if ($uploaded > 0) {
            flash_set('success', $uploaded . ' photo(s) uploaded.');
        }
        if ($failed) {
            flash_set('error', 'Some uploads failed: ' . implode(', ', $failed));
        }
    } elseif ($action === 'save_title') {
        db()->prepare('UPDATE gallery SET title = ? WHERE id = ?')
            ->execute([mb_substr(trim($_POST['title'] ?? ''), 0, 150), $id]);
        flash_set('success', 'Title saved.');
    } elseif ($action === 'toggle') {
        db()->prepare('UPDATE gallery SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
    } elseif ($action === 'delete') {
        $stmt = db()->prepare('SELECT image_path FROM gallery WHERE id = ?');
        $stmt->execute([$id]);
        if ($row = $stmt->fetch()) {
            delete_upload($row['image_path']);
        }
        db()->prepare('DELETE FROM gallery WHERE id = ?')->execute([$id]);
        flash_set('success', 'Photo deleted.');
    } elseif ($action === 'move_up' || $action === 'move_down') {
        move_row('gallery', $id, $action === 'move_up' ? 'up' : 'down');
    }
    redirect('admin/gallery.php');
}

$adminTitle = 'Gallery';
require __DIR__ . '/includes/header.php';

$images = db()->query('SELECT * FROM gallery ORDER BY sort_order ASC, id ASC')->fetchAll();
?>

<div class="admin-card">
    <div class="card-heading"><h5><i class="fa-solid fa-upload me-2 text-warning"></i>Upload Photos</h5></div>
    <form method="post" enctype="multipart/form-data" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="upload">
        <div class="col-md-9">
            <label class="form-label" for="g-images">Choose one or more images (JPG, PNG, GIF, WebP — max 8 MB each)</label>
            <input class="form-control" type="file" id="g-images" name="images[]" accept="image/*" multiple required>
        </div>
        <div class="col-md-3">
            <button class="btn btn-gold w-100" type="submit"><i class="fa-solid fa-upload me-2"></i>Upload</button>
        </div>
    </form>
</div>

<div class="admin-card">
    <div class="card-heading"><h5>Photos (<?= count($images) ?>)</h5></div>
    <?php if ($images): ?>
        <div class="row g-3">
            <?php foreach ($images as $i => $g): ?>
                <div class="col-sm-6 col-md-4 col-xl-3">
                    <div class="border rounded-3 p-2 h-100 <?= $g['is_active'] ? '' : 'opacity-50' ?>">
                        <img class="thumb-lg mb-2" src="<?= esc(base_url($g['image_path'])) ?>" alt="">
                        <form method="post" class="d-flex gap-1 mb-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="save_title">
                            <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                            <input class="form-control form-control-sm" type="text" name="title" maxlength="150" value="<?= esc($g['title']) ?>" placeholder="Title">
                            <button class="btn btn-sm btn-outline-secondary" title="Save title"><i class="fa-solid fa-floppy-disk"></i></button>
                        </form>
                        <div class="d-flex justify-content-between">
                            <div>
                                <form class="d-inline" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $g['id'] ?>"><input type="hidden" name="action" value="move_up"><button class="btn btn-sm btn-light" <?= $i === 0 ? 'disabled' : '' ?>><i class="fa-solid fa-chevron-left"></i></button></form>
                                <form class="d-inline" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $g['id'] ?>"><input type="hidden" name="action" value="move_down"><button class="btn btn-sm btn-light" <?= $i === count($images) - 1 ? 'disabled' : '' ?>><i class="fa-solid fa-chevron-right"></i></button></form>
                            </div>
                            <div>
                                <form class="d-inline" method="post"><?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <button class="btn btn-sm <?= $g['is_active'] ? 'btn-success' : 'btn-outline-secondary' ?>" title="Toggle visibility"><i class="fa-solid <?= $g['is_active'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i></button>
                                </form>
                                <form class="d-inline" method="post" data-confirm="Delete this photo permanently?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-muted mb-0">No photos yet — upload some above.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

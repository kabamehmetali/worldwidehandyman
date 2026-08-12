<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$service = ['title' => '', 'icon' => 'fa-solid fa-screwdriver-wrench', 'short_desc' => '', 'job_list' => '', 'image_path' => '', 'is_active' => 1];

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM services WHERE id = ?');
    $stmt->execute([$id]);
    $service = $stmt->fetch();
    if (!$service) {
        flash_set('error', 'Service not found.');
        redirect('admin/services.php');
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_or_die();

    $service['title'] = trim($_POST['title'] ?? '');
    $service['icon'] = trim($_POST['icon'] ?? '');
    $service['short_desc'] = trim($_POST['short_desc'] ?? '');
    $service['job_list'] = trim($_POST['job_list'] ?? '');
    $service['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if ($service['title'] === '' || mb_strlen($service['title']) > 150) {
        $errors[] = 'Please enter a title (max 150 characters).';
    }
    if ($service['icon'] === '' || mb_strlen($service['icon']) > 80) {
        $service['icon'] = 'fa-solid fa-screwdriver-wrench';
    }
    if (mb_strlen($service['short_desc']) > 600) {
        $errors[] = 'The description is too long (max 600 characters).';
    }
    if (mb_strlen($service['job_list']) > 1000) {
        $errors[] = 'The included-jobs list is too long (max 1000 characters).';
    }

    if (!$errors && !empty($_FILES['image']['name'])) {
        [$path, $uploadError] = handle_image_upload($_FILES['image'], 'services', 'service');
        if ($path === null) {
            $errors[] = $uploadError;
        } else {
            delete_upload($service['image_path']);
            $service['image_path'] = $path;
        }
    }
    if (!$errors && isset($_POST['remove_image']) && empty($_FILES['image']['name']) && $service['image_path'] !== '') {
        delete_upload($service['image_path']);
        $service['image_path'] = '';
    }

    if (!$errors) {
        if ($id > 0) {
            db()->prepare('UPDATE services SET title = ?, icon = ?, short_desc = ?, job_list = ?, image_path = ?, is_active = ? WHERE id = ?')
                ->execute([$service['title'], $service['icon'], $service['short_desc'], $service['job_list'], $service['image_path'], $service['is_active'], $id]);
            flash_set('success', 'Service updated.');
        } else {
            $max = (int) db()->query('SELECT COALESCE(MAX(sort_order), 0) FROM services')->fetchColumn();
            db()->prepare('INSERT INTO services (title, icon, short_desc, job_list, image_path, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$service['title'], $service['icon'], $service['short_desc'], $service['job_list'], $service['image_path'], $max + 1, $service['is_active']]);
            flash_set('success', 'Service added.');
        }
        redirect('admin/services.php');
    }
}

$adminTitle = $id > 0 ? 'Edit Service' : 'Add Service';
require __DIR__ . '/includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="admin-card">
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger"><?= esc($error) ?></div>
            <?php endforeach; ?>

            <form method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="s-title">Title *</label>
                    <input class="form-control" type="text" id="s-title" name="title" maxlength="150" required value="<?= esc($service['title']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="s-icon">Icon (Font Awesome class)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="<?= esc($service['icon']) ?>"></i></span>
                        <input class="form-control" type="text" id="s-icon" name="icon" maxlength="80" value="<?= esc($service['icon']) ?>">
                    </div>
                    <div class="form-text">Browse icons at <a href="https://fontawesome.com/search?o=r&m=free" target="_blank" rel="noopener">fontawesome.com</a> — e.g. <code>fa-solid fa-hammer</code></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="s-desc">Short Description</label>
                    <textarea class="form-control" id="s-desc" name="short_desc" rows="3" maxlength="600"><?= esc($service['short_desc']) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="s-jobs">What's Included</label>
                    <textarea class="form-control" id="s-jobs" name="job_list" rows="5" maxlength="1000" placeholder="One job per line"><?= esc($service['job_list'] ?? '') ?></textarea>
                    <div class="form-text">One job per line — each becomes a ticked bullet under the description on the Services page. Leave empty to show no list.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="s-image">Photo <span class="text-muted fw-normal">(optional — shown on the Services page)</span></label>
                    <input class="form-control" type="file" id="s-image" name="image" accept="image/*">
                    <?php if ($service['image_path'] !== ''): ?>
                        <div class="mt-2 d-flex align-items-center gap-3">
                            <img class="thumb-sm" src="<?= esc(base_url($service['image_path'])) ?>" alt="">
                            <label class="form-check-label"><input class="form-check-input me-1" type="checkbox" name="remove_image" value="1"> Remove current photo</label>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" id="s-active" name="is_active" value="1"<?= $service['is_active'] ? ' checked' : '' ?>>
                    <label class="form-check-label" for="s-active">Visible on the website</label>
                </div>
                <button class="btn btn-gold" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i><?= $id > 0 ? 'Save Changes' : 'Add Service' ?></button>
                <a class="btn btn-outline-secondary ms-2" href="<?= esc(base_url('admin/services.php')) ?>">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

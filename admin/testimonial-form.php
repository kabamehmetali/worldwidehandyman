<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$testimonial = ['author' => '', 'location' => '', 'rating' => 5, 'quote_text' => '', 'is_active' => 1];

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM testimonials WHERE id = ?');
    $stmt->execute([$id]);
    $testimonial = $stmt->fetch();
    if (!$testimonial) {
        flash_set('error', 'Testimonial not found.');
        redirect('admin/testimonials.php');
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_or_die();

    $testimonial['author'] = trim($_POST['author'] ?? '');
    $testimonial['location'] = trim($_POST['location'] ?? '');
    $testimonial['rating'] = max(1, min(5, (int) ($_POST['rating'] ?? 5)));
    $testimonial['quote_text'] = trim($_POST['quote_text'] ?? '');
    $testimonial['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if ($testimonial['author'] === '' || mb_strlen($testimonial['author']) > 100) {
        $errors[] = 'Please enter the author name (max 100 characters).';
    }
    if (mb_strlen($testimonial['location']) > 100) {
        $errors[] = 'Location is too long.';
    }
    if ($testimonial['quote_text'] === '') {
        $errors[] = 'Please enter the testimonial text.';
    }

    if (!$errors) {
        if ($id > 0) {
            db()->prepare('UPDATE testimonials SET author = ?, location = ?, rating = ?, quote_text = ?, is_active = ? WHERE id = ?')
                ->execute([$testimonial['author'], $testimonial['location'], $testimonial['rating'], $testimonial['quote_text'], $testimonial['is_active'], $id]);
            flash_set('success', 'Testimonial updated.');
        } else {
            $max = (int) db()->query('SELECT COALESCE(MAX(sort_order), 0) FROM testimonials')->fetchColumn();
            db()->prepare('INSERT INTO testimonials (author, location, rating, quote_text, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$testimonial['author'], $testimonial['location'], $testimonial['rating'], $testimonial['quote_text'], $max + 1, $testimonial['is_active']]);
            flash_set('success', 'Testimonial added.');
        }
        redirect('admin/testimonials.php');
    }
}

$adminTitle = $id > 0 ? 'Edit Testimonial' : 'Add Testimonial';
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
                    <div class="col-md-5">
                        <label class="form-label" for="t-author">Author *</label>
                        <input class="form-control" type="text" id="t-author" name="author" maxlength="100" required value="<?= esc($testimonial['author']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="t-location">Location</label>
                        <input class="form-control" type="text" id="t-location" name="location" maxlength="100" value="<?= esc($testimonial['location']) ?>" placeholder="e.g. North York">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="t-rating">Rating</label>
                        <select class="form-select" id="t-rating" name="rating">
                            <?php for ($r = 5; $r >= 1; $r--): ?>
                                <option value="<?= $r ?>"<?= (int) $testimonial['rating'] === $r ? ' selected' : '' ?>><?= $r ?> star<?= $r > 1 ? 's' : '' ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="t-quote">Testimonial *</label>
                        <textarea class="form-control" id="t-quote" name="quote_text" rows="4" required><?= esc($testimonial['quote_text']) ?></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="t-active" name="is_active" value="1"<?= $testimonial['is_active'] ? ' checked' : '' ?>>
                            <label class="form-check-label" for="t-active">Visible on the website</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-gold" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i><?= $id > 0 ? 'Save Changes' : 'Add Testimonial' ?></button>
                        <a class="btn btn-outline-secondary ms-2" href="<?= esc(base_url('admin/testimonials.php')) ?>">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

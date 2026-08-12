<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$faq = ['question' => '', 'answer' => '', 'is_active' => 1];

if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM faqs WHERE id = ?');
    $stmt->execute([$id]);
    $faq = $stmt->fetch();
    if (!$faq) {
        flash_set('error', 'FAQ not found.');
        redirect('admin/faqs.php');
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_or_die();

    $faq['question'] = trim($_POST['question'] ?? '');
    $faq['answer'] = trim($_POST['answer'] ?? '');
    $faq['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if ($faq['question'] === '' || mb_strlen($faq['question']) > 300) {
        $errors[] = 'Please enter the question (max 300 characters).';
    }
    if ($faq['answer'] === '') {
        $errors[] = 'Please enter the answer.';
    }

    if (!$errors) {
        if ($id > 0) {
            db()->prepare('UPDATE faqs SET question = ?, answer = ?, is_active = ? WHERE id = ?')
                ->execute([$faq['question'], $faq['answer'], $faq['is_active'], $id]);
            flash_set('success', 'FAQ updated.');
        } else {
            $max = (int) db()->query('SELECT COALESCE(MAX(sort_order), 0) FROM faqs')->fetchColumn();
            db()->prepare('INSERT INTO faqs (question, answer, sort_order, is_active) VALUES (?, ?, ?, ?)')
                ->execute([$faq['question'], $faq['answer'], $max + 1, $faq['is_active']]);
            flash_set('success', 'FAQ added.');
        }
        redirect('admin/faqs.php');
    }
}

$adminTitle = $id > 0 ? 'Edit FAQ' : 'Add FAQ';
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
                <div class="mb-3">
                    <label class="form-label" for="f-question">Question *</label>
                    <input class="form-control" type="text" id="f-question" name="question" maxlength="300" required value="<?= esc($faq['question']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="f-answer">Answer *</label>
                    <textarea class="form-control" id="f-answer" name="answer" rows="5" required><?= esc($faq['answer']) ?></textarea>
                </div>
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" id="f-active" name="is_active" value="1"<?= $faq['is_active'] ? ' checked' : '' ?>>
                    <label class="form-check-label" for="f-active">Visible on the website</label>
                </div>
                <button class="btn btn-gold" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i><?= $id > 0 ? 'Save Changes' : 'Add FAQ' ?></button>
                <a class="btn btn-outline-secondary ms-2" href="<?= esc(base_url('admin/faqs.php')) ?>">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

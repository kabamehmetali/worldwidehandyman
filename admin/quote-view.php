<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM quotes WHERE id = ?');
$stmt->execute([$id]);
$quote = $stmt->fetch();

if (!$quote) {
    flash_set('error', 'Quote request not found.');
    redirect('admin/quotes.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_or_die();
    $status = $_POST['status'] ?? 'new';
    if (in_array($status, ['new', 'contacted', 'closed'], true)) {
        db()->prepare('UPDATE quotes SET status = ? WHERE id = ?')->execute([$status, $id]);
        flash_set('success', 'Status updated.');
    }
    redirect('admin/quote-view.php?id=' . $id);
}

$adminTitle = 'Quote Request #' . $id;
require __DIR__ . '/includes/header.php';
?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="card-heading">
                <h5><i class="fa-solid fa-file-signature me-2 text-warning"></i><?= esc($quote['name']) ?></h5>
                <span class="badge-status badge-<?= esc($quote['status']) ?>"><?= esc(ucfirst($quote['status'])) ?></span>
            </div>
            <dl class="row mb-0">
                <dt class="col-sm-3">Received</dt>
                <dd class="col-sm-9"><?= esc(date('F j, Y \a\t g:i a', strtotime($quote['created_at']))) ?></dd>
                <dt class="col-sm-3">Phone</dt>
                <dd class="col-sm-9"><a href="<?= esc(phone_href($quote['phone'])) ?>"><?= esc($quote['phone']) ?></a></dd>
                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9"><?= $quote['email'] !== '' ? '<a href="mailto:' . esc($quote['email']) . '">' . esc($quote['email']) . '</a>' : '<span class="text-muted">Not provided</span>' ?></dd>
                <dt class="col-sm-3">Service</dt>
                <dd class="col-sm-9"><?= esc($quote['service'] !== '' ? $quote['service'] : 'Not specified') ?></dd>
                <dt class="col-sm-3">Job Details</dt>
                <dd class="col-sm-9"><?= nl2br(esc($quote['message'])) ?></dd>
                <dt class="col-sm-3">SMS Notification</dt>
                <dd class="col-sm-9">
                    <?php if ($quote['sms_sent']): ?>
                        <span class="text-success"><i class="fa-solid fa-circle-check me-1"></i>Sent successfully</span>
                    <?php else: ?>
                        <span class="text-danger"><i class="fa-solid fa-circle-xmark me-1"></i>Not sent</span>
                        <?php if ($quote['sms_error'] !== ''): ?><br><small class="text-muted"><?= esc($quote['sms_error']) ?></small><?php endif; ?>
                    <?php endif; ?>
                </dd>
            </dl>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="card-heading"><h5>Update Status</h5></div>
            <form method="post">
                <?= csrf_field() ?>
                <select class="form-select mb-3" name="status">
                    <option value="new"<?= $quote['status'] === 'new' ? ' selected' : '' ?>>New</option>
                    <option value="contacted"<?= $quote['status'] === 'contacted' ? ' selected' : '' ?>>Contacted</option>
                    <option value="closed"<?= $quote['status'] === 'closed' ? ' selected' : '' ?>>Closed</option>
                </select>
                <button class="btn btn-gold w-100" type="submit">Save Status</button>
            </form>
            <hr>
            <a class="btn btn-outline-secondary w-100" href="<?= esc(base_url('admin/quotes.php')) ?>"><i class="fa-solid fa-arrow-left me-2"></i>Back to Quotes</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

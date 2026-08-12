<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM contact_messages WHERE id = ?');
$stmt->execute([$id]);
$message = $stmt->fetch();

if (!$message) {
    flash_set('error', 'Message not found.');
    redirect('admin/messages.php');
}

// Opening a message marks it read
if (!$message['is_read']) {
    db()->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([$id]);
}

$adminTitle = 'Message from ' . $message['name'];
require __DIR__ . '/includes/header.php';
?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="card-heading">
                <h5><i class="fa-solid fa-envelope-open-text me-2 text-warning"></i><?= esc($message['subject'] !== '' ? $message['subject'] : 'No subject') ?></h5>
                <small class="text-muted"><?= esc(date('F j, Y g:i a', strtotime($message['created_at']))) ?></small>
            </div>
            <dl class="row">
                <dt class="col-sm-3">From</dt>
                <dd class="col-sm-9"><?= esc($message['name']) ?> &lt;<a href="mailto:<?= esc($message['email']) ?>"><?= esc($message['email']) ?></a>&gt;</dd>
                <?php if ($message['phone'] !== ''): ?>
                    <dt class="col-sm-3">Phone</dt>
                    <dd class="col-sm-9"><a href="<?= esc(phone_href($message['phone'])) ?>"><?= esc($message['phone']) ?></a></dd>
                <?php endif; ?>
            </dl>
            <hr>
            <p class="mb-0"><?= nl2br(esc($message['message'])) ?></p>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="card-heading"><h5>Actions</h5></div>
            <a class="btn btn-gold w-100 mb-2" href="mailto:<?= esc($message['email']) ?>"><i class="fa-solid fa-reply me-2"></i>Reply by Email</a>
            <form method="post" action="<?= esc(base_url('admin/messages.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_read">
                <input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
                <button class="btn btn-outline-secondary w-100 mb-2" type="submit"><i class="fa-solid fa-envelope me-2"></i>Mark as Unread</button>
            </form>
            <a class="btn btn-outline-secondary w-100 mb-2" href="<?= esc(base_url('admin/messages.php')) ?>"><i class="fa-solid fa-arrow-left me-2"></i>Back to Messages</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

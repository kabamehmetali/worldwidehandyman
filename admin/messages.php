<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_or_die();
    $id = (int) ($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'delete') {
        db()->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
        flash_set('success', 'Message deleted.');
    } elseif (($_POST['action'] ?? '') === 'toggle_read') {
        db()->prepare('UPDATE contact_messages SET is_read = 1 - is_read WHERE id = ?')->execute([$id]);
    }
    redirect('admin/messages.php');
}

$adminTitle = 'Messages';
require __DIR__ . '/includes/header.php';

$messages = db()->query('SELECT * FROM contact_messages ORDER BY created_at DESC, id DESC')->fetchAll();
?>

<div class="admin-card">
    <div class="card-heading">
        <h5>Contact Messages (<?= count($messages) ?>)</h5>
    </div>
    <?php if ($messages): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th></th><th>From</th><th>Subject</th><th>Received</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($messages as $m): ?>
                    <tr class="<?= $m['is_read'] ? '' : 'fw-semibold' ?>">
                        <td><?= $m['is_read'] ? '<i class="fa-regular fa-envelope-open text-muted"></i>' : '<i class="fa-solid fa-envelope text-danger"></i>' ?></td>
                        <td>
                            <?= esc($m['name']) ?><br>
                            <small class="text-muted fw-normal"><?= esc($m['email']) ?><?= $m['phone'] !== '' ? ' · ' . esc($m['phone']) : '' ?></small>
                        </td>
                        <td class="text-truncate" style="max-width: 320px;"><?= esc($m['subject'] !== '' ? $m['subject'] : mb_substr($m['message'], 0, 60)) ?></td>
                        <td><small><?= esc(date('M j, Y g:i a', strtotime($m['created_at']))) ?></small></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= esc(base_url('admin/message-view.php?id=' . (int) $m['id'])) ?>" title="Read"><i class="fa-solid fa-eye"></i></a>
                            <form class="d-inline" method="post" data-confirm="Delete this message permanently?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted mb-0">No contact messages yet.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

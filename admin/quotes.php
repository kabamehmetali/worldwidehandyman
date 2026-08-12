<?php
require_once __DIR__ . '/includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_csrf_or_die();
    $id = (int) ($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'status') {
        $status = $_POST['status'] ?? 'new';
        if (in_array($status, ['new', 'contacted', 'closed'], true)) {
            db()->prepare('UPDATE quotes SET status = ? WHERE id = ?')->execute([$status, $id]);
            flash_set('success', 'Quote status updated.');
        }
    } elseif (($_POST['action'] ?? '') === 'delete') {
        db()->prepare('DELETE FROM quotes WHERE id = ?')->execute([$id]);
        flash_set('success', 'Quote request deleted.');
    }
    redirect('admin/quotes.php' . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
}

$adminTitle = 'Quote Requests';
require __DIR__ . '/includes/header.php';

$filter = $_GET['status'] ?? '';
if (in_array($filter, ['new', 'contacted', 'closed'], true)) {
    $stmt = db()->prepare('SELECT * FROM quotes WHERE status = ? ORDER BY created_at DESC, id DESC');
    $stmt->execute([$filter]);
    $quotes = $stmt->fetchAll();
} else {
    $filter = '';
    $quotes = db()->query('SELECT * FROM quotes ORDER BY created_at DESC, id DESC')->fetchAll();
}
?>

<div class="admin-card">
    <div class="card-heading">
        <h5>All Quote Requests (<?= count($quotes) ?>)</h5>
        <div class="btn-group">
            <a class="btn btn-sm <?= $filter === '' ? 'btn-navy' : 'btn-outline-secondary' ?>" href="<?= esc(base_url('admin/quotes.php')) ?>">All</a>
            <a class="btn btn-sm <?= $filter === 'new' ? 'btn-navy' : 'btn-outline-secondary' ?>" href="<?= esc(base_url('admin/quotes.php?status=new')) ?>">New</a>
            <a class="btn btn-sm <?= $filter === 'contacted' ? 'btn-navy' : 'btn-outline-secondary' ?>" href="<?= esc(base_url('admin/quotes.php?status=contacted')) ?>">Contacted</a>
            <a class="btn btn-sm <?= $filter === 'closed' ? 'btn-navy' : 'btn-outline-secondary' ?>" href="<?= esc(base_url('admin/quotes.php?status=closed')) ?>">Closed</a>
        </div>
    </div>

    <?php if ($quotes): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Name</th><th>Contact</th><th>Service</th><th>Status</th><th>SMS</th><th>Received</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($quotes as $q): ?>
                    <tr>
                        <td><strong><?= esc($q['name']) ?></strong></td>
                        <td>
                            <small><i class="fa-solid fa-phone me-1 text-muted"></i><?= esc($q['phone']) ?></small>
                            <?php if ($q['email'] !== ''): ?><br><small><i class="fa-solid fa-envelope me-1 text-muted"></i><?= esc($q['email']) ?></small><?php endif; ?>
                        </td>
                        <td><?= esc($q['service'] !== '' ? $q['service'] : '—') ?></td>
                        <td><span class="badge-status badge-<?= esc($q['status']) ?>"><?= esc(ucfirst($q['status'])) ?></span></td>
                        <td>
                            <?php if ($q['sms_sent']): ?>
                                <i class="fa-solid fa-circle-check text-success" title="SMS sent"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-circle-xmark text-danger" title="<?= esc($q['sms_error'] !== '' ? $q['sms_error'] : 'SMS not sent') ?>"></i>
                            <?php endif; ?>
                        </td>
                        <td><small><?= esc(date('M j, Y g:i a', strtotime($q['created_at']))) ?></small></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= esc(base_url('admin/quote-view.php?id=' . (int) $q['id'])) ?>" title="View"><i class="fa-solid fa-eye"></i></a>
                            <form class="d-inline" method="post" data-confirm="Delete this quote request permanently?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $q['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted mb-0">No quote requests<?= $filter !== '' ? ' with this status' : '' ?> yet.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

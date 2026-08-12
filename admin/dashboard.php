<?php
$adminTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';

$counts = [
    'quotes_new'   => (int) db()->query("SELECT COUNT(*) FROM quotes WHERE status = 'new'")->fetchColumn(),
    'quotes_all'   => (int) db()->query('SELECT COUNT(*) FROM quotes')->fetchColumn(),
    'msg_unread'   => (int) db()->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn(),
    'services'     => (int) db()->query('SELECT COUNT(*) FROM services WHERE is_active = 1')->fetchColumn(),
    'gallery'      => (int) db()->query('SELECT COUNT(*) FROM gallery WHERE is_active = 1')->fetchColumn(),
    'testimonials' => (int) db()->query('SELECT COUNT(*) FROM testimonials WHERE is_active = 1')->fetchColumn(),
];
$recentQuotes = db()->query('SELECT * FROM quotes ORDER BY created_at DESC, id DESC LIMIT 5')->fetchAll();
$recentMessages = db()->query('SELECT * FROM contact_messages ORDER BY created_at DESC, id DESC LIMIT 5')->fetchAll();
?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <a class="text-decoration-none" href="<?= esc(base_url('admin/quotes.php')) ?>">
            <div class="stat-tile tile-red">
                <div class="st-icon"><i class="fa-solid fa-file-signature"></i></div>
                <div>
                    <div class="st-num"><?= $counts['quotes_new'] ?></div>
                    <div class="st-label">New Quote Requests</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a class="text-decoration-none" href="<?= esc(base_url('admin/messages.php')) ?>">
            <div class="stat-tile">
                <div class="st-icon"><i class="fa-solid fa-envelope"></i></div>
                <div>
                    <div class="st-num"><?= $counts['msg_unread'] ?></div>
                    <div class="st-label">Unread Messages</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a class="text-decoration-none" href="<?= esc(base_url('admin/services.php')) ?>">
            <div class="stat-tile tile-navy">
                <div class="st-icon"><i class="fa-solid fa-screwdriver-wrench"></i></div>
                <div>
                    <div class="st-num"><?= $counts['services'] ?></div>
                    <div class="st-label">Active Services</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a class="text-decoration-none" href="<?= esc(base_url('admin/gallery.php')) ?>">
            <div class="stat-tile">
                <div class="st-icon"><i class="fa-solid fa-images"></i></div>
                <div>
                    <div class="st-num"><?= $counts['gallery'] ?></div>
                    <div class="st-label">Gallery Photos</div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="admin-card">
            <div class="card-heading">
                <h5><i class="fa-solid fa-file-signature me-2 text-warning"></i>Recent Quote Requests</h5>
                <a class="btn btn-sm btn-navy" href="<?= esc(base_url('admin/quotes.php')) ?>">View All</a>
            </div>
            <?php if ($recentQuotes): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Name</th><th>Service</th><th>Status</th><th>SMS</th><th>Received</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($recentQuotes as $q): ?>
                            <tr>
                                <td><strong><?= esc($q['name']) ?></strong><br><small class="text-muted"><?= esc($q['phone']) ?></small></td>
                                <td><?= esc($q['service'] !== '' ? $q['service'] : '—') ?></td>
                                <td><span class="badge-status badge-<?= esc($q['status']) ?>"><?= esc(ucfirst($q['status'])) ?></span></td>
                                <td><?= $q['sms_sent'] ? '<i class="fa-solid fa-circle-check text-success"></i>' : '<i class="fa-solid fa-circle-xmark text-danger" title="' . esc($q['sms_error']) . '"></i>' ?></td>
                                <td><small><?= esc(date('M j, g:i a', strtotime($q['created_at']))) ?></small></td>
                                <td><a class="btn btn-sm btn-outline-secondary" href="<?= esc(base_url('admin/quote-view.php?id=' . (int) $q['id'])) ?>"><i class="fa-solid fa-eye"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">No quote requests yet. When customers submit the Get a Quote form, they will appear here (and arrive by SMS).</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="admin-card">
            <div class="card-heading">
                <h5><i class="fa-solid fa-envelope me-2 text-warning"></i>Recent Messages</h5>
                <a class="btn btn-sm btn-navy" href="<?= esc(base_url('admin/messages.php')) ?>">View All</a>
            </div>
            <?php if ($recentMessages): ?>
                <?php foreach ($recentMessages as $m): ?>
                    <a class="d-block text-decoration-none border-bottom py-2" href="<?= esc(base_url('admin/message-view.php?id=' . (int) $m['id'])) ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="<?= $m['is_read'] ? 'text-muted' : '' ?>"><?= $m['is_read'] ? '' : '<i class="fa-solid fa-circle text-danger me-1" style="font-size:.45rem; vertical-align: middle;"></i>' ?><?= esc($m['name']) ?></strong>
                            <small class="text-muted"><?= esc(date('M j', strtotime($m['created_at']))) ?></small>
                        </div>
                        <small class="text-muted d-block text-truncate"><?= esc($m['subject'] !== '' ? $m['subject'] : mb_substr($m['message'], 0, 80)) ?></small>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted mb-0">No messages yet.</p>
            <?php endif; ?>
        </div>
        <div class="admin-card">
            <div class="card-heading"><h5><i class="fa-solid fa-bolt me-2 text-warning"></i>Quick Actions</h5></div>
            <div class="d-grid gap-2">
                <a class="btn btn-outline-secondary text-start" href="<?= esc(base_url('admin/service-form.php')) ?>"><i class="fa-solid fa-plus me-2"></i>Add a Service</a>
                <a class="btn btn-outline-secondary text-start" href="<?= esc(base_url('admin/gallery.php')) ?>"><i class="fa-solid fa-upload me-2"></i>Upload Gallery Photos</a>
                <a class="btn btn-outline-secondary text-start" href="<?= esc(base_url('admin/page-form.php')) ?>"><i class="fa-solid fa-file-circle-plus me-2"></i>Create a Page</a>
                <a class="btn btn-outline-secondary text-start" href="<?= esc(base_url('admin/settings.php')) ?>"><i class="fa-solid fa-gear me-2"></i>Edit Site Settings</a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/auth.php';
require_admin();

$adminTitle = $adminTitle ?? 'Dashboard';
$newQuotes = (int) db()->query("SELECT COUNT(*) FROM quotes WHERE status = 'new'")->fetchColumn();
$unreadMessages = (int) db()->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();
$adminPage = basename($_SERVER['PHP_SELF']);

function admin_nav_item(string $file, string $icon, string $label, string $current, int $badge = 0): void
{
    $active = $current === $file ? ' active' : '';
    echo '<a class="admin-nav-link' . $active . '" href="' . esc(base_url('admin/' . $file)) . '">'
        . '<i class="' . esc($icon) . '"></i><span>' . esc($label) . '</span>'
        . ($badge > 0 ? '<span class="nav-badge">' . $badge . '</span>' : '')
        . '</a>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($adminTitle) ?> — Admin | <?= esc(setting('site_name')) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="<?= esc(base_url(setting('logo_icon'))) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= esc(base_url('admin/assets/admin.css')) ?>?v=<?= @filemtime(APP_ROOT . '/admin/assets/admin.css') ?>" rel="stylesheet">
</head>
<body class="admin-body">

<div class="admin-wrap">
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-brand">
            <img src="<?= esc(base_url(setting('logo_icon'))) ?>" alt="">
            <div>
                <strong><?= esc(setting('site_name')) ?></strong>
                <small>Admin Panel</small>
            </div>
        </div>
        <nav class="admin-nav">
            <span class="admin-nav-section">Overview</span>
            <?php admin_nav_item('dashboard.php', 'fa-solid fa-gauge-high', 'Dashboard', $adminPage); ?>
            <?php admin_nav_item('quotes.php', 'fa-solid fa-file-signature', 'Quote Requests', $adminPage, $newQuotes); ?>
            <?php admin_nav_item('messages.php', 'fa-solid fa-envelope', 'Messages', $adminPage, $unreadMessages); ?>

            <span class="admin-nav-section">Content</span>
            <?php admin_nav_item('services.php', 'fa-solid fa-screwdriver-wrench', 'Services', $adminPage); ?>
            <?php admin_nav_item('gallery.php', 'fa-solid fa-images', 'Gallery', $adminPage); ?>
            <?php admin_nav_item('testimonials.php', 'fa-solid fa-star', 'Testimonials', $adminPage); ?>
            <?php admin_nav_item('faqs.php', 'fa-solid fa-circle-question', 'FAQs', $adminPage); ?>
            <?php admin_nav_item('pages.php', 'fa-solid fa-file-lines', 'Pages', $adminPage); ?>

            <span class="admin-nav-section">SEO</span>
            <?php admin_nav_item('seo-locations.php', 'fa-solid fa-location-dot', 'Locations', $adminPage); ?>
            <?php admin_nav_item('seo-services.php', 'fa-solid fa-tags', 'Service Pages', $adminPage); ?>
            <?php admin_nav_item('seo-combos.php', 'fa-solid fa-diagram-project', 'Service × City', $adminPage); ?>

            <span class="admin-nav-section">Site</span>
            <?php admin_nav_item('navigation.php', 'fa-solid fa-bars-staggered', 'Navigation', $adminPage); ?>
            <?php admin_nav_item('settings.php', 'fa-solid fa-gear', 'Settings', $adminPage); ?>
        </nav>
        <div class="admin-sidebar-foot">
            <a href="<?= esc(base_url('index.php')) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Site</a>
            <a href="<?= esc(base_url('admin/logout.php')) ?>"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu"><i class="fa-solid fa-bars"></i></button>
            <h1><?= esc($adminTitle) ?></h1>
            <div class="admin-user">
                <span class="admin-avatar"><?= esc(mb_strtoupper(mb_substr(admin_user()['username'], 0, 1))) ?></span>
                <span class="d-none d-sm-inline"><?= esc(admin_user()['username']) ?></span>
            </div>
        </header>
        <main class="admin-content">
            <?= flash_render() ?>

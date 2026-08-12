<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
$page = null;
if ($slug !== '') {
    $stmt = db()->prepare('SELECT * FROM pages WHERE slug = ? AND is_published = 1');
    $stmt->execute([$slug]);
    $page = $stmt->fetch();
}

if (!$page) {
    http_response_code(404);
    $pageTitle = 'Page Not Found';
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="section text-center">
        <div class="container py-5">
            <img src="<?= esc(base_url('assets/img/cartoon-workshop.jpg')) ?>" alt="" style="max-width: 420px; border-radius: 1rem;" class="mb-4 shadow">
            <h1 class="section-title">Page Not Found</h1>
            <p class="section-sub mx-auto">The page you are looking for does not exist or is no longer published.</p>
            <a class="btn btn-navy mt-3" href="<?= esc(base_url()) ?>"><i class="fa-solid fa-house me-2"></i>Back to Home</a>
        </div>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $page['title'];
$metaDescription = $page['meta_description'] !== '' ? $page['meta_description'] : setting('meta_description');
require __DIR__ . '/includes/header.php';
?>

<section class="page-banner" style="background-image: url('<?= esc(base_url('assets/img/hero-about.jpg')) ?>');">
    <div class="container">
        <h1><?= esc($page['title']) ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= esc(base_url()) ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= esc($page['title']) ?></li>
            </ol>
        </nav>
    </div>
</section>

<section class="section section-light">
    <div class="container">
        <div class="page-content reveal">
            <?= $page['content'] /* trusted admin-authored HTML */ ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

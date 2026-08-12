<?php
require_once __DIR__ . '/includes/seo.php';

$slug = trim($_GET['slug'] ?? '');
$page = null;
if ($slug !== '') {
    $stmt = db()->prepare('SELECT * FROM pages WHERE slug = ? AND is_published = 1');
    $stmt->execute([$slug]);
    $page = $stmt->fetch();
}

if (!$page) {
    seo_not_found();
}

$pageTitle = $page['title'];
$metaDescription = $page['meta_description'] !== '' ? $page['meta_description'] : setting('meta_description');
$canonicalPath = 'page?slug=' . rawurlencode($slug);
$breadcrumbs = [['label' => 'Home', 'url' => ''], ['label' => $page['title']]];
require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-portrait" style="background-image: url('<?= esc(base_url('assets/img/hero-about.jpg')) ?>');">
    <div class="container">
        <h1><?= esc($page['title']) ?></h1>
        <?= breadcrumb_html($breadcrumbs) ?>
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

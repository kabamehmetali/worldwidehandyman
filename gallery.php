<?php
$pageTitle = 'Gallery';
$metaDescription = 'A look at Worldwide Handyman — recent work, projects and moments from around the Greater Toronto Area.';
require __DIR__ . '/includes/header.php';

$images = db()->query(
    'SELECT * FROM gallery WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
)->fetchAll();
?>

<section class="page-banner" style="background-image: url('<?= esc(base_url('assets/img/work-modern-kitchen.jpg')) ?>');">
    <div class="container">
        <h1>Gallery</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= esc(base_url()) ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Gallery</li>
            </ol>
        </nav>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-eyebrow"><i class="fa-solid fa-images"></i> Our Work</span>
            <h2 class="section-title">Recent Work &amp; Moments</h2>
            <p class="section-sub mx-auto">Click any photo to view it full size.</p>
        </div>
        <?php if ($images): ?>
            <div class="row g-4">
                <?php foreach ($images as $g): ?>
                    <div class="col-6 col-md-4 col-lg-3 reveal">
                        <a class="gallery-item" href="#" data-full="<?= esc(base_url($g['image_path'])) ?>" data-title="<?= esc($g['title']) ?>">
                            <img src="<?= esc(base_url($g['image_path'])) ?>" alt="<?= esc($g['title'] !== '' ? $g['title'] : 'Gallery image') ?>" loading="lazy">
                            <div class="gallery-overlay">
                                <span class="g-zoom"><i class="fa-solid fa-magnifying-glass-plus"></i></span>
                                <span class="g-title"><?= esc($g['title']) ?></span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center">New photos are on the way — check back soon.</p>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
$pageTitle = 'Our Services';
$metaDescription = 'Handyman services in the Greater Toronto Area: repairs, furniture assembly, TV mounting, drywall, plumbing fixtures, lighting, doors, flooring and more.';
require __DIR__ . '/includes/header.php';

$services = db()->query(
    'SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
)->fetchAll();
?>

<section class="page-banner page-banner-portrait" style="background-image: url('<?= esc(base_url('assets/img/work-bright-shop.jpg')) ?>');">
    <div class="container">
        <h1>Our Services</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= esc(base_url()) ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Services</li>
            </ol>
        </nav>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-eyebrow"><i class="fa-solid fa-screwdriver-wrench"></i> What I Do</span>
            <h2 class="section-title">One Call For Every Job On Your List</h2>
            <p class="section-sub mx-auto">Repairs, maintenance, installations, assembly and home improvement — handled by one trusted professional across the Greater Toronto Area. Don't see your job listed? Just ask.</p>
        </div>
        <div class="row g-4">
            <?php foreach ($services as $service): ?>
                <div class="col-md-6 col-lg-4 reveal">
                    <div class="service-card">
                        <?php if ($service['image_path'] !== ''): ?>
                            <img class="service-img" src="<?= esc(base_url($service['image_path'])) ?>" alt="<?= esc($service['title']) ?>" loading="lazy">
                        <?php endif; ?>
                        <div class="service-icon"><i class="<?= esc($service['icon']) ?>"></i></div>
                        <h5><?= esc($service['title']) ?></h5>
                        <p><?= esc($service['short_desc']) ?></p>
                        <?php
                        // "What's included" — one job per line, blank lines ignored
                        $jobs = array_filter(array_map('trim', preg_split('/\R/', $service['job_list'] ?? '')), 'strlen');
                        ?>
                        <?php if ($jobs): ?>
                            <ul class="check-list mb-0">
                                <?php foreach ($jobs as $job): ?>
                                    <li><i class="fa-solid fa-circle-check"></i> <?= esc($job) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="cta-band" style="background-image: url('<?= esc(base_url(setting('cta_band_image'))) ?>');">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <h2>Not sure which service you need?</h2>
                <p>Describe the job in plain words — I'll tell you exactly what it takes and what it costs. Quotes are always free.</p>
            </div>
            <div class="col-lg-5 text-lg-end">
                <a class="btn btn-gold btn-lg" href="<?= esc(base_url('quote')) ?>"><i class="fa-solid fa-file-signature me-2"></i>Get a Free Quote</a>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

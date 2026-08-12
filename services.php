<?php
/**
 * Services hub — /services
 *
 * Lists every service landing page, which is what gives them a crawl path
 * from the main navigation. Falls back to the simple `services` table when
 * no SEO service pages have been imported yet.
 */

require_once __DIR__ . '/includes/seo.php';

$seoServices = seo_service_pages();
$pillars     = array_values(array_filter($seoServices, static fn ($s) => (int) $s['is_pillar'] === 1));
$others      = array_values(array_filter($seoServices, static fn ($s) => (int) $s['is_pillar'] !== 1));
$areas       = seo_locations(1);

$pageTitle       = 'Handyman Services in Toronto & the GTA';
$metaDescription = 'Every handyman service I offer across the GTA — repairs, TV mounting, furniture assembly, drywall, painting, plumbing fixtures, lighting, doors, flooring and more.';
$canonicalPath   = 'services';
$ogImage         = 'assets/img/work-bright-shop.jpg';
$breadcrumbs     = [['label' => 'Home', 'url' => ''], ['label' => 'Services']];

$canonical = canonical_url($canonicalPath);
$schemas   = [];
if ($seoServices) {
    $schemas[] = schema_item_list('Handyman services in the Greater Toronto Area', array_map(static fn ($s) => [
        'name' => $s['name'], 'url' => 'services/' . $s['slug'],
    ], $seoServices), $canonical);
}

require __DIR__ . '/includes/header.php';

// Legacy fallback — the simple card list used before the landing pages existed
$legacyServices = $seoServices ? [] : db()->query(
    'SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
)->fetchAll();
?>

<section class="page-banner page-banner-portrait" style="background-image: url('<?= esc(base_url('assets/img/work-bright-shop.jpg')) ?>');">
    <div class="container">
        <h1>Our Services</h1>
        <?= breadcrumb_html($breadcrumbs) ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-eyebrow"><i class="fa-solid fa-screwdriver-wrench"></i> What I Do</span>
            <h2 class="section-title">One Call For Every Job On Your List</h2>
            <p class="section-sub mx-auto">Repairs, maintenance, installations, assembly and home improvement — handled by one trusted professional across the Greater Toronto Area. Don't see your job listed? Just ask.</p>
        </div>

        <?php if ($pillars): ?>
            <div class="row g-4">
                <?php foreach ($pillars as $svc): ?>
                    <div class="col-md-6 col-lg-4 reveal">
                        <a class="service-card service-card-link h-100 d-block" href="<?= esc(service_page_url($svc['slug'])) ?>">
                            <div class="service-icon"><i class="<?= esc($svc['icon']) ?>"></i></div>
                            <h5><?= esc($svc['name']) ?></h5>
                            <p><?= esc(seo_truncate($svc['meta_description'], 120)) ?></p>
                            <span class="sc-more">Learn more <i class="fa-solid fa-arrow-right"></i></span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($others): ?>
            <div class="text-center mt-5 mb-4 reveal">
                <h2 class="section-title">More Jobs I Take On</h2>
                <p class="section-sub mx-auto">Specialist and seasonal work, plus pages for condo residents, landlords and small businesses.</p>
            </div>
            <div class="row g-3">
                <?php foreach ($others as $svc): ?>
                    <div class="col-md-6 col-lg-4 reveal">
                        <a class="link-card" href="<?= esc(service_page_url($svc['slug'])) ?>">
                            <span class="lc-icon"><i class="<?= esc($svc['icon']) ?>"></i></span>
                            <span class="lc-body">
                                <strong><?= esc($svc['name']) ?></strong>
                                <small><?= esc(seo_truncate($svc['meta_description'], 58)) ?></small>
                            </span>
                            <i class="fa-solid fa-arrow-right lc-arrow"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($legacyServices): ?>
            <div class="row g-4">
                <?php foreach ($legacyServices as $service): ?>
                    <div class="col-md-6 col-lg-4 reveal">
                        <div class="service-card">
                            <?php if ($service['image_path'] !== ''): ?>
                                <img class="service-img" src="<?= esc(base_url($service['image_path'])) ?>" alt="<?= esc($service['title']) ?>" loading="lazy">
                            <?php endif; ?>
                            <div class="service-icon"><i class="<?= esc($service['icon']) ?>"></i></div>
                            <h5><?= esc($service['title']) ?></h5>
                            <p><?= esc($service['short_desc']) ?></p>
                            <?php $jobs = seo_lines($service['job_list']); ?>
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
        <?php endif; ?>
    </div>
</section>

<?php if ($areas): ?>
<section class="section section-light">
    <div class="container">
        <div class="text-center mb-4 reveal">
            <span class="section-eyebrow"><i class="fa-solid fa-map"></i> Service Area</span>
            <h2 class="section-title">Available Across the GTA</h2>
        </div>
        <div class="row g-3 justify-content-center">
            <?php foreach ($areas as $area): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a class="area-card d-block text-center" href="<?= esc(location_url($area['slug'])) ?>">
                        <i class="fa-solid fa-location-dot"></i> <?= esc($area['name']) ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a class="btn btn-outline-navy" href="<?= esc(base_url('service-areas')) ?>">All Service Areas <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>
    </div>
</section>
<?php endif; ?>

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

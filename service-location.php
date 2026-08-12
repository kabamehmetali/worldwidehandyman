<?php
/**
 * Service + city landing page — /services/{service}/{location}
 *
 * These exist only where a seo_service_locations row has been written, and
 * they lead with the copy that is unique to that pairing. The full service
 * guide is deliberately NOT repeated here — the page links up to it instead,
 * so the two URLs never compete for the same text.
 */

require_once __DIR__ . '/includes/seo.php';

$serviceSlug  = trim($_GET['service'] ?? '');
$locationSlug = trim($_GET['location'] ?? '');

$service  = $serviceSlug !== '' ? seo_service_page($serviceSlug) : null;
$location = $locationSlug !== '' ? seo_location($locationSlug) : null;
$pair     = ($service && $location) ? seo_combo((int) $service['id'], (int) $location['id']) : null;

// No hand-written pair means no page — never assemble one from the template
if (!$pair) {
    if ($service && !$location) {
        // /services/tv-mounting/not-a-place -> send them to the service page
        header('Location: ' . base_url('services/' . $service['slug']), true, 301);
        exit;
    }
    seo_not_found(
        'Page Not Found',
        'That service and area combination does not have its own page. Browse all services or all the areas I cover.'
    );
}

$name       = $service['name'];
$city       = $location['name'];
$heroImage  = $service['hero_image'] !== '' ? $service['hero_image'] : seo_stock_image($serviceSlug . $locationSlug);
$pairJobs   = seo_lines($pair['common_jobs']);
$svcJobs    = seo_lines($service['jobs']);
$process    = seo_json_list($service['process_json']);
$neighbourhoods = seo_lines($location['neighbourhoods']);

// Cross-links: the same service in nearby cities, and other services here
$otherCities   = array_values(array_filter(
    seo_combo_locations((int) $service['id']),
    static fn ($row) => $row['slug'] !== $locationSlug
));
$otherServices = array_values(array_filter(
    seo_combo_services((int) $location['id']),
    static fn ($row) => $row['slug'] !== $serviceSlug
));

/* ------------------------------------------------------------------ SEO */
$pageTitle       = $pair['meta_title'] !== '' ? $pair['meta_title'] : $name . ' in ' . $city;
$metaDescription = $pair['meta_description'] !== ''
    ? $pair['meta_description']
    : seo_truncate($pair['intro'], 155);
$canonicalPath   = 'services/' . $serviceSlug . '/' . $locationSlug;
$ogImage         = $heroImage;
$breadcrumbs     = [
    ['label' => 'Home', 'url' => ''],
    ['label' => 'Services', 'url' => 'services'],
    ['label' => $name, 'url' => 'services/' . $serviceSlug],
    ['label' => $city],
];

$canonical = canonical_url($canonicalPath);
$schemas   = [schema_service($name . ' in ' . $city, $metaDescription, $canonical, $location, $pairJobs)];
if ($pair['faq_q'] !== '' && trim($pair['faq_a']) !== '') {
    $schemas[] = schema_faq([['q' => $pair['faq_q'], 'a' => $pair['faq_a']]], $canonical);
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-portrait" style="background-image: url('<?= esc(base_url($heroImage)) ?>');">
    <div class="container">
        <h1><?= esc($pair['h1'] !== '' ? $pair['h1'] : $name . ' in ' . $city) ?></h1>
        <?= breadcrumb_html($breadcrumbs) ?>
    </div>
</section>

<!-- Pair intro -->
<section class="section">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-7 reveal">
                <span class="section-eyebrow"><i class="<?= esc($service['icon']) ?>"></i> <?= esc($city) ?> &middot; <?= esc($location['region']) ?></span>
                <h2 class="section-title"><?= esc($name) ?> for <?= esc($city) ?> Homes</h2>
                <div class="page-content"><?= seo_paragraphs($pair['intro']) ?></div>

                <?php if (trim($pair['local_angle']) !== ''): ?>
                    <div class="local-note">
                        <i class="fa-solid fa-location-crosshairs"></i>
                        <p><?= esc($pair['local_angle']) ?></p>
                    </div>
                <?php endif; ?>

                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a class="btn btn-gold" href="<?= esc(base_url('quote')) ?>"><i class="fa-solid fa-file-signature me-2"></i>Get a Free Quote</a>
                    <a class="btn btn-outline-navy" href="<?= esc(phone_link_href()) ?>"><i class="fa-solid fa-phone me-2"></i><?= esc(setting('phone')) ?></a>
                </div>
            </div>

            <div class="col-lg-5 reveal">
                <div class="seo-facts">
                    <h3><i class="fa-solid fa-map-location-dot"></i> <?= esc($name) ?> in <?= esc($city) ?></h3>
                    <?php if ($neighbourhoods): ?>
                        <div class="seo-fact">
                            <span class="sf-label">Areas covered</span>
                            <div class="sf-chips">
                                <?php foreach (array_slice($neighbourhoods, 0, 10) as $hood): ?>
                                    <span class="sf-chip"><?= esc($hood) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="seo-fact">
                        <span class="sf-label">Booking</span>
                        <p class="sf-text"><?= esc(setting('hours_weekday')) ?><br><?= esc(setting('hours_weekend')) ?></p>
                    </div>
                    <div class="seo-fact">
                        <span class="sf-label">Full guide</span>
                        <p class="sf-text mb-0">
                            <a href="<?= esc(service_page_url($serviceSlug)) ?>">Read everything about <?= esc(lcfirst($name)) ?> <i class="fa-solid fa-arrow-right ms-1"></i></a>
                        </p>
                    </div>
                    <div class="seo-fact">
                        <span class="sf-label">All work in this area</span>
                        <p class="sf-text mb-0">
                            <a href="<?= esc(location_url($locationSlug)) ?>">Handyman services in <?= esc($city) ?> <i class="fa-solid fa-arrow-right ms-1"></i></a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($pairJobs): ?>
<!-- Calls specific to this pairing -->
<section class="section section-light">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-eyebrow"><i class="fa-solid fa-list-check"></i> Typical Calls</span>
            <h2 class="section-title"><?= esc($name) ?> Jobs in <?= esc($city) ?></h2>
        </div>
        <div class="row g-3">
            <?php foreach ($pairJobs as $job): ?>
                <div class="col-md-6 col-lg-4 reveal">
                    <div class="job-chip"><i class="fa-solid fa-circle-check"></i> <?= esc($job) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($svcJobs || $process): ?>
<!-- What the job covers + how it runs -->
<section class="section">
    <div class="container">
        <div class="row g-5 align-items-start">
            <?php if ($svcJobs): ?>
                <div class="col-lg-5 reveal">
                    <span class="section-eyebrow"><i class="fa-solid fa-clipboard-check"></i> Included</span>
                    <h2 class="section-title">What <?= esc($name) ?> Covers</h2>
                    <ul class="check-list">
                        <?php foreach ($svcJobs as $job): ?>
                            <li><i class="fa-solid fa-circle-check"></i> <?= esc($job) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a class="btn btn-outline-navy" href="<?= esc(service_page_url($serviceSlug)) ?>">
                        Full <?= esc(lcfirst($name)) ?> guide <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
            <?php endif; ?>
            <?php if ($process): ?>
                <div class="col-lg-7 reveal">
                    <span class="section-eyebrow"><i class="fa-solid fa-route"></i> How It Runs</span>
                    <h2 class="section-title">From Message to Finished Job</h2>
                    <div class="process-list">
                        <?php foreach ($process as $i => $step): ?>
                            <div class="process-item">
                                <span class="pi-num"><?= $i + 1 ?></span>
                                <div>
                                    <h5><?= esc($step['title'] ?? '') ?></h5>
                                    <p><?= esc($step['text'] ?? '') ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (trim($service['pricing_notes']) !== ''): ?>
<section class="section section-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9 reveal">
                <div class="pricing-note">
                    <div class="pn-icon"><i class="fa-solid fa-tag"></i></div>
                    <div>
                        <h3>What Affects the Price in <?= esc($city) ?></h3>
                        <p><?= esc($service['pricing_notes']) ?></p>
                        <p class="mb-0"><strong>Nothing starts before the price is agreed.</strong> Send a photo or two with your request and I can usually quote without a site visit.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($pair['faq_q'] !== '' && trim($pair['faq_a']) !== ''): ?>
<section class="section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 reveal">
                <div class="single-faq">
                    <span class="section-eyebrow"><i class="fa-solid fa-circle-question"></i> Asked in <?= esc($city) ?></span>
                    <h2 class="section-title"><?= esc($pair['faq_q']) ?></h2>
                    <p class="mb-0"><?= nl2br(esc($pair['faq_a'])) ?></p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($otherServices): ?>
<section class="section section-light">
    <div class="container">
        <div class="text-center mb-4 reveal">
            <span class="section-eyebrow"><i class="fa-solid fa-screwdriver-wrench"></i> Also in <?= esc($city) ?></span>
            <h2 class="section-title">Other Work I Do in <?= esc($city) ?></h2>
        </div>
        <div class="row g-3">
            <?php foreach ($otherServices as $svc): ?>
                <div class="col-md-6 col-lg-4 reveal">
                    <a class="link-card" href="<?= esc(service_location_url($svc['slug'], $locationSlug)) ?>">
                        <span class="lc-icon"><i class="<?= esc($svc['icon']) ?>"></i></span>
                        <span class="lc-body">
                            <strong><?= esc($svc['name']) ?></strong>
                            <small>in <?= esc($city) ?></small>
                        </span>
                        <i class="fa-solid fa-arrow-right lc-arrow"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a class="btn btn-outline-navy" href="<?= esc(location_url($locationSlug)) ?>">Everything in <?= esc($city) ?> <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($otherCities): ?>
<section class="section">
    <div class="container">
        <div class="text-center mb-4 reveal">
            <span class="section-eyebrow"><i class="fa-solid fa-map"></i> Nearby</span>
            <h2 class="section-title"><?= esc($name) ?> in Other GTA Cities</h2>
        </div>
        <div class="area-inline reveal">
            <?php foreach ($otherCities as $loc): ?>
                <a href="<?= esc(service_location_url($serviceSlug, $loc['slug'])) ?>"><?= esc($loc['name']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="cta-band" style="background-image: url('<?= esc(base_url(setting('cta_band_image'))) ?>');">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <h2><?= esc($name) ?> in <?= esc($city) ?> — free quote</h2>
                <p>Describe the job, add a photo if you can, and I will come back with a clear price. No obligation either way.</p>
            </div>
            <div class="col-lg-5">
                <div class="d-flex flex-wrap align-items-center gap-4 justify-content-lg-end">
                    <a class="cta-phone" href="<?= esc(phone_link_href()) ?>">
                        <span class="icon-circle"><i class="fa-solid fa-phone"></i></span>
                        <?= esc(setting('phone')) ?>
                    </a>
                    <a class="btn btn-gold btn-lg" href="<?= esc(base_url('quote')) ?>">Get a Free Quote</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

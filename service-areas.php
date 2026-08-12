<?php
/**
 * Service areas directory — /service-areas
 *
 * The hub that links every location page. This is what gives the location
 * pages a crawl path from the main navigation.
 */

require_once __DIR__ . '/includes/seo.php';

$byRegion  = seo_locations_by_region();
$majorCities = seo_locations(1);
$totalAreas  = array_sum(array_map('count', $byRegion));

$pageTitle       = setting('seo_areas_title', 'Handyman Service Areas Across the GTA');
$metaDescription = setting('seo_areas_description');
$canonicalPath   = 'service-areas';
$ogImage         = 'assets/img/work-condo.jpg';
$breadcrumbs     = [['label' => 'Home', 'url' => ''], ['label' => 'Service Areas']];

$canonical = canonical_url($canonicalPath);
$schemas   = [];
if ($totalAreas) {
    $allForList = [];
    foreach ($byRegion as $locs) {
        foreach ($locs as $loc) {
            $allForList[] = ['name' => 'Handyman in ' . $loc['name'], 'url' => 'handyman/' . $loc['slug']];
        }
    }
    $schemas[] = schema_item_list('Handyman service areas in the Greater Toronto Area', $allForList, $canonical);
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-portrait" style="background-image: url('<?= esc(base_url('assets/img/work-condo.jpg')) ?>');">
    <div class="container">
        <h1>Handyman Service Areas</h1>
        <?= breadcrumb_html($breadcrumbs) ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-eyebrow"><i class="fa-solid fa-earth-americas"></i> Greater Toronto Area</span>
            <h2 class="section-title">Wherever You Are in the GTA</h2>
            <p class="section-sub mx-auto">
                I serve homeowners, tenants, landlords and small businesses right across the Greater Toronto Area
                <?php if ($totalAreas): ?>— <?= (int) $totalAreas ?> communities and counting<?php endif; ?>.
                Pick your area below to see the work I do there, or just send me a message.
            </p>
        </div>

        <?php if ($majorCities): ?>
            <div class="row g-4 mb-5">
                <?php foreach ($majorCities as $city): ?>
                    <div class="col-md-6 col-lg-3 reveal">
                        <a class="area-feature" href="<?= esc(location_url($city['slug'])) ?>">
                            <span class="af-icon"><i class="fa-solid fa-location-dot"></i></span>
                            <strong><?= esc($city['name']) ?></strong>
                            <small><?= esc($city['region']) ?></small>
                            <span class="af-link">Handyman in <?= esc($city['name']) ?> <i class="fa-solid fa-arrow-right"></i></span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($byRegion): ?>
            <?php foreach ($byRegion as $region => $locations): ?>
                <div class="region-block reveal">
                    <h3 class="region-title"><i class="fa-solid fa-map-location-dot"></i> <?= esc($region) ?></h3>
                    <div class="row g-3">
                        <?php foreach ($locations as $loc): ?>
                            <div class="col-6 col-md-4 col-lg-3">
                                <a class="area-card d-block" href="<?= esc(location_url($loc['slug'])) ?>">
                                    <i class="fa-solid fa-location-dot"></i> <?= esc($loc['name']) ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">
                No service areas have been published yet. Add them in Admin &rarr; SEO &rarr; Locations.
            </div>
        <?php endif; ?>

        <p class="text-center mt-5 mb-0">
            Not sure whether you are inside my service area?
            <a href="<?= esc(base_url('contact')) ?>">Send me a message</a> or
            <a href="<?= esc(base_url('quote')) ?>">request a quote</a> — if I can get to you, I will.
        </p>
    </div>
</section>

<section class="cta-band" style="background-image: url('<?= esc(base_url(setting('cta_band_image'))) ?>');">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <h2>One handyman, the whole GTA</h2>
                <p>From downtown condos to Durham and Halton subdivisions — same care, same clear pricing, same tidy finish.</p>
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

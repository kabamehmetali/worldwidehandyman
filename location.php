<?php
/**
 * Location landing page — /handyman/{slug}
 *
 * Targets "handyman {city}" searches. Every field on the page comes from the
 * seo_locations row, so no two of these render the same copy.
 */

require_once __DIR__ . '/includes/seo.php';

$slug = trim($_GET['slug'] ?? '');
$location = $slug !== '' ? seo_location($slug) : null;

if (!$location) {
    seo_not_found(
        'Service Area Not Found',
        'That service area page does not exist. Browse all the areas I cover, or send me a message — if I can get to you, I will.'
    );
}

$locationId  = (int) $location['id'];
$name        = $location['name'];
$heroImage   = $location['hero_image'] !== '' ? $location['hero_image'] : seo_stock_image($slug);
$neighbourhoods = seo_lines($location['neighbourhoods']);
$landmarks   = seo_lines($location['landmarks']);
$commonJobs  = seo_lines($location['common_jobs']);
$faqs        = seo_json_list($location['faqs_json']);
$nearby      = seo_locations_by_slugs(array_map('trim', explode(',', $location['nearby'])));

// Services: link to the service+city page where one exists, otherwise to the
// general service page. Pages are never invented — only linked when published.
$comboServices = [];
foreach (seo_combo_services($locationId) as $svc) {
    $comboServices[$svc['slug']] = $svc;
}
$allServices = seo_service_pages();

// Reviews left by customers in this area carry more weight on this page
$localReviews = seo_query(
    'SELECT * FROM testimonials WHERE is_active = 1 AND location = ? ORDER BY sort_order ASC LIMIT 3',
    [$name]
);

/* ------------------------------------------------------------------ SEO */
$pageTitle       = $location['meta_title'] !== '' ? $location['meta_title'] : 'Handyman in ' . $name;
$metaDescription = $location['meta_description'] !== ''
    ? $location['meta_description']
    : seo_truncate('Handyman services in ' . $name . '. ' . $location['intro'], 155);
$canonicalPath   = 'handyman/' . $slug;
$ogImage         = $heroImage;
$breadcrumbs     = [
    ['label' => 'Home', 'url' => ''],
    ['label' => 'Service Areas', 'url' => 'service-areas'],
    ['label' => $name],
];

$canonical = canonical_url($canonicalPath);
$schemas   = [
    schema_service('Handyman Services in ' . $name, $metaDescription, $canonical, $location, $commonJobs),
    schema_faq($faqs, $canonical),
];
if ($allServices) {
    $schemas[] = schema_item_list(
        'Handyman services available in ' . $name,
        array_map(static function ($svc) use ($comboServices, $slug) {
            return [
                'name' => $svc['name'],
                'url'  => isset($comboServices[$svc['slug']])
                    ? 'services/' . $svc['slug'] . '/' . $slug
                    : 'services/' . $svc['slug'],
            ];
        }, $allServices),
        $canonical
    );
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-portrait" style="background-image: url('<?= esc(base_url($heroImage)) ?>');">
    <div class="container">
        <h1><?= esc($location['h1'] !== '' ? $location['h1'] : 'Handyman in ' . $name) ?></h1>
        <?= breadcrumb_html($breadcrumbs) ?>
    </div>
</section>

<!-- Intro + local facts -->
<section class="section">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-7 reveal">
                <span class="section-eyebrow"><i class="fa-solid fa-location-dot"></i> <?= esc($location['region']) ?></span>
                <h2 class="section-title">Your Local Handyman in <?= esc($name) ?></h2>
                <div class="page-content"><?= seo_paragraphs($location['intro']) ?></div>

                <?php if (trim($location['local_notes']) !== ''): ?>
                    <div class="local-note">
                        <i class="fa-solid fa-circle-info"></i>
                        <p><?= esc($location['local_notes']) ?></p>
                    </div>
                <?php endif; ?>

                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a class="btn btn-gold" href="<?= esc(base_url('quote')) ?>"><i class="fa-solid fa-file-signature me-2"></i>Get a Free Quote</a>
                    <a class="btn btn-outline-navy" href="<?= esc(phone_link_href()) ?>"><i class="fa-solid fa-phone me-2"></i><?= esc(setting('phone')) ?></a>
                </div>
            </div>

            <div class="col-lg-5 reveal">
                <div class="seo-facts">
                    <h3><i class="fa-solid fa-map-location-dot"></i> Serving <?= esc($name) ?></h3>
                    <?php if ($neighbourhoods): ?>
                        <div class="seo-fact">
                            <span class="sf-label">Neighbourhoods covered</span>
                            <div class="sf-chips">
                                <?php foreach ($neighbourhoods as $hood): ?>
                                    <span class="sf-chip"><?= esc($hood) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($landmarks): ?>
                        <div class="seo-fact">
                            <span class="sf-label">Local to</span>
                            <p class="sf-text"><?= esc(implode(' · ', $landmarks)) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($location['postal_prefixes'] !== ''): ?>
                        <div class="seo-fact">
                            <span class="sf-label">Postal codes</span>
                            <p class="sf-text"><?= esc($location['postal_prefixes']) ?></p>
                        </div>
                    <?php endif; ?>
                    <div class="seo-fact">
                        <span class="sf-label">Hours</span>
                        <p class="sf-text"><?= esc(setting('hours_weekday')) ?><br><?= esc(setting('hours_weekend')) ?></p>
                    </div>
                    <div class="seo-fact">
                        <span class="sf-label">Quotes</span>
                        <p class="sf-text">Free and no-obligation, usually answered within one business day.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($commonJobs): ?>
<!-- What people here call about -->
<section class="section section-light">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-eyebrow"><i class="fa-solid fa-list-check"></i> Common Requests</span>
            <h2 class="section-title">Jobs I Get Called For in <?= esc($name) ?></h2>
        </div>
        <div class="row g-3">
            <?php foreach ($commonJobs as $job): ?>
                <div class="col-md-6 col-lg-4 reveal">
                    <div class="job-chip"><i class="fa-solid fa-circle-check"></i> <?= esc($job) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (trim($location['body_html']) !== ''): ?>
<!-- Long-form local content -->
<section class="section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="page-content seo-body reveal"><?= seo_safe_html($location['body_html']) ?></div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($allServices): ?>
<!-- Services available here -->
<section class="section section-light">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-eyebrow"><i class="fa-solid fa-screwdriver-wrench"></i> Services</span>
            <h2 class="section-title">What I Can Do in <?= esc($name) ?></h2>
            <p class="section-sub mx-auto">Every one of these is available across <?= esc($name) ?>. Several small jobs can be booked into a single visit.</p>
        </div>
        <div class="row g-3">
            <?php foreach ($allServices as $svc): ?>
                <?php
                $hasCombo = isset($comboServices[$svc['slug']]);
                $url = $hasCombo ? service_location_url($svc['slug'], $slug) : service_page_url($svc['slug']);
                ?>
                <div class="col-md-6 col-lg-4 reveal">
                    <a class="link-card<?= $hasCombo ? ' link-card-featured' : '' ?>" href="<?= esc($url) ?>">
                        <span class="lc-icon"><i class="<?= esc($svc['icon']) ?>"></i></span>
                        <span class="lc-body">
                            <strong><?= esc($svc['name']) ?></strong>
                            <small><?= esc($hasCombo ? $svc['name'] . ' in ' . $name : 'Across the GTA') ?></small>
                        </span>
                        <i class="fa-solid fa-arrow-right lc-arrow"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($localReviews): ?>
<!-- Reviews from this area -->
<section class="section">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-eyebrow"><i class="fa-solid fa-star"></i> Reviews</span>
            <h2 class="section-title">What <?= esc($name) ?> Customers Say</h2>
        </div>
        <div class="row g-4 justify-content-center">
            <?php foreach ($localReviews as $t): ?>
                <div class="col-md-6 col-lg-4 reveal">
                    <div class="testimonial-card h-100">
                        <i class="fa-solid fa-quote-right quote-mark"></i>
                        <div class="testimonial-stars">
                            <?php for ($i = 0; $i < min(5, max(0, (int) $t['rating'])); $i++): ?><i class="fa-solid fa-star"></i><?php endfor; ?>
                        </div>
                        <p class="testimonial-text">&ldquo;<?= esc($t['quote_text']) ?>&rdquo;</p>
                        <div class="d-flex align-items-center gap-3 mt-3">
                            <div class="testimonial-avatar"><?= esc(mb_strtoupper(mb_substr($t['author'], 0, 1))) ?></div>
                            <div>
                                <p class="testimonial-author"><?= esc($t['author']) ?></p>
                                <span class="testimonial-location"><?= esc($t['location']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($faqs): ?>
<!-- Location FAQ -->
<section class="section section-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="text-center mb-5 reveal">
                    <span class="section-eyebrow"><i class="fa-solid fa-circle-question"></i> Good To Know</span>
                    <h2 class="section-title"><?= esc($name) ?> Handyman Questions</h2>
                </div>
                <div class="accordion faq-accordion reveal" id="locFaq">
                    <?php foreach ($faqs as $i => $faq): ?>
                        <?php $q = $faq['q'] ?? ''; $a = $faq['a'] ?? ''; if ($q === '' || $a === '') { continue; } ?>
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button<?= $i === 0 ? '' : ' collapsed' ?>" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#locFaq<?= (int) $i ?>"
                                        aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="locFaq<?= (int) $i ?>">
                                    <?= esc($q) ?>
                                </button>
                            </h3>
                            <div id="locFaq<?= (int) $i ?>" class="accordion-collapse collapse<?= $i === 0 ? ' show' : '' ?>" data-bs-parent="#locFaq">
                                <div class="accordion-body"><?= nl2br(esc($a)) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($nearby): ?>
<!-- Nearby areas -->
<section class="section">
    <div class="container">
        <div class="text-center mb-4 reveal">
            <span class="section-eyebrow"><i class="fa-solid fa-map"></i> Nearby</span>
            <h2 class="section-title">Also Serving Close to <?= esc($name) ?></h2>
        </div>
        <div class="row g-3 justify-content-center">
            <?php foreach ($nearby as $near): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a class="area-card d-block text-center" href="<?= esc(location_url($near['slug'])) ?>">
                        <i class="fa-solid fa-location-dot"></i> <?= esc($near['name']) ?>
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
                <h2>Need a handyman in <?= esc($name) ?>?</h2>
                <p>Send me your list — photos help me price it accurately. Quotes are free and there is no obligation.</p>
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

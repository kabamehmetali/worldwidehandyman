<?php
/**
 * Service landing page — /services/{slug}
 *
 * Targets the service keyword itself ("tv mounting toronto"). Links down to
 * the service+city pages where they exist, and across to related services.
 */

require_once __DIR__ . '/includes/seo.php';

$slug = trim($_GET['slug'] ?? '');
$service = $slug !== '' ? seo_service_page($slug) : null;

if (!$service) {
    seo_not_found(
        'Service Not Found',
        'That service page does not exist. Browse everything I do, or describe the job and I will tell you whether I can help.'
    );
}

$serviceId  = (int) $service['id'];
$name       = $service['name'];
$heroImage  = $service['hero_image'] !== '' ? $service['hero_image'] : seo_stock_image($slug);
$jobs       = seo_lines($service['jobs']);
$process    = seo_json_list($service['process_json']);
$faqs       = seo_json_list($service['faqs_json']);
$related    = seo_services_by_slugs(array_map('trim', explode(',', $service['related'])));
$comboLocations = seo_combo_locations($serviceId);
$otherAreas = seo_locations();

/* ------------------------------------------------------------------ SEO */
$pageTitle       = $service['meta_title'] !== '' ? $service['meta_title'] : $name;
$metaDescription = $service['meta_description'] !== ''
    ? $service['meta_description']
    : seo_truncate($service['intro'], 155);
$canonicalPath   = 'services/' . $slug;
$ogImage         = $heroImage;
$breadcrumbs     = [
    ['label' => 'Home', 'url' => ''],
    ['label' => 'Services', 'url' => 'services'],
    ['label' => $name],
];

$canonical = canonical_url($canonicalPath);
$schemas   = [
    schema_service($name, $metaDescription, $canonical, null, $jobs),
    schema_faq($faqs, $canonical),
];
if ($process) {
    $schemas[] = [
        '@type' => 'HowTo',
        '@id'   => $canonical . '#howto',
        'name'  => 'How ' . $name . ' works with Worldwide Handyman',
        'step'  => array_map(static function ($step, $i) {
            return [
                '@type'    => 'HowToStep',
                'position' => $i + 1,
                'name'     => $step['title'] ?? '',
                'text'     => $step['text'] ?? '',
            ];
        }, $process, array_keys($process)),
    ];
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-banner page-banner-portrait" style="background-image: url('<?= esc(base_url($heroImage)) ?>');">
    <div class="container">
        <h1><?= esc($service['h1'] !== '' ? $service['h1'] : $name) ?></h1>
        <?= breadcrumb_html($breadcrumbs) ?>
    </div>
</section>

<!-- Intro + what's included -->
<section class="section">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-7 reveal">
                <span class="section-eyebrow"><i class="<?= esc($service['icon']) ?>"></i> Service</span>
                <h2 class="section-title"><?= esc($name) ?> Across the GTA</h2>
                <div class="page-content"><?= seo_paragraphs($service['intro']) ?></div>
                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a class="btn btn-gold" href="<?= esc(base_url('quote')) ?>"><i class="fa-solid fa-file-signature me-2"></i>Get a Free Quote</a>
                    <a class="btn btn-outline-navy" href="<?= esc(phone_link_href()) ?>"><i class="fa-solid fa-phone me-2"></i><?= esc(setting('phone')) ?></a>
                </div>
            </div>
            <?php if ($jobs): ?>
                <div class="col-lg-5 reveal">
                    <div class="seo-facts">
                        <h3><i class="fa-solid fa-clipboard-check"></i> What's Included</h3>
                        <ul class="check-list mb-0">
                            <?php foreach ($jobs as $job): ?>
                                <li><i class="fa-solid fa-circle-check"></i> <?= esc($job) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if (trim($service['body_html']) !== ''): ?>
<!-- Long-form service content -->
<section class="section section-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="page-content seo-body reveal"><?= seo_safe_html($service['body_html']) ?></div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($process): ?>
<!-- How it works -->
<section class="section">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-eyebrow"><i class="fa-solid fa-list-check"></i> Simple Process</span>
            <h2 class="section-title">How <?= esc($name) ?> Works</h2>
        </div>
        <div class="row g-4 steps-row">
            <?php foreach ($process as $i => $step): ?>
                <div class="col-md-<?= count($process) === 4 ? '3' : '4' ?> reveal">
                    <div class="step-card">
                        <div class="step-num"><?= $i + 1 ?></div>
                        <h5><?= esc($step['title'] ?? '') ?></h5>
                        <p><?= esc($step['text'] ?? '') ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (trim($service['pricing_notes']) !== ''): ?>
<!-- What drives the price -->
<section class="section section-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9 reveal">
                <div class="pricing-note">
                    <div class="pn-icon"><i class="fa-solid fa-tag"></i></div>
                    <div>
                        <h3>What Affects the Price</h3>
                        <p><?= esc($service['pricing_notes']) ?></p>
                        <p class="mb-0"><strong>The price is always agreed before the work starts</strong> — a fixed price or an hourly rate, whichever suits the job. Quotes are free and there is no obligation.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($comboLocations): ?>
<!-- Dedicated city pages for this service -->
<section class="section">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-eyebrow"><i class="fa-solid fa-location-dot"></i> Where I Work</span>
            <h2 class="section-title"><?= esc($name) ?> in Your City</h2>
            <p class="section-sub mx-auto">Each of these covers what this job actually involves in that part of the GTA.</p>
        </div>
        <div class="row g-3">
            <?php foreach ($comboLocations as $loc): ?>
                <div class="col-6 col-md-4 col-lg-3 reveal">
                    <a class="link-card link-card-sm" href="<?= esc(service_location_url($slug, $loc['slug'])) ?>">
                        <span class="lc-body">
                            <strong><?= esc($name) ?></strong>
                            <small><?= esc($loc['name']) ?></small>
                        </span>
                        <i class="fa-solid fa-arrow-right lc-arrow"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($faqs): ?>
<!-- Service FAQ -->
<section class="section section-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="text-center mb-5 reveal">
                    <span class="section-eyebrow"><i class="fa-solid fa-circle-question"></i> Good To Know</span>
                    <h2 class="section-title"><?= esc($name) ?> Questions</h2>
                </div>
                <div class="accordion faq-accordion reveal" id="svcFaq">
                    <?php foreach ($faqs as $i => $faq): ?>
                        <?php $q = $faq['q'] ?? ''; $a = $faq['a'] ?? ''; if ($q === '' || $a === '') { continue; } ?>
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button<?= $i === 0 ? '' : ' collapsed' ?>" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#svcFaq<?= (int) $i ?>"
                                        aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="svcFaq<?= (int) $i ?>">
                                    <?= esc($q) ?>
                                </button>
                            </h3>
                            <div id="svcFaq<?= (int) $i ?>" class="accordion-collapse collapse<?= $i === 0 ? ' show' : '' ?>" data-bs-parent="#svcFaq">
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

<?php if ($related): ?>
<!-- Related services -->
<section class="section">
    <div class="container">
        <div class="text-center mb-4 reveal">
            <span class="section-eyebrow"><i class="fa-solid fa-screwdriver-wrench"></i> Often Booked Together</span>
            <h2 class="section-title">Related Services</h2>
        </div>
        <div class="row g-3 justify-content-center">
            <?php foreach ($related as $rel): ?>
                <div class="col-md-6 col-lg-4 reveal">
                    <a class="link-card" href="<?= esc(service_page_url($rel['slug'])) ?>">
                        <span class="lc-icon"><i class="<?= esc($rel['icon']) ?>"></i></span>
                        <span class="lc-body">
                            <strong><?= esc($rel['name']) ?></strong>
                            <small><?= esc(seo_truncate($rel['meta_description'], 60)) ?></small>
                        </span>
                        <i class="fa-solid fa-arrow-right lc-arrow"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a class="btn btn-outline-navy" href="<?= esc(base_url('services')) ?>">All Services <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($otherAreas): ?>
<!-- Service area strip -->
<section class="section section-light">
    <div class="container">
        <div class="text-center mb-4 reveal">
            <span class="section-eyebrow"><i class="fa-solid fa-map"></i> Service Area</span>
            <h2 class="section-title">Available Across the Greater Toronto Area</h2>
        </div>
        <div class="area-inline reveal">
            <?php foreach ($otherAreas as $area): ?>
                <a href="<?= esc(location_url($area['slug'])) ?>"><?= esc($area['name']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="cta-band" style="background-image: url('<?= esc(base_url(setting('cta_band_image'))) ?>');">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <h2>Ready to book <?= esc(lcfirst($name)) ?>?</h2>
                <p>Tell me what you need and send a photo or two — I will come back with a clear price, usually within one business day.</p>
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

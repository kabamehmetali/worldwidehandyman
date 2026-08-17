<?php
require_once __DIR__ . '/seo.php';

/* ---------------------------------------------------------------- SEO vars
   Pages set any of these before requiring this file — see includes/seo.php. */
$siteName        = setting('site_name');
$pageTitle       = $pageTitle ?? null;
$pageTitleFull   = $pageTitleFull ?? null;
$metaDescription = $metaDescription ?? setting('meta_description');
$canonicalPath   = $canonicalPath ?? null;
$robots          = $robots ?? 'index, follow';
$ogType          = $ogType ?? 'website';
$breadcrumbs     = $breadcrumbs ?? [];
$schemas         = $schemas ?? [];

$canonical = canonical_url($canonicalPath);
if ($canonicalPath !== null) {
    seo_redirect_to_canonical($canonical);
}

if ($pageTitleFull !== null && $pageTitleFull !== '') {
    $documentTitle = $pageTitleFull;
} elseif ($pageTitle !== null && $pageTitle !== '') {
    $documentTitle = $pageTitle . ' | ' . $siteName;
} else {
    $documentTitle = $siteName . ' | ' . strip_tags(setting('tagline'));
}

$ogImageUrl = site_url($ogImage ?? setting('seo_og_image', setting('hero_image')));

// Max-length hints for rich results; harmless on pages that are noindex
$robotsFull = $robots . ', max-snippet:-1, max-image-preview:large, max-video-preview:-1';

/* --------------------------------------------------------- JSON-LD graph */
$schemaGraph = [schema_business(), schema_website(),
    schema_webpage($canonical, $documentTitle, $metaDescription, $ogImageUrl, (bool) $breadcrumbs)];
if ($breadcrumbs) {
    $schemaGraph[] = schema_breadcrumbs($breadcrumbs, $canonical);
}
foreach ($schemas as $extraNode) {
    if ($extraNode) {
        $schemaGraph[] = $extraNode;
    }
}
?>
<!DOCTYPE html>
<html lang="en-CA">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($documentTitle) ?></title>
    <meta name="description" content="<?= esc($metaDescription) ?>">
    <link rel="canonical" href="<?= esc($canonical) ?>">
    <meta name="robots" content="<?= esc($robotsFull) ?>">
    <meta name="author" content="<?= esc(setting('seo_owner_name', 'Sercan')) ?>">
    <meta name="theme-color" content="<?= esc(setting('color_primary', '#10203F')) ?>">

    <!-- Local search signals -->
    <meta name="geo.region" content="CA-<?= esc(setting('seo_region', 'ON')) ?>">
    <meta name="geo.placename" content="<?= esc(setting('seo_locality', 'Toronto')) ?>">
    <meta name="geo.position" content="<?= esc(setting('seo_geo_lat', '43.6532')) ?>;<?= esc(setting('seo_geo_lng', '-79.3832')) ?>">
    <meta name="ICBM" content="<?= esc(setting('seo_geo_lat', '43.6532')) ?>, <?= esc(setting('seo_geo_lng', '-79.3832')) ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="<?= esc($ogType) ?>">
    <meta property="og:site_name" content="<?= esc($siteName) ?>">
    <meta property="og:title" content="<?= esc($documentTitle) ?>">
    <meta property="og:description" content="<?= esc($metaDescription) ?>">
    <meta property="og:url" content="<?= esc($canonical) ?>">
    <meta property="og:image" content="<?= esc($ogImageUrl) ?>">
    <meta property="og:locale" content="en_CA">

    <!-- Twitter / X card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= esc($documentTitle) ?>">
    <meta name="twitter:description" content="<?= esc($metaDescription) ?>">
    <meta name="twitter:image" content="<?= esc($ogImageUrl) ?>">

<?php if (setting('seo_google_verification') !== ''): ?>
    <meta name="google-site-verification" content="<?= esc(setting('seo_google_verification')) ?>">
<?php endif; ?>
<?php if (setting('seo_bing_verification') !== ''): ?>
    <meta name="msvalidate.01" content="<?= esc(setting('seo_bing_verification')) ?>">
<?php endif; ?>

    <link rel="icon" type="image/png" href="<?= esc(base_url(setting('logo_icon'))) ?>">
    <link rel="apple-touch-icon" href="<?= esc(base_url(setting('logo_icon'))) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= esc(base_url('assets/css/styles.css')) ?>?v=<?= @filemtime(APP_ROOT . '/assets/css/styles.css') ?>" rel="stylesheet">

    <?= json_ld_block($schemaGraph) ?>

<?php if (setting('gtm_id') !== ''): ?>
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
    var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;
    j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','<?= esc(setting('gtm_id')) ?>');</script>
<?php elseif (setting('ga4_id') !== ''): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= rawurlencode(setting('ga4_id')) ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}
    gtag('js',new Date());gtag('config','<?= esc(setting('ga4_id')) ?>');</script>
<?php endif; ?>
    <style>
        :root {
            --ww-navy: <?= esc(setting('color_primary', '#10203F')) ?>;
            --ww-navy-dark: <?= esc(setting('color_primary_dark', '#0A1428')) ?>;
            --ww-gold: <?= esc(setting('color_accent', '#F5A800')) ?>;
            --ww-gold-light: <?= esc(setting('color_accent_light', '#FFC933')) ?>;
            --ww-red: <?= esc(setting('color_red', '#D2382C')) ?>;
            --ww-bg-light: <?= esc(setting('color_bg_light', '#F5F7FB')) ?>;
        }
    </style>
</head>
<body>
<?php if (setting('gtm_id') !== ''): ?>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= rawurlencode(setting('gtm_id')) ?>"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<?php endif; ?>

<!-- Top bar -->
<div class="topbar d-none d-md-block">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex gap-4">
            <span><i class="fa-solid fa-phone"></i> <a href="<?= esc(phone_link_href()) ?>"><?= esc(setting('phone')) ?></a></span>
            <span><i class="fa-solid fa-envelope"></i> <a href="mailto:<?= esc(setting('email')) ?>"><?= esc(setting('email')) ?></a></span>
        </div>
        <div class="d-flex gap-4 align-items-center">
            <span><i class="fa-solid fa-clock"></i> <?= esc(setting('hours_weekday')) ?></span>
            <span class="topbar-area"><i class="fa-solid fa-earth-americas"></i> <?= esc(setting('service_area')) ?></span>
            <?php if (setting('facebook_url') !== ''): ?>
                <a class="topbar-social" href="<?= esc(setting('facebook_url')) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
            <?php endif; ?>
            <?php if (setting('instagram_url') !== ''): ?>
                <a class="topbar-social" href="<?= esc(setting('instagram_url')) ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg site-navbar sticky-top" id="siteNavbar">
    <div class="container">
        <a class="navbar-brand" href="<?= esc(base_url()) ?>">
            <img class="brand-icon" src="<?= esc(base_url(setting('logo_icon'))) ?>" alt="">
            <img class="brand-logo" src="<?= esc(base_url(setting('logo_nav'))) ?>" alt="<?= esc(setting('site_name')) ?>">
        </a>
        <button class="nav-burger d-lg-none" type="button" id="navBurger"
                aria-controls="mainNav" aria-expanded="false" aria-label="Open menu">
            <span></span><span></span><span></span>
        </button>

        <div class="site-nav-panel" id="mainNav">
            <div class="nav-panel-head d-lg-none">
                <img class="nav-panel-icon" src="<?= esc(base_url(setting('logo_icon'))) ?>" alt="">
                <span class="nav-panel-eyebrow"><?= esc(strip_tags(setting('tagline'))) ?></span>
            </div>

            <ul class="navbar-nav ms-auto align-items-lg-center">
                <?php foreach (nav_links() as $i => $link): ?>
                    <?php if ($link['is_cta']): ?>
                        <li class="nav-item nav-item-cta ms-lg-3 mt-2 mt-lg-0" style="--i: <?= (int) $i ?>;">
                            <a class="btn btn-gold btn-nav-cta" href="<?= esc(base_url($link['url'])) ?>"<?= $link['open_new_tab'] ? ' target="_blank" rel="noopener"' : '' ?>>
                                <i class="fa-solid fa-file-signature me-1"></i> <?= esc($link['label']) ?>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item" style="--i: <?= (int) $i ?>;">
                            <a class="nav-link<?= is_nav_active($link['url']) ? ' active' : '' ?>"
                               href="<?= esc(base_url($link['url'])) ?>"<?= $link['open_new_tab'] ? ' target="_blank" rel="noopener"' : '' ?>>
                                <span><?= esc($link['label']) ?></span>
                                <i class="fa-solid fa-chevron-right nav-arrow d-lg-none" aria-hidden="true"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>

            <div class="nav-panel-foot d-lg-none">
                <a class="nav-panel-call" href="<?= esc(phone_link_href()) ?>">
                    <span class="npc-icon"><i class="fa-solid fa-phone"></i></span>
                    <span>
                        <small>Call or text</small>
                        <strong><?= esc(setting('phone')) ?></strong>
                    </span>
                </a>
                <a class="nav-panel-mail" href="mailto:<?= esc(setting('email')) ?>">
                    <i class="fa-solid fa-envelope"></i> <?= esc(setting('email')) ?>
                </a>
                <?php if (setting('facebook_url') !== '' || setting('instagram_url') !== '' || setting('tiktok_url') !== ''): ?>
                    <div class="nav-panel-social">
                        <?php if (setting('facebook_url') !== ''): ?>
                            <a href="<?= esc(setting('facebook_url')) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php if (setting('instagram_url') !== ''): ?>
                            <a href="<?= esc(setting('instagram_url')) ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        <?php endif; ?>
                        <?php if (setting('tiktok_url') !== ''): ?>
                            <a href="<?= esc(setting('tiktok_url')) ?>" target="_blank" rel="noopener" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

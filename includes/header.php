<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? setting('tagline');
$metaDescription = $metaDescription ?? setting('meta_description');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($pageTitle) ?> | <?= esc(setting('site_name')) ?></title>
    <meta name="description" content="<?= esc($metaDescription) ?>">
    <link rel="icon" type="image/png" href="<?= esc(base_url(setting('logo_icon'))) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= esc(base_url('assets/css/styles.css')) ?>?v=<?= @filemtime(APP_ROOT . '/assets/css/styles.css') ?>" rel="stylesheet">
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

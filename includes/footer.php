<?php
// Prefer the service landing pages so the footer links somewhere useful;
// fall back to the simple services table before they have been imported.
$footerServices = [];
foreach (array_slice(seo_service_pages(true), 0, 6) as $svc) {
    $footerServices[] = ['title' => $svc['name'], 'url' => 'services/' . $svc['slug']];
}
if (!$footerServices) {
    foreach (db()->query('SELECT title FROM services WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 6') as $row) {
        $footerServices[] = ['title' => $row['title'], 'url' => 'services'];
    }
}
$footerAreas = seo_locations();
?>
<!-- Footer -->
<footer class="site-footer">
    <div class="container">
        <div class="row gy-5">
            <div class="col-lg-4 col-md-6">
                <div class="footer-logo-card">
                    <img src="<?= esc(base_url(setting('logo_footer', setting('logo_nav')))) ?>" alt="<?= esc(setting('site_name')) ?>">
                </div>
                <p class="footer-blurb"><?= esc(setting('footer_about')) ?></p>
                <div class="footer-social">
                    <?php if (setting('facebook_url') !== ''): ?>
                        <a href="<?= esc(setting('facebook_url')) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <?php endif; ?>
                    <?php if (setting('instagram_url') !== ''): ?>
                        <a href="<?= esc(setting('instagram_url')) ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <?php endif; ?>
                    <?php if (setting('tiktok_url') !== ''): ?>
                        <a href="<?= esc(setting('tiktok_url')) ?>" target="_blank" rel="noopener" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
                    <?php endif; ?>
                    <a href="mailto:<?= esc(setting('email')) ?>" aria-label="Email"><i class="fa-solid fa-envelope"></i></a>
                    <a href="<?= esc(phone_link_href()) ?>" aria-label="Call <?= esc(setting('phone')) ?>"><i class="fa-solid fa-phone"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6 col-6">
                <h5 class="footer-title">Quick Links</h5>
                <ul class="footer-links">
                    <?php foreach (nav_links() as $link): ?>
                        <li><a href="<?= esc(base_url($link['url'])) ?>"><?= esc($link['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6 col-6">
                <h5 class="footer-title">Popular Services</h5>
                <ul class="footer-links">
                    <?php foreach ($footerServices as $fs): ?>
                        <li><a href="<?= esc(base_url($fs['url'])) ?>"><?= esc($fs['title']) ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="<?= esc(base_url('services')) ?>"><strong>All services &rarr;</strong></a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h5 class="footer-title">Get In Touch</h5>
                <ul class="footer-contact">
                    <li><i class="fa-solid fa-phone"></i><a href="<?= esc(phone_link_href()) ?>"><?= esc(setting('phone')) ?></a></li>
                    <li><i class="fa-solid fa-envelope"></i><a href="mailto:<?= esc(setting('email')) ?>"><?= esc(setting('email')) ?></a></li>
                    <li><i class="fa-solid fa-location-dot"></i><span><?= esc(setting('address')) ?></span></li>
                    <li><i class="fa-solid fa-clock"></i><span><?= esc(setting('hours_weekday')) ?><br><?= esc(setting('hours_weekend')) ?></span></li>
                </ul>
            </div>
        </div>
    </div>
    <?php if ($footerAreas): ?>
        <div class="footer-areas">
            <div class="container">
                <h6>Handyman services across the Greater Toronto Area</h6>
                <div class="footer-area-links">
                    <?php foreach ($footerAreas as $area): ?>
                        <a href="<?= esc(base_url('handyman/' . $area['slug'])) ?>">Handyman <?= esc($area['name']) ?></a>
                    <?php endforeach; ?>
                    <a class="fa-all" href="<?= esc(base_url('service-areas')) ?>">All areas &rarr;</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="footer-bottom">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <span>&copy; <?= date('Y') ?> <?= esc(setting('site_name')) ?>. All rights reserved.</span>
            <span class="footer-tagline"><?= esc(strip_tags(setting('tagline'))) ?></span>
            <span>Designed by
                <?php if (setting('designer_url') !== ''): ?>
                    <a class="designer-link" href="<?= esc(setting('designer_url')) ?>" target="_blank" rel="noopener"><?= esc(setting('designer_name', 'Creative Torontonian')) ?></a>
                <?php else: ?>
                    <span class="designer-link"><?= esc(setting('designer_name', 'Creative Torontonian')) ?></span>
                <?php endif; ?>
            </span>
        </div>
    </div>
</footer>

<a href="#" class="back-to-top" id="backToTop" aria-label="Back to top"><i class="fa-solid fa-chevron-up"></i></a>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= esc(base_url('assets/js/scripts.js')) ?>?v=<?= @filemtime(APP_ROOT . '/assets/js/scripts.js') ?>"></script>
</body>
</html>

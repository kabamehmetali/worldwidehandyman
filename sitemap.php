<?php
/**
 * XML sitemap — served at /sitemap.xml via .htaccess
 *
 * Lists every indexable URL: the fixed pages, the admin-authored pages, and
 * all published location / service / service+city landing pages.
 */

require_once __DIR__ . '/includes/seo.php';

/** @var array<int, array{loc:string, lastmod:?string, changefreq:string, priority:string}> */
$urls = [];

$add = static function (string $path, string $priority, string $changefreq, ?string $lastmod = null) use (&$urls): void {
    $urls[] = [
        'loc'        => site_url($path),
        'lastmod'    => $lastmod ? date('Y-m-d', strtotime($lastmod)) : null,
        'changefreq' => $changefreq,
        'priority'   => $priority,
    ];
};

/* ------------------------------------------------------------ core pages */
$add('', '1.0', 'weekly');
$add('services', '0.9', 'monthly');
$add('service-areas', '0.9', 'monthly');
$add('about', '0.6', 'yearly');
$add('gallery', '0.5', 'monthly');
$add('faq', '0.6', 'monthly');
$add('contact', '0.7', 'yearly');
$add('quote', '0.8', 'yearly');

/* -------------------------------------------------- admin-authored pages */
foreach (seo_query('SELECT slug, updated_at FROM pages WHERE is_published = 1') as $page) {
    $add(custom_page_path($page['slug']), '0.5', 'monthly', $page['updated_at']);
}

/* ------------------------------------------------------- service landing */
foreach (seo_service_pages() as $svc) {
    $add('services/' . $svc['slug'], $svc['is_pillar'] ? '0.9' : '0.8', 'monthly', $svc['updated_at']);
}

/* ------------------------------------------------------ location landing */
foreach (seo_locations() as $loc) {
    $add('handyman/' . $loc['slug'], (int) $loc['tier'] === 1 ? '0.9' : '0.7', 'monthly', $loc['updated_at']);
}

/* --------------------------------------------------- service x city pages */
foreach (seo_all_combos() as $pair) {
    $add('services/' . $pair['service_slug'] . '/' . $pair['location_slug'], '0.7', 'monthly', $pair['updated_at']);
}

/* ------------------------------------------------------------------ output */
header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url): ?>
    <url>
        <loc><?= esc($url['loc']) ?></loc>
<?php if ($url['lastmod']): ?>
        <lastmod><?= esc($url['lastmod']) ?></lastmod>
<?php endif; ?>
        <changefreq><?= esc($url['changefreq']) ?></changefreq>
        <priority><?= esc($url['priority']) ?></priority>
    </url>
<?php endforeach; ?>
</urlset>

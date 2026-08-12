<?php
/**
 * robots.txt — served at /robots.txt via .htaccess
 *
 * Generated rather than static so the sitemap URL always matches the domain
 * the site is actually running on (and the subfolder, in local development).
 */

require_once __DIR__ . '/includes/seo.php';

header('Content-Type: text/plain; charset=utf-8');

$lines = [
    'User-agent: *',
    'Allow: /',
    '',
    '# Private areas and endpoints that should never be indexed',
    'Disallow: ' . base_url('admin/'),
    'Disallow: ' . base_url('includes/'),
    'Disallow: ' . base_url('sql/'),
    'Disallow: ' . base_url('assets/uploads/tmp/'),
    'Disallow: /*?_csrf=',
    '',
    '# Crawl the whole site, but do not hammer it',
    'Crawl-delay: 1',
];

$extra = seo_lines(setting('seo_robots_extra'));
if ($extra) {
    $lines[] = '';
    foreach ($extra as $line) {
        $lines[] = $line;
    }
}

$lines[] = '';
$lines[] = 'Sitemap: ' . site_url('sitemap.xml');
$lines[] = '';

echo implode("\n", $lines);

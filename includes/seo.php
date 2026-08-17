<?php
/**
 * SEO layer — canonical URLs, meta tags, JSON-LD structured data, and data
 * access for the programmatic location / service landing pages.
 *
 * Page scripts set any of these BEFORE requiring includes/header.php:
 *
 *   $pageTitle       short title, rendered as "{title} | {site name}"
 *   $pageTitleFull   complete <title>, overrides $pageTitle entirely
 *   $metaDescription meta description (150-ish characters)
 *   $canonicalPath   path relative to the site root, e.g. 'services/tv-mounting'
 *                    — always set this on pages that take a query string
 *   $robots          robots directive (default "index, follow")
 *   $ogImage         relative or absolute image for social cards
 *   $ogType          Open Graph type (default "website")
 *   $breadcrumbs     [['label' => 'Services', 'url' => 'services'], ...]
 *                    last entry is the current page and needs no url
 *   $schemas         extra JSON-LD nodes merged into the @graph
 *
 * Everything degrades safely: with no settings configured the canonical falls
 * back to the requested host, and with the seo_* tables missing the data
 * helpers return empty arrays instead of throwing.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

/* ------------------------------------------------------------------ URLs */

/** Scheme + host with no trailing slash, e.g. "https://worldwidehandyman.ca". */
function site_origin(): string
{
    static $origin = null;
    if ($origin !== null) {
        return $origin;
    }

    $configured = trim(setting('site_url'));
    if ($configured !== '') {
        // Tolerate a full URL being pasted in — keep only scheme and host
        if (!preg_match('~^https?://~i', $configured)) {
            $configured = 'https://' . $configured;
        }
        $parts = parse_url($configured);
        if (!empty($parts['host'])) {
            $origin = strtolower($parts['scheme'] ?? 'https') . '://' . $parts['host']
                . (isset($parts['port']) ? ':' . $parts['port'] : '');
            return $origin;
        }
    }

    $forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
    $https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || $forwardedProto === 'https'
        || (int) ($_SERVER['SERVER_PORT'] ?? 80) === 443;

    $origin = ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    return $origin;
}

/** Absolute URL for a site-relative path. Absolute inputs pass through. */
function site_url(string $path = ''): string
{
    if (preg_match('~^(?:https?:)?//|^(?:mailto:|tel:|#)~i', $path)) {
        return $path;
    }
    return site_origin() . base_url($path);
}

/** Absolute canonical URL — $canonicalPath when given, else the current path. */
function canonical_url(?string $path = null): string
{
    if ($path !== null) {
        return site_url($path);
    }
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (!is_string($uri) || $uri === '') {
        $uri = base_url();
    }
    // /index.php and /some/dir/ both canonicalise to the directory itself
    $uri = preg_replace('~/index\.php$~i', '/', $uri);
    return site_origin() . $uri;
}

/**
 * The origin that the visitor actually requested, without consulting the SEO
 * setting. It lets public pages redirect old URL variants to their configured
 * canonical URL without ever reflecting an untrusted host in the redirect.
 */
function seo_request_origin(): string
{
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return '';
    }

    $forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
    $https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || $forwardedProto === 'https'
        || (int) ($_SERVER['SERVER_PORT'] ?? 80) === 443;

    return ($https ? 'https://' : 'http://') . strtolower($host);
}

/**
 * Permanently consolidate a public page's legacy URL variants.
 *
 * The same document was historically reachable through both clean URLs and
 * direct PHP endpoints (for example /about and /about.php). A canonical tag
 * is only a hint, so redirecting the old path is the unambiguous signal. A
 * query string on an otherwise canonical URL is retained for analytics; the
 * HTML canonical still omits it. Only safe GET/HEAD requests are redirected.
 */
function seo_redirect_to_canonical(string $canonical): void
{
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($method, ['GET', 'HEAD'], true) || headers_sent()) {
        return;
    }

    $canonicalParts = parse_url($canonical);
    if (!is_array($canonicalParts) || empty($canonicalParts['scheme']) || empty($canonicalParts['host'])) {
        return;
    }

    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $requestPath = parse_url($requestUri, PHP_URL_PATH);
    if (!is_string($requestPath) || $requestPath === '') {
        return;
    }

    $canonicalPath = (string) ($canonicalParts['path'] ?? '/');
    $canonicalOrigin = strtolower($canonicalParts['scheme'] . '://' . $canonicalParts['host']
        . (isset($canonicalParts['port']) ? ':' . $canonicalParts['port'] : ''));
    $requestOrigin = seo_request_origin();

    if ($requestOrigin === $canonicalOrigin && $requestPath === $canonicalPath) {
        return;
    }

    $target = $canonical;
    // Keep campaign parameters when only the host or protocol needs fixing.
    // When the path itself is legacy, drop its query string with the duplicate.
    if ($requestPath === $canonicalPath) {
        $requestQuery = parse_url($requestUri, PHP_URL_QUERY);
        if (is_string($requestQuery) && $requestQuery !== '') {
            $target .= '?' . $requestQuery;
        }
    }

    header('Location: ' . $target, true, 301);
    exit;
}

/* -------------------------------------------------------------- text bits */

/** Split a textarea value into trimmed, non-empty lines. */
function seo_lines(?string $text): array
{
    if ($text === null || trim($text) === '') {
        return [];
    }
    return array_values(array_filter(array_map('trim', preg_split('/\R/', $text)), 'strlen'));
}

/** Plain text with blank-line paragraph breaks -> escaped <p> blocks. */
function seo_paragraphs(?string $text): string
{
    $html = '';
    foreach (preg_split('/\R{2,}/', trim((string) $text)) as $para) {
        $para = trim($para);
        if ($para !== '') {
            $html .= '<p>' . nl2br(esc($para)) . '</p>';
        }
    }
    return $html;
}

/** Allow only the small tag set the landing-page bodies are written in. */
function seo_safe_html(?string $html): string
{
    return strip_tags((string) $html, '<h2><h3><h4><p><ul><ol><li><strong><em><br>');
}

/** Trim to a length without cutting a word in half. */
function seo_truncate(string $text, int $max): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text));
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    $cut = mb_substr($text, 0, $max - 1);
    $space = mb_strrpos($cut, ' ');
    return rtrim($space !== false ? mb_substr($cut, 0, $space) : $cut, " ,.;:-") . '…';
}

/** Decode a JSON column into a list of rows, tolerating empty/bad values. */
function seo_json_list(?string $json): array
{
    $data = json_decode((string) $json, true);
    return is_array($data) ? $data : [];
}

/* --------------------------------------------------------- data access */

/**
 * Run a query against the seo_* tables, returning [] if they do not exist yet
 * (so the site keeps working before sql/seo.sql has been imported).
 */
function seo_query(string $sql, array $params = []): array
{
    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/** All published locations, ordered. Pass a tier to filter. */
function seo_locations(?int $tier = null, bool $publishedOnly = true): array
{
    static $cache = [];
    $key = ($tier ?? 'all') . '|' . ($publishedOnly ? 1 : 0);
    if (!isset($cache[$key])) {
        $where = [];
        $params = [];
        if ($publishedOnly) {
            $where[] = 'is_published = 1';
        }
        if ($tier !== null) {
            $where[] = 'tier = ?';
            $params[] = $tier;
        }
        $sql = 'SELECT * FROM seo_locations'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY tier ASC, sort_order ASC, name ASC';
        $cache[$key] = seo_query($sql, $params);
    }
    return $cache[$key];
}

/** Published locations grouped by region, preserving order. */
function seo_locations_by_region(): array
{
    $grouped = [];
    foreach (seo_locations() as $loc) {
        $grouped[$loc['region'] ?: 'Greater Toronto Area'][] = $loc;
    }
    return $grouped;
}

function seo_location(string $slug, bool $publishedOnly = true): ?array
{
    $rows = seo_query(
        'SELECT * FROM seo_locations WHERE slug = ?' . ($publishedOnly ? ' AND is_published = 1' : ''),
        [$slug]
    );
    return $rows[0] ?? null;
}

/** All published service landing pages. */
function seo_service_pages(bool $pillarOnly = false, bool $publishedOnly = true): array
{
    static $cache = [];
    $key = ($pillarOnly ? 1 : 0) . '|' . ($publishedOnly ? 1 : 0);
    if (!isset($cache[$key])) {
        $where = [];
        if ($publishedOnly) {
            $where[] = 'is_published = 1';
        }
        if ($pillarOnly) {
            $where[] = 'is_pillar = 1';
        }
        $cache[$key] = seo_query(
            'SELECT * FROM seo_services'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY sort_order ASC, name ASC'
        );
    }
    return $cache[$key];
}

function seo_service_page(string $slug, bool $publishedOnly = true): ?array
{
    $rows = seo_query(
        'SELECT * FROM seo_services WHERE slug = ?' . ($publishedOnly ? ' AND is_published = 1' : ''),
        [$slug]
    );
    return $rows[0] ?? null;
}

/** The service+location pair row, or null when that combination has no page. */
function seo_combo(int $serviceId, int $locationId, bool $publishedOnly = true): ?array
{
    $rows = seo_query(
        'SELECT * FROM seo_service_locations WHERE service_id = ? AND location_id = ?'
        . ($publishedOnly ? ' AND is_published = 1' : ''),
        [$serviceId, $locationId]
    );
    return $rows[0] ?? null;
}

/** Locations that have a published page for this service. */
function seo_combo_locations(int $serviceId): array
{
    return seo_query(
        'SELECT l.*, sl.meta_title AS combo_title
           FROM seo_service_locations sl
           JOIN seo_locations l ON l.id = sl.location_id
          WHERE sl.service_id = ? AND sl.is_published = 1 AND l.is_published = 1
          ORDER BY l.sort_order ASC, l.name ASC',
        [$serviceId]
    );
}

/** Services that have a published page for this location. */
function seo_combo_services(int $locationId): array
{
    return seo_query(
        'SELECT s.*
           FROM seo_service_locations sl
           JOIN seo_services s ON s.id = sl.service_id
          WHERE sl.location_id = ? AND sl.is_published = 1 AND s.is_published = 1
          ORDER BY s.sort_order ASC, s.name ASC',
        [$locationId]
    );
}

/** Every published pair, joined — used by the sitemap. */
function seo_all_combos(): array
{
    return seo_query(
        'SELECT s.slug AS service_slug, l.slug AS location_slug, sl.updated_at
           FROM seo_service_locations sl
           JOIN seo_services s  ON s.id = sl.service_id
           JOIN seo_locations l ON l.id = sl.location_id
          WHERE sl.is_published = 1 AND s.is_published = 1 AND l.is_published = 1
          ORDER BY s.sort_order ASC, l.sort_order ASC'
    );
}

/** Resolve a list of location slugs to rows, keeping the given order. */
function seo_locations_by_slugs(array $slugs): array
{
    $slugs = array_values(array_filter(array_map('strval', $slugs), 'strlen'));
    if (!$slugs) {
        return [];
    }
    $in = implode(',', array_fill(0, count($slugs), '?'));
    $rows = seo_query("SELECT * FROM seo_locations WHERE is_published = 1 AND slug IN ($in)", $slugs);
    $bySlug = [];
    foreach ($rows as $row) {
        $bySlug[$row['slug']] = $row;
    }
    $ordered = [];
    foreach ($slugs as $slug) {
        if (isset($bySlug[$slug])) {
            $ordered[] = $bySlug[$slug];
        }
    }
    return $ordered;
}

/** Resolve a list of service slugs to rows, keeping the given order. */
function seo_services_by_slugs(array $slugs): array
{
    $slugs = array_values(array_filter(array_map('strval', $slugs), 'strlen'));
    if (!$slugs) {
        return [];
    }
    $in = implode(',', array_fill(0, count($slugs), '?'));
    $rows = seo_query("SELECT * FROM seo_services WHERE is_published = 1 AND slug IN ($in)", $slugs);
    $bySlug = [];
    foreach ($rows as $row) {
        $bySlug[$row['slug']] = $row;
    }
    $ordered = [];
    foreach ($slugs as $slug) {
        if (isset($bySlug[$slug])) {
            $ordered[] = $bySlug[$slug];
        }
    }
    return $ordered;
}

/* ------------------------------------------------------------ page URLs */

function location_url(string $slug): string
{
    return base_url('handyman/' . $slug);
}

function service_page_url(string $slug): string
{
    return base_url('services/' . $slug);
}

function service_location_url(string $serviceSlug, string $locationSlug): string
{
    return base_url('services/' . $serviceSlug . '/' . $locationSlug);
}

/* --------------------------------------------------------- JSON-LD graph */

/** The business node — referenced by every other node on the site. */
function schema_business(): array
{
    $id = site_url() . '#business';
    $phone = setting('phone_link') !== '' ? setting('phone_link') : setting('phone');

    $node = [
        '@type'       => setting('seo_business_type', 'HomeAndConstructionBusiness'),
        '@id'         => $id,
        'name'        => setting('site_name'),
        'description' => setting('meta_description'),
        'url'         => site_url(),
        'telephone'   => $phone,
        'email'       => setting('email'),
        'slogan'      => strip_tags(setting('tagline')),
        'image'       => site_url(setting('logo_footer', setting('logo_nav'))),
        'logo'        => [
            '@type' => 'ImageObject',
            'url'   => site_url(setting('logo_nav')),
        ],
        'priceRange'  => setting('seo_price_range', '$$'),
        'currenciesAccepted' => 'CAD',
        'paymentAccepted'    => setting('seo_payment_accepted', 'Cash, Debit, Credit Card, e-Transfer'),
        'address'     => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => setting('seo_street_address'),
            'addressLocality' => setting('seo_locality', 'Toronto'),
            'addressRegion'   => setting('seo_region', 'ON'),
            'postalCode'      => setting('seo_postal_code'),
            'addressCountry'  => setting('seo_country', 'CA'),
        ],
        'founder' => [
            '@type'    => 'Person',
            'name'     => setting('seo_owner_name', 'Sercan'),
            'jobTitle' => 'Owner and Operator',
        ],
    ];

    // Drop empty address parts so the markup stays clean
    $node['address'] = array_filter($node['address'], static fn ($v) => $v !== '');

    $lat = (float) setting('seo_geo_lat', '43.6532');
    $lng = (float) setting('seo_geo_lng', '-79.3832');
    if ($lat !== 0.0 && $lng !== 0.0) {
        $node['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => $lat, 'longitude' => $lng];
        $node['serviceArea'] = [
            '@type'        => 'GeoCircle',
            'geoMidpoint'  => ['@type' => 'GeoCoordinates', 'latitude' => $lat, 'longitude' => $lng],
            'geoRadius'    => (string) ((int) setting('seo_geo_radius_km', '60') * 1000),
        ];
    }

    if ((int) setting('seo_founding_year', '0') > 0) {
        $node['foundingDate'] = setting('seo_founding_year');
    }

    $sameAs = seo_lines(setting('seo_sameas'));
    foreach (['facebook_url', 'instagram_url', 'tiktok_url'] as $key) {
        if (setting($key) !== '') {
            $sameAs[] = setting($key);
        }
    }
    $sameAs = array_values(array_unique($sameAs));
    if ($sameAs) {
        $node['sameAs'] = $sameAs;
    }

    // Opening hours — explicit settings rather than parsing the display strings
    $days = seo_lines(str_replace(',', "\n", setting('seo_open_days',
        "Monday\nTuesday\nWednesday\nThursday\nFriday\nSaturday")));
    if ($days) {
        $node['openingHoursSpecification'] = [[
            '@type'     => 'OpeningHoursSpecification',
            'dayOfWeek' => $days,
            'opens'     => setting('seo_open_time', '08:00'),
            'closes'    => setting('seo_close_time', '20:00'),
        ]];
    }

    // Areas served — the major cities, plus the umbrella region
    $areas = [['@type' => 'AdministrativeArea', 'name' => 'Greater Toronto Area']];
    foreach (seo_locations(1) as $loc) {
        $areas[] = ['@type' => 'City', 'name' => $loc['name'] . ', ' . setting('seo_region', 'ON')];
    }
    $node['areaServed'] = $areas;

    // Offer catalogue built from the service landing pages
    $offers = [];
    foreach (seo_service_pages() as $svc) {
        $offers[] = [
            '@type'       => 'Offer',
            'itemOffered' => [
                '@type'       => 'Service',
                'name'        => $svc['name'],
                'url'         => site_url('services/' . $svc['slug']),
                'description' => $svc['meta_description'],
            ],
        ];
    }
    if ($offers) {
        $node['hasOfferCatalog'] = [
            '@type'          => 'OfferCatalog',
            'name'           => 'Handyman Services',
            'itemListElement' => $offers,
        ];
    }

    // Review markup is a manual-action risk unless the reviews are real and
    // verifiable, so it stays off until the owner explicitly turns it on.
    if (setting('seo_aggregate_rating') === '1') {
        $stats = db()->query(
            'SELECT COUNT(*) AS c, AVG(rating) AS avg FROM testimonials WHERE is_active = 1 AND rating > 0'
        )->fetch();
        if ($stats && (int) $stats['c'] > 0) {
            $node['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => round((float) $stats['avg'], 1),
                'reviewCount' => (int) $stats['c'],
                'bestRating'  => 5,
                'worstRating' => 1,
            ];
        }
    }

    return $node;
}

function schema_website(): array
{
    return [
        '@type'     => 'WebSite',
        '@id'       => site_url() . '#website',
        'url'       => site_url(),
        'name'      => setting('site_name'),
        'description' => setting('meta_description'),
        'inLanguage' => 'en-CA',
        'publisher' => ['@id' => site_url() . '#business'],
    ];
}

function schema_webpage(string $canonical, string $title, string $description, ?string $image, bool $hasBreadcrumb): array
{
    $node = [
        '@type'       => 'WebPage',
        '@id'         => $canonical . '#webpage',
        'url'         => $canonical,
        'name'        => $title,
        'description' => $description,
        'isPartOf'    => ['@id' => site_url() . '#website'],
        'about'       => ['@id' => site_url() . '#business'],
        'inLanguage'  => 'en-CA',
    ];
    if ($image) {
        $node['primaryImageOfPage'] = ['@type' => 'ImageObject', 'url' => $image];
    }
    if ($hasBreadcrumb) {
        $node['breadcrumb'] = ['@id' => $canonical . '#breadcrumb'];
    }
    return $node;
}

/** $items: [['label' => 'Services', 'url' => 'services'], ['label' => 'TV Mounting']] */
function schema_breadcrumbs(array $items, string $canonical): array
{
    $elements = [];
    $position = 1;
    foreach ($items as $item) {
        $element = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => $item['label'],
        ];
        if (!empty($item['url'])) {
            $element['item'] = site_url($item['url']);
        }
        $elements[] = $element;
    }
    return [
        '@type'           => 'BreadcrumbList',
        '@id'             => $canonical . '#breadcrumb',
        'itemListElement' => $elements,
    ];
}

/** $faqs: [['q' => ..., 'a' => ...], ...] — accepts question/answer keys too. */
function schema_faq(array $faqs, string $canonical): ?array
{
    $entities = [];
    foreach ($faqs as $faq) {
        $q = trim((string) ($faq['q'] ?? $faq['question'] ?? ''));
        $a = trim((string) ($faq['a'] ?? $faq['answer'] ?? ''));
        if ($q === '' || $a === '') {
            continue;
        }
        $entities[] = [
            '@type'          => 'Question',
            'name'           => $q,
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a],
        ];
    }
    if (!$entities) {
        return null;
    }
    return [
        '@type'      => 'FAQPage',
        '@id'        => $canonical . '#faq',
        'mainEntity' => $entities,
    ];
}

/** A Service node for a service or service+location landing page. */
function schema_service(string $name, string $description, string $canonical, ?array $location = null, array $jobs = []): array
{
    $node = [
        '@type'       => 'Service',
        '@id'         => $canonical . '#service',
        'name'        => $name,
        'description' => $description,
        'serviceType' => $name,
        'provider'    => ['@id' => site_url() . '#business'],
        'url'         => $canonical,
        'areaServed'  => $location
            ? [['@type' => 'City', 'name' => $location['name'] . ', ' . setting('seo_region', 'ON')]]
            : [['@type' => 'AdministrativeArea', 'name' => 'Greater Toronto Area']],
    ];
    if ($jobs) {
        $node['hasOfferCatalog'] = [
            '@type'           => 'OfferCatalog',
            'name'            => $name,
            'itemListElement' => array_map(static fn ($job) => [
                '@type'       => 'Offer',
                'itemOffered' => ['@type' => 'Service', 'name' => $job],
            ], array_slice($jobs, 0, 12)),
        ];
    }
    return $node;
}

/** An ItemList node — helps Google understand directory/hub pages. */
function schema_item_list(string $name, array $items, string $canonical): array
{
    $elements = [];
    $position = 1;
    foreach ($items as $item) {
        $elements[] = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => $item['name'],
            'url'      => site_url($item['url']),
        ];
    }
    return [
        '@type'           => 'ItemList',
        '@id'             => $canonical . '#list',
        'name'            => $name,
        'itemListElement' => $elements,
    ];
}

/** Render the whole graph as one script tag. */
function json_ld_block(array $nodes): string
{
    $nodes = array_values(array_filter($nodes));
    if (!$nodes) {
        return '';
    }
    $json = json_encode(
        ['@context' => 'https://schema.org', '@graph' => $nodes],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
    );
    return '<script type="application/ld+json">' . $json . '</script>';
}

/* ------------------------------------------------------- visible markup */

/** The breadcrumb trail used inside every page banner. */
function breadcrumb_html(array $items): string
{
    if (!$items) {
        return '';
    }
    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    $last = count($items) - 1;
    foreach ($items as $i => $item) {
        if ($i === $last || empty($item['url'])) {
            $html .= '<li class="breadcrumb-item active" aria-current="page">' . esc($item['label']) . '</li>';
        } else {
            $html .= '<li class="breadcrumb-item"><a href="' . esc(base_url($item['url'])) . '">'
                . esc($item['label']) . '</a></li>';
        }
    }
    return $html . '</ol></nav>';
}

/**
 * Pick a deterministic image for a landing page so neighbouring pages do not
 * all look identical. Same slug always gets the same photo.
 */
function seo_stock_image(string $slug, array $pool = []): string
{
    if (!$pool) {
        $pool = [
            'assets/img/work-install.jpg',
            'assets/img/work-kitchen-1.jpg',
            'assets/img/work-condo.jpg',
            'assets/img/work-bright-shop.jpg',
            'assets/img/work-kitchen-2.jpg',
            'assets/img/work-modern-kitchen.jpg',
            'assets/img/work-workshop-dark.jpg',
            'assets/img/work-kitchen-3.jpg',
        ];
    }
    return $pool[crc32($slug) % count($pool)];
}

/** 404 with the site's styling — used by every dynamic page. */
function seo_not_found(string $heading = 'Page Not Found', string $message = ''): void
{
    http_response_code(404);
    $GLOBALS['pageTitle'] = $heading;
    $GLOBALS['robots'] = 'noindex, follow';
    $GLOBALS['breadcrumbs'] = [['label' => 'Home', 'url' => ''], ['label' => $heading]];
    require APP_ROOT . '/includes/header.php';
    ?>
    <section class="section text-center">
        <div class="container py-5">
            <img src="<?= esc(base_url('assets/img/cartoon-workshop.jpg')) ?>" alt=""
                 style="max-width: 420px; border-radius: 1rem;" class="mb-4 shadow">
            <h1 class="section-title"><?= esc($heading) ?></h1>
            <p class="section-sub mx-auto">
                <?= esc($message !== '' ? $message : 'The page you are looking for does not exist or is no longer published.') ?>
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                <a class="btn btn-navy" href="<?= esc(base_url()) ?>"><i class="fa-solid fa-house me-2"></i>Back to Home</a>
                <a class="btn btn-outline-navy" href="<?= esc(base_url('services')) ?>">Browse Services</a>
                <a class="btn btn-gold" href="<?= esc(base_url('quote')) ?>">Get a Free Quote</a>
            </div>
        </div>
    </section>
    <?php
    require APP_ROOT . '/includes/footer.php';
    exit;
}

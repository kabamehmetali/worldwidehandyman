<?php
/**
 * Shared helpers — settings, navigation, escaping, CSRF, flash messages
 */

function esc(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function base_url(string $path = ''): string
{
    // Absolute and non-navigational URLs pass through untouched
    if (preg_match('~^(?:https?:)?//|^(?:mailto:|tel:|#)~i', $path)) {
        return $path;
    }

    return app_base_url() . public_route_path($path);
}

/**
 * Public base path.
 *
 * BASE_URL is now derived in config.php by comparing SCRIPT_NAME with
 * SCRIPT_FILENAME, which is accurate on every host and SAPI, so there is
 * nothing left to correct here.
 *
 * (An earlier fallback took dirname(SCRIPT_NAME) whenever BASE_URL was '/'.
 * That is wrong for any script not sitting in the site root — inside /admin
 * it returned '/admin/', so every admin link came out as /admin/admin/…)
 */
function app_base_url(): string
{
    return BASE_URL;
}

/**
 * Are Apache rewrite rules actually running?
 *
 * The .htaccess sets WWH_CLEAN_URLS=1 on every request it handles. If that
 * variable arrives, clean URLs are safe to emit; if it does not — because
 * .htaccess is ignored, mod_rewrite is missing, or the file was never
 * uploaded — links fall back to real PHP endpoints so the site still works.
 *
 * After an internal rewrite Apache re-exposes the variable prefixed with
 * REDIRECT_ (sometimes more than once), so any matching key counts.
 */
function clean_urls_enabled(): bool
{
    static $enabled = null;
    if ($enabled !== null) {
        return $enabled;
    }

    // An explicit constant in config.php always wins
    if (defined('USE_CLEAN_URLS')) {
        return $enabled = (bool) USE_CLEAN_URLS;
    }

    foreach ($_SERVER as $key => $value) {
        if ($value === '1' && substr($key, -15) === 'WWH_CLEAN_URLS') {
            return $enabled = true;
        }
    }
    return $enabled = (getenv('WWH_CLEAN_URLS') === '1');
}

/**
 * Resolve public routes to real PHP endpoints.
 *
 * The site supports clean URLs through .htaccess, but some production hosts
 * do not enable per-directory rewrite rules. Sending links to their real PHP
 * files keeps every page reachable in that environment instead of falling
 * through to the host's homepage route.
 */
function public_route_path(string $path): string
{
    $path = ltrim($path, '/');

    // Keep assets, admin URLs, explicit PHP files, and optional fragments or
    // query-only links exactly as supplied.
    if ($path === '' || $path[0] === '?' || $path[0] === '#') {
        return $path;
    }

    $suffixAt = strcspn($path, '?#');
    $route    = substr($path, 0, $suffixAt);
    $suffix   = substr($path, $suffixAt);

    if (preg_match('~\.(?:php|html?|css|js|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|map|txt|xml|pdf|mp4|webm)$~i', $route)
        || str_starts_with($route, 'assets/')
        || str_starts_with($route, 'admin/')) {
        return $path;
    }

    // Where rewrite rules are running, emit the clean keyword URL as-is.
    if (clean_urls_enabled()) {
        return $path;
    }

    if ($route === 'index') {
        return $suffix;
    }

    $pages = [
        'about', 'contact', 'faq', 'gallery', 'page', 'quote', 'service-areas', 'services',
    ];
    if (in_array($route, $pages, true)) {
        return $route . '.php' . $suffix;
    }

    if (preg_match('~^handyman/([a-z0-9-]+)$~i', $route, $matches)) {
        return 'location.php?slug=' . rawurlencode($matches[1]) . public_route_suffix($suffix);
    }

    if (preg_match('~^services/([a-z0-9-]+)/([a-z0-9-]+)$~i', $route, $matches)) {
        return 'service-location.php?service=' . rawurlencode($matches[1])
            . '&location=' . rawurlencode($matches[2])
            . public_route_suffix($suffix);
    }

    if (preg_match('~^services/([a-z0-9-]+)$~i', $route, $matches)) {
        return 'service.php?slug=' . rawurlencode($matches[1]) . public_route_suffix($suffix);
    }

    return $path;
}

/** Append a source route's query string to a mapped PHP query string. */
function public_route_suffix(string $suffix): string
{
    if ($suffix === '' || $suffix[0] === '#') {
        return $suffix;
    }

    $hashAt = strpos($suffix, '#');
    $query  = $hashAt === false ? $suffix : substr($suffix, 0, $hashAt);
    $hash   = $hashAt === false ? '' : substr($suffix, $hashAt);

    return '&' . ltrim($query, '?') . $hash;
}

/** All settings as [key => value], loaded once per request. */
function settings_all(): array
{
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        foreach (db()->query('SELECT setting_key, setting_value FROM settings') as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings;
}

function setting(string $key, string $default = ''): string
{
    $all = settings_all();
    return array_key_exists($key, $all) ? $all[$key] : $default;
}

function settings_save(array $pairs): void
{
    $stmt = db()->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    foreach ($pairs as $key => $value) {
        $stmt->execute([$key, $value]);
    }
}

/** Visible nav links, in order. */
function nav_links(): array
{
    static $links = null;
    if ($links === null) {
        $links = db()->query(
            'SELECT * FROM nav_links WHERE is_visible = 1 ORDER BY sort_order ASC, id ASC'
        )->fetchAll();
    }
    return $links;
}

/**
 * Reduce a URL path to the page it resolves to — no extension, 'index' for the
 * site root. URLs are extensionless now, but legacy '.php' values (an old nav
 * row, a bookmark) still normalise to the same key.
 */
function page_key(string $path): string
{
    // Any directory request ('/' or '/subdir/') is served by index
    if ($path === '' || substr($path, -1) === '/') {
        return 'index';
    }
    $name = preg_replace('/\.php$/i', '', basename($path));
    return $name === '' ? 'index' : $name;
}

/** Is this nav URL the page currently being viewed? */
function is_nav_active(string $url): bool
{
    $current = page_key(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
    $nav     = page_key(parse_url($url, PHP_URL_PATH) ?? '');
    if ($nav !== $current) {
        return false;
    }
    // Distinguish dynamic pages by their slug
    if ($nav === 'page') {
        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $navQuery);
        return ($navQuery['slug'] ?? null) === ($_GET['slug'] ?? null);
    }
    return true;
}

/* ------------------------------------------------------------- CSRF */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . esc(csrf_token()) . '">';
}

function csrf_check(?string $token): bool
{
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/* ------------------------------------------------------ flash messages */

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_get(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function flash_render(): string
{
    $html = '';
    foreach (flash_get() as $flash) {
        $type = $flash['type'] === 'error' ? 'danger' : $flash['type'];
        $html .= '<div class="alert alert-' . esc($type) . ' alert-dismissible fade show" role="alert">'
            . esc($flash['message'])
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    return $html;
}

/* ------------------------------------------------------------ misc */

function redirect(string $path): void
{
    header('Location: ' . base_url($path));
    exit;
}

/** Digits-and-plus version of a phone number, for tel: links. */
function phone_href(string $display): string
{
    $clean = preg_replace('/[^0-9+]/', '', $display);
    return 'tel:' . $clean;
}

/** tel: href for the site's phone — falls back to the display number when phone_link is blank. */
function phone_link_href(): string
{
    $raw = setting('phone_link');
    return phone_href($raw !== '' ? $raw : setting('phone'));
}

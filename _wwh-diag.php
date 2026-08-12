<?php
/**
 * Production routing diagnostic — TEMPORARY. Delete this file once the
 * routing problem is solved.
 *
 * Upload to the site root and open:  https://yourdomain.com/_wwh-diag.php?key=wwh-diag
 *
 * It answers, in one screen, why a page request might be returning the
 * homepage instead of the page that was asked for.
 */

header('X-Robots-Tag: noindex, nofollow');

if (($_GET['key'] ?? '') !== 'wwh-diag') {
    http_response_code(404);
    exit('Not found.');
}

$appRoot = str_replace('\\', '/', __DIR__);

/* ------------------------------------------------ is .htaccess being read? */
// The .htaccess sets WWH_HTACCESS=1 via mod_env. If it is missing, either
// .htaccess is ignored (AllowOverride None) or mod_env is unavailable.
$htaccessEnv = $_SERVER['WWH_HTACCESS'] ?? getenv('WWH_HTACCESS') ?: '';
$rewriteEnv  = $_SERVER['WWH_REWRITE'] ?? getenv('WWH_REWRITE') ?: '';

/* ------------------------------------------------------ apache modules */
$modules = function_exists('apache_get_modules') ? apache_get_modules() : null;
$hasRewrite = $modules === null ? null : in_array('mod_rewrite', $modules, true);

/* ------------------------------------------- .htaccess files up the tree */
$htaccessFiles = [];
$dir = $appRoot;
for ($i = 0; $i < 6; $i++) {
    $candidate = $dir . '/.htaccess';
    if (is_file($candidate)) {
        $body = (string) @file_get_contents($candidate);
        $htaccessFiles[] = [
            'path'    => $candidate,
            'size'    => strlen($body),
            'catchall'=> (bool) preg_match('~RewriteRule\s+\^?[^\s]*\s+(?:/)?index\.php~i', $body),
            'readable'=> $body !== '',
        ];
    }
    $parent = dirname($dir);
    if ($parent === $dir) {
        break;
    }
    $dir = $parent;
}

/* --------------------------------------------------- which files exist? */
$pageFiles = ['index.php', 'about.php', 'services.php', 'service.php', 'location.php',
    'service-location.php', 'service-areas.php', 'faq.php', 'contact.php', 'quote.php',
    'gallery.php', 'page.php', '404.php', 'sitemap.php', 'robots.php',
    'includes/config.php', 'includes/functions.php', 'includes/seo.php', '.htaccess'];

/* ------------------------------------------ load the app's own URL logic */
$baseUrlConst = null;
$appBaseUrl   = null;
$sampleLinks  = [];
$loadError    = '';
try {
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/includes/functions.php';
    $baseUrlConst = BASE_URL;
    $appBaseUrl   = function_exists('app_base_url') ? app_base_url() : '(function missing)';
    foreach (['', 'about', 'services', 'service-areas', 'faq', 'contact', 'quote',
              'handyman/toronto', 'services/tv-mounting', 'services/tv-mounting/toronto',
              'page?slug=example'] as $route) {
        $sampleLinks[$route] = base_url($route);
    }
} catch (Throwable $e) {
    $loadError = $e->getMessage();
}

function row(string $label, $value, string $note = ''): void
{
    $v = $value === null ? '<em>unavailable</em>'
        : ($value === '' ? '<em>(empty)</em>' : htmlspecialchars((string) $value, ENT_QUOTES));
    echo '<tr><th>' . htmlspecialchars($label, ENT_QUOTES) . '</th><td><code>' . $v . '</code>'
        . ($note ? ' <span class="note">' . $note . '</span>' : '') . '</td></tr>';
}

function verdict(bool $ok, string $good, string $bad): string
{
    return $ok ? '<span class="ok">' . $good . '</span>' : '<span class="bad">' . $bad . '</span>';
}
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>Worldwide Handyman — routing diagnostic</title>
<meta name="robots" content="noindex, nofollow">
<style>
 body{font:14px/1.6 -apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;margin:0;padding:2rem;background:#f5f7fb;color:#37415c}
 .wrap{max-width:1000px;margin:0 auto}
 h1{font-size:1.4rem;color:#10203f;margin:0 0 .3rem}
 h2{font-size:1.05rem;color:#10203f;margin:2rem 0 .6rem;padding-bottom:.4rem;border-bottom:2px solid #e3e7f0}
 table{width:100%;border-collapse:collapse;background:#fff;border-radius:.5rem;overflow:hidden;box-shadow:0 2px 8px rgba(16,32,63,.07)}
 th,td{padding:.55rem .8rem;text-align:left;vertical-align:top;border-bottom:1px solid #eef1f7;font-weight:400}
 th{width:230px;color:#10203f;font-weight:600;background:#fafbfe}
 code{background:#f2f4f9;padding:.1rem .35rem;border-radius:.25rem;font-size:.86em;word-break:break-all}
 .ok{color:#1a7f4b;font-weight:600}.bad{color:#c0392b;font-weight:600}.warn{color:#b8860b;font-weight:600}
 .note{font-size:.85em;color:#7c86a2}
 .banner{padding:1rem 1.2rem;border-radius:.5rem;margin-bottom:1.5rem;background:#fff8e1;border-left:4px solid #f5a800}
 .test a{display:inline-block;margin:.2rem .5rem .2rem 0;padding:.35rem .7rem;background:#10203f;color:#fff;border-radius:.3rem;text-decoration:none;font-size:.85em}
 .test a.alt{background:#7c86a2}
</style></head><body><div class="wrap">

<h1>Worldwide Handyman — routing diagnostic</h1>
<p class="note">Delete <code>_wwh-diag.php</code> from the server once you are done.</p>

<div class="banner">
 <strong>Is .htaccess being applied?</strong><br>
 <?= verdict($htaccessEnv === '1',
      'YES — Apache is reading .htaccess (mod_env probe returned 1).',
      'NO / UNKNOWN — the probe variable did not come through. Either AllowOverride is off for this directory, mod_env is unavailable, or the .htaccess on the server does not contain the probe block.') ?>
 <br><br>
 <strong>Are rewrite rules running?</strong><br>
 <?= verdict($rewriteEnv === '1',
      'YES — mod_rewrite executed the probe rule.',
      'NO / UNKNOWN — the rewrite probe did not fire. Clean URLs will not work until this says YES.') ?>
</div>

<h2>Request</h2>
<table>
<?php
row('SERVER_SOFTWARE', $_SERVER['SERVER_SOFTWARE'] ?? null);
row('PHP version / SAPI', PHP_VERSION . ' / ' . PHP_SAPI);
row('HTTP_HOST', $_SERVER['HTTP_HOST'] ?? null);
row('REQUEST_URI', $_SERVER['REQUEST_URI'] ?? null);
row('SCRIPT_NAME', $_SERVER['SCRIPT_NAME'] ?? null, 'drives the BASE_URL fallback');
row('SCRIPT_FILENAME', $_SERVER['SCRIPT_FILENAME'] ?? null);
row('PHP_SELF', $_SERVER['PHP_SELF'] ?? null);
row('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT'] ?? null);
row('realpath(DOCUMENT_ROOT)', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '(failed)',
    'must be a prefix of the app path below, or BASE_URL falls back to SCRIPT_NAME');
row('App directory (__DIR__)', $appRoot);
row('Doc root is a prefix?', (isset($_SERVER['DOCUMENT_ROOT'])
    && strpos($appRoot, rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: ''), '/')) === 0) ? 'yes' : 'NO');
?>
</table>

<h2>Computed URLs</h2>
<table>
<?php
if ($loadError !== '') {
    row('Load error', $loadError, 'includes/config.php or functions.php failed');
} else {
    row('BASE_URL constant', $baseUrlConst);
    row('app_base_url()', $appBaseUrl);
    foreach ($sampleLinks as $route => $built) {
        row("base_url('" . $route . "')", $built);
    }
}
?>
</table>
<p class="note">If these come out as <code>about.php</code> / <code>location.php?slug=…</code> then the
<code>public_route_path()</code> fallback is active and clean URLs are switched off site-wide.</p>

<h2>Apache modules</h2>
<table>
<?php
if ($modules === null) {
    row('apache_get_modules()', null, 'not callable — normal under PHP-FPM, CGI or LiteSpeed');
} else {
    row('mod_rewrite', $hasRewrite ? 'loaded' : 'NOT LOADED');
    row('mod_env', in_array('mod_env', $modules, true) ? 'loaded' : 'not loaded');
    row('mod_headers', in_array('mod_headers', $modules, true) ? 'loaded' : 'not loaded');
    row('all modules', implode(', ', $modules));
}
?>
</table>

<h2>.htaccess files found (this directory upwards)</h2>
<table>
<?php
if (!$htaccessFiles) {
    row('none found', null, 'no .htaccess in the app directory — clean URLs cannot work');
}
foreach ($htaccessFiles as $f) {
    row($f['path'], $f['size'] . ' bytes'
        . ($f['catchall'] ? '  <-- CONTAINS A CATCH-ALL REWRITE TO index.php' : ''),
        $f['catchall'] ? '<span class="bad">this is very likely the cause</span>' : '');
}
?>
</table>
<p class="note">A parent <code>.htaccess</code> (for example in <code>public_html</code>) that rewrites
everything to <code>index.php</code> will make every URL render the homepage, no matter what this site does.</p>

<h2>Files present</h2>
<table>
<?php foreach ($pageFiles as $f): ?>
    <?php row($f, is_file(__DIR__ . '/' . $f) ? 'present (' . filesize(__DIR__ . '/' . $f) . ' bytes)' : 'MISSING'); ?>
<?php endforeach; ?>
</table>

<h2>Click each of these and note what you get</h2>
<?php $b = rtrim($appBaseUrl ?? '/', '/'); ?>
<p class="test">
    <strong>Real PHP files:</strong><br>
    <a href="<?= $b ?>/about.php">about.php</a>
    <a href="<?= $b ?>/services.php">services.php</a>
    <a href="<?= $b ?>/location.php?slug=toronto">location.php?slug=toronto</a>
</p>
<p class="test">
    <strong>Clean URLs (need mod_rewrite):</strong><br>
    <a class="alt" href="<?= $b ?>/about">/about</a>
    <a class="alt" href="<?= $b ?>/services">/services</a>
    <a class="alt" href="<?= $b ?>/handyman/toronto">/handyman/toronto</a>
    <a class="alt" href="<?= $b ?>/services/tv-mounting/toronto">/services/tv-mounting/toronto</a>
</p>
<p class="note">
    If the <em>real PHP files</em> also show the homepage, the problem is upstream of this app —
    a parent .htaccess catch-all, a cPanel custom 404 pointing at the homepage, or the domain
    pointing at the wrong document root. If the PHP files work and only the clean URLs fail,
    it is purely a mod_rewrite / AllowOverride issue.
</p>

</div></body></html>

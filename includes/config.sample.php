<?php
/**
 * Global configuration — Worldwide Handyman  (SAMPLE)
 *
 * Copy this file to includes/config.php and fill in the real values.
 * config.php is git-ignored because it holds database credentials.
 */

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'worldwidehandyman_db');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Clean URLs — normally leave this alone.
 *
 * By default the site detects whether Apache rewrite rules are running (the
 * .htaccess sets an environment variable that clean_urls_enabled() reads) and
 * picks the right style automatically:
 *
 *   rewrite works    -> /about, /handyman/toronto, /services/tv-mounting/toronto
 *   rewrite missing  -> /about.php, /location.php?slug=toronto
 *
 * Either way every page is reachable and canonical tags, the sitemap and all
 * internal links stay consistent with each other.
 *
 * Uncomment to force one style, e.g. if a host runs rewrite rules from the
 * main server config rather than .htaccess and the probe never fires:
 */
// define('USE_CLEAN_URLS', true);

// Absolute filesystem path to the site root (this file lives in /includes)
define('APP_ROOT', dirname(__DIR__));

/**
 * Public base path of the site.
 *
 * Derived by comparing the running script's URL path (SCRIPT_NAME) with its
 * filesystem path (SCRIPT_FILENAME). Both describe the same file, so whatever
 * is left over once the in-app part is removed is exactly the URL prefix the
 * site is served under.
 *
 * This is what makes the site portable: it is correct at a domain root, in a
 * subdirectory, on an addon domain, and behind a symlinked or aliased document
 * root — including cPanel, where DOCUMENT_ROOT frequently does not match the
 * real path (/home vs /home2 symlinks) and so cannot be trusted on its own.
 */
$appRootReal = str_replace('\\', '/', realpath(APP_ROOT) ?: APP_ROOT);
$scriptName  = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$scriptFile  = str_replace('\\', '/', realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) ?: '');

$basePath = null;
if ($scriptName !== '' && $scriptFile !== '' && strpos($scriptFile, $appRootReal) === 0) {
    // The part of the script path that sits inside the app, e.g. "admin/dashboard.php"
    $relative = ltrim(substr($scriptFile, strlen($appRootReal)), '/');
    if ($relative !== '' && substr($scriptName, -strlen($relative)) === $relative) {
        $basePath = rtrim(substr($scriptName, 0, -strlen($relative)), '/');
    }
}

if ($basePath === null) {
    // Fallback: locate the app directory underneath the document root
    $docRoot = rtrim(str_replace('\\', '/', realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? '')) ?: ''), '/');
    $basePath = ($docRoot !== '' && strpos($appRootReal, $docRoot) === 0)
        ? rtrim(substr($appRootReal, strlen($docRoot)), '/')
        : '';
}

define('BASE_URL', $basePath . '/');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Toronto');

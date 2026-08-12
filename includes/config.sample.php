<?php
/**
 * Global configuration — Worldwide Handyman  (SAMPLE)
 *
 * Copy this file to includes/config.php and fill in the real values.
 * config.php is git-ignored because it holds database credentials.
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'worldwidehandyman_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Absolute filesystem path to the site root (this file lives in /includes)
define('APP_ROOT', dirname(__DIR__));

// URL base path of the site, works at domain root, in a subdirectory, and
// when production hosting maps the site through a symlink or alias.
$docRoot = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? APP_ROOT) ?: ''), '/');
$appRoot = str_replace('\\', '/', APP_ROOT);
$basePath = ($docRoot !== '' && strpos($appRoot, $docRoot) === 0)
    ? substr($appRoot, strlen($docRoot))
    : '';

// A symlinked production app may sit outside DOCUMENT_ROOT even though it is
// served below a subdirectory. SCRIPT_NAME retains that public subdirectory.
if ($basePath === '') {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (str_starts_with($scriptName, '/')) {
        $scriptDir = rtrim(dirname($scriptName), '/');
        if ($scriptDir !== '' && $scriptDir !== '.') {
            $basePath = $scriptDir;
        }
    }
}
define('BASE_URL', rtrim($basePath, '/') . '/');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Toronto');

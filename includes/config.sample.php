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

// URL base path of the site, works at domain root or in a subdirectory
$docRoot = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? APP_ROOT) ?: ''), '/');
$appRoot = str_replace('\\', '/', APP_ROOT);
$basePath = ($docRoot !== '' && strpos($appRoot, $docRoot) === 0)
    ? substr($appRoot, strlen($docRoot))
    : '';
define('BASE_URL', rtrim($basePath, '/') . '/');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Toronto');

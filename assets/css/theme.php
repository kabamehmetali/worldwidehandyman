<?php
/**
 * Theme stylesheet — the brand colours chosen in Admin → Settings → Colors.
 *
 * Linked by includes/header.php (through theme_css_url()) only once a colour
 * differs from the defaults in styles.css. It is a real stylesheet rather than
 * a <style> block because the site's Content-Security-Policy discards inline
 * styles.
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: text/css; charset=utf-8');
// The URL carries a hash of the colours, so this response never goes stale.
// config.php starts a session, which would otherwise mark it uncacheable.
header('Cache-Control: public, max-age=31536000, immutable');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
header_remove('Pragma');
header_remove('Set-Cookie');

echo ":root {\n";
foreach (theme_colors() as $property => $value) {
    echo "    {$property}: {$value};\n";
}
echo "}\n";

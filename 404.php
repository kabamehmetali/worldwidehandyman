<?php
/**
 * Catch-all 404 — every unmatched URL is rewritten here by .htaccess so the
 * visitor gets the site's own styling and a real 404 status code.
 */

require_once __DIR__ . '/includes/seo.php';

seo_not_found();

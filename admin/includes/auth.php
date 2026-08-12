<?php
/**
 * Admin bootstrap — config, DB, helpers, auth guard.
 * Include this at the top of every admin page.
 */

require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/includes/seo.php';
require_once __DIR__ . '/helpers.php';

function admin_user(): ?array
{
    static $user = null;
    if ($user === null && !empty($_SESSION['admin_id'])) {
        $stmt = db()->prepare('SELECT id, username FROM users WHERE id = ?');
        $stmt->execute([(int) $_SESSION['admin_id']]);
        $user = $stmt->fetch() ?: null;
        if ($user === null) {
            unset($_SESSION['admin_id']);
        }
    }
    return $user ?: null;
}

function require_admin(): void
{
    if (!admin_user()) {
        header('Location: ' . base_url('admin/login.php'));
        exit;
    }
}

/** Abort on invalid CSRF for admin POSTs. */
function admin_csrf_or_die(): void
{
    // When the request body exceeds post_max_size, PHP silently discards
    // $_POST and $_FILES — explain that instead of a bogus CSRF failure.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES)
        && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        flash_set('error', 'Upload too large — everything combined exceeded the server limit of '
            . ini_get('post_max_size') . '. Try fewer or smaller files.');
        header('Location: ' . ($_SERVER['REQUEST_URI'] ?? base_url('admin/dashboard.php')));
        exit;
    }
    if (!csrf_check($_POST['_csrf'] ?? null)) {
        http_response_code(400);
        exit('Invalid CSRF token. Go back, reload the page, and try again.');
    }
}

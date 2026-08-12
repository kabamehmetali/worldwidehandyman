<?php
require_once __DIR__ . '/includes/auth.php';

header('Location: ' . base_url(admin_user() ? 'admin/dashboard.php' : 'admin/login.php'));
exit;

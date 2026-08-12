<?php
require_once __DIR__ . '/includes/auth.php';

if (admin_user()) {
    header('Location: ' . base_url('admin/dashboard.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? null)) {
        $error = 'Session expired — please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = db()->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $user['id'];
            header('Location: ' . base_url('admin/dashboard.php'));
            exit;
        }

        sleep(1); // slow down brute-force attempts
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | <?= esc(setting('site_name')) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="<?= esc(base_url(setting('logo_icon'))) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= esc(base_url('admin/assets/admin.css')) ?>?v=<?= @filemtime(APP_ROOT . '/admin/assets/admin.css') ?>" rel="stylesheet">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <img class="logo" src="<?= esc(base_url(setting('logo_icon'))) ?>" alt="<?= esc(setting('site_name')) ?>">
        <h1>Welcome Back</h1>
        <p class="sub">Sign in to manage <?= esc(setting('site_name')) ?></p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger py-2"><?= esc($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= esc(base_url('admin/login.php')) ?>">
            <?= csrf_field() ?>
            <div class="mb-3 text-start">
                <label class="form-label" for="username">Username</label>
                <input class="form-control" type="text" id="username" name="username" required autofocus autocomplete="username">
            </div>
            <div class="mb-4 text-start">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button class="btn btn-gold w-100 py-2" type="submit"><i class="fa-solid fa-right-to-bracket me-2"></i>Sign In</button>
        </form>
        <div class="login-tagline"><?= esc(strip_tags(setting('tagline'))) ?></div>
    </div>
</div>
</body>
</html>

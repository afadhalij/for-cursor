<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    $next = $_GET['next'] ?? (APP_BASE . '/');
    header('Location: ' . $next);
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'Session expired. Please try again.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if (login_attempt($username, $password)) {
            $next = $_POST['next'] ?? (APP_BASE . '/');
            header('Location: ' . $next);
            exit;
        }
        $error = 'Wrong username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="robots" content="noindex,nofollow">
<title>Sign in · <?= h(APP_NAME) ?> · Management</title>
<link rel="icon" type="image/webp" href="<?= h(APP_BASE) ?>/../assets/logo.webp">
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="<?= h(APP_BASE) ?>/assets/css/app.css?v=2" rel="stylesheet">
<style>
    body { display: flex; align-items: center; justify-content: center; padding: 24px; }
    .login-card { max-width: 380px; width: 100%; padding: 2.25rem; }
    .login-logo {
        width: 72px; height: 72px; border-radius: 18px; object-fit: cover;
        box-shadow: 0 0 0 1px rgba(245,230,200,.18), 0 0 24px rgba(233,185,98,.2); margin: 0 auto;
    }
</style>
</head>
<body>
<div class="login-card glass-card">
    <img src="<?= h(APP_BASE) ?>/../assets/logo.webp" alt="Inganzo Ngari" class="login-logo d-block">
    <h1 class="text-center mt-3 mb-1" style="font-family:'Cinzel',serif; font-weight:700; font-size:20px; letter-spacing:.18em;"><?= h(APP_NAME) ?></h1>
    <p class="text-center text-muted mb-4" style="font-size:11px; letter-spacing:.32em; text-transform:uppercase;">Management Sign-in</p>

    <?php if ($error): ?>
        <div class="alert" style="background:rgba(192,57,43,.12); border:1px solid rgba(192,57,43,.4); color:#ffb4ad; font-size:13px; padding:.6rem .8rem; border-radius:.65rem;">
            <i class="fa-solid fa-circle-exclamation me-1"></i> <?= h($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="next" value="<?= h($_GET['next'] ?? (APP_BASE . '/')) ?>">

        <div class="mb-3">
            <label class="form-label required" for="username">Username</label>
            <input type="text" class="form-control" id="username" name="username" required autofocus>
        </div>

        <div class="mb-3">
            <label class="form-label required" for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>

        <button class="btn btn-gold w-100 mt-2" type="submit">
            <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Sign in
        </button>
    </form>

    <p class="text-center mt-3 mb-0" style="font-size:11px; color:var(--c-muted); letter-spacing:.08em;">
        Authorised personnel only.
    </p>
</div>
</body>
</html>

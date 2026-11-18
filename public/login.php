<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use App\Auth\Auth;
use App\Middleware\CSRF;

$auth = new Auth();

if ($auth->isLoggedIn()) {
    redirect('dashboard.php');
}

$error      = '';
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verify();

    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';
    $remember   = isset($_POST['remember']);
    $ip         = clientIp();

    $result = $auth->login($identifier, $password, $ip, $remember);

    if (isset($result['ok'])) {
        redirect('dashboard.php');
    } else {
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="card">
    <div class="card__header">
        <div class="logo">🔐</div>
        <h1>Welcome back</h1>
        <p>Sign in to your account</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" novalidate>
        <?= CSRF::field() ?>

        <div class="form-group">
            <label for="identifier">Username or Email</label>
            <input type="text" id="identifier" name="identifier"
                   value="<?= e($identifier) ?>"
                   autocomplete="username" required
                   placeholder="Username or email">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="input-wrap">
                <input type="password" id="password" name="password"
                       autocomplete="current-password" required
                       placeholder="Your password">
                <button type="button" class="toggle-pw" aria-label="Toggle password visibility">👁</button>
            </div>
        </div>

        <div class="form-row">
            <label class="checkbox-label">
                <input type="checkbox" name="remember" value="1">
                <span>Remember me for 30 days</span>
            </label>
            <a href="forgot.php" class="link--muted">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn--primary btn--full">Sign In</button>
    </form>

    <p class="card__footer">No account? <a href="register.php">Create one</a></p>
</div>

<script src="../assets/js/app.js"></script>
</body>
</html>

<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use App\Auth\Auth;
use App\Middleware\CSRF;

$auth = new Auth();

if ($auth->isLoggedIn()) {
    redirect('dashboard.php');
}

$error   = '';
$success = '';
$fields  = ['username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verify();

    $username  = trim($_POST['username']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  = $_POST['password']       ?? '';
    $password2 = $_POST['password_confirm'] ?? '';

    $fields['username'] = $username;
    $fields['email']    = $email;

    if ($password !== $password2) {
        $error = 'Passwords do not match.';
    } else {
        $result = $auth->register($username, $email, $password);
        if (isset($result['ok'])) {
            $success = 'Account created! You can now <a href="login.php">log in</a>.';
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="card">
    <div class="card__header">
        <div class="logo">🔐</div>
        <h1>Create Account</h1>
        <p>Join <?= APP_NAME ?> today</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert--success"><?= $success ?></div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" action="register.php" novalidate id="registerForm">
        <?= CSRF::field() ?>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username"
                   value="<?= e($fields['username']) ?>"
                   autocomplete="username" required
                   pattern="[a-zA-Z0-9_]{3,50}"
                   placeholder="e.g. john_doe">
            <span class="hint">3–50 characters, letters/numbers/underscores only.</span>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   value="<?= e($fields['email']) ?>"
                   autocomplete="email" required
                   placeholder="you@example.com">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="input-wrap">
                <input type="password" id="password" name="password"
                       autocomplete="new-password" required
                       placeholder="Create a strong password">
                <button type="button" class="toggle-pw" aria-label="Toggle password visibility">👁</button>
            </div>
            <div class="strength-bar"><div id="strengthFill"></div></div>
            <span class="hint" id="strengthLabel"></span>
        </div>

        <div class="form-group">
            <label for="password_confirm">Confirm Password</label>
            <div class="input-wrap">
                <input type="password" id="password_confirm" name="password_confirm"
                       autocomplete="new-password" required
                       placeholder="Repeat your password">
                <button type="button" class="toggle-pw" aria-label="Toggle password visibility">👁</button>
            </div>
        </div>

        <button type="submit" class="btn btn--primary btn--full">Create Account</button>
    </form>
    <?php endif; ?>

    <p class="card__footer">Already have an account? <a href="login.php">Log in</a></p>
</div>

<script src="../assets/js/app.js"></script>
</body>
</html>

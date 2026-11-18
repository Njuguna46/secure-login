<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use App\DB\Database;
use App\Middleware\CSRF;

$message = '';
$isError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verify();

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Enter a valid email address.';
        $isError = true;
    } else {
        $db   = Database::connect();
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Always show the same message to prevent email enumeration
        $message = 'If that email is registered, a reset link has been sent.';

        if ($user) {
            $token     = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expires   = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // In production: store token in a password_resets table and email it.
            // This stub logs it for demonstration purposes only.
            error_log("Password reset token for user {$user['id']}: $token (expires $expires)");

            /*
             * TODO: Send email with link:
             * BASE_URL . '/reset.php?token=' . $token
             * Store hashed token in `password_resets` table.
             */
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="card">
    <div class="card__header">
        <div class="logo">🔑</div>
        <h1>Forgot Password</h1>
        <p>Enter your email and we'll send a reset link.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert--<?= $isError ? 'error' : 'success' ?>"><?= e($message) ?></div>
    <?php endif; ?>

    <form method="POST" action="forgot.php" novalidate>
        <?= CSRF::field() ?>

        <div class="form-group">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email"
                   autocomplete="email" required
                   placeholder="you@example.com">
        </div>

        <button type="submit" class="btn btn--primary btn--full">Send Reset Link</button>
    </form>

    <p class="card__footer"><a href="login.php">← Back to login</a></p>
</div>
</body>
</html>

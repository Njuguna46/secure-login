<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

use App\Auth\Auth;

$auth = new Auth();
$user = $auth->user();

if (!$user) {
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-page">
<nav class="navbar">
    <div class="navbar__brand">🔐 <?= APP_NAME ?></div>
    <div class="navbar__user">
        <span>👤 <?= e($user['username']) ?></span>
        <a href="logout.php" class="btn btn--outline btn--sm">Log out</a>
    </div>
</nav>

<main class="dashboard">
    <div class="dashboard__hero">
        <h1>Welcome, <?= e($user['username']) ?>!</h1>
        <p>You are securely logged in. Your session is protected and monitored.</p>
    </div>

    <div class="cards-grid">
        <div class="info-card">
            <div class="info-card__icon">🛡️</div>
            <h3>Session Security</h3>
            <ul>
                <li>✅ Bcrypt password hash (cost <?= BCRYPT_COST ?>)</li>
                <li>✅ CSRF token validated</li>
                <li>✅ Session ID regenerated on login</li>
                <li>✅ Idle timeout: <?= SESSION_LIFETIME / 60 ?> minutes</li>
                <li>✅ Secure, HttpOnly cookies</li>
            </ul>
        </div>

        <div class="info-card">
            <div class="info-card__icon">🚫</div>
            <h3>Brute-force Guard</h3>
            <ul>
                <li>✅ Max <?= MAX_LOGIN_ATTEMPTS ?> attempts per window</li>
                <li>✅ Lockout: <?= LOCKOUT_DURATION / 60 ?> minutes</li>
                <li>✅ Tracks by username + IP</li>
                <li>✅ Constant-time password comparison</li>
                <li>✅ Attempts cleared on success</li>
            </ul>
        </div>

        <div class="info-card">
            <div class="info-card__icon">🍪</div>
            <h3>Remember-me</h3>
            <ul>
                <li>✅ Cryptographically random token</li>
                <li>✅ Stored as SHA-256 hash in DB</li>
                <li>✅ Rotated on every use</li>
                <li>✅ Secure + HttpOnly + SameSite</li>
                <li>✅ Expires in 30 days</li>
            </ul>
        </div>

        <div class="info-card">
            <div class="info-card__icon">🔒</div>
            <h3>HTTP Headers</h3>
            <ul>
                <li>✅ X-Frame-Options: DENY</li>
                <li>✅ X-Content-Type-Options: nosniff</li>
                <li>✅ Referrer-Policy set</li>
                <li>✅ Content-Security-Policy</li>
                <li>✅ XSS-Protection header</li>
            </ul>
        </div>
    </div>
</main>
</body>
</html>

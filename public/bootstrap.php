<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

// ─── Autoloader (PSR-4 style, no Composer needed) ────────────────────────────
spl_autoload_register(function (string $class): void {
    $base = __DIR__ . '/../src/';
    $rel  = str_replace('App\\', '', $class);
    $file = $base . str_replace('\\', '/', $rel) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// ─── Security headers ────────────────────────────────────────────────────────
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; script-src 'self' 'unsafe-inline'");

// ─── Session ─────────────────────────────────────────────────────────────────
use App\Middleware\Session;
Session::start();

// ─── Helper: get client IP ───────────────────────────────────────────────────
function clientIp(): string
{
    // Trust X-Forwarded-For only if you're behind a known proxy; keep it simple here.
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// ─── Helper: safe redirect ───────────────────────────────────────────────────
function redirect(string $path): never
{
    $url = BASE_URL . '/' . ltrim($path, '/');
    header('Location: ' . $url);
    exit;
}

// ─── Helper: HTML-escape ─────────────────────────────────────────────────────
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

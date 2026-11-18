<?php
declare(strict_types=1);

// ─── Environment ────────────────────────────────────────────────────────────
define('APP_ENV',  getenv('APP_ENV')  ?: 'development'); // 'production' on live
define('APP_NAME', 'SecureLogin');
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost/secure-login/public');

// ─── Database ────────────────────────────────────────────────────────────────
define('DB_HOST',    getenv('DB_HOST')    ?: '127.0.0.1');
define('DB_PORT',    getenv('DB_PORT')    ?: '3306');
define('DB_NAME',    getenv('DB_NAME')    ?: 'secure_login');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_CHARSET', 'utf8mb4');

// ─── Session ─────────────────────────────────────────────────────────────────
define('SESSION_LIFETIME',    1800);   // 30 minutes idle timeout
define('SESSION_REGENERATE',   300);   // regenerate ID every 5 minutes

// ─── Brute-force / Rate limiting ─────────────────────────────────────────────
define('MAX_LOGIN_ATTEMPTS',     5);
define('LOCKOUT_DURATION',     900);   // 15 minutes in seconds

// ─── Password policy ─────────────────────────────────────────────────────────
define('PASSWORD_MIN_LENGTH',    8);
define('BCRYPT_COST',           12);

// ─── Error display ───────────────────────────────────────────────────────────
if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    error_reporting(0);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// ─── Timezone ────────────────────────────────────────────────────────────────
date_default_timezone_set('UTC');

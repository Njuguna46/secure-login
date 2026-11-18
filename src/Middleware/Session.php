<?php
declare(strict_types=1);

namespace App\Middleware;

/**
 * Configure and start the session with hardened settings.
 * Call once at the top of every entry point.
 */
class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Harden cookie
        session_set_cookie_params([
            'lifetime' => 0,              // browser session cookie
            'path'     => '/',
            'domain'   => '',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        ini_set('session.use_strict_mode',    '1'); // reject unrecognised IDs
        ini_set('session.use_only_cookies',   '1'); // no session ID in URL
        ini_set('session.use_trans_sid',      '0');
        ini_set('session.gc_maxlifetime',     (string)SESSION_LIFETIME);
        ini_set('session.entropy_length',     '32');

        session_name('SLSESSID');
        session_start();
    }
}

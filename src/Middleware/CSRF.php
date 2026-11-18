<?php
declare(strict_types=1);

namespace App\Middleware;

class CSRF
{
    private const TOKEN_KEY = '_csrf_token';

    /** Generate (or return existing) token for the current session. */
    public static function token(): string
    {
        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_KEY];
    }

    /** Render a hidden input field. */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Verify submitted token. Terminates with 403 on failure.
     */
    public static function verify(): void
    {
        $submitted = $_POST['_csrf_token'] ?? '';
        $expected  = $_SESSION[self::TOKEN_KEY] ?? '';

        if (!hash_equals($expected, $submitted)) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }

        // Rotate after each use
        unset($_SESSION[self::TOKEN_KEY]);
    }
}

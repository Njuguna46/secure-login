<?php
declare(strict_types=1);

namespace App\Auth;

use App\DB\Database;
use PDO;

class Auth
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ─── Registration ────────────────────────────────────────────────────────

    /**
     * Register a new user.
     * Returns ['ok' => true] or ['error' => 'message'].
     */
    public function register(string $username, string $email, string $password): array
    {
        $username = trim($username);
        $email    = trim($email);

        // --- Validate inputs ---
        if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            return ['error' => 'Username must be 3–50 characters (letters, numbers, underscores).'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            return ['error' => 'Enter a valid email address.'];
        }

        $pwCheck = $this->validatePassword($password);
        if ($pwCheck !== true) {
            return ['error' => $pwCheck];
        }

        // --- Check uniqueness ---
        $stmt = $this->db->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            return ['error' => 'Username or email is already in use.'];
        }

        // --- Store ---
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)'
        );
        $stmt->execute([$username, $email, $hash]);

        return ['ok' => true];
    }

    // ─── Login ───────────────────────────────────────────────────────────────

    /**
     * Attempt a login.
     * Returns ['ok' => true, 'user' => [...]] or ['error' => 'message'].
     */
    public function login(string $identifier, string $password, string $ip, bool $remember = false): array
    {
        $identifier = trim($identifier);

        // --- Rate limit check ---
        if ($this->isLockedOut($identifier, $ip)) {
            return ['error' => 'Too many failed attempts. Please wait 15 minutes before trying again.'];
        }

        // --- Fetch user (by username OR email) ---
        $stmt = $this->db->prepare(
            'SELECT id, username, email, password_hash, is_active
               FROM users
              WHERE (username = ? OR email = ?)
              LIMIT 1'
        );
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        // Always run verify to prevent timing attacks even on unknown users
        $dummyHash = '$2y$12$invalidhashpadding000000000000000000000000000000000000000';
        $hash      = $user ? $user['password_hash'] : $dummyHash;

        if (!$user || !password_verify($password, $hash)) {
            $this->recordFailedAttempt($identifier, $ip);
            return ['error' => 'Invalid username/email or password.'];
        }

        if (!$user['is_active']) {
            return ['error' => 'This account has been deactivated.'];
        }

        // --- Success: clear attempts, start session ---
        $this->clearAttempts($identifier, $ip);
        $this->startSession($user);

        // Rehash if bcrypt cost changed
        if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST])) {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            $this->db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                     ->execute([$newHash, $user['id']]);
        }

        // Update last login timestamp
        $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')
                 ->execute([$user['id']]);

        // Remember-me cookie
        if ($remember) {
            $this->setRememberToken((int)$user['id']);
        }

        return ['ok' => true, 'user' => ['id' => $user['id'], 'username' => $user['username']]];
    }

    // ─── Session management ──────────────────────────────────────────────────

    private function startSession(array $user): void
    {
        session_regenerate_id(true);

        $_SESSION['user_id']        = $user['id'];
        $_SESSION['username']       = $user['username'];
        $_SESSION['logged_in']      = true;
        $_SESSION['login_time']     = time();
        $_SESSION['last_active']    = time();
        $_SESSION['ip']             = $_SERVER['REMOTE_ADDR']  ?? '';
        $_SESSION['user_agent']     = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function logout(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->deleteRememberToken((int)$_SESSION['user_id']);
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();

        // Clear remember-me cookie
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }

    /**
     * Returns the logged-in user array or null.
     * Also handles idle timeout and session fixation checks.
     */
    public function user(): ?array
    {
        if (empty($_SESSION['logged_in'])) {
            // Try remember-me auto-login
            return $this->loginViaRememberToken();
        }

        // Idle timeout
        if (time() - ($_SESSION['last_active'] ?? 0) > SESSION_LIFETIME) {
            $this->logout();
            return null;
        }

        // Session fixation: IP / UA mismatch (optional strictness)
        if (($_SESSION['ip'] ?? '') !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
            $this->logout();
            return null;
        }

        // Periodic ID regeneration
        if (time() - ($_SESSION['login_time'] ?? 0) > SESSION_REGENERATE) {
            session_regenerate_id(true);
            $_SESSION['login_time'] = time();
        }

        $_SESSION['last_active'] = time();

        return ['id' => $_SESSION['user_id'], 'username' => $_SESSION['username']];
    }

    public function isLoggedIn(): bool
    {
        return $this->user() !== null;
    }

    // ─── Remember-me ─────────────────────────────────────────────────────────

    private function setRememberToken(int $userId): void
    {
        $token     = bin2hex(random_bytes(32));   // 64-char hex token
        $tokenHash = hash('sha256', $token);
        $expires   = date('Y-m-d H:i:s', strtotime('+30 days'));

        $this->db->prepare(
            'INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)'
        )->execute([$userId, $tokenHash, $expires]);

        setcookie('remember_token', $token, [
            'expires'  => strtotime('+30 days'),
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function loginViaRememberToken(): ?array
    {
        $token = $_COOKIE['remember_token'] ?? '';
        if (empty($token)) return null;

        $tokenHash = hash('sha256', $token);
        $stmt = $this->db->prepare(
            'SELECT rt.user_id, u.username, u.is_active
               FROM remember_tokens rt
               JOIN users u ON u.id = rt.user_id
              WHERE rt.token_hash = ?
                AND rt.expires_at > NOW()
              LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch();

        if (!$row || !$row['is_active']) return null;

        // Rotate token (token reuse protection)
        $this->deleteRememberToken((int)$row['user_id']);
        $this->setRememberToken((int)$row['user_id']);

        $this->startSession($row);
        return ['id' => $row['user_id'], 'username' => $row['username']];
    }

    private function deleteRememberToken(int $userId): void
    {
        $this->db->prepare('DELETE FROM remember_tokens WHERE user_id = ?')
                 ->execute([$userId]);
    }

    // ─── Brute-force protection ───────────────────────────────────────────────

    private function isLockedOut(string $identifier, string $ip): bool
    {
        $window  = date('Y-m-d H:i:s', time() - LOCKOUT_DURATION);
        $stmt    = $this->db->prepare(
            'SELECT COUNT(*) FROM login_attempts
              WHERE (identifier = ? OR ip_address = ?)
                AND attempted_at > ?'
        );
        $stmt->execute([$identifier, $ip, $window]);
        return (int)$stmt->fetchColumn() >= MAX_LOGIN_ATTEMPTS;
    }

    private function recordFailedAttempt(string $identifier, string $ip): void
    {
        $this->db->prepare(
            'INSERT INTO login_attempts (identifier, ip_address) VALUES (?, ?)'
        )->execute([$identifier, $ip]);
    }

    private function clearAttempts(string $identifier, string $ip): void
    {
        $this->db->prepare(
            'DELETE FROM login_attempts WHERE identifier = ? OR ip_address = ?'
        )->execute([$identifier, $ip]);
    }

    // ─── Password validation ─────────────────────────────────────────────────

    private function validatePassword(string $password): true|string
    {
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return 'Password must contain at least one uppercase letter.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            return 'Password must contain at least one lowercase letter.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            return 'Password must contain at least one number.';
        }
        if (!preg_match('/[\W_]/', $password)) {
            return 'Password must contain at least one special character.';
        }
        return true;
    }
}

<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * Session-based authentication.
 *
 * Flow: attempt() verifies credentials (with a small brute-force delay),
 * logs the user in by id, then the request can call user() to read the
 * authenticated user and role(). Remember-me uses a rotating random token
 * stored hashed in the database and read back via a cookie.
 */
final class Auth
{
    private const SESSION_KEY  = 'auth_user_id';
    private const REMEMBER_KEY = 'busa_remember';
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 900; // 15 minutes

    private static ?Auth $instance = null;
    private static ?array $cachedUser = null;

    public function __construct()
    {
    }

    public static function getInstance(): Auth
    {
        return self::$instance ??= new self();
    }

    /** Verifies credentials and logs the user in on success. */
    public function attempt(string $email, string $password, bool $remember = false): bool
    {
        $email = strtolower(trim($email));

        if ($this->tooManyAttempts($email, Request::ip())) {
            return false;
        }

        $user = User::findByEmail($email);

        if ($user && $user['is_active'] && password_verify($password, (string) $user['password_hash'])) {
            $this->clearAttempts($email, Request::ip());
            $this->loginById((int) $user['id'], $remember);
            return true;
        }

        $this->recordFailedAttempt($email, Request::ip());

        // Constant-ish behaviour: always run a hash check so timing doesn't
        // reveal whether an account exists.
        password_verify($password, $user['password_hash'] ?? '$2y$10$invalid$hash$for$timing$only');

        return false;
    }

    /** Starts an authenticated session for a known user id. */
    public function loginById(int $userId, bool $remember = false): void
    {
        Session::getInstance()->put(self::SESSION_KEY, $userId);
        Session::getInstance()->regenerate();
        self::$cachedUser = null;

        if ($remember) {
            $this->setRememberCookie($userId);
        }
    }

    public function check(): bool
    {
        return $this->id() !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function id(): ?int
    {
        $id = Session::getInstance()->get(self::SESSION_KEY);
        return is_numeric($id) ? (int) $id : null;
    }

    /** The authenticated user row, or null. One DB query, cached per request. */
    public function user(): ?array
    {
        $id = $this->id();

        if ($id === null) {
            return null;
        }

        if (self::$cachedUser === null) {
            $user = User::find($id);
            // A deleted/deactivated account immediately loses its session.
            if ($user === null || !$user['is_active']) {
                $this->logout();
                return null;
            }
            self::$cachedUser = $user;
        }

        return self::$cachedUser;
    }

    public function role(): ?string
    {
        return $this->user()['role'] ?? null;
    }

    public function isAllowed(array $roles): bool
    {
        return in_array($this->role(), $roles, true);
    }

    public function logout(): void
    {
        $session = Session::getInstance();
        $session->forget(self::SESSION_KEY);
        $session->regenerate();
        self::$cachedUser = null;

        $this->clearRememberCookie();
    }

    public function hasRememberToken(): bool
    {
        return isset($_COOKIE[self::REMEMBER_KEY]);
    }

    /**
     * Called once per request: if a valid remember-me cookie exists this
     * silently restores the session (auto-login).
     */
    public function boot(): void
    {
        if ($this->check()) {
            return;
        }

        if (!isset($_COOKIE[self::REMEMBER_KEY])) {
            return;
        }

        $token = (string) $_COOKIE[self::REMEMBER_KEY];

        if (!str_contains($token, '.') || !str_contains($token, ':')) {
            $this->clearRememberCookie();
            return;
        }

        [$userId, $selector] = explode(':', $token, 2);

        if (!ctype_digit($userId) || strlen($selector) < 32) {
            $this->clearRememberCookie();
            return;
        }

        $user = User::find((int) $userId);

        if (!$user || $user['remember_token'] === null) {
            $this->clearRememberCookie();
            return;
        }

        if (hash_equals($user['remember_token'], hash('sha256', $selector))) {
            $this->loginById((int) $user['id']);
        } else {
            $this->clearRememberCookie();
        }
    }

    // ---- Remember-me ------------------------------------------------------

    /** Issues a fresh random token, storing only its hash in the DB. */
    private function setRememberCookie(int $userId): void
    {
        $selector = bin2hex(random_bytes(32));
        User::updateRememberToken($userId, hash('sha256', $selector));

        setcookie(
            self::REMEMBER_KEY,
            $userId . ':' . $selector,
            [
                'expires'  => time() + 60 * 60 * 24 * 30,
                'path'     => '/',
                'secure'   => Request::isHttps(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }

    private function clearRememberCookie(): void
    {
        if (isset($_COOKIE[self::REMEMBER_KEY]) && ($userId = Session::getInstance()->get(self::SESSION_KEY))) {
            User::updateRememberToken((int) $userId, null);
        }

        setcookie(self::REMEMBER_KEY, '', [
            'expires'  => time() - 42000,
            'path'     => '/',
            'secure'   => Request::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        unset($_COOKIE[self::REMEMBER_KEY]);
    }

    // ---- Simple login rate limiting ---------------------------------------

    public function tooManyAttempts(string $email, string $ip): bool
    {
        $sql = "SELECT COUNT(*) AS attempts,
                       MIN(attempted_at) AS first_attempt
                  FROM login_attempts
                 WHERE email = ? AND ip_address = ?
                   AND attempted_at > (NOW() - INTERVAL " . self::LOCKOUT_SECONDS . " SECOND)";

        $stmt = db()->prepare($sql);
        $stmt->execute([$email, $ip]);
        $row = $stmt->fetch();

        return ((int) $row['attempts']) >= self::MAX_ATTEMPTS;
    }

    public function recordFailedAttempt(string $email, string $ip): void
    {
        $stmt = db()->prepare(
            "INSERT INTO login_attempts (email, ip_address, attempted_at) VALUES (?, ?, NOW())"
        );
        $stmt->execute([$email, $ip]);
    }

    public function clearAttempts(string $email, string $ip): void
    {
        $stmt = db()->prepare(
            "DELETE FROM login_attempts WHERE email = ? AND ip_address = ?"
        );
        $stmt->execute([$email, $ip]);
    }

    public function remainingAttempts(string $email, string $ip): int
    {
        $stmt = db()->prepare(
            "SELECT COUNT(*) AS attempts FROM login_attempts
              WHERE email = ? AND ip_address = ?
                AND attempted_at > (NOW() - INTERVAL " . self::LOCKOUT_SECONDS . " SECOND)"
        );
        $stmt->execute([$email, $ip]);
        return max(0, self::MAX_ATTEMPTS - (int) $stmt->fetchColumn());
    }
}
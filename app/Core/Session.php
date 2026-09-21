<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Thin wrapper around PHP's $_SESSION.
 *
 * Responsibilities:
 *  - start PHP sessions with secure cookie flags
 *  - expose typed get/set/forget
 *  - flash messages (shown once)
 *  - one-time "old input" values
 *  - safe session regeneration
 */
final class Session
{
    private static ?Session $instance = null;

    private function __construct()
    {
    }

    public static function getInstance(): Session
    {
        return self::$instance ??= new self();
    }

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $name = (string) config('session.name', 'busa_session');

        // HttpOnly + SameSite are always on; Secure is only enabled on HTTPS
        session_name($name);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => Request::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Stores a message that is shown exactly once (toast/alert system). */
    public function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    /** Pulls and clears pending flash messages. */
    public function pullFlashes(): array
    {
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flashes;
    }

    /** Remembers form input for repopulating a form after a validation error. */
    public function flashInput(array $input): void
    {
        $_SESSION['_old_input'] = $input;
    }

    public function oldInput(string $key = '', mixed $default = null): mixed
    {
        if ($key === '') {
            return $_SESSION['_old_input'] ?? [];
        }
        return $_SESSION['_old_input'][$key] ?? $default;
    }

    public function clearOldInput(): void
    {
        unset($_SESSION['_old_input']);
    }

    /** Regenerates the ID to prevent session fixation; keeps data intact. */
    public function regenerate(): void
    {
        session_regenerate_id(true);
        unset($_SESSION['_csrf_token']);
    }

    public function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}
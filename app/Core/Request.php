<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Request abstraction. Keeps controllers free of $_SERVER / $_POST plumbing
 * and centralises JSON-body and AJAX detection.
 */
final class Request
{
    public static function method(): string
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Support PUT/DELETE spoofing for simple HTML forms
        if ($method === 'POST' && isset($_POST['_method'])) {
            return strtoupper((string) $_POST['_method']);
        }

        return $method;
    }

    /** The application path, e.g. "/reservations/42". Query string removed. */
    public static function path(): string
    {
        static $path = null;

        if ($path !== null) {
            return $path;
        }

        $uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $base = url_base();

        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        $uri = '/' . ltrim($uri, '/');

        // Collapse trailing slashes: /rooms/  =>  /rooms
        $path = ($uri === '/') ? '/' : rtrim($uri, '/');
        return $path;
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        // JSON API bodies are read the same way as form data
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && self::isJsonBody()) {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            return $data[$key] ?? $default;
        }

        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /** All current request data merged (query + body). */
    public static function all(): array
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && self::isJsonBody()) {
            return json_decode(file_get_contents('php://input'), true) ?? [];
        }
        return array_merge($_GET, $_POST);
    }

    public static function file(string $key): ?array
    {
        $file = $_FILES[$key] ?? null;

        return ($file && isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE)
            ? $file
            : null;
    }

    public static function isAjax(): bool
    {
        return (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') || self::isJsonBody();
    }

    public static function wantsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || self::isAjax();
    }

    public static function isJsonBody(): bool
    {
        return str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');
    }

    public static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? 0) === 443
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    }

    public static function ip(): string
    {
        // Never trust client headers blindly; fall back to REMOTE_ADDR
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /** Returns the trimmed base URL (scheme://host) for building absolute links. */
    public static function baseUrl(): string
    {
        return config('app.url');
    }
}
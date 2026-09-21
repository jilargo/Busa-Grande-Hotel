<?php

declare(strict_types=1);

/*
 * Global helper functions. Keeping these tiny utilities together avoids
 * repetitive code across controllers and views.
 */

use App\Core\Config;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Session;
use App\Core\View;

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false) {
        return $default;
    }
    return $value;
}

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

function base_path(string $path = ''): string
{
    return $path === '' ? BASE_PATH : BASE_PATH . '/' . ltrim($path, '/');
}

/**
 * URL base prefix so the app works both at a domain root (Herd/Valet)
 * and inside a sub-folder (XAMPP htdocs).
 */
function url_base(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $configured = (string) env('APP_BASE_PATH', '');
    if ($configured !== '') {
        return $base = '/' . trim($configured, '/');
    }

    // Auto-detect from the executing script. With the dev server router this
    // resolves to ''; under Apache sub-folder hosting it yields e.g.
    // "/busa-grande-hotel/public".
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $base = ($dir === '/' || $dir === '.') ? '' : $dir;
    return $base;
}

/** Builds an application URL (keeps it relative to the route prefix). */
function url(string $path = ''): string
{
    $base = url_base();
    $path = '/' . ltrim($path, '/');
    return $base . $path;
}

/** Builds a URL to a public asset. */
function asset(string $path): string
{
    $path = ltrim($path, '/');

    // Files uploaded at runtime live directly under the public directory
    // (e.g. uploads/rooms/photo.jpg), not under assets/.
    if (str_starts_with($path, 'uploads/')) {
        return url($path);
    }

    return url('assets/' . $path);
}

/** Returns the persistent PDO connection. */
function db(): PDO
{
    return Database::connection();
}

/** HTML-escape a value before printing it (XSS protection). */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function back(): never
{
    redirect(Session::get('_previous_url') ?: '/');
}

/**
 * Renders a view inside a layout. Layouts live in views/layouts/.
 * Pass a false/null layout to render a bare view (used by AJAX partials).
 */
function view(string $name, array $data = [], ?string $layout = 'app'): void
{
    View::render($name, $data, $layout);
}

/** Returns a JSON response for AJAX endpoints. */
function json(mixed $payload, int $status = 200): never
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function session(): Session
{
    return Session::getInstance();
}

function auth(): \App\Core\Auth
{
    return \App\Core\Auth::getInstance();
}

function logger(): Logger
{
    return Logger::getInstance();
}

/** Formats a decimal amount as hotel-style money. */
function money(float|string $amount, ?string $currency = null): string
{
    $currency ??= (string) config('app.currency', '₱');

    return $currency . number_format((float) $amount, 2);
}

/** Tailwind colour classes for each domain status value. */
function badge_classes(string $status): string
{
    $map = [
        // reservation statuses
        'pending'     => 'bg-amber-50 text-amber-700 ring-amber-200',
        'confirmed'   => 'bg-sky-50 text-sky-700 ring-sky-200',
        'checked_in'  => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'checked_out' => 'bg-slate-100 text-slate-600 ring-slate-300',
        'cancelled'   => 'bg-rose-50 text-rose-600 ring-rose-200',
        'no_show'     => 'bg-orange-50 text-orange-700 ring-orange-200',
        // payment statuses
        'paid'        => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'partial'     => 'bg-amber-50 text-amber-700 ring-amber-200',
        'refunded'    => 'bg-slate-100 text-slate-600 ring-slate-300',
        // room statuses
        'available'   => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'reserved'    => 'bg-sky-50 text-sky-700 ring-sky-200',
        'occupied'    => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'maintenance' => 'bg-rose-50 text-rose-600 ring-rose-200',
        'cleaning'    => 'bg-amber-50 text-amber-700 ring-amber-200',
        // roles
        'admin'       => 'bg-gold-100 text-gold-700 ring-gold-300',
        'staff'       => 'bg-sky-100 text-sky-700 ring-sky-300',
        'guest'       => 'bg-slate-100 text-slate-600 ring-slate-300',
    ];

    $base = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset';
    return $base . ' ' . ($map[$status] ?? 'bg-slate-50 text-slate-600 ring-slate-200');
}

/** Prints a pill <span> for any domain status. */
function status_badge(string $status, ?string $label = null): string
{
    $label ??= str_replace('_', ' ', $status);
    return '<span class="' . badge_classes($status) . '">' . e($label) . '</span>';
}

/** Reservation-level payment status derived from paid vs total. */
function payment_status_label(string $derived): string
{
    return match ($derived) {
        'paid'     => 'Paid',
        'partial'  => 'Partial',
        'refunded' => 'Refunded',
        default    => 'Pending',
    };
}
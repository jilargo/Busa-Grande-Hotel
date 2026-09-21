<?php

declare(strict_types=1);

/*
 * Bootstrap file: loads Composer, environment variables, error handling,
 * configuration, and the session. Every HTTP request begins here.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use App\Core\Config;
use App\Core\Exceptions\HttpException;
use App\Core\Logger;

// Load .env (does not overwrite real environment variables)
Dotenv\Dotenv::createImmutable(BASE_PATH)->safeLoad();

// Application configuration (reads values from the environment)
Config::load();

// Security defaults
if ((\App\Core\Request::isHttps()) && !headers_sent() && session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// Centralised error handling.

/**
 * Logs the exception and renders a friendly page. Technical details are only
 * exposed when APP_DEBUG is enabled.
 */
$handleException = function (Throwable $e): void {
    Logger::error('Unhandled exception: ' . $e->getMessage(), [
        'exception' => get_class($e),
        'file'      => $e->getFile() . ':' . $e->getLine(),
        'trace'     => $e->getTraceAsString(),
        'uri'       => $_SERVER['REQUEST_URI'] ?? 'cli',
    ]);

    $status = $e instanceof HttpException ? $e->statusCode() : 500;

    // Form validation failures go BACK to the form with a friendly flash and
    // the entered values preserved — never as a raw error page.
    if ($e instanceof \App\Core\Exceptions\ValidationException && !\App\Core\Request::wantsJson()) {
        $firstMessage = $e->errors();
        $message = $firstMessage !== [] ? reset($firstMessage) : $e->getMessage();
        session()->flash('error', (string) $message);
        session()->flashInput($_POST);
        redirect(\App\Core\Session::getInstance()->get('_previous_url') ?: 'dashboard');
    }

    if ($e instanceof HttpException || \App\Core\Request::wantsJson()) {
        if (\App\Core\Request::wantsJson()) {
            if ($e instanceof HttpException && $e->statusCode() === 422) {
                $errors = $e instanceof \App\Core\Exceptions\ValidationException ? $e->errors() : [];
                http_response_code(422);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => $errors]);
                return;
            }
            http_response_code($status);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            return;
        }

        http_response_code($status);
        \App\Core\View::render('errors/error', [
            'title'   => 'Error',
            'status'  => $status,
            'message' => $e->getMessage(),
            'debug'   => config('app.debug') ? $e : null,
        ], 'errors');
        return;
    }

    http_response_code($status);
    \App\Core\View::render('errors/error', [
        'title'   => 'Internal Error',
        'status'  => 500,
        'message' => 'Something went wrong while processing your request. Please try again.',
        'debug'   => config('app.debug') ? $e : null,
    ], 'errors');
};

set_exception_handler($handleException);
set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false; // respect @-operator / disabled error types
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Start the encrypted-level session.
\App\Core\Session::start();

// Small guard: expose nothing to listeners that we shouldn't.
if (PHP_SAPI !== 'cli') {
    header_remove('X-Powered-By');
}
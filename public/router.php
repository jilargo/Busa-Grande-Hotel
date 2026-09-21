<?php

declare(strict_types=1);

/*
 * Router helper for PHP's built-in development server:
 *
 *   php -S 127.0.0.1:8090 -t public public/router.php
 *
 * Existing files (CSS/JS/images) are served directly by PHP; everything else
 * is handed to the front controller.
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
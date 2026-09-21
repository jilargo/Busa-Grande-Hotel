<?php

declare(strict_types=1);

/*
 * Front controller — every request enters here.
 *
 * Flow: bootstrap (env, error handling, session) → remember-me boot → route
 * definition → dispatch → controller renders a view or redirects.
 */

require dirname(__DIR__) . '/bootstrap/app.php';

use App\Core\Request;
use App\Core\Router;

// Restore a session from a valid remember-me cookie, if present.
auth()->boot();

$router = new Router();
$router->load(require BASE_PATH . '/routes/web.php');
$router->dispatch(new Request());
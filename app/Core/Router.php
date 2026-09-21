<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\CsrfException;
use App\Core\Exceptions\ForbiddenException;
use App\Core\Exceptions\NotFoundException;

/**
 * Tiny routing engine with parameterised URLs and middleware.
 *
 * Routes are declared in routes/web.php as:
 *   [method, path, "Controller@method", [middleware...]]
 *
 * Matching supports {placeholders}, e.g. /reservations/{id}.
 */
final class Router
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    private array $routes = [];

    /** Registers all route definitions (called from the front controller). */
    public function load(array $definitions): void
    {
        foreach ($definitions as $definition) {
            [$method, $path, $handler] = $definition;
            $middleware = $definition[3] ?? [];
            $this->add($method, $path, $handler, $middleware);
        }
    }

    public function add(string $method, string $path, callable|string $handler, array $middleware = []): void
    {
        $method = strtoupper($method);
        $path = '/' . trim($path, '/');
        $this->routes[$method][$path] = ['handler' => $handler, 'middleware' => $middleware];
    }

    public function dispatch(Request $request): void
    {
        // Remember where we came from so back() can redirect sensibly.
        if (!$request->isAjax() && $request->method() === 'GET') {
            Session::getInstance()->put('_previous_url', $request->path() . (($_SERVER['QUERY_STRING'] ?? '') ? '?' . $_SERVER['QUERY_STRING'] : ''));
        }

        // Global CSRF check for every state-changing request.
        if (!in_array($request->method(), self::SAFE_METHODS, true)) {
            $token = (string) $request->input('_csrf', '') ?: (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
            if (!Csrf::validate($token)) {
                throw new CsrfException();
            }
        }

        [$handler, $middleware, $params] = $this->match($request->method(), $request->path());

        foreach ($middleware as $name) {
            $this->runMiddleware($name, $request);
        }

        if (is_string($handler)) {
            [$class, $method] = explode('@', $handler . '@');
            $controller = new ("App\\Controllers\\" . $class)();
            $controller->{$method}($request, ...$params);
        } else {
            $handler($request, ...$params);
        }
    }

    /** Finds a matching route; exact match first, then {param} patterns. */
    private function match(string $method, string $path): array
    {
        if (isset($this->routes[$method][$path])) {
            return [$this->routes[$method][$path]['handler'], $this->routes[$method][$path]['middleware'], []];
        }

        foreach ($this->routes[$method] ?? [] as $routePath => $route) {
            if (!str_contains($routePath, '{')) {
                continue;
            }

            $regex = '#^' . preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $routePath) . '$#';
            if (preg_match($regex, $path, $matches)) {
                $params = array_values(array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
                // Route placeholders are almost always numeric ids; cast them so
                // typed method signatures (int $id) don't receive raw strings.
                $params = array_map(
                    fn ($v) => ctype_digit($v) ? (int) $v : urldecode($v),
                    $params
                );
                return [$route['handler'], $route['middleware'], $params];
            }
        }

        throw new NotFoundException();
    }

    private function runMiddleware(string $name, Request $request): void
    {
        switch (true) {
            case $name === 'auth' || str_starts_with($name, 'role:'):
                if (auth()->guest()) {
                    if ($request->wantsJson()) {
                        $this->userJson(false, ['You must be signed in to do that.']);
                        return; // unreachable – json() exits
                    }
                    Session::getInstance()->flash('error', 'Please sign in to continue.');
                    redirect('login');
                }

                if ($name !== 'auth') {
                    $roles = explode(',', substr($name, 5));
                    if (!auth()->isAllowed($roles)) {
                        throw new ForbiddenException('You do not have permission to access this page.');
                    }
                }
                return;

            case $name === 'guest':
                if (auth()->check()) {
                    redirect('dashboard');
                }
                return;

            default:
                throw new \RuntimeException("Unknown middleware: {$name}");
        }
    }

    private function userJson(bool $a, array $messages): never
    {
        json(['success' => $a, 'message' => $messages[0]], 401);
    }
}
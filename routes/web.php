<?php

declare(strict_types=1);

/*
 * Route table.
 *
 * Each entry: [METHOD, PATH, "Controller@method", [middleware...]]
 *
 * Middleware:
 *   - guest          only for logged-OUT visitors (login/register pages)
 *   - auth           any signed-in user
 *   - role:admin     admin only
 *   - role:staff     staff only
 *   - role:guest     guest only
 *   - role:staff,admin   staff or admin
 *
 * Every POST/PUT/PATCH/DELETE request is CSRF-checked by the router.
 */

return [
    // ---- Public marketing ----
    ['GET', '/', 'HomeController@index'],

    // ---- Authentication ----
    ['GET', '/register', 'AuthController@showRegister', ['guest']],
    ['POST', '/register', 'AuthController@register', ['guest']],
    ['GET', '/login', 'AuthController@showLogin', ['guest']],
    ['POST', '/login', 'AuthController@login', ['guest']],
    ['POST', '/logout', 'AuthController@logout', ['auth']],
    ['GET', '/auth/google', 'GoogleAuthController@redirect', ['guest']],
    ['GET', '/auth/google/callback', 'GoogleAuthController@callback', ['guest']],

    // ---- Role-aware dashboard ----
    ['GET', '/dashboard', 'DashboardController@index', ['auth']],

    // ---- Admin only: users ----
    ['GET', '/users', 'UserController@index', ['role:admin']],
    ['POST', '/users', 'UserController@store', ['role:admin']],
    ['POST', '/users/{id}/active', 'UserController@setActive', ['role:admin']],
    ['POST', '/users/{id}/delete', 'UserController@destroy', ['role:admin']],
];

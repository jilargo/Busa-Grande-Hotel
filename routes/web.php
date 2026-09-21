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
    ['GET', '/room-types/{id}', 'HomeController@roomType'],

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

    // ---- Staff & admin: reservations ----
    ['GET', '/reservations', 'ReservationController@index', ['role:staff,admin']],
    ['GET', '/reservations/create', 'ReservationController@create', ['role:staff,admin']],
    ['POST', '/reservations', 'ReservationController@store', ['role:staff,admin']],
    ['GET', '/reservations/{id}', 'ReservationController@show', ['role:staff,admin']],
    ['POST', '/reservations/{id}/status', 'ReservationController@updateStatus', ['role:staff,admin']],
    ['POST', '/reservations/{id}/cancel', 'ReservationController@cancel', ['role:staff,admin']],

    // ---- Staff & admin: rooms ----
    ['GET', '/rooms', 'RoomController@index', ['role:staff,admin']],
    ['GET', '/rooms/create', 'RoomController@create', ['role:staff,admin']],
    ['POST', '/rooms', 'RoomController@store', ['role:staff,admin']],
    ['GET', '/rooms/{id}', 'RoomController@show', ['role:staff,admin']],
    ['GET', '/rooms/{id}/edit', 'RoomController@edit', ['role:staff,admin']],
    ['POST', '/rooms/{id}/update', 'RoomController@update', ['role:staff,admin']],
    ['POST', '/rooms/{id}/delete', 'RoomController@destroy', ['role:admin']],
    ['POST', '/rooms/{id}/status', 'RoomController@changeStatus', ['role:staff,admin']],

    // ---- Staff & admin: guests ----
    ['GET', '/guests', 'GuestController@index', ['role:staff,admin']],
    ['GET', '/guests/create', 'GuestController@create', ['role:staff,admin']],
    ['POST', '/guests', 'GuestController@store', ['role:staff,admin']],
    ['GET', '/guests/{id}', 'GuestController@show', ['role:staff,admin']],
    ['GET', '/guests/{id}/edit', 'GuestController@edit', ['role:staff,admin']],
    ['POST', '/guests/{id}/update', 'GuestController@update', ['role:staff,admin']],
    ['POST', '/guests/{id}/delete', 'GuestController@destroy', ['role:admin']],

    // ---- Admin only: room types ----
    ['GET', '/room-types', 'RoomTypeController@index', ['role:admin']],
    ['GET', '/room-types/create', 'RoomTypeController@create', ['role:admin']],
    ['POST', '/room-types', 'RoomTypeController@store', ['role:admin']],
    ['GET', '/room-types/{id}/edit', 'RoomTypeController@edit', ['role:admin']],
    ['POST', '/room-types/{id}/update', 'RoomTypeController@update', ['role:admin']],
    ['POST', '/room-types/{id}/delete', 'RoomTypeController@destroy', ['role:admin']],

    // ---- Admin only: users ----
    ['GET', '/users', 'UserController@index', ['role:admin']],
    ['POST', '/users', 'UserController@store', ['role:admin']],
    ['POST', '/users/{id}/active', 'UserController@setActive', ['role:admin']],
    ['POST', '/users/{id}/delete', 'UserController@destroy', ['role:admin']],

    // ---- JSON endpoints (booking form, live availability) ----
    ['GET', '/api/available-rooms', 'ApiController@availableRooms', ['role:staff,admin']],
    ['GET', '/api/guests', 'ApiController@searchGuests', ['role:staff,admin']],
];

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Core\Exceptions\ValidationException;
use App\Models\Guest;
use App\Models\Role;
use App\Models\User;
use App\Services\GoogleAuthService;

/**
 * Email/password authentication flow:
 *
 *   Browser ──▶ GET  /login   (form; includes CSRF token)
 *   Browser ──▶ POST /login   ──▶ validate ──▶ Auth::attempt()
 *     ──▶ password_verify()   ──▶ session regenerate ──▶ redirect /dashboard
 *
 * Registration creates a user + linked guest profile in one transaction.
 */
final class AuthController
{
    public function showLogin(Request $request): void
    {
        $google = new GoogleAuthService();

        view('auth/login', [
            'title'     => 'Sign in - ' . config('app.name'),
            'googleEnabled' => $google->configured(),
        ], 'auth');
    }

    public function login(Request $request): void
    {
        $data = Validator::validate($request->all(), [
            'email'    => ['required', 'email', 'max:190'],
            'password' => ['required', 'min:1'],
        ]);

        $remember = $request->input('remember') === 'on' || $request->input('remember') === '1';

        if (!auth()->attempt($data['email'], $data['password'], $remember)) {
            session()->flash('error', 'These credentials do not match our records.');
            redirect('login');
        }

        session()->flash('success', 'Welcome back, ' . auth()->user()['name'] . '!');
        redirect('dashboard');
    }

    public function showRegister(Request $request): void
    {
        $google = new GoogleAuthService();

        view('auth/register', [
            'title'         => 'Create account - ' . config('app.name'),
            'googleEnabled' => $google->configured(),
        ], 'auth');
    }

    public function register(Request $request): void
    {
        $data = Validator::validate($request->all(), [
            'name'                  => ['required', 'max:120'],
            'email'                 => ['required', 'email', 'max:190', 'unique:users,email'],
            'password'              => ['required', 'min:8', 'password_regex'],
            'password_confirmation' => ['required'],
        ], );

        $data['password'] = $request->input('password');

        if ($data['password'] !== $request->input('password_confirmation')) {
            throw new ValidationException(['password_confirmation' => 'The password confirmation does not match.']);
        }

        $guestRoleId = Role::idFor(Role::GUEST) ?? 1;

        db()->beginTransaction();

        try {
            $userId = User::create([
                'name'          => $data['name'],
                'email'         => $data['email'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role_id'       => $guestRoleId,
                'is_active'     => 1,
            ]);

            Guest::create([
                'user_id'    => $userId,
                'first_name' => $data['name'],
                'last_name'  => '',
                'email'      => $data['email'],
            ]);

            db()->commit();
        } catch (\Throwable $e) {
            db()->rollBack();
            throw $e;
        }

        auth()->loginById($userId);
        session()->flash('success', 'Your account has been created. Welcome to Busa Grande Hotel!');

        redirect('dashboard');
    }

    public function logout(Request $request): void
    {
        auth()->logout();
        session()->flash('success', 'You have been signed out. See you soon!');
        redirect('/');
    }
}
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\Exceptions\HttpException;
use App\Models\Guest;
use App\Models\Role;
use App\Models\User;
use App\Services\GoogleAuthService;

/**
 * Google sign-in.
 *
 *   GET /auth/google          → build consent URL, stash a random "state",
 *                               send the user to Google
 *   GET /auth/google/callback → verify state + code against Google, find or
 *                               create the local user, start a PHP session
 *
 * The OAuth "state" parameter is bound to the user's session, which blocks
 * login-CSRF (an attacker cannot forge a callback that uses our state token).
 */
final class GoogleAuthController
{
    public function redirect(Request $request): void
    {
        $google = new GoogleAuthService();

        if (!$google->configured()) {
            session()->flash('error', 'Google sign-in is not configured yet.');
            redirect('login');
        }

        $state = bin2hex(random_bytes(16));
        session()->put('google_oauth_state', $state);

        header('Location: ' . $google->authorizationUrl($state));
        exit;
    }

    public function callback(Request $request): void
    {
        // Reject callbacks we did not initiate (state must match).
        $expected = (string) session()->get('google_oauth_state', '');
        session()->forget('google_oauth_state');

        $state = (string) $request->input('state', '');
        $code  = (string) $request->input('code', '');
        $error = (string) $request->input('error', '');

        if ($error !== '') {
            session()->flash('error', 'Google sign-in was cancelled or declined.');
            redirect('login');
        }

        if ($state === '' || $code === '' || !hash_equals($expected, $state)) {
            throw new HttpException(400, 'The Google sign-in response could not be verified. Please try again.');
        }

        $google = new GoogleAuthService();

        try {
            $profile = $google->getUserProfile($code);
        } catch (HttpException $e) {
            session()->flash('error', $e->getMessage());
            redirect('login');
        }

        $guestRoleId = Role::idFor(Role::GUEST) ?? 1;
        $user = User::findOrCreateByGoogle($profile, $guestRoleId);

        if ($user === []) {
            throw new HttpException(500, 'Could not create your account. Please try again.');
        }

        // Ensure a guest profile exists so guest bookings have somewhere to go.
        if (Guest::findByUserId((int) $user['id']) === null) {
            Guest::create([
                'user_id'    => (int) $user['id'],
                'first_name' => $user['name'],
                'last_name'  => '',
                'email'      => $user['email'],
            ]);
        }

        auth()->loginById((int) $user['id']);
        session()->flash('success', 'Signed in with Google. Welcome back, ' . $user['name'] . '!');

        redirect('dashboard');
    }
}
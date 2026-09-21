<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Guest;
use App\Models\User;

/**
 * Guest account + profile management (session-driven, guest can only edit
 * their own record — the user id comes from Auth, never from the URL).
 */
final class ProfileController
{
    public function show(Request $request): void
    {
        $user  = auth()->user();
        $guest = Guest::findByUserId((int) $user['id']);

        view('guest/profile', [
            'title' => 'My Profile - ' . config('app.name'),
            'user'  => $user,
            'guest' => $guest,
        ]);
    }

    public function update(Request $request): void
    {
        $user  = auth()->user();
        $guest = Guest::findByUserId((int) $user['id'])
            ?? throw new \App\Core\Exceptions\NotFoundException('Guest profile not found.');

        $name  = Validator::validate($request->all(), ['name' => ['required', 'max:120']]);
        $guestData = Validator::validate($request->all(), [
            'first_name' => ['required', 'max:80'],
            'last_name'  => ['required', 'max:80'],
            'phone'      => ['max:30'],
            'address'    => ['max:200'],
            'city'       => ['max:100'],
            'country'    => ['max:100'],
            'date_of_birth' => ['nullable', 'date'],
        ]);

        User::updateProfile((int) $user['id'], $name['name']);
        Guest::update((int) $guest['id'], [
            'first_name' => $guestData['first_name'],
            'last_name'  => $guestData['last_name'],
            'email'      => $guest['email'],
            'phone'      => $guestData['phone'],
            'address'    => $guestData['address'],
            'city'       => $guestData['city'],
            'country'    => $guestData['country'],
            'date_of_birth' => $guestData['date_of_birth'],
        ]);

        session()->flash('success', 'Your profile has been updated.');
        redirect('account/profile');
    }

    public function changePassword(Request $request): void
    {
        $user = auth()->user();

        $data = Validator::validate($request->all(), [
            'current_password'      => ['required', 'min:1'],
            'new_password'          => ['required', 'min:8', 'password_regex'],
            'new_password_confirmation' => ['required'],
        ]);

        if (!password_verify($data['current_password'], (string) $user['password_hash'])) {
            throw new ValidationException(['current_password' => 'Your current password is incorrect.']);
        }

        if ($data['new_password'] !== $request->input('new_password_confirmation')) {
            throw new ValidationException(['new_password_confirmation' => 'The new password confirmation does not match.']);
        }

        User::updatePassword((int) $user['id'], password_hash($request->input('new_password'), PASSWORD_DEFAULT));

        // Force re-authentication with the new password on other devices.
        session()->flash('success', 'Your password has been changed.');

        // Google-only accounts have no password yet; make sure one exists.
        redirect('account/profile');
    }
}
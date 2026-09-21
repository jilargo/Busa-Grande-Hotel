<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Validator;
use App\Models\Role;
use App\Models\User;

/**
 * User administration (admin only): list staff, add staff, toggle status,
 * assign roles (admin/staff) and remove users.
 */
final class UserController
{
    public function index(Request $request): void
    {
        view('admin/users/index', [
            'title' => 'Users - ' . config('app.name'),
            'users' => User::all((string) $request->input('q', '')),
            'q'     => (string) $request->input('q', ''),
        ]);
    }

    public function store(Request $request): void
    {
        $data = Validator::validate($request->all(), [
            'name'      => ['required', 'max:120'],
            'email'     => ['required', 'email', 'max:190', 'unique:users,email'],
            'password'  => ['required', 'min:8', 'password_regex'],
            'role'      => ['required', 'in:admin,staff'],
        ]);

        $roleId = Role::idFor($data['role'])
            ?? throw new \App\Core\Exceptions\ValidationException(['role' => 'Invalid role.']);

        User::create([
            'name'          => $data['name'],
            'email'         => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role_id'       => $roleId,
            'is_active'     => 1,
        ]);

        session()->flash('success', 'User "' . $data['name'] . '" created.');
        redirect('users');
    }

    public function setActive(Request $request, int $id): void
    {
        $user = User::find($id)
            ?? throw new \App\Core\Exceptions\NotFoundException('User not found.');

        // An admin can never disable themselves.
        if ($id === (int) auth()->id()) {
            session()->flash('error', 'You cannot disable your own account.');
            redirect('users');
        }

        $active = $request->input('active') === '1' ? 1 : 0;
        db()->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute([$active, $id]);

        session()->flash('success', $user['name'] . ' is now ' . ($active ? 'active' : 'deactivated') . '.');
        redirect('users');
    }

    public function destroy(Request $request, int $id): void
    {
        if ($id === (int) auth()->id()) {
            session()->flash('error', 'You cannot delete your own account.');
            redirect('users');
        }

        try {
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            session()->flash('success', 'User deleted.');
        } catch (\PDOException $e) {
            session()->flash('error', 'This user could not be deleted because other records reference them.');
        }

        redirect('users');
    }
}
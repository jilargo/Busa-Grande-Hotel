<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Validator;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\User;

/**
 * Guest (customer) CRUD + search for staff/admin.
 */
final class GuestController
{
    public function index(Request $request): void
    {
        view('admin/guests/index', [
            'title'  => 'Guests - ' . config('app.name'),
            'guests' => Guest::all($request->all()),
            'q'      => (string) $request->input('q', ''),
        ]);
    }

    public function create(Request $request): void
    {
        view('admin/guests/form', [
            'title' => 'New Guest - ' . config('app.name'),
            'guest' => null,
            'users' => User::all(''),
        ]);
    }

    public function store(Request $request): void
    {
        $data = Validator::validate($request->all(), [
            'first_name' => ['required', 'max:80'],
            'last_name'  => ['required', 'max:80'],
            'email'      => ['nullable', 'email', 'max:190'],
            'phone'      => ['max:30'],
            'address'    => ['max:200'],
            'city'       => ['max:100'],
            'country'    => ['max:100'],
            'date_of_birth' => ['nullable', 'date'],
            'id_type'    => ['max:50'],
            'id_number'  => ['max:100'],
            'emergency_contact_name'  => ['max:120'],
            'emergency_contact_phone' => ['max:30'],
            'notes'      => ['max:1000'],
            'user_id'    => ['nullable', 'integer'],
        ]);

        $id = Guest::create($data);

        session()->flash('success', 'Guest created.');
        redirect('guests/' . $id);
    }

    public function show(Request $request, int $id): void
    {
        $guest = Guest::find($id)
            ?? throw new \App\Core\Exceptions\NotFoundException('Guest not found.');

        $reservations = Reservation::all(['guest_id' => $id]);

        $stmt = db()->prepare(
            'SELECT COALESCE(SUM(p.amount), 0)
               FROM payments p
               JOIN reservations r ON r.id = p.reservation_id
              WHERE r.guest_id = ? AND p.amount > 0'
        );
        $stmt->execute([$id]);
        $totalSpent = (float) $stmt->fetchColumn();

        view('admin/guests/show', [
            'title' => $guest['first_name'] . ' ' . $guest['last_name'] . ' - ' . config('app.name'),
            'guest' => $guest,
            'accountName' => $guest['user_id'] ? (User::find((int) $guest['user_id'])['name'] ?? null) : null,
            'reservations' => $reservations,
            'totalSpent' => $totalSpent,
        ]);
    }

    public function edit(Request $request, int $id): void
    {
        $guest = Guest::find($id)
            ?? throw new \App\Core\Exceptions\NotFoundException('Guest not found.');

        view('admin/guests/form', [
            'title' => 'Edit Guest - ' . config('app.name'),
            'guest' => $guest,
            'users' => User::all(''),
        ]);
    }

    public function update(Request $request, int $id): void
    {
        Guest::find($id) ?? throw new \App\Core\Exceptions\NotFoundException('Guest not found.');

        $data = Validator::validate($request->all(), [
            'first_name' => ['required', 'max:80'],
            'last_name'  => ['required', 'max:80'],
            'email'      => ['nullable', 'email', 'max:190'],
            'phone'      => ['max:30'],
            'address'    => ['max:200'],
            'city'       => ['max:100'],
            'country'    => ['max:100'],
            'date_of_birth' => ['nullable', 'date'],
            'id_type'    => ['max:50'],
            'id_number'  => ['max:100'],
            'emergency_contact_name'  => ['max:120'],
            'emergency_contact_phone' => ['max:30'],
            'notes'      => ['max:1000'],
            'user_id'    => ['nullable', 'integer'],
        ]);

        Guest::update($id, $data);

        session()->flash('success', 'Guest updated.');
        redirect('guests/' . $id);
    }

    public function destroy(Request $request, int $id): void
    {
        if (!auth()->isAllowed(['admin'])) {
            throw new \App\Core\Exceptions\ForbiddenException();
        }

        try {
            Guest::destroy($id);
            session()->flash('success', 'Guest deleted.');
        } catch (\PDOException $e) {
            session()->flash('error', 'This guest cannot be deleted because they have reservations.');
        }

        redirect('guests');
    }
}
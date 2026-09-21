<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\Reservation;
use App\Services\CheckInService;

/**
 * Check-in flow:
 *
 *   GET  /reservations/{id}/check-in  → review the stay (guest/room/dates)
 *   POST /reservations/{id}/check-in  → CheckInService::checkIn()
 *                                        [INSERT check_ins, status → checked_in,
 *                                         room → occupied] in a transaction
 */
final class CheckInController
{
    public function form(Request $request, int $id): void
    {
        $reservation = Reservation::find($id)
            ?? throw new \App\Core\Exceptions\NotFoundException('Reservation not found.');

        view('admin/checkin/form', [
            'title'       => 'Check-in ' . $reservation['reference'] . ' - ' . config('app.name'),
            'reservation' => $reservation,
        ]);
    }

    public function store(Request $request, int $id): void
    {
        $service = new CheckInService();
        $reservation = $service->checkIn($id, (int) auth()->id());

        session()->flash('success', $reservation['reference'] . ' is now checked in. Welcome the guest to ' . $reservation['room_number'] . '!');
        redirect('reservations/' . $id);
    }
}
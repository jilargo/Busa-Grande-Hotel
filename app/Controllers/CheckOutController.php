<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\CheckOutService;

/**
 * Check-out flow:
 *
 *   GET  /reservations/{id}/check-out → review charges, balance and payments
 *   POST /reservations/{id}/check-out → CheckOutService::checkOut()
 *                                        [INSERT check_outs, status → checked_out,
 *                                         room → cleaning] in a transaction
 */
final class CheckOutController
{
    public function form(Request $request, int $id): void
    {
        $reservation = Reservation::find($id)
            ?? throw new \App\Core\Exceptions\NotFoundException('Reservation not found.');

        view('admin/checkout/form', [
            'title'       => 'Check-out ' . $reservation['reference'] . ' - ' . config('app.name'),
            'reservation' => $reservation,
            'payments'    => Payment::forReservation($id),
        ]);
    }

    public function store(Request $request, int $id): void
    {
        $notes = trim((string) $request->input('notes', '')) ?: null;

        $service = new CheckOutService();
        $reservation = $service->checkOut($id, (int) auth()->id(), $notes);

        session()->flash('success', $reservation['reference'] . ' checked out. Housekeeping has been notified for room ' . $reservation['room_number'] . '.');
        redirect('reservations/' . $id);
    }
}
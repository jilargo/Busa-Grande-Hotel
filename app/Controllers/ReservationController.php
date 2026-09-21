<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Exceptions\ForbiddenException;
use App\Core\Request;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\ReservationService;

/**
 * Reservation management for staff/admin (the guest-facing journey lives in
 * BookingController).
 */
final class ReservationController
{
    public function index(Request $request): void
    {
        view('admin/reservations/index', [
            'title'       => 'Reservations - ' . config('app.name'),
            'reservations'=> Reservation::all($request->all()),
            'filters'     => $request->all(),
        ]);
    }

    public function create(Request $request): void
    {
        $checkIn  = (string) $request->input('check_in', date('Y-m-d'));
        $checkOut = (string) $request->input('check_out', date('Y-m-d', strtotime('+1 day')));

        view('admin/reservations/form', [
            'title'     => 'New Reservation - ' . config('app.name'),
            'guests'    => Guest::all(),
            'rooms'     => Room::availableBetween($checkIn, $checkOut),
            'roomTypes' => RoomType::all(),
            'checkIn'   => $checkIn,
            'checkOut'  => $checkOut,
            'guestId'   => $request->input('guest_id', ''),
        ]);
    }

    public function store(Request $request): void
    {
        $service = new ReservationService();
        $reservation = $service->create($request->all(), (int) auth()->id());

        session()->flash('success', 'Reservation ' . $reservation['reference'] . ' created.');
        redirect('reservations/' . $reservation['id']);
    }

    public function show(Request $request, int $id): void
    {
        $reservation = Reservation::find($id)
            ?? throw new \App\Core\Exceptions\NotFoundException('Reservation not found.');

        view('admin/reservations/show', [
            'title'      => $reservation['reference'] . ' - ' . config('app.name'),
            'reservation'=> $reservation,
            'payments'   => \App\Models\Payment::forReservation($id),
            'checkIn'    => \App\Models\CheckIn::forReservation($id),
            'checkOut'   => \App\Models\CheckOut::forReservation($id),
        ]);
    }

    /** Admin/staff override of a reservation's status, e.g. mark no-show. */
    public function updateStatus(Request $request, int $id): void
    {
        $reservation = Reservation::find($id)
            ?? throw new \App\Core\Exceptions\NotFoundException('Reservation not found.');

        $allowed = [
            Reservation::STATUS_PENDING,
            Reservation::STATUS_CONFIRMED,
            Reservation::STATUS_NO_SHOW,
            Reservation::STATUS_CANCELLED,
        ];

        $status = (string) $request->input('status', '');

        if (!in_array($status, $allowed, true)) {
            throw new \App\Core\Exceptions\ValidationException(['status' => 'Invalid status for this action.']);
        }

        // "No show" is only possible on a confirmed reservation.
        if ($status === Reservation::STATUS_NO_SHOW
            && !in_array($reservation['status'], [
                Reservation::STATUS_CONFIRMED,
                Reservation::STATUS_PENDING,
            ], true)) {
            throw new \App\Core\Exceptions\ValidationException(
                ['status' => 'Only pending or confirmed reservations can be marked as no-show.'],
                'This reservation is not eligible for a no-show status.'
            );
        }

        Reservation::updateStatus($id, $status);
        session()->flash('success', 'Reservation ' . $reservation['reference'] . ' marked as ' . str_replace('_', ' ', $status) . '.');
        redirect('reservations/' . $id);
    }

    public function cancel(Request $request, int $id): void
    {
        $reservation = Reservation::find($id)
            ?? throw new \App\Core\Exceptions\NotFoundException('Reservation not found.');

        $service = new ReservationService();
        $service->cancel($reservation, (int) auth()->id());

        session()->flash('success', 'Reservation ' . $reservation['reference'] . ' cancelled.');
        redirect('reservations/' . $id);
    }
}
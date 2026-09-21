<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Exceptions\NotFoundException;
use App\Core\Exceptions\ForbiddenException;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\ReservationService;

/**
 * Guest self-service: browse rooms, book, view and cancel their own
 * reservations.
 *
 * SECURITY: the guest id is always resolved from the authenticated session,
 * never from a URL/query parameter. A logged-in guest can only ever act on
 * reservations linked to their own guest profile.
 */
final class BookingController
{
    private function currentGuest(): array
    {
        $guest = Guest::findByUserId((int) auth()->id());

        if (!$guest) {
            throw new NotFoundException('Your guest profile was not found. Please contact the front desk.');
        }

        return $guest;
    }

    /** The booking wizard: pick dates, then a room. */
    public function book(Request $request): void
    {
        $checkIn  = (string) $request->input('check_in', date('Y-m-d'));
        $checkOut = (string) $request->input('check_out', date('Y-m-d', strtotime('+2 day')));
        $valid    = true;

        if (!strtotime($checkIn) || !strtotime($checkOut) || strtotime($checkOut) <= strtotime($checkIn)) {
            $valid = false;
            $checkIn  = date('Y-m-d');
            $checkOut = date('Y-m-d', strtotime('+2 day'));
        }

        $rooms = $valid ? Room::availableBetween($checkIn, $checkOut, $request->all()) : [];

        view('guest/book', [
            'title'    => 'Book a Room - ' . config('app.name'),
            'checkIn'  => $checkIn,
            'checkOut' => $checkOut,
            'rooms'    => $rooms,
            'roomTypes'=> RoomType::all(),
            'valid'    => $valid,
        ]);
    }

    /** Creates a reservation for the authenticated guest. */
    public function store(Request $request): void
    {
        $guest = $this->currentGuest();

        $data = Validator::validate($request->all(), [
            'room_id'      => ['required', 'integer'],
            'check_in'     => ['required', 'date'],
            'check_out'    => ['required', 'date', 'after:check_in'],
            'guests_count' => ['required', 'integer', 'min_value:1', 'max_value:50'],
            'special_requests' => ['max:1000'],
        ]);

        $service = new ReservationService();
        $reservation = $service->create($data + ['guest_id' => (int) $guest['id']], (int) auth()->id());

        session()->flash('success', 'Reservation ' . $reservation['reference'] . ' confirmed. See you soon!');
        redirect('account/reservations/' . $reservation['id']);
    }

    public function myReservations(Request $request): void
    {
        view('guest/reservations', [
            'title'  => 'My Reservations - ' . config('app.name'),
            'reservations' => Reservation::forGuest((int) auth()->id()),
        ]);
    }

    public function show(Request $request, int $id): void
    {
        $reservation = $this->ownReservation($id);

        view('guest/reservation-show', [
            'title'      => $reservation['reference'] . ' - ' . config('app.name'),
            'reservation'=> $reservation,
            'payments'   => \App\Models\Payment::forReservation((int) $reservation['id']),
        ]);
    }

    public function cancel(Request $request, int $id): void
    {
        $reservation = $this->ownReservation($id);

        $service = new ReservationService();
        $service->cancel($reservation, (int) auth()->id());

        session()->flash('success', 'Your reservation ' . $reservation['reference'] . ' has been cancelled.');
        redirect('account/reservations');
    }

    /** Loads a reservation and refuses unless it belongs to this guest. */
    private function ownReservation(int $id): array
    {
        $reservation = Reservation::find($id)
            ?? throw new NotFoundException('Reservation not found.');

        if ((int) $reservation['guest_id'] !== (int) $this->currentGuest()['id']) {
            throw new ForbiddenException('You can only view your own reservations.');
        }

        return $reservation;
    }
}
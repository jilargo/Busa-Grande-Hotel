<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\ValidationException;
use App\Core\Logger;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;

/**
 * Reservation business rules live here — the single authority for "can we
 * accept this booking?" so controllers stay thin and the logic can be tested.
 *
 * Enforced rules:
 *  - valid dates, duration >= 1 night
 *  - guest count within room capacity
 *  - room exists and is physically bookable
 *  - NO overlapping active reservation for the same room (checked on the
 *    server, not just in JavaScript)
 *
 * Creating a reservation touches one table only, but the process is wrapped
 * in a transaction because failures mid-way (e.g. reference collision) must
 * not leave partial rows.
 */
final class ReservationService
{
    /**
     * Validates and creates a reservation. Returns the new reservation row.
     *
     * @throws ValidationException with a field => message map on any failure
     */
    public function create(array $input, ?int $userId = null): array
    {
        $errors = [];

        $guest = isset($input['guest_id']) ? Guest::find((int) $input['guest_id']) : null;
        $roomId  = (int) ($input['room_id'] ?? 0);
        $room    = Room::find($roomId);
        $checkIn = (string) ($input['check_in'] ?? '');
        $checkOut = (string) ($input['check_out'] ?? '');
        $guests  = (int) ($input['guests_count'] ?? 1);

        if (!$guest) {
            $errors['guest_id'] = 'Please select a valid guest.';
        }

        if (!$room) {
            $errors['room_id'] = 'The selected room no longer exists.';
        }

        if ($checkIn === '' || !strtotime($checkIn)) {
            $errors['check_in'] = 'A valid check-in date is required.';
        }
        if ($checkOut === '' || !strtotime($checkOut)) {
            $errors['check_out'] = 'A valid check-out date is required.';
        }

        if (isset($errors['check_in'], $errors['check_out'])) {
            // Nothing else can be meaningfully checked yet.
            throw new ValidationException($errors);
        }

        if ($checkIn !== '' && $checkOut !== '' && strtotime($checkOut) <= strtotime($checkIn)) {
            $errors['check_out'] = 'Check-out must be at least one day after check-in.';
        }

        if ($room && $checkIn !== '' && $checkOut !== '' && strtotime($checkOut) > strtotime($checkIn)) {
            if ($guests < 1) {
                $errors['guests_count'] = 'At least one guest is required.';
            } elseif ($guests > (int) $room['capacity']) {
                $errors['guests_count'] = 'This room sleeps a maximum of ' . $room['capacity'] . ' guests.';
            }

            // THE critical server-side availability check.
            if (!Room::canBeBooked($roomId, $checkIn, $checkOut)) {
                $errors['room_id'] = 'This room is no longer available for the selected dates.';
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $nights = Reservation::nightsBetween($checkIn, $checkOut);
        $price  = (float) $room['price_per_night'];
        $total  = $price * $nights;

        db()->beginTransaction();

        try {
            // Explicitly retry in the rare case two bookings generate the same
            // reference simultaneously.
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $reference = Reservation::generateReference();
                $resId = Reservation::create([
                    'reference'            => $reference,
                    'guest_id'             => (int) $guest['id'],
                    'room_id'              => $roomId,
                    'user_id'              => $userId,
                    'check_in'             => $checkIn,
                    'check_out'            => $checkOut,
                    'guests_count'         => $guests,
                    'nights'               => $nights,
                    'room_price_snapshot'  => $price,
                    'total_amount'         => $total,
                    'status'               => $input['status'] ?? Reservation::STATUS_CONFIRMED,
                    'special_requests'     => $input['special_requests'] ?? null,
                ]);

                if ($resId > 0) {
                    db()->commit();
                    return Reservation::find($resId) ?? [];
                }
            }

            throw new \RuntimeException('Could not generate a unique booking reference.');
        } catch (\Throwable $e) {
            db()->rollBack();
            Logger::error('Reservation creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Cancels an eligible reservation (pending or confirmed). The room is
     * released immediately because it was never physically occupied.
     */
    public function cancel(array $reservation, ?int $byUserId): void
    {
        if (!in_array($reservation['status'], [
            Reservation::STATUS_PENDING,
            Reservation::STATUS_CONFIRMED,
        ], true)) {
            throw new ValidationException([
                'status' => 'Only pending or confirmed reservations can be cancelled.',
            ], 'This reservation can no longer be cancelled.');
        }

        db()->beginTransaction();
        try {
            Reservation::updateStatus((int) $reservation['id'], Reservation::STATUS_CANCELLED);
            db()->commit();
        } catch (\Throwable $e) {
            db()->rollBack();
            throw $e;
        }
    }
}
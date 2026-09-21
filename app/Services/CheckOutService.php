<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\ValidationException;
use App\Core\Logger;
use App\Models\CheckOut;
use App\Models\Reservation;
use App\Models\Room;

/**
 * Check-out workflow, including the physical room-state transition.
 *
 *   Occupied  ──check-out──▶  Cleaning  ──(housekeeping)──▶  Available
 *
 * The room is NOT made available instantly: it moves to "cleaning" so the
 * front desk can hand it to housekeeping. Staff set it back to Available
 * from the Rooms screen once it is ready (the Overlap guard still prevents
 * double-bookings in the meantime).
 *
 * Transaction required: three related rows change together.
 */
final class CheckOutService
{
    public function checkOut(int $reservationId, int $byUserId, ?string $notes = null): array
    {
        $reservation = Reservation::find($reservationId);

        if (!$reservation) {
            throw new ValidationException(['reservation_id' => 'Reservation not found.']);
        }

        if ($reservation['status'] !== Reservation::STATUS_CHECKED_IN) {
            throw new ValidationException(
                ['status' => 'Only checked-in reservations can be checked out.'],
                'This reservation is not currently checked in.'
            );
        }

        db()->beginTransaction();

        try {
            CheckOut::create(
                (int) $reservation['id'],
                (int) $reservation['room_id'],
                (int) $reservation['guest_id'],
                $byUserId,
                date('Y-m-d H:i:s'),
                $notes
            );

            Reservation::updateStatus((int) $reservation['id'], Reservation::STATUS_CHECKED_OUT);
            Room::updateStatus((int) $reservation['room_id'], Room::STATUS_CLEANING);

            db()->commit();
        } catch (\Throwable $e) {
            db()->rollBack();
            Logger::error('Check-out failed, rolled back', [
                'reservation_id' => $reservationId,
                'error'          => $e->getMessage(),
            ]);
            throw $e;
        }

        return Reservation::find((int) $reservation['id']) ?? [];
    }
}
<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\ValidationException;
use App\Core\Logger;
use App\Models\CheckIn;
use App\Models\Reservation;
use App\Models\Room;

/**
 * Check-in workflow.
 *
 * A check-in changes THREE related rows: a check_ins audit row, the
 * reservation status, and the room status. If any step fails they must all
 * roll back together — that is why this runs inside an explicit transaction.
 *
 *   BEGIN
 *     INSERT INTO check_ins           (who, when, which reservation/room)
 *     UPDATE reservations SET status = 'checked_in'
 *     UPDATE rooms SET status = 'occupied'
 *   COMMIT  |  ROLLBACK
 */
final class CheckInService
{
    public function checkIn(int $reservationId, int $byUserId): array
    {
        $reservation = Reservation::find($reservationId);

        if (!$reservation) {
            throw new ValidationException(['reservation_id' => 'Reservation not found.']);
        }

        if (!in_array($reservation['status'], [
            Reservation::STATUS_CONFIRMED,
            Reservation::STATUS_PENDING,
        ], true)) {
            throw new ValidationException(
                ['status' => 'Only confirmed reservations can be checked in when guests arrive.'],
                'This reservation is not in a check-in-able state.'
            );
        }

        $room = Room::find((int) $reservation['room_id']);
        if (!$room) {
            throw new ValidationException(['room_id' => 'The assigned room no longer exists.']);
        }

        db()->beginTransaction();

        try {
            CheckIn::create(
                (int) $reservation['id'],
                (int) $reservation['room_id'],
                (int) $reservation['guest_id'],
                $byUserId,
                date('Y-m-d H:i:s')
            );

            Reservation::updateStatus((int) $reservation['id'], Reservation::STATUS_CHECKED_IN);
            Room::updateStatus((int) $reservation['room_id'], Room::STATUS_OCCUPIED);

            db()->commit();
        } catch (\Throwable $e) {
            db()->rollBack();
            Logger::error('Check-in failed, rolled back', [
                'reservation_id' => $reservationId,
                'error'          => $e->getMessage(),
            ]);
            throw $e;
        }

        return Reservation::find((int) $reservation['id']) ?? [];
    }
}
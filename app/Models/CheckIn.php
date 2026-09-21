<?php

declare(strict_types=1);

namespace App\Models;

final class CheckIn
{
    public static function create(int $reservationId, int $roomId, int $guestId, int $userId, string $actualCheckIn): void
    {
        $stmt = db()->prepare(
            'INSERT INTO check_ins (reservation_id, room_id, guest_id, checked_in_by, actual_check_in)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$reservationId, $roomId, $guestId, $userId, $actualCheckIn]);
    }

    public static function forReservation(int $reservationId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM check_ins WHERE reservation_id = ?');
        $stmt->execute([$reservationId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function recent(int $limit = 8): array
    {
        $stmt = db()->prepare(
            'SELECT ci.*, g.first_name, g.last_name, r.reference, rm.room_number
               FROM check_ins ci
          LEFT JOIN reservations r  ON r.id = ci.reservation_id
          LEFT JOIN guests g        ON g.id = ci.guest_id
          LEFT JOIN rooms rm        ON rm.id = ci.room_id
           ORDER BY ci.actual_check_in DESC
              LIMIT ?'
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }
}
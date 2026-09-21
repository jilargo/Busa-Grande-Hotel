<?php

declare(strict_types=1);

namespace App\Models;

final class CheckOut
{
    public static function create(int $reservationId, int $roomId, int $guestId, int $userId, string $actualCheckOut, ?string $notes): void
    {
        $stmt = db()->prepare(
            'INSERT INTO check_outs (reservation_id, room_id, guest_id, checked_out_by, actual_check_out, notes)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$reservationId, $roomId, $guestId, $userId, $actualCheckOut, $notes]);
    }

    public static function forReservation(int $reservationId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM check_outs WHERE reservation_id = ?');
        $stmt->execute([$reservationId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function recent(int $limit = 8): array
    {
        $stmt = db()->prepare(
            'SELECT co.*, g.first_name, g.last_name, r.reference, rm.room_number
               FROM check_outs co
          LEFT JOIN reservations r  ON r.id = co.reservation_id
          LEFT JOIN guests g        ON g.id = co.guest_id
          LEFT JOIN rooms rm        ON rm.id = co.room_id
           ORDER BY co.actual_check_out DESC
              LIMIT ?'
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }
}
<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Reservation queries. The "nights" and "total_amount" values are stored at
 * booking time on purpose: they act as a billing snapshot so a later room
 * price change never alters an existing booking.
 */
final class Reservation
{
    public const STATUS_PENDING    = 'pending';
    public const STATUS_CONFIRMED  = 'confirmed';
    public const STATUS_CHECKED_IN = 'checked_in';
    public const STATUS_CHECKED_OUT = 'checked_out';
    public const STATUS_CANCELLED  = 'cancelled';
    public const STATUS_NO_SHOW    = 'no_show';

    /** Statuses that still occupy a room on the requested dates. */
    public const ACTIVE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_CHECKED_IN,
    ];

    private const SELECT_SQL = '
        SELECT r.*,
               g.first_name AS guest_first_name, g.last_name AS guest_last_name,
               g.email AS guest_email, g.phone AS guest_phone,
               rm.room_number, rm.status AS room_status,
               rt.name AS room_type_name,
               (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.reservation_id = r.id)
                   AS total_paid
          FROM reservations r
     LEFT JOIN guests g   ON g.id = r.guest_id
     LEFT JOIN rooms rm   ON rm.id = r.room_id
     LEFT JOIN room_types rt ON rt.id = rm.room_type_id';

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare(self::SELECT_SQL . ' WHERE r.id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByReference(string $reference): ?array
    {
        $stmt = db()->prepare(self::SELECT_SQL . ' WHERE r.reference = ?');
        $stmt->execute([$reference]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Generates a short, human-friendly booking reference (e.g. BRV-3F8A2C). */
    public static function generateReference(): string
    {
        return 'BRV-' . strtoupper(bin2hex(random_bytes(3)));
    }

    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO reservations
                (reference, guest_id, room_id, user_id, check_in, check_out,
                 guests_count, nights, room_price_snapshot, total_amount, status,
                 special_requests)
             VALUES
                (:reference, :guest_id, :room_id, :user_id, :check_in, :check_out,
                 :guests_count, :nights, :room_price_snapshot, :total_amount, :status,
                 :special_requests)'
        );

        \App\Core\Database::execute($stmt, [
            ':reference'           => $data['reference'],
            ':guest_id'            => $data['guest_id'],
            ':room_id'             => $data['room_id'],
            ':user_id'             => $data['user_id'] ?? null,
            ':check_in'            => $data['check_in'],
            ':check_out'           => $data['check_out'],
            ':guests_count'        => $data['guests_count'],
            ':nights'              => $data['nights'],
            ':room_price_snapshot' => $data['room_price_snapshot'],
            ':total_amount'        => $data['total_amount'],
            ':status'              => $data['status'],
            ':special_requests'    => $data['special_requests'] ?? null,
        ]);

        return (int) db()->lastInsertId();
    }

    public static function updateStatus(int $id, string $status): void
    {
        $stmt = db()->prepare('UPDATE reservations SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public static function nightsBetween(string $checkIn, string $checkOut): int
    {
        return max(1, (int) ((strtotime($checkOut) - strtotime($checkIn)) / 86400));
    }

    /** Derives the reservation-level payment status from payments made. */
    public static function paymentStatus(array $reservation): string
    {
        $paid   = (float) $reservation['total_paid'];
        $total  = (float) $reservation['total_amount'];

        if ($paid < 0) {
            return 'refunded';
        }
        if ($paid >= $total - 0.009) {
            return 'paid';
        }
        if ($paid > 0) {
            return 'partial';
        }
        return 'pending';
    }

    public static function all(array $filters = []): array
    {
        $sql = self::SELECT_SQL;
        $where  = [];
        $params = [];

        if (($filters['q'] ?? '') !== '') {
            $q = '%' . $filters['q'] . '%';
            $where[] = '(r.reference LIKE ? OR rm.room_number LIKE ?
                         OR g.first_name LIKE ? OR g.last_name LIKE ?)';
            array_push($params, $q, $q, $q, $q);
        }
        if (($filters['status'] ?? '') !== '') {
            $where[] = 'r.status = ?';
            $params[] = $filters['status'];
        }
        if (($filters['check_in_from'] ?? '') !== '') {
            $where[] = 'r.check_in >= ?';
            $params[] = $filters['check_in_from'];
        }
        if (($filters['check_in_to'] ?? '') !== '') {
            $where[] = 'r.check_in <= ?';
            $params[] = $filters['check_in_to'];
        }
        if (($filters['guest_id'] ?? '') !== '') {
            $where[] = 'r.guest_id = ?';
            $params[] = $filters['guest_id'];
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY r.check_in DESC, r.id DESC';
        $stmt = db()->prepare($sql);
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /** All reservations belonging to the guest profile tied to a user id. */
    public static function forGuest(int $userId): array
    {
        $stmt = db()->prepare(
            self::SELECT_SQL . '
               WHERE g.user_id = ?
            ORDER BY r.check_in DESC, r.id DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function countByStatus(): array
    {
        $rows  = db()->query('SELECT status, COUNT(*) AS total FROM reservations GROUP BY status')->fetchAll() ?: [];
        $counts = [
            self::STATUS_PENDING => 0, self::STATUS_CONFIRMED => 0, self::STATUS_CHECKED_IN => 0,
            self::STATUS_CHECKED_OUT => 0, self::STATUS_CANCELLED => 0, self::STATUS_NO_SHOW => 0,
        ];
        foreach ($rows as $row) {
            if (isset($counts[$row['status']])) {
                $counts[$row['status']] = (int) $row['total'];
            }
        }
        return $counts;
    }

    public static function recent(int $limit = 8): array
    {
        $stmt = db()->prepare(self::SELECT_SQL . ' ORDER BY r.created_at DESC, r.id DESC LIMIT ?');
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function forDate(string $date, string $what): array
    {
        $column = $what === 'check_in' ? 'r.check_in' : 'r.check_out';

        $statuses = $what === 'check_in'
            ? [self::STATUS_CONFIRMED, self::STATUS_PENDING, self::STATUS_CHECKED_IN]
            : [self::STATUS_CHECKED_IN, self::STATUS_CONFIRMED];

        $sql = self::SELECT_SQL . "
             WHERE {$column} = ?
               AND r.status IN ('" . implode("','", $statuses) . "')
          ORDER BY {$column}";

        $stmt = db()->prepare($sql);
        $stmt->execute([$date]);
        return $stmt->fetchAll() ?: [];
    }

    public static function destroy(int $id): void
    {
        db()->prepare('DELETE FROM reservations WHERE id = ?')->execute([$id]);
    }
}
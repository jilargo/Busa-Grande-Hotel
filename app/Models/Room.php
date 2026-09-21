<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Room queries.
 *
 * Availability rules used by the reservation logic (both here in
 * canBeBooked() and enforced again inside ReservationService):
 *  - the physical room must not be under maintenance or being cleaned, and
 *  - no active reservation (pending/confirmed/checked-in) may overlap the
 *    requested date range.
 *
 * IMPORTANT: the overlapping-window check is  a < check_out AND check_out > a,
 * which treats a guest leaving on the morning of their check-out date. The
 * room can therefore be re-booked from the afternoon of that same day.
 */
final class Room
{
    public const STATUS_AVAILABLE  = 'available';
    public const STATUS_RESERVED   = 'reserved';
    public const STATUS_OCCUPIED   = 'occupied';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_CLEANING   = 'cleaning';

    /** Statuses that physically block a room from being newly booked. */
    private const BLOCKED_STATUSES = [
        self::STATUS_OCCUPIED,
        self::STATUS_MAINTENANCE,
        self::STATUS_CLEANING,
    ];

    /** SQL fragment for the overlapping-date check (see class doc). */
    public const OVERLAP_SQL = 'check_in < :check_out AND check_out > :check_in';

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT r.*, rt.name AS room_type_name, rt.base_price AS room_type_base_price
               FROM rooms r
          LEFT JOIN room_types rt ON rt.id = r.room_type_id
              WHERE r.id = ?'
        );
        $stmt->execute([$id]);
        $room = $stmt->fetch();
        return $room ?: null;
    }

    public static function findByNumber(string $number): ?array
    {
        $stmt = db()->prepare('SELECT * FROM rooms WHERE room_number = ?');
        $stmt->execute([$number]);
        $room = $stmt->fetch();
        return $room ?: null;
    }

    public static function all(array $filters = []): array
    {
        $sql = 'SELECT r.*, rt.name AS room_type_name, rt.base_price AS room_type_base_price
                  FROM rooms r
             LEFT JOIN room_types rt ON rt.id = r.room_type_id';
        $where  = [];
        $params = [];

        if (($filters['q'] ?? '') !== '') {
            $where[] = 'r.room_number LIKE :q';
            $params[':q'] = '%' . $filters['q'] . '%';
        }
        if (($filters['room_type_id'] ?? '') !== '') {
            $where[] = 'r.room_type_id = :room_type_id';
            $params[':room_type_id'] = $filters['room_type_id'];
        }
        if (($filters['status'] ?? '') !== '') {
            $where[] = 'r.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (($filters['min_capacity'] ?? '') !== '') {
            $where[] = 'r.capacity >= :min_capacity';
            $params[':min_capacity'] = $filters['min_capacity'];
        }
        if (($filters['max_price'] ?? '') !== '') {
            $where[] = 'r.price_per_night <= :max_price';
            $params[':max_price'] = $filters['max_price'];
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY r.room_number';
        $stmt = db()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO rooms
                (room_number, room_type_id, floor, capacity, price_per_night, status,
                 description, amenities, image)
             VALUES
                (:room_number, :room_type_id, :floor, :capacity, :price_per_night, :status,
                 :description, :amenities, :image)'
        );

        \App\Core\Database::execute($stmt, [
            ':room_number'     => $data['room_number'],
            ':room_type_id'    => $data['room_type_id'],
            ':floor'           => $data['floor'],
            ':capacity'        => $data['capacity'],
            ':price_per_night' => $data['price_per_night'],
            ':status'          => $data['status'] ?? self::STATUS_AVAILABLE,
            ':description'     => $data['description'] ?? null,
            ':amenities'       => $data['amenities'] ?? null,
            ':image'           => $data['image'] ?? null,
        ]);

        return (int) db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = db()->prepare(
            'UPDATE rooms SET
                room_number = :room_number, room_type_id = :room_type_id, floor = :floor,
                capacity = :capacity, price_per_night = :price_per_night, status = :status,
                description = :description, amenities = :amenities, image = :image
             WHERE id = :id'
        );

        \App\Core\Database::execute($stmt, [
            ':room_number'     => $data['room_number'],
            ':room_type_id'    => $data['room_type_id'],
            ':floor'           => $data['floor'],
            ':capacity'        => $data['capacity'],
            ':price_per_night' => $data['price_per_night'],
            ':status'          => $data['status'],
            ':description'     => $data['description'] ?? null,
            ':amenities'       => $data['amenities'] ?? null,
            ':image'           => $data['image'] ?? null,
            ':id'              => $id,
        ]);
    }

    public static function updateStatus(int $id, string $status): void
    {
        $stmt = db()->prepare('UPDATE rooms SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    /** Deletes a room — fails at the DB level if reservations reference it. */
    public static function destroy(int $id): void
    {
        db()->prepare('DELETE FROM rooms WHERE id = ?')->execute([$id]);
    }

    /**
     * True when the room has no blocking status and no overlapping active
     * reservation. This is the authoritative availability check also used by
     * the reservation service. Exclude a reservation id when editing one.
     */
    public static function canBeBooked(int $roomId, string $checkIn, string $checkOut, ?int $excludeReservationId = null): bool
    {
        $room = self::find($roomId);

        if (!$room || in_array($room['status'], self::BLOCKED_STATUSES, true)) {
            return false;
        }

        $sql = 'SELECT id FROM reservations
                 WHERE room_id = :room_id
                   AND status IN (:pending, :confirmed, :checked_in)
                   AND ' . self::OVERLAP_SQL;

        if ($excludeReservationId !== null) {
            $sql .= ' AND id <> :exclude_id';
        }

        $sql .= ' LIMIT 1';

        $stmt = db()->prepare($sql);
        $stmt->bindValue(':room_id', $roomId);
        $stmt->bindValue(':check_in', $checkIn);
        $stmt->bindValue(':check_out', $checkOut);
        $stmt->bindValue(':pending', Reservation::STATUS_PENDING);
        $stmt->bindValue(':confirmed', Reservation::STATUS_CONFIRMED);
        $stmt->bindValue(':checked_in', Reservation::STATUS_CHECKED_IN);
        if ($excludeReservationId !== null) {
            $stmt->bindValue(':exclude_id', $excludeReservationId);
        }
        $stmt->execute();

        return $stmt->fetch() === false;
    }

    /**
     * Rooms that are free for the given stay (used for booking and the AJAX
     * availability picker).
     */
    public static function availableBetween(string $checkIn, string $checkOut, array $filters = []): array
    {
        $sql = 'SELECT r.*, rt.name AS room_type_name, rt.base_price AS room_type_base_price
                  FROM rooms r
             LEFT JOIN room_types rt ON rt.id = r.room_type_id
                 WHERE r.status IN (:available, :reserved)
                   AND NOT EXISTS (
SELECT 1 FROM reservations res
                         WHERE res.room_id = r.id
                           AND res.status IN (:pending, :confirmed, :checked_in)
                           AND res.check_in < :check_out AND res.check_out > :check_in
                   )';

        $params = [
            ':available' => self::STATUS_AVAILABLE,
            ':reserved'  => self::STATUS_RESERVED,
            ':pending'   => Reservation::STATUS_PENDING,
            ':confirmed' => Reservation::STATUS_CONFIRMED,
            ':checked_in'=> Reservation::STATUS_CHECKED_IN,
            ':check_in'  => $checkIn,
            ':check_out' => $checkOut,
        ];

        if (($filters['room_type_id'] ?? '') !== '') {
            $sql .= ' AND r.room_type_id = :room_type_id';
            $params[':room_type_id'] = $filters['room_type_id'];
        }
        if (($filters['guests'] ?? '') !== '') {
            $sql .= ' AND r.capacity >= :guests';
            $params[':guests'] = $filters['guests'];
        }

        $sql .= ' ORDER BY r.price_per_night';

        $stmt = db()->prepare($sql);
        // Use named params with correct re-binding pattern
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /** Also books reservations that are just "reserved"-marked as available. */
    public static function statusCounts(): array
    {
        $rows = db()->query('SELECT status, COUNT(*) AS total FROM rooms GROUP BY status')->fetchAll() ?: [];
        $counts = [
            self::STATUS_AVAILABLE => 0, self::STATUS_RESERVED => 0, self::STATUS_OCCUPIED => 0,
            self::STATUS_MAINTENANCE => 0, self::STATUS_CLEANING => 0,
        ];
        foreach ($rows as $row) {
            if (isset($counts[$row['status']])) {
                $counts[$row['status']] = (int) $row['total'];
            }
        }
        return $counts;
    }

    public static function count(): int
    {
        return (int) db()->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
    }
}
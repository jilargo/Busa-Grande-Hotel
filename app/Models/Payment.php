<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Payment records. Each record is one transaction against a reservation.
 * A refund is recorded as a separate record with a negative amount, which
 * keeps the SUM(amount) calculation straightforward.
 */
final class Payment
{
    /** Valid payment methods (a real gateway could extend these later). */
    public const METHODS = [
        'cash',
        'bank_transfer',
        'credit_card',
        'debit_card',
        'other',
    ];

    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REFUNDED  = 'refunded';

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT p.*, r.reference, u.name AS recorded_by_name
               FROM payments p
          LEFT JOIN reservations r ON r.id = p.reservation_id
          LEFT JOIN users u ON u.id = p.recorded_by
              WHERE p.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO payments
                (reservation_id, amount, method, status, reference_number, payment_date, recorded_by, notes)
             VALUES
                (:reservation_id, :amount, :method, :status, :reference_number, :payment_date, :recorded_by, :notes)'
        );

        \App\Core\Database::execute($stmt, [
            ':reservation_id'  => $data['reservation_id'],
            ':amount'          => $data['amount'],
            ':method'          => $data['method'],
            ':status'          => $data['status'],
            ':reference_number'=> $data['reference_number'] ?? null,
            ':payment_date'    => $data['payment_date'],
            ':recorded_by'     => $data['recorded_by'] ?? null,
            ':notes'           => $data['notes'] ?? null,
        ]);

        return (int) db()->lastInsertId();
    }

    public static function forReservation(int $reservationId): array
    {
        $stmt = db()->prepare(
            'SELECT p.*, u.name AS recorded_by_name
               FROM payments p
          LEFT JOIN users u ON u.id = p.recorded_by
              WHERE p.reservation_id = ?
           ORDER BY p.payment_date DESC, p.id DESC'
        );
        $stmt->execute([$reservationId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function totalForReservation(int $reservationId): float
    {
        $stmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE reservation_id = ?');
        $stmt->execute([$reservationId]);
        return (float) $stmt->fetchColumn();
    }

    public static function recent(int $limit = 8): array
    {
        $stmt = db()->prepare(
            'SELECT p.*, r.reference, g.first_name, g.last_name
               FROM payments p
          LEFT JOIN reservations r ON r.id = p.reservation_id
          LEFT JOIN guests g       ON g.id = r.guest_id
           ORDER BY p.payment_date DESC, p.id DESC
              LIMIT ?'
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }
}
<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Guest (customer) records. Guests may be linked to a user account
 * (user_id) or be walk-in guests created by front-desk staff.
 */
final class Guest
{
    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM guests WHERE id = ?');
        $stmt->execute([$id]);
        $guest = $stmt->fetch();
        return $guest ?: null;
    }

    public static function findByUserId(int $userId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM guests WHERE user_id = ?');
        $stmt->execute([$userId]);
        $guest = $stmt->fetch();
        return $guest ?: null;
    }

    public static function create(array $data): int
    {
        $sql = 'INSERT INTO guests
                    (user_id, first_name, last_name, email, phone, address, city, country,
                     date_of_birth, id_type, id_number, emergency_contact_name,
                     emergency_contact_phone, notes)
                VALUES
                    (:user_id, :first_name, :last_name, :email, :phone, :address, :city, :country,
                     :date_of_birth, :id_type, :id_number, :emergency_contact_name,
                     :emergency_contact_phone, :notes)';

        $stmt = db()->prepare($sql);
        \App\Core\Database::execute($stmt, [
            ':user_id'                => $data['user_id'] ?? null,
            ':first_name'             => $data['first_name'],
            ':last_name'              => $data['last_name'],
            ':email'                  => $data['email'] ?? null,
            ':phone'                  => $data['phone'] ?? null,
            ':address'                => $data['address'] ?? null,
            ':city'                   => $data['city'] ?? null,
            ':country'                => $data['country'] ?? null,
            ':date_of_birth'          => $data['date_of_birth'] ?? null,
            ':id_type'                => $data['id_type'] ?? null,
            ':id_number'              => $data['id_number'] ?? null,
            ':emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            ':emergency_contact_phone'=> $data['emergency_contact_phone'] ?? null,
            ':notes'                  => $data['notes'] ?? null,
        ]);

        return (int) db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = db()->prepare(
            'UPDATE guests SET
                user_id = COALESCE(:user_id, user_id),
                first_name = :first_name,
                last_name = :last_name,
                email = :email,
                phone = :phone,
                address = :address,
                city = :city,
                country = :country,
                date_of_birth = :date_of_birth,
                id_type = :id_type,
                id_number = :id_number,
                emergency_contact_name = :emergency_contact_name,
                emergency_contact_phone = :emergency_contact_phone,
                notes = :notes
             WHERE id = :id'
        );

        \App\Core\Database::execute($stmt, [
            ':user_id'                 => $data['user_id'] ?? null,
            ':first_name'              => $data['first_name'],
            ':last_name'               => $data['last_name'],
            ':email'                   => $data['email'] ?? null,
            ':phone'                   => $data['phone'] ?? null,
            ':address'                 => $data['address'] ?? null,
            ':city'                    => $data['city'] ?? null,
            ':country'                 => $data['country'] ?? null,
            ':date_of_birth'           => $data['date_of_birth'] ?? null,
            ':id_type'                 => $data['id_type'] ?? null,
            ':id_number'               => $data['id_number'] ?? null,
            ':emergency_contact_name'  => $data['emergency_contact_name'] ?? null,
            ':emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            ':notes'                   => $data['notes'] ?? null,
            ':id'                      => $id,
        ]);
    }

    /** Searchable staff/admin list. */
    public static function all(array $filters = []): array
    {
        $sql = 'SELECT g.*, u.name AS account_name
                  FROM guests g
             LEFT JOIN users u ON u.id = g.user_id';
        $where = [];
        $params = [];

        if (($filters['q'] ?? '') !== '') {
            $q = '%' . $filters['q'] . '%';
            $where[] = '(g.first_name LIKE ? OR g.last_name LIKE ? OR g.email LIKE ? OR g.phone LIKE ?)';
            array_push($params, $q, $q, $q, $q);
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY g.created_at DESC';
        $stmt = db()->prepare($sql);
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function destroy(int $id): void
    {
        // FK constraints make this fail if the guest has reservations.
        db()->prepare('DELETE FROM guests WHERE id = ?')->execute([$id]);
    }
}
<?php

declare(strict_types=1);

namespace App\Models;

final class RoomType
{
    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM room_types WHERE id = ?');
        $stmt->execute([$id]);
        $roomType = $stmt->fetch();
        return $roomType ?: null;
    }

    public static function all(): array
    {
        return db()->query('SELECT * FROM room_types ORDER BY base_price')->fetchAll() ?: [];
    }

    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO room_types (name, slug, description, base_price, max_guests, amenities, image)
             VALUES (:name, :slug, :description, :base_price, :max_guests, :amenities, :image)'
        );

        \App\Core\Database::execute($stmt, [
            ':name'        => $data['name'],
            ':slug'        => $data['slug'],
            ':description' => $data['description'] ?? null,
            ':base_price'  => $data['base_price'],
            ':max_guests'  => $data['max_guests'],
            ':amenities'   => $data['amenities'] ?? null,
            ':image'       => $data['image'] ?? null,
        ]);

        return (int) db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = db()->prepare(
            'UPDATE room_types SET
                name = :name, slug = :slug, description = :description,
                base_price = :base_price, max_guests = :max_guests,
                amenities = :amenities, image = :image
             WHERE id = :id'
        );

        \App\Core\Database::execute($stmt, [
            ':name'        => $data['name'],
            ':slug'        => $data['slug'],
            ':description' => $data['description'] ?? null,
            ':base_price'  => $data['base_price'],
            ':max_guests'  => $data['max_guests'],
            ':amenities'   => $data['amenities'] ?? null,
            ':image'       => $data['image'] ?? null,
            ':id'          => $id,
        ]);
    }

    public static function destroy(int $id): void
    {
        db()->prepare('DELETE FROM room_types WHERE id = ?')->execute([$id]);
    }

    public static function count(): int
    {
        return (int) db()->query('SELECT COUNT(*) FROM room_types')->fetchColumn();
    }
}
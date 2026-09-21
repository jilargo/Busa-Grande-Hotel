<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Roles are stored in a table rather than a PHP enum so new roles can be
 * added without code changes. The application only ever uses three today.
 */
final class Role
{
    public const ADMIN = 'admin';
    public const STAFF = 'staff';
    public const GUEST = 'guest';

    public static function all(): array
    {
        return db()->query('SELECT * FROM roles ORDER BY id')->fetchAll() ?: [];
    }

    public static function idFor(string $name): ?int
    {
        $stmt = db()->prepare('SELECT id FROM roles WHERE name = ?');
        $stmt->execute([$name]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM roles WHERE id = ?');
        $stmt->execute([$id]);
        $role = $stmt->fetch();
        return $role ?: null;
    }
}
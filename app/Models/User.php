<?php

declare(strict_types=1);

namespace App\Models;

/**
 * User table queries. Rows returned by find()/findByEmail() always include a
 * joined "role" column so authorization checks are a single query.
 */
final class User
{
    /** Base SELECT including the role name. */
    private static function selectSql(): string
    {
        return "SELECT u.*, r.name AS role
                  FROM users u
             LEFT JOIN roles r ON r.id = u.role_id";
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare(self::selectSql() . ' WHERE u.id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = db()->prepare(self::selectSql() . ' WHERE u.email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByGoogleId(string $googleId): ?array
    {
        $stmt = db()->prepare(self::selectSql() . ' WHERE u.google_id = ?');
        $stmt->execute([$googleId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /** Creates a user and returns the new id. */
    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO users (name, email, password_hash, role_id, google_id, is_active)
             VALUES (:name, :email, :password_hash, :role_id, :google_id, :is_active)'
        );

        \App\Core\Database::execute($stmt, [
            ':name'          => $data['name'],
            ':email'         => $data['email'],
            ':password_hash' => $data['password_hash'] ?? null,
            ':role_id'       => $data['role_id'],
            ':google_id'     => $data['google_id'] ?? null,
            ':is_active'     => $data['is_active'] ?? 1,
        ]);

        return (int) db()->lastInsertId();
    }

    public static function updatePassword(int $id, string $hash): void
    {
        $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $id]);
    }

    public static function updateRememberToken(int $id, ?string $hash): void
    {
        $stmt = db()->prepare('UPDATE users SET remember_token = ? WHERE id = ?');
        $stmt->execute([$hash, $id]);
    }

    public static function updateProfile(int $id, string $name): void
    {
        $stmt = db()->prepare('UPDATE users SET name = ? WHERE id = ?');
        $stmt->execute([$name, $id]);
    }

    /** Admin user management list, with optional keyword search. */
    public static function all(string $search = ''): array
    {
        $sql = self::selectSql();

        if ($search !== '') {
            $sql .= ' WHERE u.name LIKE :q OR u.email LIKE :q';
            $stmt = db()->prepare($sql);
            $q = "%{$search}%";
            $stmt->bindValue(':q', $q);
            $stmt->execute();
            return $stmt->fetchAll() ?: [];
        }

        $stmt = db()->query($sql . ' ORDER BY u.created_at DESC');
        return $stmt->fetchAll() ?: [];
    }

    /** Ensures a user row exists for a Google-authenticated email. */
    public static function findOrCreateByGoogle(array $profile, int $roleId): array
    {
        $user = self::findByEmail($profile['email']);
        if ($user) {
            // Link the Google id to an existing password account.
            $stmt = db()->prepare('UPDATE users SET google_id = ? WHERE id = ?');
            $stmt->execute([$profile['google_id'], $user['id']]);
            return $user;
        }

        if ($user = self::findByGoogleId($profile['google_id'])) {
            return $user;
        }

        $id = self::create([
            'name'          => $profile['name'],
            'email'         => $profile['email'],
            'password_hash' => null, // passwordless account: Google is the provider
            'role_id'       => $roleId,
            'google_id'     => $profile['google_id'],
            'is_active'     => 1,
        ]);

        return self::find($id) ?? [];
    }
}
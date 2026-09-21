<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Single PDO connection for the whole request.
 *
 * PDO is configured with ERRMODE_EXCEPTION so every SQL problem surfaces
 * immediately, emulated prepares are switched OFF (real prepared statements
 * protect against SQL injection), and the MySQL timezone is aligned with PHP.
 */
final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $config = Config::get('db', []);
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['name']
        );

        self::$connection = new PDO($dsn, $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00'",
        ]);

        return self::$connection;
    }

    /**
     * Binds and runs a statement built with named (:name) or positional
     * placeholders. Native MySQL prepares require each placeholder to be
     * bound exactly once and appear once in the SQL, so multi-use named
     * placeholders (e.g. a LIKE :q repeated) must be written as "?".
     */
    public static function execute(\PDOStatement $stmt, array $params = []): \PDOStatement
    {
        foreach ($params as $key => $value) {
            if (is_int($key)) {
                $stmt->bindValue($key + 1, $value);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();

        return $stmt;
    }

    /** Used by the test suite to switch to an isolated test database. */
    public static function reset(?PDO $connection = null): void
    {
        self::$connection = $connection;
    }
}
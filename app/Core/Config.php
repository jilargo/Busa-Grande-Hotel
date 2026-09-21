<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Static registry that holds the application configuration array.
 * Values are resolved from environment variables in config/config.php.
 */
final class Config
{
    private static array $items = [];

    public static function load(array $items = []): void
    {
        // Defaults come from config/config.php unless a caller overrides them
        // (the test suite uses this to point at a separate test database).
        self::$items = $items !== [] ? $items : require BASE_PATH . '/config/config.php';
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        // Dot notation: "db.host" -> $items['db']['host']
        $segments = explode('.', $key);
        $value    = self::$items;

        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }

    public static function items(): array
    {
        return self::$items;
    }
}
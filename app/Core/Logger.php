<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Simple file logger writing to storage/logs.
 * Technical details go here; users only ever see friendly messages.
 */
final class Logger
{
    private static ?Logger $instance = null;
    private string $file;

    private function __construct()
    {
        $this->file = base_path('storage/logs/app.log');
        $dir        = dirname($this->file);

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    public static function getInstance(): Logger
    {
        return self::$instance ??= new self();
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        self::getInstance()->write($level, $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    public function write(string $level, string $message, array $context = []): void
    {
        $line = sprintf(
            "[%s] %s %s %s%s",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context !== [] ? json_encode($context) : '',
            PHP_EOL
        );

        @file_put_contents($this->file, $line, FILE_APPEND | LOCK_EX);
    }
}
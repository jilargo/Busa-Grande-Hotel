<?php

declare(strict_types=1);

/*
 * Runs every .sql file in database/migrations in filename order.
 * All migration files are idempotent (IF NOT EXISTS / CREATE DATABASE IF
 * NOT EXISTS), so this script can be run repeatedly without damage.
 *
 * Usage:  php scripts/migrate.php
 */

require dirname(__DIR__) . '/bootstrap/app.php';

use App\Core\Database;

$dir = BASE_PATH . '/database/migrations';

$files = glob($dir . '/*.sql');
sort($files);

if ($files === []) {
    fwrite(STDOUT, "No migration files found in database/migrations.\n");
    exit(0);
}

$pdo = Database::connection();

foreach ($files as $file) {
    $sql = file_get_contents($file);

    try {
        $pdo->exec($sql);
        echo '✓ ' . basename($file) . "\n";
    } catch (PDOException $e) {
        echo '✗ ' . basename($file) . ' — ' . $e->getMessage() . "\n";
        // Keep going so the developer sees every failing file at once,
        // then report a non-zero exit code.
        $failed = true;
    }
}

exit(isset($failed) ? 1 : 0);
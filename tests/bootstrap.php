<?php

declare(strict_types=1);

/*
 * PHPUnit bootstrap.
 *
 *  1. Loads the application exactly like a normal request,
 *  2. points the database at a dedicated test database (<base>_test)
 *     and switches the DB user to the local root account (the app user is
 *     only granted rights on the dev database),
 *  3. rebuilds the test database from the migrations in database/migrations,
 *  4. seeds the minimal base accounts (roles + users).
 *
 * The dev database is never touched: the database name is redirected BEFORE
 * the app boots.
 */

require __DIR__ . '/../vendor/autoload.php';

// Redirect the app to a dedicated test database, connecting as root because
// the application user is scoped to the dev database only.
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$baseDb = getenv('DB_DATABASE') ?: 'busa_grande_hotel';
$testDb = $baseDb . '_test';

putenv('DB_DATABASE=' . $testDb);
$_ENV['DB_DATABASE']   = $testDb;
$_SERVER['DB_DATABASE'] = $testDb;

putenv('DB_USERNAME=root');
$_ENV['DB_USERNAME'] = 'root';

putenv('DB_PASSWORD=');
$_ENV['DB_PASSWORD'] = '';

// Boot the application (Config reads the overridden env values).
require dirname(__DIR__) . '/bootstrap/app.php';

use App\Core\Config;
use App\Core\Database;

// Create the test database first via a server-level connection (the app DSN
// already names the test database, which must exist before it can connect).
$db = Config::get('db');
$server = new PDO(
    sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $db['host'], $db['port']),
    $db['user'],
    $db['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$server->exec('DROP DATABASE IF EXISTS `' . $db['name'] . '`');
$server->exec(
    'CREATE DATABASE `' . $db['name'] . '`
        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
);

// Reconnect through the app and apply every migration, skipping the
// CREATE DATABASE / USE header in 001 because we are already connected
// directly to the test database.
Database::reset();
$pdo = Database::connection();

// Apply every migration, skipping the CREATE DATABASE / USE header in 001
// because we are already connected directly to the test database.
foreach (glob(dirname(__DIR__) . '/database/migrations/*.sql') as $file) {
    $sql = file_get_contents($file);
    $sql = preg_replace('/CREATE\s+DATABASE.*?;\s*USE\s+[^;]+;/is', '', $sql);
    $pdo->exec($sql);
}

// Minimal base data so foreign keys resolve in every test.
$roles = ['admin', 'staff', 'guest'];
foreach ($roles as $role) {
    $pdo->prepare('INSERT INTO roles (name) VALUES (?)')->execute([$role]);
}

$roleId = fn (string $name) => (int) $pdo
    ->query("SELECT id FROM roles WHERE name = '{$name}'")
    ->fetchColumn();

$users = [
    ['Busa Admin', 'admin@test.local', 'Admin123!', 'admin'],
    ['Busa Staff', 'staff@test.local', 'Staff123!', 'staff'],
    ['Busa Guest', 'guest@test.local', 'Guest123!', 'guest'],
];
foreach ($users as [$name, $email, $password, $role]) {
    $pdo->prepare(
        'INSERT INTO users (name, email, password_hash, role_id, is_active)
         VALUES (?, ?, ?, ?, 1)'
    )->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $roleId($role)]);
}
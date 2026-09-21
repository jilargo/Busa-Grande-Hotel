<?php

declare(strict_types=1);

/*
 * Populates the database with realistic demo data (idempotent — safe to run
 * multiple times). Real bcrypt hashes are generated here in PHP, never
 * hard-coded.
 *
 * Usage:  php scripts/seed.php
 */

require dirname(__DIR__) . '/bootstrap/app.php';

use App\Models\Reservation;

$pdo = db();

function exists(string $table, string $column, string $value): bool
{
    $stmt = db()->prepare("SELECT 1 FROM `{$table}` WHERE `{$column}` = ? LIMIT 1");
    $stmt->execute([$value]);
    return (bool) $stmt->fetch();
}

echo "Seeding Busa Grande Hotel...\n";

// ---- Roles ----------------------------------------------------------------
$roles = ['admin', 'staff', 'guest'];
foreach ($roles as $role) {
    if (!exists('roles', 'name', $role)) {
        db()->prepare('INSERT INTO roles (name) VALUES (?)')->execute([$role]);
        echo "  + role: {$role}\n";
    }
}

$roleId = fn (string $name) => (int) db()->query("SELECT id FROM roles WHERE name = '{$name}'")->fetchColumn();

// ---- Users ----------------------------------------------------------------
$users = [
    ['Amara Okoye',   'admin@busagrande.test',  'Admin123!',  'admin'],
    ['Kwame Boateng', 'staff@busagrande.test',  'Staff123!',  'staff'],
    ['Adeola Okafor', 'guest@busagrande.test',  'Guest123!',  'guest'],
];

$userIds = [];
foreach ($users as [$name, $email, $password, $role]) {
    if (exists('users', 'email', $email)) {
        $userIds[$email] = (int) db()->query("SELECT id FROM users WHERE email = '{$email}'")->fetchColumn();
        continue;
    }
    db()->prepare(
        'INSERT INTO users (name, email, password_hash, role_id, is_active) VALUES (?, ?, ?, ?, 1)'
    )->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $roleId($role)]);
    $userIds[$email] = (int) db()->lastInsertId();
    echo "  + user: {$email} / {$password}\n";
}

// ---- Room types -----------------------------------------------------------
$types = [
    ['Standard Room', 'standard', 'A comfortable classic room with everything you need for a restful stay.', 180, 2, 'Free Wi-Fi, Air conditioning, Cable TV, Daily housekeeping, Coffee station'],
    ['Deluxe Room',   'deluxe',   'Our most popular room with extra space and a city view.',                   260, 3, 'Free Wi-Fi, Air conditioning, Cable TV, Mini bar, City view, Bathrobes, Daily housekeeping'],
    ['Suite',         'suite',    'A generous suite with a separate living area for the discerning traveller.',  450, 4, 'Free Wi-Fi, Air conditioning, Cable TV, Mini bar, City view, Separate living room, Jacuzzi tub'],
    ['Family Room',   'family',   'Two king beds and space for the whole family to spread out.',                350, 5, 'Free Wi-Fi, Air conditioning, Cable TV, Two king beds, Kids corner, Daily housekeeping'],
];

$typeIds = [];
foreach ($types as [$name, $slug, $desc, $price, $maxGuests, $amenities]) {
    if (exists('room_types', 'slug', $slug)) {
        $typeIds[$slug] = (int) db()->query("SELECT id FROM room_types WHERE slug = '{$slug}'")->fetchColumn();
        continue;
    }
    db()->prepare(
        'INSERT INTO room_types (name, slug, description, base_price, max_guests, amenities, image) VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([$name, $slug, $desc, $price, $maxGuests, $amenities, 'images/rooms/' . $slug . '.svg']);
    $typeIds[$slug] = (int) db()->lastInsertId();
    echo "  + room type: {$name}\n";
}

// ---- Rooms ----------------------------------------------------------------
$rooms = [
    ['101', 'standard', 1, 2, 180], ['102', 'standard', 1, 2, 180], ['103', 'standard', 1, 2, 190],
    ['104', 'deluxe',   1, 3, 260],
    ['201', 'standard', 2, 2, 180], ['202', 'deluxe',   2, 3, 265], ['203', 'suite', 2, 4, 450],
    ['204', 'family',   2, 5, 350],
    ['301', 'standard', 3, 2, 200], ['302', 'deluxe',   3, 3, 270], ['303', 'suite', 3, 4, 450],
    ['304', 'family',   3, 5, 370],
];

$roomIds = [];
foreach ($rooms as [$number, $typeSlug, $floor, $capacity, $price]) {
    if (exists('rooms', 'room_number', $number)) {
        $roomIds[$number] = (int) db()->query("SELECT id FROM rooms WHERE room_number = '{$number}'")->fetchColumn();
        continue;
    }
    $status = ($number === '301') ? 'maintenance' : 'available';
    db()->prepare(
        'INSERT INTO rooms (room_number, room_type_id, floor, capacity, price_per_night, status)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([$number, $typeIds[$typeSlug], $floor, $capacity, $price, $status]);
    $roomIds[$number] = (int) db()->lastInsertId();
    echo "  + room: {$number}\n";
}

// ---- Guests ---------------------------------------------------------------
if (!exists('guests', 'email', 'guest@busagrande.test')) {
    db()->prepare(
        'INSERT INTO guests (user_id, first_name, last_name, email, phone, address, city, country)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $userIds['guest@busagrande.test'], 'Adeola', 'Okafor', 'guest@busagrande.test',
        '+234 801 234 5678', '12 Marina Road', 'Lagos', 'Nigeria',
    ]);
    echo "  + guest: Adeola Okafor (linked to guest account)\n";
}

$walkins = [
    ['John', 'Mensah',   'john.mensah@example.com',  '+233 201 555 1100'],
    ['Grace', 'Mensah',  'grace.mensah@example.com', '+233 201 555 2200'],
    ['Liam', 'Chen',     'liam.chen@example.com',    '+1 415 555 3344'],
];

$guestIds = [];
foreach ($walkins as [$firstName, $lastName, $email, $phone]) {
    if (exists('guests', 'email', $email)) {
        $guestIds[$email] = (int) db()->query("SELECT id FROM guests WHERE email = '{$email}'")->fetchColumn();
        continue;
    }
    db()->prepare('INSERT INTO guests (first_name, last_name, email, phone, address, city, country) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([$firstName, $lastName, $email, $phone, '14 Airport Road', 'Accra', 'Ghana']);
    $guestIds[$email] = (int) db()->lastInsertId();
    echo "  + guest: {$firstName} {$lastName}\n";
}

$guestAccountId = (int) db()->query("SELECT id FROM guests WHERE email = 'guest@busagrande.test'")->fetchColumn();
$guestIds['guest@busagrande.test'] = $guestAccountId;

// ---- Reservations (+ payments + check history) -----------------------------
$t = strtotime(date('Y-m-d')); // midnight today

// Insert a reservation row if its unique reference does not exist yet.
$addReservation = function (string $ref, string $guestEmailIdx, string $roomNum, int $inDays, int $outDays, int $guests, string $status, ?float $payAmount = null, ?string $payMethod = null, string $special = '') use ($guestIds, $roomIds, &$t) {
    static $userIdStaff = null;
    if ($userIdStaff === null) {
        $userIdStaff = (int) db()->query("SELECT id FROM users WHERE email = 'staff@busagrande.test'")->fetchColumn();
    }

    if (exists('reservations', 'reference', $ref)) {
        return;
    }

    $checkIn  = date('Y-m-d', $t + $inDays * 86400);
    $checkOut = date('Y-m-d', $t + $outDays * 86400);
    $nights   = (int) ($outDays - $inDays);
    $room     = db()->prepare('SELECT price_per_night FROM rooms WHERE id = ?');
    $room->execute([$roomIds[$roomNum]]);
    $price = (float) $room->fetchColumn();
    $total = $nights * $price;

    db()->beginTransaction();
    db()->prepare(
        'INSERT INTO reservations
            (reference, guest_id, room_id, user_id, check_in, check_out, guests_count,
             nights, room_price_snapshot, total_amount, status, special_requests)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $ref, $guestIds[$guestEmailIdx], $roomIds[$roomNum], $userIdStaff,
        $checkIn, $checkOut, $guests, $nights, $price, $total, $status, $special,
    ]);
    $resId = (int) db()->lastInsertId();

    if ($payAmount) {
        db()->prepare(
            'INSERT INTO payments (reservation_id, amount, method, status, payment_date, recorded_by)
             VALUES (?, ?, ?, \'completed\', NOW(), ?)'
        )->execute([$resId, $payAmount, $payMethod, $userIdStaff]);
    }
    db()->commit();
    echo "  + reservation: {$ref} room {$roomNum}\n";
};

$addReservation('BRV-DEMO101', 'john.mensah@example.com',  '202', 0,  2,  2, 'confirmed');
$addReservation('BRV-DEMO102', 'grace.mensah@example.com', '201', -3, 0,  2, 'checked_in');
$addReservation('BRV-DEMO103', 'liam.chen@example.com',    '303', -10, -8, 4, 'checked_out', 900, 'credit_card');
$addReservation('BRV-DEMO104', 'guest@busagrande.test',    '104', 5,  8,  2, 'confirmed',  520, 'bank_transfer', 'High floor preferred');
$addReservation('BRV-DEMO105', 'john.mensah@example.com',  '101', 3,  5,  2, 'cancelled');
$addReservation('BRV-DEMO106', 'grace.mensah@example.com', '102', 2,  4,  2, 'pending');

// Check-in/check-out audit rows for the stays above.
$link = function (string $ref, string $inOrOut): ?array {
    $stmt = db()->prepare(
        'SELECT r.id AS reservation_id, r.room_id, r.guest_id,
                u.id AS user_id
           FROM reservations r
          LEFT JOIN users u ON u.email = \'staff@busagrande.test\'
          WHERE r.reference = ?'
    );
    $stmt->execute([$ref]);
    return $stmt->fetch() ?: null;
};

foreach (['BRV-DEMO102', 'BRV-DEMO103'] as $ref) {
    $row = $link($ref, 'in');
    if ($row && !exists('check_ins', 'reservation_id', (string) $row['reservation_id'])) {
        db()->prepare('INSERT INTO check_ins (reservation_id, room_id, guest_id, checked_in_by, actual_check_in) VALUES (?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL 1 DAY))')
            ->execute([$row['reservation_id'], $row['room_id'], $row['guest_id'], $row['user_id']]);
    }
}

foreach (['BRV-DEMO103'] as $ref) {
    $row = $link($ref, 'out');
    if ($row && !exists('check_outs', 'reservation_id', (string) $row['reservation_id'])) {
        db()->prepare('INSERT INTO check_outs (reservation_id, room_id, guest_id, checked_out_by, actual_check_out, notes) VALUES (?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL)')
            ->execute([$row['reservation_id'], $row['room_id'], $row['guest_id'], $row['user_id']]);
    }
}

// Sync room states with the demo stays.
db()->prepare("UPDATE rooms SET status = 'available' WHERE room_number NOT IN ('202','201','301')")->execute();
db()->prepare("UPDATE rooms SET status = 'occupied' WHERE room_number IN ('202','201')")->execute();

echo "\nDone. Log in with any of the seeded accounts (see README).\n";
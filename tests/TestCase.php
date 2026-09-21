<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base class for integration-style tests that hit the real (test) database.
 *
 * Every test starts from a clean slate: the data tables are truncated and a
 * standard room type + staff user are created, so foreign keys resolve.
 */
abstract class TestCase extends BaseTestCase
{
    /** Tables whose data is wiped before every test. Roles are reference data
     *  and stay put so foreign keys always resolve. */
    private const MUTATED_TABLES = [
        'check_outs',
        'check_ins',
        'payments',
        'reservations',
        'guests',
        'rooms',
        'room_types',
        'users',
    ];

    protected int $standardTypeId;
    protected int $staffUserId;

    protected function setUp(): void
    {
        $pdo = Database::connection();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (self::MUTATED_TABLES as $table) {
            $pdo->exec("TRUNCATE TABLE `{$table}`");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $this->standardTypeId = $this->createRoomType('Standard Room', 'standard', 180.00);
        $this->staffUserId    = $this->createUser('staff@test.local', 'staff');
    }

    // ---- Fixtures ----------------------------------------------------------

    /**
     * Creates a user with an ids of role from the roles lookup table.
     */
    protected function createUser(string $email, string $role, string $name = 'Test Staff'): int
    {
        $roleId = (int) db()->query("SELECT id FROM roles WHERE name = '{$role}'")->fetchColumn();

        return User::create([
            'name'          => $name,
            'email'         => $email,
            'password_hash' => password_hash('Secret123!', PASSWORD_DEFAULT),
            'role_id'       => $roleId,
        ]);
    }

    protected function createRole(string $name): int
    {
        $pdo = Database::connection();
        $pdo->prepare('INSERT INTO roles (name) VALUES (?)')->execute([$name]);

        return (int) $pdo->lastInsertId();
    }

    protected function createRoomType(
        string $name,
        string $slug,
        float $basePrice,
        int $maxGuests = 2
    ): int {
        return RoomType::create([
            'name'        => $name,
            'slug'        => $slug,
            'description' => "Test fixture for {$name}.",
            'base_price'  => $basePrice,
            'max_guests'  => $maxGuests,
            'amenities'   => null,
            'image'       => null,
        ]);
    }

    protected function createRoom(
        string $number,
        int $typeId,
        int $capacity = 2,
        float $price = 180.00,
        string $status = Room::STATUS_AVAILABLE
    ): int {
        return Room::create([
            'room_number'     => $number,
            'room_type_id'    => $typeId,
            'floor'           => 1,
            'capacity'        => $capacity,
            'price_per_night' => $price,
            'status'          => $status,
        ]);
    }

    protected function createGuest(
        string $firstName,
        string $lastName = 'Test',
        ?string $email = null,
        ?int $userId = null
    ): int {
        return Guest::create([
            'user_id'    => $userId,
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $email ?? strtolower($firstName) . '@test.local',
        ]);
    }

    /**
     * Writes a reservation row directly (no business rules) for overlap tests.
     */
    protected function createReservation(
        int $roomId,
        int $guestId,
        string $checkIn,
        string $checkOut,
        string $status = Reservation::STATUS_CONFIRMED,
        float $price = 180.00
    ): int {
        $nights = Reservation::nightsBetween($checkIn, $checkOut);

        return Reservation::create([
            'reference'           => 'TEST-' . strtoupper(bin2hex(random_bytes(3))),
            'guest_id'            => $guestId,
            'room_id'             => $roomId,
            'user_id'             => $this->staffUserId,
            'check_in'            => $checkIn,
            'check_out'           => $checkOut,
            'guests_count'        => 2,
            'nights'              => $nights,
            'room_price_snapshot' => $price,
            'total_amount'        => $price * $nights,
            'status'              => $status,
            'special_requests'    => null,
        ]);
    }
}
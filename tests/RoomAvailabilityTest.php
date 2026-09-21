<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Reservation;
use App\Models\Room;

/**
 * The backbone of the business: the half-open overlap rule.
 *
 * A guest occupying until "check_out" frees the room on the AFTERNOON of that
 * date, so a new reservation may legitimately start on the same day. Every
 * assertion below pins that behaviour down so it can never silently change.
 */
final class RoomAvailabilityTest extends TestCase
{
    protected function makeRoom(string $status = Room::STATUS_AVAILABLE): int
    {
        return $this->createRoom('101', $this->standardTypeId, 2, 180.00, $status);
    }

    public function testFreshRoomIsBookable(): void
    {
        $roomId = $this->makeRoom();
        $this->assertTrue(Room::canBeBooked($roomId, '2026-05-01', '2026-05-03'));
    }

    public function testDistinctRoomsDoNotAffectEachOther(): void
    {
        $roomA = $this->makeRoom();
        $roomB = $this->createRoom('102', $this->standardTypeId);
        $guest = $this->createGuest('First');

        $this->createReservation($roomA, $guest, '2026-05-01', '2026-05-05');

        $this->assertFalse(Room::canBeBooked($roomA, '2026-05-04', '2026-05-06'));
        $this->assertTrue(Room::canBeBooked($roomB, '2026-05-04', '2026-05-06'));
    }

    public function testOverlappingStartBeforeExistingCheckIn(): void
    {
        $roomId = $this->makeRoom();
        $guest  = $this->createGuest('First');
        $this->createReservation($roomId, $guest, '2026-05-05', '2026-05-08');

        $this->assertFalse(Room::canBeBooked($roomId, '2026-05-03', '2026-05-06'));
        $this->assertFalse(Room::canBeBooked($roomId, '2026-05-03', '2026-05-11'));
    }

    public function testAdjacentReservationIsAllowedOnCheckOutDay(): void
    {
        $roomId = $this->makeRoom();
        $guest  = $this->createGuest('First');
        $this->createReservation($roomId, $guest, '2026-05-05', '2026-05-08');

        // New guest arrives the day the previous one leaves.
        $this->assertTrue(Room::canBeBooked($roomId, '2026-05-08', '2026-05-10'));
        $this->assertTrue(Room::canBeBooked($roomId, '2026-05-09', '2026-05-10'));
    }

    public function testEnclosingReservationBlocksEverythingInside(): void
    {
        $roomId = $this->makeRoom();
        $guest  = $this->createGuest('First');
        $this->createReservation($roomId, $guest, '2026-05-01', '2026-05-10');

        $this->assertFalse(Room::canBeBooked($roomId, '2026-05-04', '2026-05-06'));
    }

    public function testCancelledReservationDoesNotBlock(): void
    {
        $roomId = $this->makeRoom();
        $guest  = $this->createGuest('First');
        $this->createReservation($roomId, $guest, '2026-05-05', '2026-05-08', Reservation::STATUS_CANCELLED);

        $this->assertTrue(Room::canBeBooked($roomId, '2026-05-05', '2026-05-08'));
    }

    public function testCheckedInReservationStillBlocksUntilCheckedOut(): void
    {
        $roomId = $this->makeRoom();
        $guest  = $this->createGuest('First');
        $this->createReservation($roomId, $guest, '2026-05-05', '2026-05-08', Reservation::STATUS_CHECKED_IN);

        $this->assertFalse(Room::canBeBooked($roomId, '2026-05-07', '2026-05-10'));
    }

    public function testPhysicalStatusesPhysicallyBlockTheRoom(): void
    {
        foreach ([
            ['201', Room::STATUS_OCCUPIED],
            ['202', Room::STATUS_MAINTENANCE],
            ['203', Room::STATUS_CLEANING],
        ] as [$number, $status]) {
            $roomId = $this->createRoom($number, $this->standardTypeId, 2, 180.00, $status);
            $this->assertFalse(
                Room::canBeBooked($roomId, '2026-05-01', '2026-05-03'),
                "Room with status '{$status}' must not be bookable."
            );
        }
    }

    public function testExcludeAllowsEditingTheBookingItself(): void
    {
        $roomId = $this->makeRoom();
        $guest  = $this->createGuest('First');
        $resId  = $this->createReservation($roomId, $guest, '2026-05-05', '2026-05-08');

        // Without the exclusion the room looks taken…
        $this->assertFalse(Room::canBeBooked($roomId, '2026-05-05', '2026-05-08'));

        // …but when editing that very reservation it is "available" to itself.
        $this->assertTrue(Room::canBeBooked($roomId, '2026-05-05', '2026-05-08', $resId));
    }

    public function testAvailableBetweenReturnsOnlyFreeRooms(): void
    {
        $roomA = $this->makeRoom();
        $roomB = $this->createRoom('102', $this->standardTypeId);
        $guest = $this->createGuest('First');

        $this->createReservation($roomA, $guest, '2026-05-01', '2026-05-05');

        $free = Room::availableBetween('2026-05-01', '2026-05-05');

        $this->assertCount(1, $free);
        $this->assertSame((string) $roomB, (string) $free[0]['id']);
    }

    public function testAvailableBetweenExcludesMaintenanceRooms(): void
    {
        $roomA = $this->makeRoom();
        $this->createRoom('102', $this->standardTypeId, 2, 180.00, Room::STATUS_MAINTENANCE);

        $free = Room::availableBetween('2026-05-01', '2026-05-05');

        $numbers = array_column($free, 'room_number');
        $this->assertContains('101', $numbers);
        $this->assertNotContains('102', $numbers);
    }
}
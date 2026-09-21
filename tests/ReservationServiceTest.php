<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Exceptions\ValidationException;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\ReservationService;

/**
 * ReservationService::create() is the single authority for "can we accept
 * this booking?" — these tests pin the validation rules and price math.
 */
final class ReservationServiceTest extends TestCase
{
    private ReservationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReservationService();
    }

    private function validInput(int $roomId, int $guestId, string $in = '2026-06-01', string $out = '2026-06-04'): array
    {
        return [
            'guest_id'      => (string) $guestId,
            'room_id'       => (string) $roomId,
            'check_in'      => $in,
            'check_out'     => $out,
            'guests_count'  => '2',
        ];
    }

    public function testCreatesReservationWithCalculatedNightsAndTotal(): void
    {
        $roomId = $this->createRoom('201', $this->standardTypeId, 2, 250.00);
        $guestId = $this->createGuest('Ada');

        $reservation = $this->service->create($this->validInput($roomId, $guestId), $this->staffUserId);

        $this->assertSame(3, (int) $reservation['nights']);
        $this->assertSame('750.00', (string) $reservation['total_amount']);
        $this->assertSame('250.00', (string) $reservation['room_price_snapshot']);
        $this->assertSame(Reservation::STATUS_CONFIRMED, $reservation['status']);
        $this->assertSame((string) $this->staffUserId, (string) $reservation['user_id']);
    }

    public function testRejectsOverlappingBooking(): void
    {
        $roomId = $this->makeRoom();
        $guestA = $this->createGuest('Ada');
        $guestB = $this->createGuest('Ben');
        $this->service->create($this->validInput($roomId, $guestA, '2026-06-05', '2026-06-09'), $this->staffUserId);

        try {
            $this->service->create($this->validInput($roomId, $guestB, '2026-06-08', '2026-06-11'), $this->staffUserId);
            $this->fail('Expected ValidationException for an overlapping booking.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('room_id', $e->errors());
        }
    }

    public function testAllowsBackToBackBookingOnSameDay(): void
    {
        $roomId = $this->makeRoom();
        $guestA = $this->createGuest('Ada');
        $guestB = $this->createGuest('Ben');
        $this->service->create($this->validInput($roomId, $guestA, '2026-06-05', '2026-06-09'), $this->staffUserId);

        $reservation = $this->service->create($this->validInput($roomId, $guestB, '2026-06-09', '2026-06-12'), $this->staffUserId);

        $this->assertSame('2026-06-09', $reservation['check_in']);
    }

    public function testRejectsGuestCountBeyondCapacity(): void
    {
        $roomId = $this->createRoom('201', $this->standardTypeId, 2);
        $guestId = $this->createGuest('Ada');

        $input = $this->validInput($roomId, $guestId);
        $input['guests_count'] = '5';

        try {
            $this->service->create($input, $this->staffUserId);
            $this->fail('Expected ValidationException for too many guests.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('guests_count', $e->errors());
        }
    }

    public function testRejectsCheckOutBeforeCheckIn(): void
    {
        $roomId = $this->createRoom('201', $this->standardTypeId);
        $guestId = $this->createGuest('Ada');

        try {
            $this->service->create($this->validInput($roomId, $guestId, '2026-06-10', '2026-06-10'), $this->staffUserId);
            $this->fail('Expected ValidationException for an invalid date range.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('check_out', $e->errors());
        }
    }

    public function testRejectsMissingGuest(): void
    {
        $roomId = $this->createRoom('201', $this->standardTypeId);

        try {
            $this->service->create($this->validInput($roomId, 999_999), $this->staffUserId);
            $this->fail('Expected ValidationException for a missing guest.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('guest_id', $e->errors());
        }
    }

    public function testPendingReservationStillBlocksTheRoom(): void
    {
        $roomId = $this->createRoom('201', $this->standardTypeId);
        $guestA = $this->createGuest('Ada');
        $guestB = $this->createGuest('Ben');

        $input = $this->validInput($roomId, $guestA, '2026-06-05', '2026-06-09');
        $input['status'] = Reservation::STATUS_PENDING;
        $this->service->create($input, $this->staffUserId);

        $this->assertFalse(Room::canBeBooked($roomId, '2026-06-07', '2026-06-10'));
    }

    public function testCancelReleasesTheRoom(): void
    {
        $roomId = $this->makeRoom();
        $guest = $this->createGuest('Ada');

        $reservation = $this->service->create($this->validInput($roomId, $guest), $this->staffUserId);
        $this->service->cancel($reservation, $this->staffUserId);

        $this->assertSame(Reservation::STATUS_CANCELLED, Reservation::find((int) $reservation['id'])['status']);
        $this->assertTrue(Room::canBeBooked($roomId, '2026-06-01', '2026-06-04'));
    }

    public function testCancelRejectsCheckedInReservation(): void
    {
        $roomId = $this->makeRoom();
        $guest = $this->createGuest('Ada');

        $reservation = $this->service->create($this->validInput($roomId, $guest), $this->staffUserId);
        Reservation::updateStatus((int) $reservation['id'], Reservation::STATUS_CHECKED_IN);

        $this->expectException(ValidationException::class);
        $this->service->cancel(Reservation::find((int) $reservation['id']) ?? [], $this->staffUserId);
    }

    private function makeRoom(): int
    {
        return $this->createRoom('201', $this->standardTypeId);
    }
}
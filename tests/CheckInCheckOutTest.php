<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Exceptions\ValidationException;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\CheckInService;
use App\Services\CheckOutService;
use App\Services\PaymentService;

/**
 * The physical lifecycle of a stay:
 *
 *   confirmed ──check-in──▶ checked_in + room=occupied
 *   checked_in ──check-out──▶ checked_out + room=cleaning
 *
 * plus payments recorded against the reservation.
 */
final class CheckInCheckOutTest extends TestCase
{
    private CheckInService $checkIn;
    private CheckOutService $checkOut;
    private PaymentService $payments;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checkIn   = new CheckInService();
        $this->checkOut  = new CheckOutService();
        $this->payments  = new PaymentService();
    }

    private function makeReservation(string $status = Reservation::STATUS_CONFIRMED): array
    {
        $roomId  = $this->createRoom('301', $this->standardTypeId, 2, 180.00);
        $guestId = $this->createGuest('Cara');

        $this->createReservation($roomId, $guestId, '2026-07-01', '2026-07-04', $status, 180.00);

        return Reservation::find((int) db()->lastInsertId()) ?? [];
    }

    public function testCheckInMovesReservationAndRoomStateTogether(): void
    {
        $reservation = $this->makeReservation();

        $result = $this->checkIn->checkIn((int) $reservation['id'], $this->staffUserId);

        $this->assertSame(Reservation::STATUS_CHECKED_IN, $result['status']);
        $this->assertSame(
            Room::STATUS_OCCUPIED,
            Room::find((int) $reservation['room_id'])['status']
        );

        $audit = db()->query(
            'SELECT COUNT(*) FROM check_ins WHERE reservation_id = ' . (int) $reservation['id']
        )->fetchColumn();
        $this->assertSame('1', (string) $audit);
    }

    public function testCheckOutMovesRoomToCleaningNotAvailable(): void
    {
        $reservation = $this->makeReservation(Reservation::STATUS_CHECKED_IN);

        $result = $this->checkOut->checkOut((int) $reservation['id'], $this->staffUserId, 'Left early');

        $this->assertSame(Reservation::STATUS_CHECKED_OUT, $result['status']);
        $this->assertSame(
            Room::STATUS_CLEANING,
            Room::find((int) $reservation['room_id'])['status']
        );
        $this->assertFalse(Room::canBeBooked((int) $reservation['room_id'], '2026-07-05', '2026-07-07'));
    }

    public function testGuestIsFreeToBookAfterHousekeeping(): void
    {
        $reservation = $this->makeReservation(Reservation::STATUS_CHECKED_IN);
        $this->checkOut->checkOut((int) $reservation['id'], $this->staffUserId);

        // Staff marks the room clean — a floor above is free again.
        Room::updateStatus((int) $reservation['room_id'], Room::STATUS_AVAILABLE);

        $this->assertTrue(Room::canBeBooked((int) $reservation['room_id'], '2026-07-05', '2026-07-07'));
    }

    public function testCheckInRejectsAlreadyCheckedOutReservation(): void
    {
        $reservation = $this->makeReservation(Reservation::STATUS_CHECKED_OUT);

        $this->expectException(ValidationException::class);
        $this->checkIn->checkIn((int) $reservation['id'], $this->staffUserId);
    }

    public function testCheckOutRejectsNonCheckedInReservation(): void
    {
        $reservation = $this->makeReservation(Reservation::STATUS_CONFIRMED);

        $this->expectException(ValidationException::class);
        $this->checkOut->checkOut((int) $reservation['id'], $this->staffUserId);
    }

    public function testPaymentMovesBalanceAndStatus(): void
    {
        $reservation = $this->makeReservation();
        $this->checkIn->checkIn((int) $reservation['id'], $this->staffUserId);

        $result = $this->payments->record([
            'reservation_id' => (string) $reservation['id'],
            'amount'         => '100.00',
            'method'         => 'cash',
            'payment_date'   => '2026-07-01',
        ], $this->staffUserId);

        $this->assertGreaterThan(0, $result['payment_id']);
        $this->assertSame(100.0, $result['total_paid']);

        $fresh = Reservation::find((int) $reservation['id']);
        $this->assertSame('partial', Reservation::paymentStatus($fresh));
    }

    public function testFullPaymentMarksReservationAsPaid(): void
    {
        $reservation = $this->makeReservation();
        $this->payments->record([
            'reservation_id' => (string) $reservation['id'],
            'amount'         => '540.00',
            'method'         => 'credit_card',
        ], $this->staffUserId);

        $this->assertSame('paid', Reservation::paymentStatus(Reservation::find((int) $reservation['id'])));
    }

    public function testRefundReversesThePayment(): void
    {
        $reservation = $this->makeReservation();
        $result = $this->payments->record([
            'reservation_id' => (string) $reservation['id'],
            'amount'         => '540.00',
            'method'         => 'bank_transfer',
        ], $this->staffUserId);

        $this->payments->refund((int) $result['payment_id'], $this->staffUserId);

        $this->assertSame(0.0, Payment::totalForReservation((int) $reservation['id']));
    }

    public function testPaymentRejectsCancelledReservation(): void
    {
        $reservation = $this->makeReservation(Reservation::STATUS_CANCELLED);

        $this->expectException(ValidationException::class);
        $this->payments->record([
            'reservation_id' => (string) $reservation['id'],
            'amount'         => '100.00',
            'method'         => 'cash',
        ], $this->staffUserId);
    }

    public function testPaymentRejectsZeroAmount(): void
    {
        $reservation = $this->makeReservation();

        $this->expectException(ValidationException::class);
        $this->payments->record([
            'reservation_id' => (string) $reservation['id'],
            'amount'         => '0.00',
            'method'         => 'cash',
        ], $this->staffUserId);
    }
}
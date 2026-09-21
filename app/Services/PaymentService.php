<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\ValidationException;
use App\Models\Payment;
use App\Models\Reservation;

/**
 * Manual payment recording.
 *
 * A real payment gateway can plug into this service later: record() is the
 * single write path for money movements. Refunds are stored as records with
 * a negative amount, so the reservation balance is simply SUM(amount).
 */
final class PaymentService
{
    public function record(array $input, int $byUserId): array
    {
        $reservation = Reservation::find((int) ($input['reservation_id'] ?? 0));

        if (!$reservation) {
            throw new ValidationException(['reservation_id' => 'Reservation not found.']);
        }

        if ($reservation['status'] === Reservation::STATUS_CANCELLED) {
            throw new ValidationException(
                ['reservation_id' => 'Payments cannot be recorded against cancelled reservations.'],
                'This reservation has been cancelled.'
            );
        }

        $amount = (float) ($input['amount'] ?? 0);
        $method = (string) ($input['method'] ?? '');
        $date   = (string) ($input['payment_date'] ?? date('Y-m-d'));

        if ($amount <= 0) {
            throw new ValidationException(['amount' => 'Payment amount must be greater than zero.']);
        }
        if (!in_array($method, Payment::METHODS, true)) {
            throw new ValidationException(['method' => 'Please choose a valid payment method.']);
        }

        $id = Payment::create([
            'reservation_id'   => (int) $reservation['id'],
            'amount'           => $amount,
            'method'           => $method,
            'status'           => Payment::STATUS_COMPLETED,
            'reference_number' => $input['reference_number'] ?? null,
            'payment_date'     => $date,
            'recorded_by'      => $byUserId,
            'notes'            => $input['notes'] ?? null,
        ]);

        return ['payment_id' => $id, 'total_paid' => Payment::totalForReservation((int) $reservation['id'])];
    }

    /** Records a refund (negative amount via normal Payment::create). */
    public function refund(int $paymentId, int $byUserId, ?string $notes = null): void
    {
        $payment = Payment::find($paymentId);

        if (!$payment) {
            throw new ValidationException(['payment_id' => 'Payment not found.']);
        }

        if ($payment['status'] === Payment::STATUS_REFUNDED) {
            throw new ValidationException(['payment_id' => 'This payment has already been refunded.']);
        }

        Payment::create([
            'reservation_id'   => (int) $payment['reservation_id'],
            'amount'           => -((float) $payment['amount']),
            'method'           => $payment['method'],
            'status'           => Payment::STATUS_REFUNDED,
            'reference_number' => $payment['reference_number'],
            'payment_date'     => date('Y-m-d'),
            'recorded_by'      => $byUserId,
            'notes'            => $notes ?? 'Refund of payment #' . $paymentId,
        ]);
    }
}
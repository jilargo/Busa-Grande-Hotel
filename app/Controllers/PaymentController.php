<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\PaymentService;

/**
 * Manual payment recording and refunds.
 */
final class PaymentController
{
    public function store(Request $request, int $id): void
    {
        $reservation = Reservation::find($id)
            ?? throw new \App\Core\Exceptions\NotFoundException('Reservation not found.');

        $service = new PaymentService();
        $result = $service->record([
            'reservation_id'   => $reservation['id'],
            'amount'           => $request->input('amount'),
            'method'           => $request->input('method'),
            'payment_date'     => $request->input('payment_date', date('Y-m-d')),
            'reference_number' => $request->input('reference_number'),
            'notes'            => $request->input('notes'),
        ], (int) auth()->id());

        session()->flash('success', 'Payment of ' . number_format((float) $request->input('amount'), 2) . ' recorded.');
        redirect('reservations/' . $id . '#payments');
    }

    public function refund(Request $request, int $paymentId): void
    {
        $payment = Payment::find($paymentId)
            ?? throw new \App\Core\Exceptions\NotFoundException('Payment not found.');

        $service = new PaymentService();
        $service->refund($paymentId, (int) auth()->id());

        session()->flash('success', 'Refund recorded for payment #' . $paymentId . '.');
        redirect('reservations/' . $payment['reservation_id'] . '#payments');
    }
}
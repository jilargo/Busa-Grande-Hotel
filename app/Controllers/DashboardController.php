<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\CheckIn;
use App\Models\CheckOut;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;

/**
 * Role-based dashboards. The single /dashboard route renders a different
 * dashboard depending on the authenticated user's role.
 */
final class DashboardController
{
    public function index(Request $request): void
    {
        $user = auth()->user();

        match ($user['role']) {
            'admin'  => $this->adminDashboard($user),
            'staff'  => $this->staffDashboard($user),
            default  => $this->guestDashboard($user),
        };

        exit;
    }

    private function adminDashboard(array $user): void
    {
        $statusCounts = Room::statusCounts();
        $today = date('Y-m-d');

        $stats = [
            'total_rooms'        => $statusCounts['available'] + $statusCounts['reserved'] + $statusCounts['occupied'] + $statusCounts['maintenance'] + $statusCounts['cleaning'],
            'available_rooms'    => $statusCounts['available'] + $statusCounts['reserved'],
            'occupied_rooms'     => $statusCounts['occupied'],
            'maintenance_rooms'  => $statusCounts['maintenance'],
            'cleaning_rooms'     => $statusCounts['cleaning'],
            'today_check_ins'    => count(Reservation::forDate($today, 'check_in')),
            'today_check_outs'   => count(Reservation::forDate($today, 'check_out')),
            'pending_count'      => Reservation::countByStatus()['pending'],
            'total_guests'       => (int) db()->query('SELECT COUNT(*) FROM guests')->fetchColumn(),
            'revenue_month'      => $this->revenueForMonth(date('Y-m')),
            'revenue_total'      => (float) db()->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = \'completed\'')->fetchColumn(),
        ];

        view('admin/dashboard', [
            'title'        => 'Admin Dashboard - ' . config('app.name'),
            'user'         => $user,
            'stats'        => $stats,
            'roomStatuses' => $statusCounts,
            'recentReservations' => Reservation::recent(7),
            'recentCheckIns'     => CheckIn::recent(5),
            'recentPayments'     => Payment::recent(5),
        ]);
    }

    private function staffDashboard(array $user): void
    {
        $today = date('Y-m-d');
        $available = Room::statusCounts();

        view('staff/dashboard', [
            'title'             => 'Staff Dashboard - ' . config('app.name'),
            'user'              => $user,
            'available_rooms'   => $available['available'] + $available['reserved'],
            'occupied_rooms'    => $available['occupied'],
            'today_check_ins'   => count(Reservation::forDate($today, 'check_in')),
            'today_check_outs'  => count(Reservation::forDate($today, 'check_out')),
            'pending_count'     => Reservation::countByStatus()['pending'],
            'recentReservations' => Reservation::recent(8),
        ]);
    }

    private function guestDashboard(array $user): void
    {
        $guest = Guest::findByUserId((int) $user['id']);
        $reservations = $guest ? Reservation::forGuest((int) $user['id']) : [];
        $upcoming = array_filter($reservations, fn ($r) =>
            in_array($r['status'], [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED], true)
        );
        $active = array_values(array_filter($reservations, fn ($r) => $r['status'] === Reservation::STATUS_CHECKED_IN));
        $past = array_values(array_filter($reservations, fn ($r) => in_array($r['status'], [
            Reservation::STATUS_CHECKED_OUT, Reservation::STATUS_CANCELLED, Reservation::STATUS_NO_SHOW,
        ], true)));

        view('guest/dashboard', [
            'title'      => 'My Account - ' . config('app.name'),
            'user'       => $user,
            'guest'      => $guest,
            'upcoming'   => array_values($upcoming),
            'active'     => $active,
            'past'       => $past,
        ]);
    }

    private function revenueForMonth(string $ym): float
    {
        $stmt = db()->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM payments
              WHERE status = 'completed' AND DATE_FORMAT(payment_date, '%Y-%m') = ?"
        );
        $stmt->execute([$ym]);
        return (float) $stmt->fetchColumn();
    }
}
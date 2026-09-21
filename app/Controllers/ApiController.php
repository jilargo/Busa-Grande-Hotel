<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\Guest;
use App\Models\Room;

/**
 * Lightweight JSON endpoints used by jQuery to make the UI feel live.
 * All responses follow { "success": bool, ... }.
 */
final class ApiController
{
    /**
     * GET /api/available-rooms?check_in=&check_out=&room_type_id=&guests=
     * Powers the "pick dates, then choose a free room" flow without reloads.
     */
    public function availableRooms(Request $request): void
    {
        $checkIn  = (string) $request->input('check_in', '');
        $checkOut = (string) $request->input('check_out', '');

        if (!$checkIn || !$checkOut || strtotime($checkOut) <= strtotime($checkIn)) {
            json(['success' => false, 'message' => 'Please choose valid dates.', 'rooms' => []]);
        }

        $rooms = Room::availableBetween($checkIn, $checkOut, [
            'room_type_id' => (string) $request->input('room_type_id', ''),
            'guests'       => (string) $request->input('guests', ''),
        ]);

        json([
            'success' => true,
            'rooms'   => array_map(fn ($room) => [
                'id'              => $room['id'],
                'room_number'     => $room['room_number'],
                'room_type_name'  => $room['room_type_name'],
                'floor'           => $room['floor'],
                'capacity'        => $room['capacity'],
                'price_per_night' => (float) $room['price_per_night'],
                'image'           => $room['image'],
                'amenities'       => $room['amenities'],
                'status'          => $room['status'],
            ], $rooms),
        ]);
    }

    /**
     * GET /api/rooms/{id}/availability?check_in=&check_out=&exclude=
     * Real-time "is this room free?" used on the reservation form.
     */
    public function roomAvailability(Request $request, int $id): void
    {
        $checkIn  = (string) $request->input('check_in', '');
        $checkOut = (string) $request->input('check_out', '');
        $exclude  = $request->input('exclude') !== '' ? (int) $request->input('exclude') : null;

        if (!$checkIn || !$checkOut || strtotime($checkOut) <= strtotime($checkIn)) {
            json(['success' => false, 'available' => false, 'message' => 'Invalid date range.']);
        }

        $room = Room::find($id);
        if (!$room) {
            json(['success' => false, 'available' => false, 'message' => 'Room not found.']);
        }

        $free = Room::canBeBooked($id, $checkIn, $checkOut, $exclude);

        json([
            'success'   => true,
            'available' => $free,
            'message'   => $free
                ? 'This room is available for the selected dates.'
                : 'This room is taken for the selected dates by an active reservation.',
        ]);
    }

    /**
     * GET /api/guests?q=
     * Type-ahead guest search for the reservation form.
     */
    public function searchGuests(Request $request): void
    {
        $term = (string) $request->input('q', '');

        if (strlen($term) < 2) {
            json(['success' => true, 'guests' => []]);
        }

        $guests = Guest::all(['q' => $term]);

        json([
            'success' => true,
            'guests'  => array_map(fn ($g) => [
                'id'         => $g['id'],
                'name'       => $g['first_name'] . ' ' . $g['last_name'],
                'email'      => $g['email'],
                'phone'      => $g['phone'],
            ], array_slice($guests, 0, 10)),
        ]);
    }
}
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Exceptions\NotFoundException;
use App\Core\Request;
use App\Models\Room;
use App\Models\RoomType;

/**
 * Public marketing pages (no authentication).
 */
final class HomeController
{
    public function index(Request $request): void
    {
        view('public/home', [
            'title'     => config('app.name'),
            'roomTypes' => RoomType::all(),
        ], 'marketing');
    }

    /** Public detail page for one room type, listing the rooms in it. */
    public function roomType(Request $request, int $id): void
    {
        $roomType = RoomType::find($id)
            ?? throw new NotFoundException('That room type could not be found.');

        // Rooms under maintenance are hidden from public browsing.
        $rooms = array_values(array_filter(
            Room::all(['room_type_id' => $id]),
            fn (array $room): bool => $room['status'] !== Room::STATUS_MAINTENANCE
        ));

        view('public/room-type', [
            'title'    => $roomType['name'] . ' - ' . config('app.name'),
            'roomType' => $roomType,
            'rooms'    => $rooms,
        ], 'marketing');
    }
}
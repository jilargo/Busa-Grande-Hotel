<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Validator;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\UploadService;

/**
 * Room management. Listing, creation, editing and status changes are
 * available to staff; deletion is admin-only (and blocked at the DB level
 * while any reservation references a room).
 */
final class RoomController
{
    private const STATUS_OPTIONS = [
        Room::STATUS_AVAILABLE,
        Room::STATUS_RESERVED,
        Room::STATUS_OCCUPIED,
        Room::STATUS_MAINTENANCE,
        Room::STATUS_CLEANING,
    ];

    public function index(Request $request): void
    {
        view('admin/rooms/index', [
            'title'     => 'Rooms - ' . config('app.name'),
            'rooms'     => Room::all($request->all()),
            'roomTypes' => RoomType::all(),
            'filters'   => $request->all(),
        ]);
    }

    public function create(Request $request): void
    {
        view('admin/rooms/form', [
            'title'     => 'New Room - ' . config('app.name'),
            'room'      => null,
            'roomTypes' => RoomType::all(),
            'statuses'  => self::STATUS_OPTIONS,
        ]);
    }

    public function store(Request $request): void
    {
        $data = Validator::validate($request->all(), [
            'room_number'    => ['required', 'max:20', 'unique:rooms,room_number'],
            'room_type_id'   => ['required', 'integer'],
            'floor'          => ['required', 'integer', 'min_value:0', 'max_value:100'],
            'capacity'       => ['required', 'integer', 'min_value:1', 'max_value:50'],
            'price_per_night'=> ['required', 'numeric', 'min_value:0', 'max_value:100000'],
            'status'         => ['required', 'in:available,reserved,occupied,maintenance,cleaning'],
            'description'    => ['max:1000'],
            'amenities'      => ['max:1000'],
        ]);

        if (!RoomType::find((int) $data['room_type_id'])) {
            throw new \App\Core\Exceptions\ValidationException(['room_type_id' => 'Please choose a valid room type.']);
        }

        $upload = new UploadService();
        $imagePath = $upload->storeImage($request->file('image'));

        Room::create($data + ['image' => $imagePath]);

        session()->flash('success', 'Room ' . $data['room_number'] . ' created.');
        redirect('rooms');
    }

    public function show(Request $request, int $id): void
    {
        $room = Room::find($id)
            ?? throw new \App\Core\Exceptions\NotFoundException('Room not found.');

        $upcoming = Reservation::all(['q' => (string) $room['room_number']]);

        view('admin/rooms/show', [
            'title'    => 'Room ' . $room['room_number'] . ' - ' . config('app.name'),
            'room'     => $room,
            'upcoming' => $upcoming,
        ]);
    }

    public function edit(Request $request, int $id): void
    {
        $room = Room::find($id)
            ?? throw new \App\Core\Exceptions\NotFoundException('Room not found.');

        view('admin/rooms/form', [
            'title'     => 'Edit Room ' . $room['room_number'] . ' - ' . config('app.name'),
            'room'      => $room,
            'roomTypes' => RoomType::all(),
            'statuses'  => self::STATUS_OPTIONS,
        ]);
    }

    public function update(Request $request, int $id): void
    {
        $room = Room::find($id)
            ?? throw new \App\Core\Exceptions\NotFoundException('Room not found.');

        $data = Validator::validate($request->all(), [
            'room_number'    => ['required', 'max:20', 'unique:rooms,room_number,' . $id],
            'room_type_id'   => ['required', 'integer'],
            'floor'          => ['required', 'integer', 'min_value:0', 'max_value:100'],
            'capacity'       => ['required', 'integer', 'min_value:1', 'max_value:50'],
            'price_per_night'=> ['required', 'numeric', 'min_value:0', 'max_value:100000'],
            'status'         => ['required', 'in:available,reserved,occupied,maintenance,cleaning'],
            'description'    => ['max:1000'],
            'amenities'      => ['max:1000'],
        ]);

        if (!RoomType::find((int) $data['room_type_id'])) {
            throw new \App\Core\Exceptions\ValidationException(['room_type_id' => 'Please choose a valid room type.']);
        }

        $upload = new UploadService();
        $imagePath = $upload->storeImage($request->file('image')) ?? $room['image'];

        Room::update($id, $data + ['image' => $imagePath]);

        session()->flash('success', 'Room ' . $data['room_number'] . ' updated.');
        redirect('rooms');
    }

    public function destroy(Request $request, int $id): void
    {
        // Only admin deletes rooms.
        if (!auth()->isAllowed(['admin'])) {
            throw new \App\Core\Exceptions\ForbiddenException();
        }

        try {
            Room::destroy($id);
            session()->flash('success', 'Room deleted.');
        } catch (\PDOException $e) {
            session()->flash('error', 'This room cannot be deleted because it is linked to reservations.');
        }

        redirect('rooms');
    }

    /** Quick status change (available / maintenance / cleaning, etc.). */
    public function changeStatus(Request $request, int $id): void
    {
        $room = Room::find($id)
            ?? throw new \App\Core\Exceptions\NotFoundException('Room not found.');

        $status = (string) $request->input('status', '');

        if (!in_array($status, self::STATUS_OPTIONS, true)) {
            throw new \App\Core\Exceptions\ValidationException(['status' => 'Invalid room status.']);
        }

        // Guard: a room with a guest checked in right now can never be flipped
        // to "available" by hand — it must go through check-out first.
        if ($status === Room::STATUS_AVAILABLE) {
            $stmt = db()->prepare(
                'SELECT id FROM reservations
                  WHERE room_id = ? AND status = ? LIMIT 1'
            );
            $stmt->execute([$id, Reservation::STATUS_CHECKED_IN]);
            if ($stmt->fetch()) {
                session()->flash('error', 'A guest is currently checked into this room. Use check-out instead.');
                redirect('rooms');
            }
        }

        Room::updateStatus($id, $status);
        session()->flash('success', 'Room ' . $room['room_number'] . ' is now ' . $status . '.');

        redirect('rooms');
    }
}
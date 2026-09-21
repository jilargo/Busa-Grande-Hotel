<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Validator;
use App\Models\RoomType;
use App\Services\UploadService;

/**
 * Room-type management. Admin only.
 */
final class RoomTypeController
{
    public function index(Request $request): void
    {
        view('admin/room-types/index', [
            'title'   => 'Room Types - ' . config('app.name'),
            'roomTypes' => RoomType::all(),
        ]);
    }

    public function create(Request $request): void
    {
        view('admin/room-types/form', [
            'title'    => 'New Room Type - ' . config('app.name'),
            'roomType' => null,
        ]);
    }

    public function store(Request $request): void
    {
        $data = Validator::validate($request->all(), [
            'name'       => ['required', 'max:100'],
            'slug'       => ['required', 'max:100', 'unique:room_types,slug'],
            'base_price' => ['required', 'numeric', 'min_value:0', 'max_value:100000'],
            'max_guests' => ['required', 'integer', 'min_value:1', 'max_value:50'],
            'description'=> ['max:1000'],
            'amenities'  => ['max:1000'],
        ]);

        $upload = new UploadService();
        $imagePath = $upload->storeImage($request->file('image'));

        RoomType::create($data + ['image' => $imagePath]);

        session()->flash('success', 'Room type "' . $data['name'] . '" created.');
        redirect('room-types');
    }

    public function edit(Request $request, int $id): void
    {
        $roomType = RoomType::find($id)
            ?? throw new \App\Core\Exceptions\NotFoundException('Room type not found.');

        view('admin/room-types/form', [
            'title'    => 'Edit Room Type - ' . config('app.name'),
            'roomType' => $roomType,
        ]);
    }

    public function update(Request $request, int $id): void
    {
        $roomType = RoomType::find($id)
            ?? throw new \App\Core\Exceptions\NotFoundException('Room type not found.');

        $data = Validator::validate($request->all(), [
            'name'       => ['required', 'max:100'],
            'slug'       => ['required', 'max:100', 'unique:room_types,slug,' . $id],
            'base_price' => ['required', 'numeric', 'min_value:0', 'max_value:100000'],
            'max_guests' => ['required', 'integer', 'min_value:1', 'max_value:50'],
            'description'=> ['max:1000'],
            'amenities'  => ['max:1000'],
        ]);

        $upload = new UploadService();
        $imagePath = $upload->storeImage($request->file('image')) ?? $roomType['image'];

        RoomType::update($id, $data + ['image' => $imagePath]);

        session()->flash('success', 'Room type updated.');
        redirect('room-types');
    }

    public function destroy(Request $request, int $id): void
    {
        try {
            RoomType::destroy($id);
            session()->flash('success', 'Room type deleted.');
        } catch (\PDOException $e) {
            session()->flash('error', 'This room type cannot be deleted while rooms still use it.');
        }

        redirect('room-types');
    }
}
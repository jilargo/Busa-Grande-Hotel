<?php
/**
 * Room create/edit form. Vars: $room (null when creating), $roomTypes, $statuses
 */
$isEdit = $room !== null;
$old    = session()->oldInput();
$val    = fn ($field, $default = '') => e((string) ($old[$field] ?? ($room[$field] ?? $default)));

$statusLabels = [
    'available' => 'Available', 'reserved' => 'Reserved', 'occupied' => 'Occupied',
    'maintenance' => 'Maintenance', 'cleaning' => 'Cleaning',
];
?>
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-brand-950"><?= $isEdit ? 'Edit room ' . e($room['room_number']) : 'New room' ?></h1>
    <p class="mt-1 text-sm text-slate-500">Room details, pricing and housekeeping status.</p>
</div>

<form method="post" action="<?= url($isEdit ? 'rooms/' . $room['id'] . '/update' : 'rooms') ?>" enctype="multipart/form-data" class="max-w-3xl">
    <?= \App\Core\Csrf::field() ?>

    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="room_number" class="mb-1 block text-sm font-medium text-slate-700">Room number *</label>
                <input type="text" id="room_number" name="room_number" value="<?= $val('room_number') ?>" required maxlength="20"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>
            <div>
                <label for="room_type_id" class="mb-1 block text-sm font-medium text-slate-700">Room type *</label>
                <select id="room_type_id" name="room_type_id" required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                    <option value="">Choose…</option>
                    <?php foreach ($roomTypes as $type): ?>
                        <option value="<?= (int) $type['id'] ?>" <?= (int) ($old['room_type_id'] ?? ($room['room_type_id'] ?? 0)) === (int) $type['id'] ? 'selected' : '' ?>>
                            <?= e($type['name']) ?> (<?= money($type['base_price']) ?> base)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="floor" class="mb-1 block text-sm font-medium text-slate-700">Floor *</label>
                <input type="number" id="floor" name="floor" value="<?= $val('floor', '1') ?>" required min="0" max="100"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>
            <div>
                <label for="capacity" class="mb-1 block text-sm font-medium text-slate-700">Capacity (max guests) *</label>
                <input type="number" id="capacity" name="capacity" value="<?= $val('capacity', '2') ?>" required min="1" max="50"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>
            <div>
                <label for="price_per_night" class="mb-1 block text-sm font-medium text-slate-700">Price per night ($) *</label>
                <input type="number" id="price_per_night" name="price_per_night" value="<?= $val('price_per_night') ?>" required min="0" step="0.01"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>
            <div>
                <label for="status" class="mb-1 block text-sm font-medium text-slate-700">Status *</label>
                <select id="status" name="status" required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= ($old['status'] ?? ($room['status'] ?? '')) === $s ? 'selected' : '' ?>><?= e($statusLabels[$s] ?? ucfirst($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="mt-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <label for="description" class="mb-1 block text-sm font-medium text-slate-700">Description</label>
        <textarea id="description" name="description" rows="3" maxlength="1000"
                  class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20"><?= $val('description') ?></textarea>

        <label for="amenities" class="mb-1 mt-4 block text-sm font-medium text-slate-700">Amenities</label>
        <textarea id="amenities" name="amenities" rows="3" maxlength="1000"
                  class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20"><?= $val('amenities') ?></textarea>
        <p class="mt-1 text-xs text-slate-400">One per line, e.g. "Free Wi-Fi"</p>

        <label for="image" class="mb-1 mt-4 block text-sm font-medium text-slate-700">Room photo (JPEG/PNG/WebP, max 2 MB)</label>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp,image/gif"
               class="block w-full rounded-lg border border-slate-300 text-sm text-slate-500 file:mr-3 file:rounded-l-lg file:border-0 file:bg-brand-800 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-700">
        <?php if ($isEdit && $room['image']): ?>
            <p class="mt-2 text-xs text-slate-400">Current: <?= e($room['image']) ?> — upload a new file to replace it.</p>
        <?php endif; ?>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <button type="submit" class="rounded-lg bg-brand-800 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
            <?= $isEdit ? 'Save changes' : 'Create room' ?>
        </button>
        <a href="<?= url('rooms') ?>" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-500 hover:text-slate-700">Cancel</a>
    </div>
</form>
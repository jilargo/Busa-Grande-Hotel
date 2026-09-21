<?php
/**
 * Rooms listing for staff/admin. Vars: $rooms, $roomTypes, $filters
 */
?>
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="font-display text-2xl font-semibold text-brand-950">Rooms</h1>
        <p class="mt-1 text-sm text-slate-500"><?= count($rooms) ?> room<?= count($rooms) === 1 ? '' : 's' ?> on the board.</p>
    </div>
    <a href="<?= url('rooms/create') ?>" class="inline-flex items-center gap-2 rounded-lg bg-brand-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">
        <?= App\Helpers\Icons::render('plus', 'h-4 w-4') ?> New room
    </a>
</div>

<!-- Filters -->
<form method="get" action="<?= url('rooms') ?>" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-400">Search</label>
            <input type="text" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Room number" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-400">Type</label>
            <select name="room_type_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                <option value="">All types</option>
                <?php foreach ($roomTypes as $type): ?>
                    <option value="<?= (int) $type['id'] ?>" <?= ($filters['room_type_id'] ?? '') == $type['id'] ? 'selected' : '' ?>><?= e($type['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-400">Status</label>
            <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                <option value="">All statuses</option>
                <?php foreach (['available', 'reserved', 'occupied', 'maintenance', 'cleaning'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-400">Min capacity</label>
            <input type="number" name="min_capacity" value="<?= e($filters['min_capacity'] ?? '') ?>" min="1" placeholder="Any" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full rounded-lg bg-brand-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Filter</button>
        </div>
    </div>
</form>

<!-- Table -->
<div class="mt-5 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
    <?php if (empty($rooms)): ?>
        <div class="p-6"><?php view('partials.empty', ['message' => 'No rooms match your filters.', 'action' => ['label' => 'New room', 'href' => 'rooms/create']], false); ?></div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[46rem] text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                        <th class="px-5 py-3">Room</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Floor</th>
                        <th class="px-4 py-3">Capacity</th>
                        <th class="px-4 py-3">Rate / night</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($rooms as $room): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <a href="<?= url('rooms/' . $room['id']) ?>" class="font-semibold text-brand-700 hover:underline"><?= e($room['room_number']) ?></a>
                            </td>
                            <td class="px-4 py-3 text-slate-600"><?= e($room['room_type_name']) ?></td>
                            <td class="px-4 py-3 text-slate-500"><?= (int) $room['floor'] ?></td>
                            <td class="px-4 py-3"><?= (int) $room['capacity'] ?> guests</td>
                            <td class="px-4 py-3 font-medium"><?= money($room['price_per_night']) ?></td>
                            <td class="px-4 py-3"><?= status_badge($room['status']) ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="<?= url('rooms/' . $room['id'] . '/edit') ?>" class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-brand-700" title="Edit">
                                        <?= App\Helpers\Icons::render('edit', 'h-4 w-4') ?>
                                    </a>
                                    <?php if (auth()->isAllowed(['admin'])): ?>
                                        <form method="post" action="<?= url('rooms/' . $room['id'] . '/delete') ?>" data-confirm="Delete room <?= e($room['room_number']) ?>? Deletion is blocked while reservations reference it.">
                                            <?= \App\Core\Csrf::field() ?>
                                            <button type="submit" class="rounded-lg p-2 text-slate-500 transition hover:bg-rose-50 hover:text-rose-600" title="Delete">
                                                <?= App\Helpers\Icons::render('trash', 'h-4 w-4') ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php
/** Single room detail. Vars: $room, $upcoming */
$card = 'rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200';
?>
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-3">
        <h1 class="font-display text-2xl font-semibold text-brand-950">Room <?= e($room['room_number']) ?></h1>
        <?= status_badge($room['status']) ?>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= url('rooms/' . $room['id'] . '/edit') ?>" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 transition hover:bg-slate-50">Edit</a>
        <?php if (auth()->isAllowed(['admin'])): ?>
            <form method="post" action="<?= url('rooms/' . $room['id'] . '/delete') ?>" data-confirm="Delete room <?= e($room['room_number']) ?>?">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-500">Delete</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="<?= $card ?> lg:col-span-2">
        <h2 class="font-semibold text-brand-950">Details</h2>
        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Room type</dt><dd class="mt-1 text-sm font-medium"><?= e($room['room_type_name']) ?></dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Floor</dt><dd class="mt-1 text-sm font-medium"><?= (int) $room['floor'] ?></dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Capacity</dt><dd class="mt-1 text-sm font-medium"><?= (int) $room['capacity'] ?> guests</dd></div>
            <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Price per night</dt><dd class="mt-1 font-display text-lg font-semibold text-brand-800"><?= money($room['price_per_night']) ?></dd></div>
        </dl>
        <?php if (!empty($room['description'])): ?>
            <p class="mt-4 text-sm text-slate-600"><?= e($room['description']) ?></p>
        <?php endif; ?>
        <?php if (!empty($room['amenities'])): ?>
            <div class="mt-4">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Amenities</h3>
                <div class="mt-2 flex flex-wrap gap-2">
                    <?php foreach (array_filter(array_map('trim', explode("\n", $room['amenities']))) as $item): ?>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600"><?= e($item) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick status change -->
    <div class="<?= $card ?>">
        <h2 class="font-semibold text-brand-950">Housekeeping status</h2>
        <p class="mt-1 text-xs text-slate-400">Flip the room for maintenance or cleaning. A room with a checked-in guest cannot be set to "available" by hand.</p>
        <form method="post" action="<?= url('rooms/' . $room['id'] . '/status') ?>" class="mt-4 space-y-3">
            <?= \App\Core\Csrf::field() ?>
            <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                <?php foreach (['available', 'reserved', 'maintenance', 'cleaning'] as $s): ?>
                    <option value="<?= $s ?>" <?= $room['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="w-full rounded-lg bg-brand-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Update status</button>
        </form>
    </div>
</div>

<!-- Upcoming stays -->
<div class="<?= $card ?> mt-6">
    <h2 class="font-semibold text-brand-950">Reservations referencing room <?= e($room['room_number']) ?></h2>
    <?php if (empty($upcoming)): ?>
        <p class="mt-3 text-sm text-slate-400">No reservations reference this room yet.</p>
    <?php else: ?>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[34rem] text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                        <th class="py-2.5 pr-4">Reference</th>
                        <th class="px-4 py-2.5">Guest</th>
                        <th class="px-4 py-2.5">Dates</th>
                        <th class="px-4 py-2.5">Total</th>
                        <th class="px-4 py-2.5">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($upcoming as $r): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 pr-4"><a href="<?= url('reservations/' . $r['id']) ?>" class="font-mono text-xs font-semibold text-brand-700 hover:underline"><?= e($r['reference']) ?></a></td>
                            <td class="px-4 py-3"><?= e($r['guest_first_name'] . ' ' . $r['guest_last_name']) ?></td>
                            <td class="px-4 py-3 text-slate-500"><?= e(date('j M', strtotime($r['check_in']))) ?> – <?= e(date('j M', strtotime($r['check_out']))) ?></td>
                            <td class="px-4 py-3 font-medium"><?= money($r['total_amount']) ?></td>
                            <td class="px-4 py-3"><?= status_badge($r['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
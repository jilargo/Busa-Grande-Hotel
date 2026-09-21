<?php
/** Guest detail (staff/admin). Vars: $guest, $reservations, $totalSpent */
$card = 'rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200';
?>
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-3">
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-800 font-display text-lg font-semibold text-white">
            <?= e(strtoupper(substr($guest['first_name'], 0, 1) . substr($guest['last_name'], 0, 1))) ?>
        </div>
        <div>
            <h1 class="font-display text-2xl font-semibold text-brand-950"><?= e($guest['first_name'] . ' ' . $guest['last_name']) ?></h1>
            <p class="text-sm text-slate-500"><?= e($guest['email'] ?: 'No email') ?> · <?= e($guest['phone'] ?: 'No phone') ?></p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="<?= url('reservations/create?guest_id=' . $guest['id']) ?>" class="rounded-lg bg-brand-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">New reservation</a>
        <a href="<?= url('guests/' . $guest['id'] . '/edit') ?>" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 transition hover:bg-slate-50">Edit</a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="<?= $card ?>">
        <h2 class="font-semibold text-brand-950">Guest details</h2>
        <dl class="mt-4 space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-slate-400">City</dt><dd class="font-medium"><?= e($guest['city'] ?: '—') ?></dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Country</dt><dd class="font-medium"><?= e($guest['country'] ?: '—') ?></dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Account</dt><dd class="font-medium"><?= $accountName ? e($accountName) : '<span class="text-slate-400">walk-in</span>' ?></dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Lifetime spend</dt><dd class="font-medium text-brand-800"><?= money($totalSpent) ?></dd></div>
        </dl>
        <div class="mt-5 border-t border-slate-100 pt-4">
            <a href="<?= url('checkin/create?guest_id=' . $guest['id']) ?>" class="text-sm font-semibold text-brand-700 hover:underline">Check this guest in →</a>
        </div>
    </div>

    <div class="<?= $card ?> lg:col-span-2">
        <h2 class="font-semibold text-brand-950">Reservation history</h2>
        <?php if (empty($reservations)): ?>
            <p class="mt-3 text-sm text-slate-400">This guest has no reservations yet.</p>
        <?php else: ?>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[36rem] text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                            <th class="py-2.5 pr-4">Reference</th>
                            <th class="px-4 py-2.5">Dates</th>
                            <th class="px-4 py-2.5">Room</th>
                            <th class="px-4 py-2.5">Total</th>
                            <th class="px-4 py-2.5">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($reservations as $r): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="py-3 pr-4"><a href="<?= url('reservations/' . $r['id']) ?>" class="font-mono text-xs font-semibold text-brand-700 hover:underline"><?= e($r['reference']) ?></a></td>
                                <td class="px-4 py-3 text-slate-500"><?= e(date('j M', strtotime($r['check_in']))) ?> – <?= e(date('j M', strtotime($r['check_out']))) ?></td>
                                <td class="px-4 py-3"><?= e($r['room_number'] ?: '—') ?></td>
                                <td class="px-4 py-3 font-medium"><?= money($r['total_amount']) ?></td>
                                <td class="px-4 py-3"><?= status_badge($r['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
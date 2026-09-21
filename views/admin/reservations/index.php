<?php
/** Reservations listing (staff/admin). Vars: $reservations, $filters */
?>
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="font-display text-2xl font-semibold text-brand-950">Reservations</h1>
        <p class="mt-1 text-sm text-slate-500"><?= count($reservations) ?> booking<?= count($reservations) === 1 ? '' : 's' ?> found.</p>
    </div>
    <a href="<?= url('reservations/create') ?>" class="inline-flex items-center gap-2 rounded-lg bg-brand-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">
        <?= App\Helpers\Icons::render('plus', 'h-4 w-4') ?> New reservation
    </a>
</div>

<form method="get" action="<?= url('reservations') ?>" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-400">Search</label>
            <input type="text" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Reference, guest, room…" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-400">Status</label>
            <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                <option value="">All statuses</option>
                <?php foreach (['pending', 'confirmed', 'checked_in', 'checked_out', 'no_show', 'cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-400">Check-in from</label>
            <input type="date" name="check_in_from" value="<?= e($filters['check_in_from'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-400">Check-in to</label>
            <input type="date" name="check_in_to" value="<?= e($filters['check_in_to'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full rounded-lg bg-brand-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Filter</button>
        </div>
    </div>
</form>

<div class="mt-5 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
    <?php if (empty($reservations)): ?>
        <div class="p-6"><?php view('partials.empty', ['message' => 'No reservations match your filters.', 'action' => ['label' => 'New reservation', 'href' => 'reservations/create']], false); ?></div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[56rem] text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                        <th class="px-5 py-3">Reference</th>
                        <th class="px-4 py-3">Guest</th>
                        <th class="px-4 py-3">Room</th>
                        <th class="px-4 py-3">Dates</th>
                        <th class="px-4 py-3">Guests</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Paid</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($reservations as $r): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3"><a href="<?= url('reservations/' . $r['id']) ?>" class="font-mono text-xs font-semibold text-brand-700 hover:underline"><?= e($r['reference']) ?></a></td>
                            <td class="px-4 py-3"><?= e($r['guest_first_name'] . ' ' . $r['guest_last_name']) ?></td>
                            <td class="px-4 py-3"><?= e($r['room_number'] ?? '—') ?></td>
                            <td class="px-4 py-3 text-slate-500"><?= e(date('j M', strtotime($r['check_in']))) ?> – <?= e(date('j M', strtotime($r['check_out']))) ?></td>
                            <td class="px-4 py-3"><?= (int) $r['guests_count'] ?></td>
                            <td class="px-4 py-3 font-medium"><?= money($r['total_amount']) ?></td>
                            <td class="px-4 py-3 text-slate-500"><?= money($r['total_paid']) ?></td>
                            <td class="px-4 py-3"><?= status_badge($r['status']) ?></td>
                            <td class="px-4 py-3">
                                <a href="<?= url('reservations/' . $r['id']) ?>" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 ring-1 ring-brand-200 transition hover:bg-brand-50">
                                    View <?= App\Helpers\Icons::render('chevron-right', 'h-3.5 w-3.5') ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
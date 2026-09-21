<?php
/** Admin dashboard. Vars: $stats, $roomStatuses, $recentReservations, $recentCheckIns, $recentPayments, $user */
$card = 'rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200';
?>
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-brand-950">Good <?= date('H') < 12 ? 'morning' : (date('H') < 18 ? 'afternoon' : 'evening') ?>, <?= e(explode(' ', $user['name'])[0]) ?></h1>
    <p class="mt-1 text-sm text-slate-500">Here is how the house looks today, <?= date('l, j F Y') ?>.</p>
</div>

<!-- ===== Stat cards ===== -->
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="<?= $card ?>">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Available rooms</p>
                <p class="mt-1 font-display text-3xl font-semibold text-brand-950"><?= (int) $stats['available_rooms'] ?></p>
            </div>
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><?= App\Helpers\Icons::render('bed') ?></span>
        </div>
        <p class="mt-2 text-xs text-slate-400"><?= (int) $stats['cleaning_rooms'] ?> in cleaning</p>
    </div>

    <div class="<?= $card ?>">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Occupied</p>
                <p class="mt-1 font-display text-3xl font-semibold text-brand-950"><?= (int) $stats['occupied_rooms'] ?></p>
            </div>
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"><?= App\Helpers\Icons::render('key') ?></span>
        </div>
        <p class="mt-2 text-xs text-slate-400"><?= (int) $stats['total_rooms'] ?> rooms in total</p>
    </div>

    <div class="<?= $card ?>">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Arrivals · Departures</p>
                <p class="mt-1 font-display text-3xl font-semibold text-brand-950"><?= (int) $stats['today_check_ins'] ?> <span class="text-base text-slate-400">/</span> <?= (int) $stats['today_check_outs'] ?></p>
            </div>
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600"><?= App\Helpers\Icons::render('checkin') ?></span>
        </div>
        <p class="mt-2 text-xs text-slate-400">Due today, per the ledger</p>
    </div>

    <div class="<?= $card ?>">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Revenue · month</p>
                <p class="mt-1 font-display text-3xl font-semibold text-emerald-600"><?= money($stats['revenue_month']) ?></p>
            </div>
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gold-100 text-gold-600"><?= App\Helpers\Icons::render('wallet') ?></span>
        </div>
        <p class="mt-2 text-xs text-slate-400"><?= (int) $stats['pending_count'] ?> pending reservations</p>
    </div>
</div>

<!-- ===== Second row ===== -->
<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <!-- Recent reservations -->
    <section class="<?= $card ?> lg:col-span-2">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-brand-950">Recent reservations</h2>
            <a href="<?= url('reservations') ?>" class="text-sm font-semibold text-brand-700 hover:text-brand-900">View all →</a>
        </div>

        <?php if (empty($recentReservations)): ?>
            <?php view('partials.empty', ['message' => 'No reservations recorded yet.', 'action' => ['label' => 'New reservation', 'href' => 'reservations/create']], false); ?>
        <?php else: ?>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[36rem] text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                            <th class="py-2.5 pr-4">Reference</th>
                            <th class="px-4 py-2.5">Guest</th>
                            <th class="px-4 py-2.5">Room</th>
                            <th class="px-4 py-2.5">Dates</th>
                            <th class="px-4 py-2.5">Total</th>
                            <th class="px-4 py-2.5">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($recentReservations as $r): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="py-3 pr-4">
                                    <a href="<?= url('reservations/' . $r['id']) ?>" class="font-mono text-xs font-semibold text-brand-700 hover:underline"><?= e($r['reference']) ?></a>
                                </td>
                                <td class="px-4 py-3"><?= e($r['guest_first_name'] . ' ' . $r['guest_last_name']) ?></td>
                                <td class="px-4 py-3"><?= e($r['room_number']) ?></td>
                                <td class="px-4 py-3 text-slate-500"><?= e(date('j M', strtotime($r['check_in']))) ?> – <?= e(date('j M', strtotime($r['check_out']))) ?></td>
                                <td class="px-4 py-3 font-medium"><?= money($r['total_amount']) ?></td>
                                <td class="px-4 py-3"><?= status_badge($r['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- Right column: recent check-ins -->
    <section class="<?= $card ?>">
        <h2 class="font-semibold text-brand-950">Recent check-ins</h2>
        <?php if (empty($recentCheckIns)): ?>
            <p class="mt-3 text-sm text-slate-400">No check-ins yet today.</p>
        <?php else: ?>
            <ul class="mt-3 divide-y divide-slate-100">
                <?php foreach ($recentCheckIns as $ci): ?>
                    <li class="flex items-center gap-3 py-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">
                            <?= e(strtoupper(substr($ci['first_name'], 0, 1) . substr($ci['last_name'], 0, 1))) ?>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-800"><?= e($ci['first_name'] . ' ' . $ci['last_name']) ?></p>
                            <p class="text-xs text-slate-400"><?= e($ci['reference']) ?> · Room <?= e($ci['room_number']) ?></p>
                        </div>
                        <time class="shrink-0 text-xs text-slate-400"><?= e(date('j M H:i', strtotime($ci['actual_check_in']))) ?></time>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<!-- ===== Room availability heat summary ===== -->
<section class="<?= $card ?> mt-6">
    <h2 class="font-semibold text-brand-950">Room availability</h2>
    <div class="mt-4 grid gap-3 sm:grid-cols-5">
        <?php
        $segments = [
            ['Available', $roomStatuses['available'], 'bg-emerald-500'],
            ['Reserved', $roomStatuses['reserved'], 'bg-sky-500'],
            ['Occupied', $roomStatuses['occupied'], 'bg-indigo-500'],
            ['Cleaning', $roomStatuses['cleaning'], 'bg-amber-500'],
            ['Maintenance', $roomStatuses['maintenance'], 'bg-rose-500'],
        ];
        $total = max(1, (int) $stats['total_rooms']);
        foreach ($segments as [$label, $count, $color]): ?>
            <div class="rounded-xl bg-slate-50 p-4">
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full <?= $color ?>"></span>
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= e($label) ?></span>
                </div>
                <p class="mt-2 font-display text-2xl font-semibold text-brand-950"><?= (int) $count ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php
/** Staff dashboard. Vars: $user, $available_rooms, $occupied_rooms, $today_check_ins, $today_check_outs, $pending_count, $recentReservations */
$card = 'rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200';
?>
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-brand-950">Good <?= date('H') < 12 ? 'morning' : (date('H') < 18 ? 'afternoon' : 'evening') ?>, <?= e(explode(' ', $user['name'])[0]) ?></h1>
    <p class="mt-1 text-sm text-slate-500">Front desk overview · <?= date('l, j F Y') ?></p>
</div>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="<?= $card ?>">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Available rooms</p>
        <p class="mt-1 font-display text-3xl font-semibold text-brand-950"><?= (int) $available_rooms ?></p>
        <a href="<?= url('rooms') ?>" class="mt-2 inline-block text-xs font-semibold text-brand-700 hover:underline">Open room board →</a>
    </div>
    <div class="<?= $card ?>">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Occupied</p>
        <p class="mt-1 font-display text-3xl font-semibold text-brand-950"><?= (int) $occupied_rooms ?></p>
    </div>
    <div class="<?= $card ?>">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Check-ins today</p>
        <p class="mt-1 font-display text-3xl font-semibold text-brand-950"><?= (int) $today_check_ins ?></p>
        <a href="<?= url('reservations') ?>" class="mt-2 inline-block text-xs font-semibold text-brand-700 hover:underline">Find a booking →</a>
    </div>
    <div class="<?= $card ?>">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Check-outs today</p>
        <p class="mt-1 font-display text-3xl font-semibold text-brand-950"><?= (int) $today_check_outs ?></p>
        <p class="mt-2 text-xs text-slate-400"><?= (int) $pending_count ?> pending elsewhere</p>
    </div>
</div>

<section class="<?= $card ?> mt-6">
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-brand-950">Upcoming & active reservations</h2>
        <a href="<?= url('reservations/create') ?>" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-800 px-3 py-1.5 text-sm font-semibold text-white transition hover:bg-brand-700">
            <?= App\Helpers\Icons::render('plus', 'h-4 w-4') ?> New reservation
        </a>
    </div>

    <?php if (empty($recentReservations)): ?>
        <?php view('partials.empty', ['message' => 'No reservations yet.', 'hint' => 'Create the first booking from the reservations screen.'], false); ?>
    <?php else: ?>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[40rem] text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                        <th class="py-2.5 pr-4">Guest</th>
                        <th class="px-4 py-2.5">Room</th>
                        <th class="px-4 py-2.5">Check-in</th>
                        <th class="px-4 py-2.5">Check-out</th>
                        <th class="px-4 py-2.5">Status</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($recentReservations as $r): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 pr-4">
                                <p class="font-medium text-slate-800"><?= e($r['guest_first_name'] . ' ' . $r['guest_last_name']) ?></p>
                                <p class="font-mono text-xs text-slate-400"><?= e($r['reference']) ?></p>
                            </td>
                            <td class="px-4 py-3"><?= e($r['room_number']) ?></td>
                            <td class="px-4 py-3"><?= e(date('d M Y', strtotime($r['check_in']))) ?></td>
                            <td class="px-4 py-3"><?= e(date('d M Y', strtotime($r['check_out']))) ?></td>
                            <td class="px-4 py-3"><?= status_badge($r['status']) ?></td>
                            <td class="px-4 py-3 text-right">
                                <a href="<?= url('reservations/' . $r['id']) ?>" class="font-semibold text-brand-700 hover:underline">Open →</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
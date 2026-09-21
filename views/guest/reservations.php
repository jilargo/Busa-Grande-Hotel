<?php
/** Guest reservation list. Vars: $reservations */
?>
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="font-display text-2xl font-semibold text-brand-950">My reservations</h1>
        <p class="mt-1 text-sm text-slate-500">Every stay, past and future.</p>
    </div>
    <a href="<?= url('account/book') ?>" class="inline-flex items-center gap-2 rounded-lg bg-brand-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">
        <?= App\Helpers\Icons::render('plus', 'h-4 w-4') ?> New booking
    </a>
</div>

<?php if (empty($reservations)): ?>
    <?php view('partials.empty', [
        'message' => 'You have no reservations yet.',
        'hint'    => 'When you book a stay it will appear here.',
        'action'  => ['label' => 'Book a room', 'href' => 'account/book'],
    ], false); ?>
<?php else: ?>
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[40rem] text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                        <th class="px-5 py-3">Reference</th>
                        <th class="px-4 py-3">Room</th>
                        <th class="px-4 py-3">Check-in</th>
                        <th class="px-4 py-3">Check-out</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($reservations as $r): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-mono text-xs font-semibold text-brand-700"><?= e($r['reference']) ?></td>
                            <td class="px-4 py-3">
                                <?= e($r['room_number']) ?>
                                <span class="text-xs text-slate-400">· <?= e($r['room_type_name']) ?></span>
                            </td>
                            <td class="px-4 py-3"><?= e(date('j M Y', strtotime($r['check_in']))) ?></td>
                            <td class="px-4 py-3"><?= e(date('j M Y', strtotime($r['check_out']))) ?></td>
                            <td class="px-4 py-3 font-medium"><?= money($r['total_amount']) ?></td>
                            <td class="px-4 py-3"><?= status_badge($r['status']) ?></td>
                            <td class="px-4 py-3 text-right">
                                <a href="<?= url('account/reservations/' . $r['id']) ?>" class="font-semibold text-brand-700 hover:underline">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
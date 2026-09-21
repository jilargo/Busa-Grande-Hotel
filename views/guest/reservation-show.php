<?php
/** Guest single reservation. Vars: $reservation, $payments */
$r = $reservation;
$payable = in_array($r['status'], ['pending', 'confirmed'], true);
$ps = App\Models\Reservation::paymentStatus($r);
?>
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <div class="flex items-center gap-3">
            <h1 class="font-display text-2xl font-semibold text-brand-950"><?= e($r['reference']) ?></h1>
            <?= status_badge($r['status']) ?>
        </div>
        <p class="mt-1 text-sm text-slate-500">Room <?= e($r['room_number']) ?> · <?= e($r['room_type_name']) ?></p>
    </div>
    <?php if ($payable): ?>
        <form method="post" action="<?= url('account/reservations/' . $r['id'] . '/cancel') ?>" data-confirm="Are you sure you want to cancel this reservation?">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="rounded-lg border border-rose-300 bg-white px-4 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50">Cancel reservation</button>
        </form>
    <?php endif; ?>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 lg:col-span-2">
        <div class="border-b border-slate-100 bg-brand-800 p-6 text-white">
            <p class="text-xs uppercase tracking-widest text-brand-300">Stay summary</p>
            <p class="mt-2 font-display text-xl font-semibold">
                <?= e(date('D, j M Y', strtotime($r['check_in']))) ?> → <?= e(date('D, j M Y', strtotime($r['check_out']))) ?>
            </p>
            <p class="mt-1 text-sm text-brand-200"><?= (int) $r['nights'] ?> night<?= (int) $r['nights'] === 1 ? '' : 's' ?> · <?= (int) $r['guests_count'] ?> guest<?= (int) $r['guests_count'] === 1 ? '' : 's' ?></p>
        </div>

        <dl class="divide-y divide-slate-100 text-sm">
            <div class="flex items-center justify-between px-6 py-4">
                <dt class="text-slate-500">Nightly rate (charged at booking)</dt>
                <dd class="font-medium text-slate-800"><?= money($r['room_price_snapshot']) ?></dd>
            </div>
            <div class="flex items-center justify-between px-6 py-4">
                <dt class="text-slate-500">Total stay</dt>
                <dd class="font-semibold text-brand-950"><?= money($r['total_amount']) ?></dd>
            </div>
            <div class="flex items-center justify-between px-6 py-4">
                <dt class="text-slate-500">Amount paid</dt>
                <dd class="font-medium <?= ((float) $r['total_paid'] >= (float) $r['total_amount']) ? 'text-emerald-600' : 'text-amber-600' ?>"><?= money($r['total_paid']) ?></dd>
            </div>
            <div class="flex items-center justify-between px-6 py-4">
                <dt class="text-slate-500">Balance due</dt>
                <dd class="text-sm font-semibold text-slate-800"><?= money(max(0, (float) $r['total_amount'] - (float) $r['total_paid'])) ?></dd>
            </div>
            <?php if (!empty($r['special_requests'])): ?>
                <div class="px-6 py-4">
                    <dt class="text-slate-500">Special requests</dt>
                    <dd class="mt-1 rounded-lg bg-amber-50 px-3 py-2 text-amber-800"><?= e($r['special_requests']) ?></dd>
                </div>
            <?php endif; ?>
        </dl>
    </div>

    <!-- Payments -->
    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-brand-950">Payments</h2>
            <span><?= status_badge($ps, payment_status_label($ps)) ?></span>
        </div>

        <?php if (empty($payments)): ?>
            <p class="mt-4 text-sm text-slate-400">No payments recorded. You can pay at the front desk on arrival.</p>
        <?php else: ?>
            <ul class="mt-3 divide-y divide-slate-100">
                <?php foreach ($payments as $p): ?>
                    <li class="flex items-center justify-between py-3 text-sm">
                        <div>
                            <p class="font-medium text-slate-800">
                                <?= money($p['amount']) ?>
                                <?php if ((float) $p['amount'] < 0): ?><span class="text-xs text-slate-400">(refund)</span><?php endif; ?>
                            </p>
                            <p class="text-xs text-slate-400"><?= e(date('j M Y', strtotime($p['payment_date']))) ?> · <?= e(ucwords(str_replace('_', ' ', $p['method']))) ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <a href="<?= url('account/book') ?>" class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-900">Book another stay →</a>
    </div>
</div>
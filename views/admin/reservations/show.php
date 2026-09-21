<?php
/** Reservation detail (staff/admin). Vars: $reservation, $payments, $checkIn, $checkOut */
$r      = $reservation;
$total  = (float) $r['total_amount'];
$paid   = (float) $r['total_paid'];
$due    = $total - $paid;
$card   = 'rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200';
$status = $r['status'];
?>
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-3">
        <h1 class="font-display text-2xl font-semibold text-brand-950">
            <span class="font-mono text-xl"><?= e($r['reference']) ?></span>
        </h1>
        <?= status_badge($status) ?>
        <?= payment_status_label(\App\Models\Reservation::paymentStatus($r)) ?>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <?php if (in_array($status, ['pending', 'confirmed'], true)): ?>
            <a href="<?= url('reservations/' . $r['id'] . '/check-in') ?>" class="rounded-lg bg-brand-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Check in</a>
        <?php endif; ?>
        <?php if ($status === 'checked_in'): ?>
            <a href="<?= url('reservations/' . $r['id'] . '/check-out') ?>" class="rounded-lg bg-brand-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Check out</a>
        <?php endif; ?>
        <?php if (in_array($status, ['pending', 'confirmed'], true)): ?>
            <form method="post" action="<?= url('reservations/' . $r['id'] . '/status') ?>" data-confirm="Mark <?= e($r['reference']) ?> as no-show?">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" name="status" value="no_show">
                <button type="submit" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-600 ring-1 ring-slate-300 transition hover:bg-slate-50">No-show</button>
            </form>
        <?php endif; ?>
        <?php if (in_array($status, ['pending', 'confirmed'], true)): ?>
            <form method="post" action="<?= url('reservations/' . $r['id'] . '/cancel') ?>" data-confirm="Cancel reservation <?= e($r['reference']) ?>?">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-500">Cancel</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <!-- Stay details -->
    <div class="<?= $card ?> lg:col-span-2">
        <h2 class="font-semibold text-brand-950">Stay</h2>
        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Guest</dt>
                <dd class="mt-1 text-sm font-medium"><?= e($r['guest_first_name'] . ' ' . $r['guest_last_name']) ?></dd>
                <dd class="text-xs text-slate-500"><?= e($r['guest_email'] ?: '—') ?> · <?= e($r['guest_phone'] ?: '—') ?></dd>
                <a href="<?= url('guests/' . $r['guest_id']) ?>" class="mt-1 inline-block text-xs font-semibold text-brand-700 hover:underline">Guest profile →</a>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Room (at booking)</dt>
                <dd class="mt-1 text-sm font-medium"><?= e($r['room_number'] === null ? '— not yet assigned' : 'Room ' . $r['room_number']) ?></dd>
                <dd class="text-xs text-slate-500"><?= e($r['room_type_name'] ?: '') ?> <?= e($r['room_type_name'] ? '·' : '') ?> <?= money($r['room_price_snapshot']) ?>/night at booking</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Dates</dt>
                <dd class="mt-1 text-sm font-medium"><?= e(date('j M Y', strtotime($r['check_in']))) ?> → <?= e(date('j M Y', strtotime($r['check_out']))) ?></dd>
                <dd class="text-xs text-slate-500"><?= (int) $r['nights'] ?> night<?= (int) $r['nights'] === 1 ? '' : 's' ?> · <?= (int) $r['guests_count'] ?> guest<?= (int) $r['guests_count'] === 1 ? '' : 's' ?></dd>
            </div>
        </dl>
        <?php if (!empty($r['special_requests'])): ?>
            <div class="mt-4 rounded-lg border border-gold-200 bg-gold-50 px-4 py-3 text-sm text-gold-800">
                <strong>Special requests:</strong> <?= e($r['special_requests']) ?>
            </div>
        <?php endif; ?>
        <?php if ($r['user_id']): ?>
            <p class="mt-4 text-xs text-slate-400">Recorded by user #<?= (int) $r['user_id'] ?>.</p>
        <?php endif; ?>
    </div>

    <!-- Totals -->
    <div class="<?= $card ?>">
        <h2 class="font-semibold text-brand-950">Totals</h2>
        <dl class="mt-4 space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Room charge</dt><dd class="font-medium"><?= money($total) ?></dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Paid</dt><dd class="font-medium text-emerald-600"><?= money($paid) ?></dd></div>
            <div class="flex justify-between border-t border-slate-100 pt-3"><dt class="font-semibold text-brand-950">Balance due</dt><dd class="font-display text-lg font-semibold text-brand-800"><?= money($due) ?></dd></div>
        </dl>
        <?php if (in_array($status, ['confirmed', 'checked_in'], true)): ?>
            <p class="mt-3 text-xs text-slate-400">Use the payment form below to record money received.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Check-in / check-out records -->
<div class="<?= $card ?> mt-6">
    <h2 class="font-semibold text-brand-950">Log</h2>
    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Check-in</p>
            <?php if ($checkIn): ?>
                <p class="mt-2 text-sm"><?= e(date('j M Y, H:i', strtotime($checkIn['actual_check_in']))) ?></p>
                <p class="text-xs text-slate-500">by staff #<?= (int) $checkIn['checked_in_by'] ?> · room #<?= (int) $checkIn['room_id'] ?></p>
            <?php else: ?>
                <p class="mt-2 text-sm text-slate-400">Not checked in yet.</p>
            <?php endif; ?>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Check-out</p>
            <?php if ($checkOut): ?>
                <p class="mt-2 text-sm"><?= e(date('j M Y, H:i', strtotime($checkOut['actual_check_out']))) ?></p>
                <p class="text-xs text-slate-500">by staff #<?= (int) $checkOut['checked_out_by'] ?></p>
                <?php if ($checkOut['notes']): ?><p class="mt-1 text-xs text-slate-500"><?= e($checkOut['notes']) ?></p><?php endif; ?>
            <?php else: ?>
                <p class="mt-2 text-sm text-slate-400">Not checked out yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Payments -->
<div id="payments" class="<?= $card ?> mt-6">
    <h2 class="font-semibold text-brand-950">Payments</h2>
    <?php if (empty($payments)): ?>
        <p class="mt-3 text-sm text-slate-400">No payments recorded yet.</p>
    <?php else: ?>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[38rem] text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                        <th class="py-2.5 pr-4">Date</th>
                        <th class="px-4 py-2.5">Method</th>
                        <th class="px-4 py-2.5">Reference</th>
                        <th class="px-4 py-2.5">By</th>
                        <th class="px-4 py-2.5 text-right">Amount</th>
                        <th class="px-4 py-2.5 text-right">Refund</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($payments as $p): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 pr-4 text-slate-500"><?= e(date('j M Y', strtotime($p['payment_date']))) ?></td>
                            <td class="px-4 py-3"><?= e(ucfirst(str_replace('_', ' ', $p['method']))) ?></td>
                            <td class="px-4 py-3 font-mono text-xs"><?= e($p['reference_number'] ?? '—') ?></td>
                            <td class="px-4 py-3 text-slate-500"><?= e($p['recorded_by_name'] ?? '—') ?></td>
                            <td class="px-4 py-3 text-right font-medium <?= $p['amount'] < 0 ? 'text-rose-600' : '' ?>"><?= money($p['amount']) ?></td>
                            <td class="px-4 py-3 text-right">
                                <?php if ($p['amount'] > 0 && $p['status'] === 'completed'): ?>
                                    <form method="post" action="<?= url('payments/' . $p['id'] . '/refund') ?>" data-confirm="Record a full refund for this payment?">
                                        <?= \App\Core\Csrf::field() ?>
                                        <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline">Refund</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h3 class="mt-6 text-sm font-semibold text-brand-950">Record a payment</h3>
    <form method="post" action="<?= url('reservations/' . $r['id'] . '/payments') ?>" class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <?= \App\Core\Csrf::field() ?>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-400">Amount ($)</label>
            <input type="number" name="amount" step="0.01" min="0.01" value="<?= $due > 0 ? number_format($due, 2, '.', '') : '' ?>" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-400">Method</label>
            <select name="method" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                <?php foreach (\App\Models\Payment::METHODS as $m): ?>
                    <option value="<?= $m ?>"><?= e(ucfirst(str_replace('_', ' ', $m))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-400">Date</label>
            <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-400">Reference #</label>
            <input type="text" name="reference_number" maxlength="100" placeholder="Optional"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full rounded-lg bg-brand-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Record</button>
        </div>
    </form>
</div>
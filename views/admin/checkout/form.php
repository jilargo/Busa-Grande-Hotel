<?php
/** Check-out confirmation (staff/admin). Vars: $reservation, $payments */
$r     = $reservation;
$total = (float) $r['total_amount'];
$paid  = (float) $r['total_paid'];
$due   = $total - $paid;
$card  = 'rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200';
?>
<div class="mb-6 flex items-center gap-3">
    <h1 class="font-display text-2xl font-semibold text-brand-950">Check out</h1>
    <span class="font-mono text-sm font-semibold text-brand-700"><?= e($r['reference']) ?></span>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="<?= $card ?> lg:col-span-2">
        <h2 class="font-semibold text-brand-950">Review before departure</h2>
        <p class="mt-1 text-sm text-slate-500">Completing this sends room <strong><?= e($r['room_number']) ?></strong> to housekeeping (cleaning).</p>

        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Guest</dt>
                <dd class="mt-1 text-sm font-medium"><?= e($r['guest_first_name'] . ' ' . $r['guest_last_name']) ?></dd>
                <dd class="text-xs text-slate-500"><?= e($r['guest_email'] ?: '—') ?> · <?= e($r['guest_phone'] ?: '—') ?></dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Already paid</dt>
                <dd class="mt-1 text-sm font-medium"><?= money($paid) ?> <span class="text-xs text-slate-400">of <?= money($total) ?></span></dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Balance due</dt>
                <dd class="mt-1 font-display text-xl font-semibold <?= $due <= 0 ? 'text-emerald-600' : 'text-rose-600' ?>">
                    <?= money($due) ?>
                </dd>
            </div>
        </dl>

        <?php if (count($payments)): ?>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[30rem] text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                            <th class="py-2 pr-4">Date</th>
                            <th class="px-4 py-2">Method</th>
                            <th class="px-4 py-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td class="py-2 pr-4 text-slate-500"><?= e(date('j M Y', strtotime($p['payment_date']))) ?></td>
                                <td class="px-4 py-2"><?= e(ucfirst(str_replace('_', ' ', $p['method']))) ?></td>
                                <td class="px-4 py-2 text-right font-medium <?= $p['amount'] < 0 ? 'text-rose-600' : '' ?>"><?= money($p['amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= url('reservations/' . $r['id'] . '/check-out') ?>" class="mt-6 border-t border-slate-100 pt-5">
            <?= \App\Core\Csrf::field() ?>

            <?php if ($due > 0): ?>
                <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    There is still a balance of <strong><?= money($due) ?></strong>. You can record the settlement on the
                    <a href="<?= url('reservations/' . $r['id'] . '#payments') ?>" class="font-semibold underline">reservation page</a> before or after this check-out.
                </div>
            <?php endif; ?>

            <label for="notes" class="mb-1 block text-sm font-medium text-slate-700">Departure notes</label>
            <textarea id="notes" name="notes" rows="3" maxlength="1000" placeholder="Damages, late check-out, extras…"
                      class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20"></textarea>

            <div class="mt-4 flex items-center gap-3">
                <button type="submit" class="rounded-lg bg-brand-800 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Confirm check-out</button>
                <a href="<?= url('reservations/' . $r['id']) ?>" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-500 hover:text-slate-700">Back</a>
            </div>
        </form>
    </div>

    <div class="<?= $card ?>">
        <h2 class="font-semibold text-brand-950">During this stay</h2>
        <dl class="mt-4 space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Room charge</dt><dd class="font-medium"><?= money($total) ?></dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Paid so far</dt><dd class="font-medium text-emerald-600"><?= money($paid) ?></dd></div>
            <div class="flex justify-between border-t border-slate-100 pt-3"><dt class="font-semibold text-brand-950">Balance due</dt><dd class="font-medium <?= $due <= 0 ? 'text-emerald-600' : 'text-rose-600' ?>"><?= money($due) ?></dd></div>
        </dl>
        <p class="mt-4 rounded-lg bg-slate-50 px-4 py-3 text-xs text-slate-500">
            After check-out the room becomes <span class="font-semibold text-slate-700">cleaning</span> and is not bookable again until housekeeping marks it available.
        </p>
    </div>
</div>
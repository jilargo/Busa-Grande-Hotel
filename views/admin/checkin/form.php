<?php
/** Check-in confirmation (staff/admin). Vars: $reservation */
$r = $reservation;
$card = 'rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200';
?>
<div class="mb-6 flex items-center gap-3">
    <h1 class="font-display text-2xl font-semibold text-brand-950">Check in</h1>
    <span class="font-mono text-sm font-semibold text-brand-700"><?= e($r['reference']) ?></span>
</div>

<div class="max-w-3xl">
    <div class="<?= $card ?>">
        <h2 class="font-semibold text-brand-950">Confirm the arrival</h2>
        <p class="mt-1 text-sm text-slate-500">This records the actual check-in and marks room <strong><?= e($r['room_number']) ?></strong> as occupied.</p>

        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Guest</dt>
                <dd class="mt-1 text-sm font-medium"><?= e($r['guest_first_name'] . ' ' . $r['guest_last_name']) ?></dd>
                <dd class="text-xs text-slate-500"><?= e($r['guest_email'] ?: '—') ?> · <?= e($r['guest_phone'] ?: '—') ?></dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Room</dt>
                <dd class="mt-1 text-sm font-medium"><?= e($r['room_number'] ?? '—') ?> (<?= e($r['room_type_name'] ?: '—') ?>)</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Planned dates</dt>
                <dd class="mt-1 text-sm font-medium"><?= e(date('j M Y', strtotime($r['check_in']))) ?> → <?= e(date('j M Y', strtotime($r['check_out']))) ?></dd>
                <dd class="text-xs text-slate-500"><?= (int) $r['nights'] ?> night<?= (int) $r['nights'] === 1 ? '' : 's' ?></dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Guests</dt>
                <dd class="mt-1 text-sm font-medium"><?= (int) $r['guests_count'] ?></dd>
            </div>
        </dl>

        <div class="mt-6 border-t border-slate-100 pt-5">
            <p class="text-sm text-slate-600">
                The check-in timestamp is taken now, on <strong><?= date('D, j M Y H:i') ?></strong>.
            </p>
            <div class="mt-4 flex items-center gap-3">
                <form method="post" action="<?= url('reservations/' . $r['id'] . '/check-in') ?>">
                    <?= \App\Core\Csrf::field() ?>
                    <button type="submit" class="rounded-lg bg-brand-800 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Confirm check-in</button>
                </form>
                <a href="<?= url('reservations/' . $r['id']) ?>" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-500 hover:text-slate-700">Back</a>
            </div>
        </div>
    </div>
</div>
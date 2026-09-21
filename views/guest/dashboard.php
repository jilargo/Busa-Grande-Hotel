<?php
/** Guest dashboard. Vars: $user, $guest, $upcoming, $active, $past */
$card = 'rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200';
$firstName = $guest['first_name'] !== '' ? $guest['first_name'] : $user['name'];
?>
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="font-display text-2xl font-semibold text-brand-950">Hello, <?= e($firstName) ?></h1>
        <p class="mt-1 text-sm text-slate-500">
            <?php if ($guest): ?>
                Member since <?= e(date('F Y', strtotime($guest['created_at']))) ?> · <?= count($active) ?> active stay
            <?php else: ?>
                Welcome to Busa Grande Hotel.
            <?php endif; ?>
        </p>
    </div>
    <a href="<?= url('account/book') ?>" class="inline-flex items-center gap-2 rounded-xl bg-gold-500 px-5 py-2.5 text-sm font-semibold text-brand-950 shadow-lg shadow-gold-500/20 transition hover:bg-gold-400">
        Book a room
    </a>
</div>

<!-- Active stay -->
<?php if (!empty($active)): ?>
    <?php $booking = $active[0]; ?>
    <div class="mb-6 overflow-hidden rounded-2xl bg-gradient-to-r from-brand-800 to-brand-950 text-white shadow-lg">
        <div class="p-6 sm:p-8">
            <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-gold-400">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span> You are currently staying with us
            </p>
            <div class="mt-4 grid gap-6 sm:grid-cols-3">
                <div>
                    <p class="text-xs uppercase tracking-wide text-brand-300">Reservation</p>
                    <p class="mt-1 font-mono text-sm text-white"><?= e($booking['reference']) ?></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-brand-300">Room</p>
                    <p class="mt-1 font-display text-2xl font-semibold text-gold-300"><?= e($booking['room_number']) ?></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-brand-300">Until</p>
                    <p class="mt-1 text-sm font-medium"><?= e(date('j F Y', strtotime($booking['check_out']))) ?></p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="grid gap-6 lg:grid-cols-3">
    <!-- Upcoming -->
    <section class="<?= $card ?> lg:col-span-2">
        <h2 class="font-semibold text-brand-950">Upcoming reservations</h2>
        <?php if (empty($upcoming)): ?>
            <?php view('partials.empty', [
                'message' => 'No upcoming stays.',
                'hint'    => 'Rooms are filling fast — why not book your next stay?',
                'action'  => ['label' => 'Browse rooms', 'href' => 'account/book'],
            ], false); ?>
        <?php else: ?>
            <div class="mt-4 space-y-3">
                <?php foreach ($upcoming as $r): ?>
                    <a href="<?= url('account/reservations/' . $r['id']) ?>" class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 p-4 transition hover:border-brand-300 hover:bg-brand-50/40">
                        <div class="min-w-0">
                            <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-800">
                                <?= e($r['room_number']) ?>
                                <span class="text-xs font-normal text-slate-400"><?= e($r['room_type_name']) ?></span>
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                <?= e(date('D, j M', strtotime($r['check_in']))) ?> – <?= e(date('D, j M', strtotime($r['check_out']))) ?>
                                · <?= (int) $r['nights'] ?> night<?= (int) $r['nights'] === 1 ? '' : 's' ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <?= status_badge($r['status']) ?>
                            <p class="mt-1 text-sm font-semibold text-brand-900"><?= money($r['total_amount']) ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Profile snapshot -->
    <section class="<?= $card ?>">
        <h2 class="font-semibold text-brand-950">Your details</h2>
        <dl class="mt-4 space-y-3 text-sm">
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Name</dt><dd class="font-medium"><?= e($guest['first_name'] . ' ' . $guest['last_name']) ?></dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Email</dt><dd class="truncate font-medium"><?= e($guest['email'] ?: $user['email']) ?></dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Phone</dt><dd class="font-medium"><?= e($guest['phone'] ?: '—') ?></dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">City</dt><dd class="font-medium"><?= e($guest['city'] ?: '—') ?></dd></div>
        </dl>
        <a href="<?= url('account/profile') ?>" class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-900">Edit profile →</a>
    </section>
</div>

<!-- Past stays -->
<?php if (!empty($past)): ?>
    <section class="<?= $card ?> mt-6">
        <h2 class="font-semibold text-brand-950">Previous stays</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[36rem] text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                        <th class="py-2.5 pr-4">Room</th>
                        <th class="px-4 py-2.5">Stay</th>
                        <th class="px-4 py-2.5">Total</th>
                        <th class="px-4 py-2.5">Status</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($past as $r): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 pr-4"><?= e($r['room_number']) ?></td>
                            <td class="px-4 py-3 text-slate-500"><?= e(date('j M Y', strtotime($r['check_in']))) ?> – <?= e(date('j M Y', strtotime($r['check_out']))) ?></td>
                            <td class="px-4 py-3 font-medium"><?= money($r['total_amount']) ?></td>
                            <td class="px-4 py-3"><?= status_badge($r['status']) ?></td>
                            <td class="px-4 py-3 text-right"><a href="<?= url('account/reservations/' . $r['id']) ?>" class="font-semibold text-brand-700 hover:underline">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
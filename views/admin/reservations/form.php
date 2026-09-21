<?php
/**
 * Reservation create form (staff/admin). Vars: $guests, $rooms, $roomTypes,
 * $checkIn, $checkOut. POSTs to /reservations (ReservationService::create).
 * Only currently-available rooms are offered on the first render.
 */
$old       = session()->oldInput();
$checkIn   = (string) ($old['check_in'] ?? $checkIn);
$checkOut  = (string) ($old['check_out'] ?? $checkOut);
?>
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-brand-950">New reservation</h1>
    <p class="mt-1 text-sm text-slate-500">Book a room for a walk-in or existing guest.</p>
</div>

<form method="post" action="<?= url('reservations') ?>" class="max-w-3xl">
    <?= \App\Core\Csrf::field() ?>

    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="guest_id" class="mb-1 block text-sm font-medium text-slate-700">Guest *</label>
                <select id="guest_id" name="guest_id" required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                    <option value="">Choose…</option>
                    <?php foreach ($guests as $g): ?>
                        <option value="<?= (int) $g['id'] ?>" <?= (int) ($old['guest_id'] ?? $guestId ?? 0) === (int) $g['id'] ? 'selected' : '' ?>>
                            <?= e($g['first_name'] . ' ' . $g['last_name'] . ($g['email'] ? ' · ' . $g['email'] : '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="mt-1 text-xs text-slate-400">Need a fresh profile? <a href="<?= url('guests/create') ?>" class="font-semibold text-brand-700 hover:underline">Create a guest</a> first.</p>
            </div>
            <div>
                <label for="room_id" class="mb-1 block text-sm font-medium text-slate-700">Room *</label>
                <select id="room_id" name="room_id" required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                    <option value="">Choose…</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?= (int) $room['id'] ?>" <?= (int) ($old['room_id'] ?? 0) === (int) $room['id'] ? 'selected' : '' ?>>
                            <?= e($room['room_number']) ?> — <?= e($room['room_type_name']) ?> (<?= money($room['price_per_night']) ?>/night)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="mt-1 text-xs text-slate-400" id="room-hint">Rooms shown are free for the dates below.</p>
            </div>
            <div>
                <label for="check_in" class="mb-1 block text-sm font-medium text-slate-700">Check-in *</label>
                <input type="date" id="check_in" name="check_in" value="<?= e($checkIn) ?>" required
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>
            <div>
                <label for="check_out" class="mb-1 block text-sm font-medium text-slate-700">Check-out *</label>
                <input type="date" id="check_out" name="check_out" value="<?= e($checkOut) ?>" required
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>
            <div>
                <label for="guests_count" class="mb-1 block text-sm font-medium text-slate-700">Guests *</label>
                <input type="number" id="guests_count" name="guests_count" value="<?= (int) ($old['guests_count'] ?? 2) ?>" required min="1" max="50"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>
        </div>

        <label for="special_requests" class="mb-1 mt-4 block text-sm font-medium text-slate-700">Special requests</label>
        <textarea id="special_requests" name="special_requests" rows="3" maxlength="1000"
                  class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20"><?= e((string) ($old['special_requests'] ?? '')) ?></textarea>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <button type="submit" class="rounded-lg bg-brand-800 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Create reservation</button>
        <a href="<?= url('reservations') ?>" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-500 hover:text-slate-700">Cancel</a>
    </div>
</form>

<script>
    // When the dates change the set of free rooms changes with them.
    // Navigate back to this page with the new dates so the room dropdown is
    // re-rendered server-side with an honest list.
    $(function () {
        $('#check_in, #check_out').on('change', function () {
            var from = $('#check_in').val(), to = $('#check_out').val();
            if (!from || !to) return;
            var params = new URLSearchParams({ check_in: from, check_out: to });
            var selected = $('#guest_id').val();
            if (selected) params.set('guest_id', selected);
            window.location.search = params.toString();
        });
    });
</script>
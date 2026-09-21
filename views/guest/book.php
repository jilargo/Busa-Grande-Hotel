<?php
/** Guest booking wizard. Vars: $checkIn, $checkOut, $rooms, $roomTypes, $valid */
?>
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-brand-950">Book a room</h1>
    <p class="mt-1 text-sm text-slate-500">Choose your dates and pick from the rooms we have available.</p>
</div>

<!-- Step 1: dates + filters -->
<div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
    <form id="availability-form" method="get" action="<?= url('account/book') ?>" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label for="check_in" class="mb-1 block text-sm font-medium text-slate-700">Check-in</label>
            <input type="date" id="check_in" name="check_in" value="<?= e($checkIn) ?>" min="<?= date('Y-m-d') ?>"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
        </div>
        <div>
            <label for="check_out" class="mb-1 block text-sm font-medium text-slate-700">Check-out</label>
            <input type="date" id="check_out" name="check_out" value="<?= e($checkOut) ?>"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
        </div>
        <div>
            <label for="room_type_id" class="mb-1 block text-sm font-medium text-slate-700">Room type</label>
            <select id="room_type_id" name="room_type_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                <option value="">All types</option>
                <?php foreach ($roomTypes as $type): ?>
                    <option value="<?= (int) $type['id'] ?>"><?= e($type['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="guests" class="mb-1 block text-sm font-medium text-slate-700">Guests</label>
            <select id="guests" name="guests" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?> guest<?= $i === 1 ? '' : 's' ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="sm:col-span-2 lg:col-span-4 lg:flex lg:items-end lg:justify-end">
            <button type="submit" class="w-full rounded-lg bg-brand-800 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 lg:w-auto">
                Check availability
            </button>
        </div>
    </form>
</div>

<?php if (!$valid): ?>
    <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
        Please pick a check-out date that is after your check-in date.
    </div>
<?php endif; ?>

<!-- Step 2: choose a room -->
<?php if ($valid): ?>
    <div class="mt-6">
        <?php if (empty($rooms)): ?>
            <?php view('partials.empty', [
                'message' => 'No rooms are free for those dates.',
                'hint'    => 'Try different dates or fewer guests.',
            ], false); ?>
        <?php else: ?>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($rooms as $room): ?>
                    <?php $nights = App\Models\Reservation::nightsBetween($checkIn, $checkOut); ?>
                    <?php $total  = (float) $room['price_per_night'] * $nights; ?>
                    <form method="post" action="<?= url('account/reservations') ?>" class="flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition hover:shadow-lg">
                        <?= \App\Core\Csrf::field() ?>
                        <input type="hidden" name="room_id" value="<?= (int) $room['id'] ?>">
                        <input type="hidden" name="check_in" value="<?= e($checkIn) ?>">
                        <input type="hidden" name="check_out" value="<?= e($checkOut) ?>">
                        <input type="hidden" name="guests_count" value="1">

                        <div class="flex h-36 items-center justify-center bg-gradient-to-br from-slate-200 to-slate-300 text-slate-400">
                            <svg class="h-10 w-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V5a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v16M7 8h2m2 0h2m2 0h2m-8 4h2m2 0h2m2 0h2"/></svg>
                        </div>

                        <div class="flex flex-1 flex-col p-5">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="font-display text-lg font-semibold text-brand-950">Room <?= e($room['room_number']) ?></h3>
                                    <p class="text-xs font-medium uppercase tracking-wide text-gold-600"><?= e($room['room_type_name']) ?></p>
                                </div>
                                <p class="text-right">
                                    <span class="font-display text-xl font-semibold text-brand-800"><?= money($room['price_per_night']) ?></span>
                                    <span class="block text-xs text-slate-400">per night</span>
                                </p>
                            </div>

                            <p class="mt-3 text-xs text-slate-500">
                                Floor <?= (int) $room['floor'] ?> · Sleeps <?= (int) $room['capacity'] ?>
                            </p>

                            <div class="mt-auto pt-4">
                                <button type="submit"
                                        class="w-full rounded-lg bg-gold-500 px-4 py-2.5 text-sm font-semibold text-brand-950 transition hover:bg-gold-400">
                                    Reserve <?= (int) $nights ?> night<?= $nights === 1 ? '' : 's' ?> for <?= money($total) ?>
                                </button>
                            </div>
                        </div>
                    </form>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
    // Keep the "guests" select in sync with the URL query if provided.
    $(function () {
        var params = new URLSearchParams(window.location.search);
        if (params.get('guests')) { $('#guests').val(params.get('guests')); }
        if (params.get('room_type_id')) { $('#room_type_id').val(params.get('room_type_id')); }

        // Prevent an impossible date range on the client for better UX; the
        // server still validates everything.
        $('#availability-form').on('submit', function () {
            var cin = new Date($('#check_in').val());
            var cout = new Date($('#check_out').val());
            if (cout <= cin) {
                alert('Check-out must be at least one day after check-in.');
                return false;
            }
        });
    });
</script>
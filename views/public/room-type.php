<?php
/**
 * Public room-type detail page: photos, details and the rooms in this category.
 * Vars: $title, $roomType, $rooms
 */

$amenities = array_values(array_filter(array_map(
    'trim',
    preg_split('/[,\n]/', (string) ($roomType['amenities'] ?? '')) ?: []
)));

$statusMeta = [
    App\Models\Room::STATUS_AVAILABLE => ['Available',      'bg-emerald-50 text-emerald-700 ring-emerald-200'],
    App\Models\Room::STATUS_RESERVED  => ['Available',      'bg-emerald-50 text-emerald-700 ring-emerald-200'],
    App\Models\Room::STATUS_OCCUPIED  => ['Occupied',       'bg-amber-50 text-amber-700 ring-amber-200'],
    App\Models\Room::STATUS_CLEANING  => ['Available soon', 'bg-slate-100 text-slate-600 ring-slate-300'],
];

$bookUrl = url('account/book') . '?room_type_id=' . (int) $roomType['id'];
?>

<!-- ======================= Breadcrumb ======================= -->
<div class="border-b border-slate-100 bg-slate-50">
    <div class="mx-auto max-w-7xl px-4 py-4 text-sm text-slate-500 sm:px-6">
        <a href="<?= url('/') ?>" class="transition hover:text-brand-700">Home</a>
        <span class="mx-2 text-slate-300">/</span>
        <a href="<?= url('/') ?>#rooms" class="transition hover:text-brand-700">Rooms &amp; Suites</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="font-medium text-slate-700"><?= e($roomType['name']) ?></span>
    </div>
</div>

<!-- ======================= Hero ======================= -->
<section class="relative overflow-hidden bg-brand-950 text-white">
    <div class="absolute inset-0">
        <?php if ($roomType['image']): ?>
            <img src="<?= asset($roomType['image']) ?>" alt="<?= e($roomType['name']) ?>" class="h-full w-full object-cover opacity-60">
            <div class="absolute inset-0 bg-gradient-to-t from-brand-950 via-brand-950/70 to-brand-950/30"></div>
        <?php else: ?>
            <div class="h-full w-full bg-gradient-to-br from-brand-800 to-brand-950"></div>
        <?php endif; ?>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-28">
        <p class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.25em] text-gold-400">
            <span class="h-px w-8 bg-gold-400"></span> Room category
        </p>
        <h1 class="max-w-3xl font-display text-4xl font-semibold leading-tight sm:text-5xl"><?= e($roomType['name']) ?></h1>
        <p class="mt-5 max-w-2xl text-base leading-relaxed text-brand-100"><?= e($roomType['description']) ?></p>

        <div class="mt-8 flex flex-wrap items-center gap-x-8 gap-y-4">
            <div>
                <p class="text-xs uppercase tracking-wider text-brand-300">From</p>
                <p class="font-display text-2xl font-semibold text-gold-300"><?= money($roomType['base_price']) ?> <span class="text-sm font-normal text-brand-200">/ night</span></p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wider text-brand-300">Sleeps</p>
                <p class="font-display text-2xl font-semibold"><?= (int) $roomType['max_guests'] ?> <span class="text-sm font-normal text-brand-200">guests</span></p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wider text-brand-300">Rooms</p>
                <p class="font-display text-2xl font-semibold"><?= count($rooms) ?></p>
            </div>
        </div>

        <div class="mt-9 flex flex-wrap items-center gap-3">
            <a href="<?= e($bookUrl) ?>"
               class="rounded-xl bg-gold-500 px-6 py-3 text-sm font-semibold text-brand-950 shadow-lg shadow-gold-500/20 transition hover:bg-gold-400">
                Book this room
            </a>
            <a href="<?= url('/') ?>#rooms"
               class="rounded-xl border border-white/25 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                Back to all rooms
            </a>
        </div>
    </div>
</section>

<!-- ======================= Details & amenities ======================= -->
<section class="bg-white py-16">
    <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <h2 class="font-display text-2xl font-semibold text-brand-950">About this room</h2>
            <p class="mt-4 leading-relaxed text-slate-600">
                <?= e($roomType['description']) ?>
            </p>
            <p class="mt-3 leading-relaxed text-slate-600">
                Every <?= e(strtolower($roomType['name'])) ?> is serviced daily, fitted with
                blackout curtains and premium linen, and supported by our 24-hour front desk.
                Rates are per night and include taxes.
            </p>
        </div>

        <aside class="rounded-2xl bg-slate-50 p-6 ring-1 ring-slate-200">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-500">In-room amenities</h3>
            <?php if ($amenities): ?>
                <ul class="mt-4 space-y-3 text-sm text-slate-700">
                    <?php foreach ($amenities as $amenity): ?>
                        <li class="flex items-start gap-3">
                            <svg class="mt-0.5 h-4 w-4 flex-none text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span><?= e($amenity) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="mt-4 text-sm text-slate-400">Amenities will be listed shortly.</p>
            <?php endif; ?>
        </aside>
    </div>
</section>

<!-- ======================= Rooms in this category ======================= -->
<section class="bg-slate-50 py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-gold-600">Browse the rooms</p>
        <h2 class="mt-2 font-display text-3xl font-semibold text-brand-950"><?= e($roomType['name']) ?>s in the house</h2>

        <?php if (empty($rooms)): ?>
            <p class="mt-8 text-slate-400">No rooms of this type are available to browse right now.</p>
        <?php else: ?>
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($rooms as $room): ?>
                    <?php
                    [$statusLabel, $statusClass] = $statusMeta[$room['status']] ?? ['Unavailable', 'bg-slate-100 text-slate-600 ring-slate-300'];
                    $image = $room['image'] ?: $roomType['image'];
                    $roomAmenities = array_values(array_filter(array_map(
                        'trim',
                        preg_split('/[,\n]/', (string) ($room['amenities'] ?? '')) ?: []
                    )));
                    ?>
                    <article class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-lg">
                        <div class="relative h-48 overflow-hidden bg-brand-900">
                            <?php if ($image): ?>
                                <img src="<?= asset($image) ?>" alt="Room <?= e($room['room_number']) ?>" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            <?php else: ?>
                                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-brand-800 to-brand-950">
                                    <span class="font-display text-5xl font-semibold text-gold-400/80"><?= e($room['room_number']) ?></span>
                                </div>
                            <?php endif; ?>
                            <span class="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-0.5 text-xs font-semibold text-brand-900">
                                Room <?= e($room['room_number']) ?>
                            </span>
                            <span class="absolute right-3 top-3 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 <?= $statusClass ?>">
                                <?= e($statusLabel) ?>
                            </span>
                        </div>

                        <div class="flex flex-1 flex-col p-5">
                            <div class="flex items-baseline justify-between gap-3">
                                <h3 class="font-display text-lg font-semibold text-brand-950">Room <?= e($room['room_number']) ?></h3>
                                <p class="text-sm font-semibold text-brand-700"><?= money($room['price_per_night']) ?><span class="font-normal text-slate-400"> / night</span></p>
                            </div>

                            <p class="mt-2 text-xs uppercase tracking-wider text-slate-400">
                                Floor <?= (int) $room['floor'] ?> · Sleeps <?= (int) $room['capacity'] ?>
                            </p>

                            <?php if ($room['description']): ?>
                                <p class="mt-3 line-clamp-2 text-sm text-slate-500"><?= e($room['description']) ?></p>
                            <?php endif; ?>

                            <?php if ($roomAmenities): ?>
                                <ul class="mt-4 flex flex-wrap gap-1.5">
                                    <?php foreach (array_slice($roomAmenities, 0, 4) as $amenity): ?>
                                        <li class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs text-slate-600"><?= e($amenity) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <div class="mt-5 flex-1"></div>
                            <a href="<?= e($bookUrl) ?>"
                               class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                                Reserve this room
                                <?= App\Helpers\Icons::render('chevron-right', 'h-4 w-4') ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

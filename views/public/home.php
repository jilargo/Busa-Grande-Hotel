<?php
/** Landing page. Vars: $title, $roomTypes */
?>
<!-- ======================= Hero ======================= -->
<section class="relative overflow-hidden bg-brand-950 text-white">
    <div class="absolute inset-0"
         style="background:
            radial-gradient(55rem 32rem at 85% -10%, rgba(201,163,74,.22), transparent 60%),
            radial-gradient(40rem 26rem at -10% 110%, rgba(58,109,82,.5), transparent 60%);"></div>
    <div class="absolute inset-0 opacity-[0.04]"
         style="background-image: repeating-linear-gradient(45deg, #fff 0 1px, transparent 1px 22px);"></div>

    <div class="relative mx-auto max-w-7xl px-4 py-28 sm:px-6 sm:py-36">
        <p class="mb-4 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.25em] text-gold-400">
            <span class="h-px w-8 bg-gold-400"></span> Welcome to
        </p>
        <h1 class="max-w-3xl font-display text-4xl font-semibold leading-tight sm:text-6xl">
            Busa Grande<br>
            <span class="italic text-gold-300">Grand hospitality,</span> down to every detail.
        </h1>
        <p class="mt-6 max-w-xl text-base leading-relaxed text-brand-100 sm:text-lg">
            From quiet city rooms to whole-floor suites, every stay is handled
            with the calm precision of a well-run house. Reserve online and
            check in without a queue.
        </p>
        <div class="mt-9 flex flex-wrap items-center gap-3">
            <a href="<?= url('register') ?>"
               class="rounded-xl bg-gold-500 px-6 py-3 text-sm font-semibold text-brand-950 shadow-lg shadow-gold-500/20 transition hover:bg-gold-400">
                Book your stay
            </a>
            <a href="#rooms"
               class="rounded-xl border border-white/25 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                Explore rooms
            </a>
        </div>
        <div class="mt-12 grid max-w-lg grid-cols-3 gap-6 border-t border-white/10 pt-8 text-center">
            <div><p class="font-display text-2xl font-semibold text-gold-300">12</p><p class="mt-1 text-xs uppercase tracking-wider text-brand-200">Rooms</p></div>
            <div><p class="font-display text-2xl font-semibold text-gold-300">4</p><p class="mt-1 text-xs uppercase tracking-wider text-brand-200">Categories</p></div>
            <div><p class="font-display text-2xl font-semibold text-gold-300">1</p><p class="mt-1 text-xs uppercase tracking-wider text-brand-200">Grand suite</p></div>
        </div>
    </div>
</section>

<!-- ======================= Rooms / room types ======================= -->
<section id="rooms" class="bg-white py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <p class="text-center text-xs font-semibold uppercase tracking-[0.25em] text-gold-600">Rooms & Suites</p>
        <h2 class="mt-2 text-center font-display text-3xl font-semibold text-brand-950 sm:text-4xl">Stay in style</h2>

        <?php if (empty($roomTypes)): ?>
            <p class="mt-8 text-center text-slate-400">Our collection is being prepared. Please check back soon.</p>
        <?php else: ?>
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <?php foreach ($roomTypes as $i => $type): ?>
                    <a href="<?= url('room-types/' . $type['id']) ?>"
                       class="group block overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-lg">
                        <div class="relative h-44 overflow-hidden bg-brand-900">
                            <?php if ($type['image']): ?>
                                <img src="<?= asset($type['image']) ?>" alt="<?= e($type['name']) ?>" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            <?php else: ?>
                                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-brand-800 to-brand-950">
                                    <span class="font-display text-5xl font-semibold text-gold-400/80"><?= e(strtoupper(substr($type['name'], 0, 1))) ?></span>
                                </div>
                            <?php endif; ?>
                            <span class="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-0.5 text-xs font-semibold text-brand-900"><?= money($type['base_price']) ?> / night</span>
                        </div>
                        <div class="p-5">
                            <h3 class="font-display text-lg font-semibold text-brand-950"><?= e($type['name']) ?></h3>
                            <p class="mt-1 line-clamp-2 text-sm text-slate-500"><?= e($type['description']) ?></p>
                            <p class="mt-3 flex items-center justify-between text-xs text-slate-400">
                                <span>Up to <?= (int) $type['max_guests'] ?> guests</span>
                                <span class="inline-flex items-center gap-1 font-semibold text-brand-700 transition group-hover:gap-2">
                                    View rooms <?= App\Helpers\Icons::render('chevron-right', 'h-3.5 w-3.5') ?>
                                </span>
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ======================= Amenities ======================= -->
<section id="amenities" class="bg-brand-950 py-20 text-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <p class="text-center text-xs font-semibold uppercase tracking-[0.25em] text-gold-400">Amenities</p>
        <h2 class="mt-2 text-center font-display text-3xl font-semibold sm:text-4xl">Everything you need</h2>
        <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <?php
            $amenities = [
                ['bed', 'Restful rooms', 'Blackout curtains, premium mattresses and a quiet floor policy.'],
                ['wallet', 'Fair rates', 'Transparent nightly pricing with no hidden resort fees.'],
                ['sparkle', 'Housekeeping', 'Twice-daily service and same-day deep cleaning.'],
                ['shield', 'Secure stays', 'Key-card access and 24/7 front desk staff.'],
            ];
            foreach ($amenities as [$icon, $name, $desc]): ?>
                <div class="rounded-2xl bg-white/5 p-6 ring-1 ring-white/10">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gold-500 text-brand-950">
                        <?= App\Helpers\Icons::render($icon, 'h-5 w-5') ?>
                    </span>
                    <h3 class="mt-4 font-semibold"><?= e($name) ?></h3>
                    <p class="mt-1 text-sm leading-relaxed text-brand-200"><?= e($desc) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ======================= About ======================= -->
<section id="about" class="bg-white py-20">
    <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-gold-600">About the hotel</p>
            <h2 class="mt-2 font-display text-3xl font-semibold text-brand-950 sm:text-4xl">A house run with intention</h2>
            <p class="mt-5 leading-relaxed text-slate-600">
                Busa Grande began as a family home above a bakery and grew into a
                twelve-room boutique hotel. We still run it like a family home:
                every reservation is answered by a person, every room is checked by
                hand, and every guest leaves with a story worth telling.
            </p>
            <p class="mt-3 leading-relaxed text-slate-600">
                Our booking system is designed to make the front desk — and the
                guest — feel effortless. Reserve online, check in on arrival, and
                let us handle the rest.
            </p>
            <a href="<?= url('register') ?>" class="mt-7 inline-flex items-center gap-2 rounded-lg bg-brand-800 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                Create your account
            </a>
        </div>
        <div class="relative">
            <div class="rounded-2xl bg-gradient-to-br from-brand-700 via-brand-800 to-brand-950 p-10 text-center text-brand-100 shadow-2xl">
                <p class="font-display text-6xl font-semibold text-gold-400">12</p>
                <p class="mt-1 text-sm uppercase tracking-wider">rooms · 3 floors</p>
                <div class="mx-auto mt-6 h-px w-24 bg-gold-500/40"></div>
                <p class="mt-6 text-sm italic text-brand-200">"The front desk knows your name before you do."</p>
            </div>
        </div>
    </div>
</section>
<?php
/** Room types list (admin). Vars: $roomTypes */
?>
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="font-display text-2xl font-semibold text-brand-950">Room types</h1>
        <p class="mt-1 text-sm text-slate-500">Catalogue and base pricing for each category.</p>
    </div>
    <a href="<?= url('room-types/create') ?>" class="inline-flex items-center gap-2 rounded-lg bg-brand-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">
        <?= App\Helpers\Icons::render('plus', 'h-4 w-4') ?> New room type
    </a>
</div>

<?php if (empty($roomTypes)): ?>
    <?php view('partials.empty', ['message' => 'No room types yet.', 'action' => ['label' => 'Add a room type', 'href' => 'room-types/create']], false); ?>
<?php else: ?>
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <?php foreach ($roomTypes as $type): ?>
            <article class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex h-28 items-center justify-center bg-gradient-to-br from-brand-700 to-brand-950">
                    <?php if ($type['image']): ?>
                        <img src="<?= asset($type['image']) ?>" alt="<?= e($type['name']) ?>" class="h-full w-full object-cover">
                    <?php else: ?>
                        <span class="font-display text-4xl font-semibold text-gold-400"><?= e(strtoupper(substr($type['name'], 0, 1))) ?></span>
                    <?php endif; ?>
                </div>
                <div class="p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="font-display text-lg font-semibold text-brand-950"><?= e($type['name']) ?></h3>
                            <p class="text-xs font-medium uppercase tracking-wide text-gold-600"><?= e($type['slug']) ?></p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600"><?= money($type['base_price']) ?></span>
                    </div>
                    <p class="mt-2 line-clamp-2 text-sm text-slate-500"><?= e($type['description']) ?></p>
                    <p class="mt-3 text-xs text-slate-400">Sleeps up to <?= (int) $type['max_guests'] ?> guests</p>
                    <div class="mt-4 flex items-center gap-2">
                        <a href="<?= url('room-types/' . $type['id'] . '/edit') ?>" class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 transition hover:bg-slate-50">
                            <?= App\Helpers\Icons::render('edit', 'h-4 w-4') ?> Edit
                        </a>
                        <form method="post" action="<?= url('room-types/' . $type['id'] . '/delete') ?>" data-confirm="Delete room type <?= e($type['name']) ?>? Rooms using it block deletion.">
                            <?= \App\Core\Csrf::field() ?>
                            <button type="submit" class="flex items-center justify-center rounded-lg bg-white px-3 py-2 text-rose-600 ring-1 ring-slate-300 transition hover:bg-rose-50">
                                <?= App\Helpers\Icons::render('trash', 'h-4 w-4') ?>
                            </button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
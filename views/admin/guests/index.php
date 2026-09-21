<?php
/** Guest list (staff/admin). Vars: $guests, $q */
?>
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="font-display text-2xl font-semibold text-brand-950">Guests</h1>
        <p class="mt-1 text-sm text-slate-500"><?= count($guests) ?> guest<?= count($guests) === 1 ? '' : 's' ?> in the register.</p>
    </div>
    <a href="<?= url('guests/create') ?>" class="inline-flex items-center gap-2 rounded-lg bg-brand-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">
        <?= App\Helpers\Icons::render('plus', 'h-4 w-4') ?> New guest
    </a>
</div>

<form method="get" action="<?= url('guests') ?>" class="mb-5 flex max-w-md gap-2">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search name, email or phone…"
           class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-brand-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">
        <?= App\Helpers\Icons::render('search', 'h-4 w-4') ?> Search
    </button>
</form>

<div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
    <?php if (empty($guests)): ?>
        <div class="p-6"><?php view('partials.empty', ['message' => 'No guests found.', 'q' => $q ? 'Try a different search.' : 'Create your first guest record.'], false); ?></div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[44rem] text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                        <th class="px-5 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Phone</th>
                        <th class="px-4 py-3">City</th>
                        <th class="px-4 py-3">Account</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($guests as $guest): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <a href="<?= url('guests/' . $guest['id']) ?>" class="font-semibold text-brand-700 hover:underline">
                                    <?= e($guest['first_name'] . ' ' . $guest['last_name']) ?>
                                </a>
                                <?php if (!empty($guest['account_name'])): ?>
                                    <span class="ml-1 rounded-full bg-gold-100 px-2 py-0.5 text-xs text-gold-700">member</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-slate-500"><?= e($guest['email'] ?: '—') ?></td>
                            <td class="px-4 py-3 text-slate-500"><?= e($guest['phone'] ?: '—') ?></td>
                            <td class="px-4 py-3 text-slate-500"><?= e($guest['city'] ?: '—') ?></td>
                            <td class="px-4 py-3"><?= $guest['account_name'] ? e($guest['account_name']) : '<span class="text-slate-400">walk-in</span>' ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="<?= url('guests/' . $guest['id'] . '/edit') ?>" class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-brand-700" title="Edit">
                                        <?= App\Helpers\Icons::render('edit', 'h-4 w-4') ?>
                                    </a>
                                    <?php if (auth()->isAllowed(['admin'])): ?>
                                        <form method="post" action="<?= url('guests/' . $guest['id'] . '/delete') ?>" data-confirm="Delete guest <?= e($guest['first_name'] . ' ' . $guest['last_name']) ?>?">
                                            <?= \App\Core\Csrf::field() ?>
                                            <button type="submit" class="rounded-lg p-2 text-slate-500 transition hover:bg-rose-50 hover:text-rose-600" title="Delete">
                                                <?= App\Helpers\Icons::render('trash', 'h-4 w-4') ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
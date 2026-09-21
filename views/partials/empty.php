<?php
/**
 * Friendly empty state for tables/lists.
 * Expects: $icon, $message, $hint (optional), $action (optional [label, href])
 */
$icon  = $icon ?? 'search';
$hint  = $hint ?? null;
$action = $action ?? null;
?>
<div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
        <?= App\Helpers\Icons::render($icon, 'h-7 w-7') ?>
    </span>
    <p class="mt-4 text-sm font-semibold text-slate-700"><?= e($message) ?></p>
    <?php if ($hint): ?>
        <p class="mt-1 max-w-sm text-sm text-slate-400"><?= e($hint) ?></p>
    <?php endif; ?>
    <?php if ($action): ?>
        <a href="<?= url($action['href']) ?>" class="mt-5 inline-flex items-center gap-2 rounded-lg bg-brand-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">
            <?= App\Helpers\Icons::render('plus', 'h-4 w-4') ?>
            <?= e($action['label']) ?>
        </a>
    <?php endif; ?>
</div>
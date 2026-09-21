<?php
/**
 * Reusable page heading with optional action buttons.
 * Expects: $h1, $subtitle (optional), $actions (array of [label, href, style])
 */
$actions = $actions ?? [];
?>
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="font-display text-2xl font-semibold text-brand-950"><?= e($h1) ?></h1>
        <?php if (!empty($subtitle)): ?>
            <p class="mt-1 text-sm text-slate-500"><?= e($subtitle) ?></p>
        <?php endif; ?>
    </div>
    <?php if ($actions !== []): ?>
        <div class="flex flex-wrap items-center gap-2">
            <?php foreach ($actions as $action): ?>
                <?php
                    $actionStyle = $action['style'] ?? 'primary';
                    $classes = match ($actionStyle) {
                        'primary'   => 'bg-brand-800 text-white hover:bg-brand-700',
                        'gold'      => 'bg-gold-500 text-brand-950 hover:bg-gold-400',
                        'danger'    => 'bg-rose-600 text-white hover:bg-rose-500',
                        'secondary' => 'bg-white text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50',
                    };
                ?>
                <a href="<?= url($action['href']) ?>"
                   class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition <?= $classes ?>">
                    <?= e($action['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
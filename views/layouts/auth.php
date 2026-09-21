<?php
/**
 * Authentication layout: a clean centred card on a branded background.
 * Available vars: $title, $content
 */
$title = $title ?? config('app.name');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= \App\Core\Csrf::token() ?>">
    <title><?= e($title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="flex min-h-screen items-center justify-center bg-brand-950 px-4 py-10">

    <!-- soft radial glow -->
    <div class="pointer-events-none fixed inset-0"
         style="background: radial-gradient(60rem 30rem at 50% -10%, rgba(201,163,74,.18), transparent 60%);"></div>

    <div class="relative w-full max-w-md">
        <a href="<?= url('/') ?>" class="mb-6 flex items-center justify-center gap-2 text-white">
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gold-500 text-brand-950">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V5a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v16M7 8h2m2 0h2m2 0h2m-8 4h2m2 0h2m2 0h2"/></svg>
            </span>
            <span class="font-display text-2xl font-semibold">
                Busa Grande <span class="text-gold-400">Hotel</span>
            </span>
        </a>

        <div class="rounded-2xl bg-white p-8 shadow-2xl ring-1 ring-gold-300/40">
            <?php foreach (session()->pullFlashes() as $flash): ?>
                <div class="mb-4 rounded-lg border px-4 py-3 text-sm font-medium <?= $flash['type'] === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
                    <?= e($flash['message']) ?>
                </div>
            <?php endforeach; ?>

            <?= $content ?>
        </div>

        <p class="mt-6 text-center text-sm text-brand-400">© <?= date('Y') ?> Busa Grande Hotel</p>
    </div>

</body>
</html>
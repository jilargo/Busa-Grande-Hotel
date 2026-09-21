<?php
/**
 * Bare layout for error pages (500/403/404).
 * Available vars: $status, $message, $debug
 */
$status = $status ?? 500;
$errorTitles = [
    404 => 'Page not found',
    403 => 'Access denied',
    401 => 'Unauthorized',
    419 => 'Session expired',
    422 => 'Request could not be processed',
    500 => 'Internal error',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= (int) $status ?> · Busa Grande Hotel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="flex min-h-screen items-center justify-center bg-brand-950 px-4">
    <div class="w-full max-w-lg text-center text-white">
        <p class="font-display text-7xl font-semibold text-gold-400"><?= (int) $status ?></p>
        <h1 class="mt-3 font-display text-2xl text-white"><?= e($errorTitles[$status] ?? 'Unexpected error') ?></h1>
        <p class="mt-3 text-brand-200"><?= e($message) ?></p>

        <?php if (!empty($debug) && config('app.debug')): ?>
            <pre class="mt-6 overflow-x-auto rounded-lg bg-black/30 p-4 text-left text-xs leading-relaxed text-brand-300"><?= e((string) $debug) ?></pre>
        <?php endif; ?>

        <a href="<?= url('/') ?>" class="mt-8 inline-block rounded-lg bg-gold-500 px-5 py-2.5 text-sm font-semibold text-brand-950 transition hover:bg-gold-400">
            Back to the hotel
        </a>
    </div>
</body>
</html>
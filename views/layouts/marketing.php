<?php
/**
 * Public marketing layout: top navigation + content + footer.
 * Used by the landing page.
 *
 * Available vars: $title, $content
 */
$title = $title ?? config('app.name');
$base  = url('/');
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= \App\Core\Csrf::token() ?>">
    <title><?= e($title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="bg-white">

    <!-- Top navigation -->
    <header class="sticky top-0 z-40 border-b border-white/10 bg-brand-950/95 text-white backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6">
            <a href="<?= url('/') ?>" class="flex items-center gap-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gold-500 text-brand-950">
                    <!-- hotel glyph -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V5a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v16M7 8h2m2 0h2m2 0h2m-8 4h2m2 0h2m2 0h2"/></svg>
                </span>
                <span class="font-display text-xl font-semibold tracking-wide">
                    Busa Grande <span class="text-gold-400">Hotel</span>
                </span>
            </a>

            <nav class="hidden items-center gap-8 text-sm font-medium text-brand-100 md:flex">
                <a href="<?= url('/') ?>#rooms" class="transition hover:text-gold-300">Rooms</a>
                <a href="<?= url('/') ?>#amenities" class="transition hover:text-gold-300">Amenities</a>
                <a href="<?= url('/') ?>#about" class="transition hover:text-gold-300">About</a>
                <a href="<?= url('/') ?>#contact" class="transition hover:text-gold-300">Contact</a>
            </nav>

            <div class="flex items-center gap-3">
                <?php if (auth()->check()): ?>
                    <a href="<?= url('dashboard') ?>"
                       class="rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-brand-950 transition hover:bg-gold-400">
                        My Dashboard
                    </a>
                <?php else: ?>
                    <a href="<?= url('login') ?>" class="hidden text-sm font-medium text-brand-100 transition hover:text-gold-300 sm:block">Sign in</a>
                    <a href="<?= url('register') ?>"
                       class="rounded-lg bg-gold-500 px-4 py-2 text-sm font-semibold text-brand-950 transition hover:bg-gold-400">
                        Book Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Page content -->
    <?= $content ?>

    <!-- Footer -->
    <footer id="contact" class="bg-brand-950 text-brand-200">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-3">
            <div>
                <p class="font-display text-2xl font-semibold text-white">Busa Grande <span class="text-gold-400">Hotel</span></p>
                <p class="mt-3 text-sm leading-relaxed">
                    A landmark of understated luxury in the heart of the city.
                    Impeccable service, refined rooms, and hospitality that feels like home.
                </p>
            </div>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-gold-400">Contact</p>
                <ul class="mt-4 space-y-2 text-sm">
                    <li>Upper Doongan,Butuan City</li>
                    <li>+63951-092-7821</li>
                    <li>reservations@busagrande.com</li>
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-gold-400">Explore</p>
                <ul class="mt-4 space-y-2 text-sm">
                    <li><a href="<?= url('/') ?>#rooms" class="transition hover:text-white">Rooms & Suites</a></li>
                    <li><a href="<?= url('/') ?>#amenities" class="transition hover:text-white">Amenities</a></li>
                    <li><a href="<?= url('register') ?>" class="transition hover:text-white">Create an account</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10 py-5 text-center text-xs text-brand-400">
            &copy; <?= date('Y') ?> Busa Grande Hotel. A demo project — all rates shown are fictional.
        </div>
    </footer>

</body>
</html>
<?php
/**
 * Authenticated application layout: responsive sidebar + top bar + content.
 *
 * Available vars: $title, $content, and anything a controller passes.
 */
$title    = $title ?? config('app.name');
$user     = $user ?? auth()->user();
$role     = $user['role'] ?? 'guest';
$flashes  = session()->pullFlashes();
$requestPath = ltrim(\App\Core\Request::path(), '/');

// Role-specific navigation
$navItems = match ($role) {
    'admin' => [
        ['Dashboard',      'dashboard',            'grid'],
        ['Reservations',   'reservations',         'calendar'],
        ['Rooms',          'rooms',                'bed'],
        ['Room Types',     'room-types',           'tag'],
        ['Guests',         'guests',               'users'],
        ['Users',          'users',                'shield'],
    ],
    'staff' => [
        ['Dashboard',      'dashboard',            'grid'],
        ['Reservations',   'reservations',         'calendar'],
        ['Rooms',          'rooms',                'bed'],
        ['Guests',         'guests',               'users'],
    ],
    default => [
        ['My Dashboard',   'dashboard',            'grid'],
        ['Book a Room',    'account/book',         'bed'],
        ['My Reservations','account/reservations', 'calendar'],
        ['My Profile',     'account/profile',      'user'],
    ],
};
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
<body class="min-h-screen bg-slate-100">

    <!-- ======================= Sidebar ======================= -->
    <aside id="app-sidebar"
           class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-brand-950 text-brand-100 transition-transform duration-200 lg:translate-x-0">

        <div class="flex items-center gap-2 border-b border-white/10 px-5 py-4">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gold-500 text-brand-950">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V5a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v16M7 8h2m2 0h2m2 0h2m-8 4h2m2 0h2m2 0h2"/></svg>
            </span>
            <span class="font-display text-lg font-semibold leading-tight text-white">
                Busa Grande<br><span class="text-gold-400">Hotel</span>
            </span>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            <?php foreach ($navItems as [$label, $href, $icon]): ?>
                <?php
                    $active = $requestPath === $href || str_starts_with($requestPath, $href . '/')
                        || ($href === 'dashboard' && $requestPath === '/');
                ?>
                <a href="<?= url($href) ?>"
                   class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                          <?= $active ? 'bg-gold-500 text-brand-950' : 'text-brand-200 hover:bg-white/5 hover:text-white' ?>">
                    <?= App\Helpers\Icons::render($icon) ?>
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="border-t border-white/10 p-4">
            <form method="post" action="<?= url('logout') ?>" data-confirm="Sign out of your account?">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-brand-300 transition hover:bg-white/5 hover:text-white">
                    <?= App\Helpers\Icons::render('logout') ?>
                    Sign out
                </button>
            </form>
        </div>
    </aside>

    <!-- Sidebar scrim for mobile -->
    <div id="sidebar-scrim" class="fixed inset-0 z-40 hidden bg-black/50 lg:hidden"></div>

    <!-- ======================= Main column ======================= -->
    <div class="flex min-h-screen flex-col lg:pl-64">

        <!-- Top bar -->
        <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6">
            <div class="flex items-center gap-3">
                <button id="sidebar-toggle" type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Open menu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="font-display text-lg font-semibold text-brand-950"><?= e($title) ?></h1>
            </div>

            <div class="relative">
                <button id="user-menu-btn" type="button"
                        class="flex items-center gap-3 rounded-full px-2 py-1.5 transition hover:bg-slate-100">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-800 font-semibold text-white">
                        <?= e(strtoupper(substr($user['name'], 0, 1))) ?>
                    </span>
                    <span class="hidden text-left sm:block">
                        <span class="block text-sm font-semibold text-slate-800"><?= e($user['name']) ?></span>
                        <span class="block text-xs capitalize text-slate-400"><?= e($role) ?></span>
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="hidden h-4 w-4 text-slate-400 sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                </button>

                <!-- Dropdown -->
                <div id="user-menu" class="absolute right-0 mt-2 hidden w-56 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg">
                    <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Signed in as</p>
                    <p class="px-3 pb-2 text-sm font-medium text-slate-800"><?= e($user['email']) ?></p>
                    <div class="my-1 border-t border-slate-100"></div>
                    <a href="<?= url('dashboard') ?>" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Dashboard</a>
                    <?php if ($role === 'guest'): ?>
                        <a href="<?= url('account/profile') ?>" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">My profile</a>
                    <?php endif; ?>
                    <form method="post" action="<?= url('logout') ?>" class="mt-1 border-t border-slate-100 pt-1">
                        <?= \App\Core\Csrf::field() ?>
                        <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-rose-600 hover:bg-rose-50">Sign out</button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <?= $content ?>
            </div>
        </main>

        <footer class="border-t border-slate-200 px-6 py-4 text-center text-xs text-slate-400">
            Busa Grande Hotel · Internal management system
        </footer>
    </div>

    <!-- ======================= Toasts ======================= -->
    <div id="toast-stack" class="pointer-events-none fixed right-4 top-4 z-[60] flex w-full max-w-sm flex-col gap-2"></div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        // Render any queued flash messages as toasts.
        window.busaFlashes = <?= json_encode(array_map(fn ($f) => [
            'type'    => $f['type'] === 'success' ? 'success' : 'error',
            'message' => $f['message'],
        ], $flashes)) ?>;
    </script>
    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
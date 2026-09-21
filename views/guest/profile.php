<?php
/** Guest profile. Vars: $user, $guest (may be null if no profile record exists) */
$guest = $guest ?? ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '', 'city' => '', 'country' => ''];
$card = 'rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200';
?>
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-brand-950">My profile</h1>
    <p class="mt-1 text-sm text-slate-500">Keep your contact details up to date and change your password.</p>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <!-- Profile details -->
    <section class="<?= $card ?>">
        <h2 class="text-lg font-semibold text-brand-950">Personal details</h2>
        <form method="post" action="<?= url('account/profile') ?>" class="mt-5 space-y-4">
            <?= \App\Core\Csrf::field() ?>

            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Account name</label>
                <input type="text" id="name" name="name" value="<?= e($user['name']) ?>" required maxlength="120"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="first_name" class="mb-1 block text-sm font-medium text-slate-700">First name</label>
                    <input type="text" id="first_name" name="first_name" value="<?= e($guest['first_name']) ?>" required
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                </div>
                <div>
                    <label for="last_name" class="mb-1 block text-sm font-medium text-slate-700">Last name</label>
                    <input type="text" id="last_name" name="last_name" value="<?= e($guest['last_name']) ?>" required
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                <input type="email" value="<?= e($guest['email'] ?: $user['email']) ?>" disabled
                       class="w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-500">
                <p class="mt-1 text-xs text-slate-400">Email is tied to your sign-in account and cannot be changed here.</p>
            </div>

            <div>
                <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                <input type="text" id="phone" name="phone" value="<?= e($guest['phone']) ?>" maxlength="30"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="city" class="mb-1 block text-sm font-medium text-slate-700">City</label>
                    <input type="text" id="city" name="city" value="<?= e($guest['city']) ?>" maxlength="100"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                </div>
                <div>
                    <label for="country" class="mb-1 block text-sm font-medium text-slate-700">Country</label>
                    <input type="text" id="country" name="country" value="<?= e($guest['country']) ?>" maxlength="100"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                </div>
            </div>

            <button type="submit" class="w-full rounded-lg bg-brand-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 sm:w-auto">
                Save changes
            </button>
        </form>
    </section>

    <!-- Password -->
    <section class="<?= $card ?>">
        <h2 class="text-lg font-semibold text-brand-950">Change password</h2>
        <p class="mt-1 text-sm text-slate-500">Choose a strong password with at least 8 characters.</p>

        <?php if (empty($user['password_hash'])): ?>
            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                You signed up with Google. Set a password below if you also want to sign in with email.
            </div>
        <?php endif; ?>

        <form method="post" action="<?= url('account/profile/password') ?>" class="mt-5 space-y-4">
            <?= \App\Core\Csrf::field() ?>

            <?php if (!empty($user['password_hash'])): ?>
                <div>
                    <label for="current_password" class="mb-1 block text-sm font-medium text-slate-700">Current password</label>
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                </div>
            <?php endif; ?>

            <div>
                <label for="new_password" class="mb-1 block text-sm font-medium text-slate-700">New password</label>
                <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>
            <div>
                <label for="new_password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Confirm new password</label>
                <input type="password" id="new_password_confirmation" name="new_password_confirmation" required minlength="8" autocomplete="new-password"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>

            <button type="submit" class="w-full rounded-lg border border-brand-800 bg-white px-4 py-2.5 text-sm font-semibold text-brand-800 transition hover:bg-brand-50 sm:w-auto">
                Update password
            </button>
        </form>
    </section>
</div>
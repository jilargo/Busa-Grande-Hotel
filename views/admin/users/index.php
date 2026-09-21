<?php
/** User administration (admin only). Vars: $users, $q */
?>
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-brand-950">Users</h1>
    <p class="mt-1 text-sm text-slate-500">Staff accounts and their roles.</p>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <!-- Create form -->
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 class="font-semibold text-brand-950">Add staff</h2>
        <form method="post" action="<?= url('users') ?>" class="mt-4 space-y-4">
            <?= \App\Core\Csrf::field() ?>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Full name</label>
                <input type="text" name="name" required maxlength="120"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                <input type="email" name="email" required maxlength="190"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                <input type="password" name="password" required minlength="8"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                <p class="mt-1 text-xs text-slate-400">At least 8 chars with upper, lower and a digit.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Role</label>
                <select name="role" required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="w-full rounded-lg bg-brand-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Create user</button>
        </form>
    </div>

    <!-- User list -->
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 lg:col-span-2">
        <form method="get" action="<?= url('users') ?>" class="border-b border-slate-100 p-4">
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search users…"
                   class="w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
        </form>

        <?php if (empty($users)): ?>
            <div class="p-6"><?php view('partials.empty', ['message' => 'No users found.'], false); ?></div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[36rem] text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                            <th class="px-5 py-3">Name</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Role</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($users as $u): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 font-medium"><?= e($u['name']) ?>
                                    <?php if ((int) $u['id'] === (int) auth()->id()): ?>
                                        <span class="ml-1 rounded-full bg-brand-100 px-2 py-0.5 text-xs font-semibold text-brand-700">you</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-slate-500"><?= e($u['email']) ?></td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold <?= $u['role'] === 'admin' ? 'bg-gold-100 text-gold-800' : 'bg-slate-100 text-slate-600' ?>"><?= e($u['role']) ?></span>
                                </td>
                                <td class="px-4 py-3"><?= $u['is_active'] ? status_badge('available', 'Active') : '<span class="inline-flex rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">Disabled</span>' ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <?php if ((int) $u['id'] !== (int) auth()->id()): ?>
                                            <form method="post" action="<?= url('users/' . $u['id'] . '/active') ?>">
                                                <?= \App\Core\Csrf::field() ?>
                                                <input type="hidden" name="active" value="<?= $u['is_active'] ? '0' : '1' ?>">
                                                <button type="submit" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold <?= $u['is_active'] ? 'text-amber-700 hover:bg-amber-50' : 'text-brand-700 hover:bg-brand-50' ?>">
                                                    <?= $u['is_active'] ? 'Disable' : 'Enable' ?>
                                                </button>
                                            </form>
                                            <form method="post" action="<?= url('users/' . $u['id'] . '/delete') ?>" data-confirm="Delete user <?= e($u['name']) ?>?">
                                                <?= \App\Core\Csrf::field() ?>
                                                <button type="submit" class="flex items-center justify-center rounded-lg p-2 text-slate-500 transition hover:bg-rose-50 hover:text-rose-600" title="Delete">
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
</div>
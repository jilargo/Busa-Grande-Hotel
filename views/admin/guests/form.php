<?php
/** Guest create/edit form (staff/admin). Vars: $guest (null when creating), $users (linkable accounts) */
$isEdit = $guest !== null;
$old    = session()->oldInput();
$val    = fn ($field, $default = '') => e((string) ($old[$field] ?? ($guest[$field] ?? $default)));
?>
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-brand-950"><?= $isEdit ? 'Edit guest' : 'New guest' ?></h1>
    <p class="mt-1 text-sm text-slate-500">Walk-ins get a lightweight record; members link to a sign-in account.</p>
</div>

<form method="post" action="<?= url($isEdit ? 'guests/' . $guest['id'] . '/update' : 'guests') ?>" class="max-w-2xl">
    <?= \App\Core\Csrf::field() ?>

    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="first_name" class="mb-1 block text-sm font-medium text-slate-700">First name *</label>
                <input type="text" id="first_name" name="first_name" value="<?= $val('first_name') ?>" required maxlength="100"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>
            <div>
                <label for="last_name" class="mb-1 block text-sm font-medium text-slate-700">Last name *</label>
                <input type="text" id="last_name" name="last_name" value="<?= $val('last_name') ?>" required maxlength="100"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                <input type="email" id="email" name="email" value="<?= $val('email') ?>" maxlength="190" placeholder="guest@example.com"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>
            <div>
                <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                <input type="text" id="phone" name="phone" value="<?= $val('phone') ?>" maxlength="30" placeholder="+255 …"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>
            <div>
                <label for="city" class="mb-1 block text-sm font-medium text-slate-700">City</label>
                <input type="text" id="city" name="city" value="<?= $val('city') ?>" maxlength="100"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>
            <div>
                <label for="country" class="mb-1 block text-sm font-medium text-slate-700">Country</label>
                <input type="text" id="country" name="country" value="<?= $val('country') ?>" maxlength="100"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>
        </div>

        <div class="mt-5">
            <label for="user_id" class="mb-1 block text-sm font-medium text-slate-700">Link to account</label>
            <select id="user_id" name="user_id"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                <option value="">No account (walk-in guest)</option>
                <?php if (!empty($users)): foreach ($users as $u): ?>
                    <option value="<?= (int) $u['id'] ?>" <?= (int) ($old['user_id'] ?? ($guest['user_id'] ?? 0)) === (int) $u['id'] ? 'selected' : '' ?>>
                        <?= e($u['name'] . ' · ' . $u['email']) ?>
                    </option>
                <?php endforeach; endif; ?>
            </select>
            <p class="mt-1 text-xs text-slate-400">Members keep their booking history under one profile.</p>
        </div>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <button type="submit" class="rounded-lg bg-brand-800 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
            <?= $isEdit ? 'Save changes' : 'Create guest' ?>
        </button>
        <a href="<?= url('guests') ?>" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-500 hover:text-slate-700">Cancel</a>
    </div>
</form>
<?php
/** Room type create/edit form (admin). Vars: $roomType (null when creating) */
$isEdit = $roomType !== null;
$old    = session()->oldInput();
$val    = fn ($field, $default = '') => e((string) ($old[$field] ?? ($roomType[$field] ?? $default)));

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}
?>
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-brand-950"><?= $isEdit ? 'Edit room type' : 'New room type' ?></h1>
    <p class="mt-1 text-sm text-slate-500">Catalogue entry — individual rooms can override the nightly rate.</p>
</div>

<form method="post" action="<?= url($isEdit ? 'room-types/' . $roomType['id'] . '/update' : 'room-types') ?>" enctype="multipart/form-data" class="max-w-2xl">
    <?= \App\Core\Csrf::field() ?>

    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Name *</label>
                <input type="text" id="name" name="name" value="<?= $val('name') ?>" required maxlength="100"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>
            <div>
                <label for="slug" class="mb-1 block text-sm font-medium text-slate-700">Slug *</label>
                <input type="text" id="slug" name="slug" value="<?= $val('slug') ?>" required maxlength="100" placeholder="standard-room"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>
            <div>
                <label for="base_price" class="mb-1 block text-sm font-medium text-slate-700">Base price ($) *</label>
                <input type="number" id="base_price" name="base_price" value="<?= $val('base_price', '0') ?>" required min="0" step="0.01"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>
            <div>
                <label for="max_guests" class="mb-1 block text-sm font-medium text-slate-700">Max guests *</label>
                <input type="number" id="max_guests" name="max_guests" value="<?= $val('max_guests', '2') ?>" required min="1" max="50"
                       class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            </div>
        </div>

        <label for="description" class="mb-1 mt-4 block text-sm font-medium text-slate-700">Description</label>
        <textarea id="description" name="description" rows="3" maxlength="1000"
                  class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20"><?= $val('description') ?></textarea>

        <label for="amenities" class="mb-1 mt-4 block text-sm font-medium text-slate-700">Amenities</label>
        <textarea id="amenities" name="amenities" rows="3" maxlength="1000"
                  class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20"><?= $val('amenities') ?></textarea>
        <p class="mt-1 text-xs text-slate-400">One per line.</p>

        <label for="image" class="mb-1 mt-4 block text-sm font-medium text-slate-700">Type photo (JPEG/PNG/WebP, max 2 MB)</label>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp,image/gif"
               class="block w-full rounded-lg border border-slate-300 text-sm text-slate-500 file:mr-3 file:rounded-l-lg file:border-0 file:bg-brand-800 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-700">
    </div>

    <div class="mt-6 flex items-center gap-3">
        <button type="submit" class="rounded-lg bg-brand-800 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
            <?= $isEdit ? 'Save changes' : 'Create room type' ?>
        </button>
        <a href="<?= url('room-types') ?>" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-500 hover:text-slate-700">Cancel</a>
    </div>
</form>

<script>
    $(function () {
        // Auto-fill the slug from the name (only while creating).
        var isEdit = <?= $isEdit ? 'true' : 'false' ?>;
        if (!isEdit) {
            $('#name').on('input', function () {
                if (!$('#slug').val().length) {
                    $('#slug').val($(this).val().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''));
                }
            });
        }
    });
</script>
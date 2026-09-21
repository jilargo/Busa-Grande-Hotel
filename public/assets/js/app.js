/**
 * Busa Grande Hotel — shared front-end behaviour.
 * jQuery only; each concern is small and self-contained.
 */
(function ($) {
    'use strict';

    var CSRF = $('meta[name="csrf-token"]').attr('content') || '';

    // ---- Global AJAX setup: always send the CSRF token ----------------
    $.ajaxSetup({
        headers: { 'X-CSRF-Token': CSRF }
    });

    // ---- Mobile sidebar -------------------------------------------------
    function closeSidebar() {
        $('#app-sidebar').addClass('-translate-x-full');
        $('#sidebar-scrim').addClass('hidden');
    }

    $('#sidebar-toggle').on('click', function () {
        $('#app-sidebar').toggleClass('-translate-x-full');
        $('#sidebar-scrim').toggleClass('hidden');
    });
    $('#sidebar-scrim').on('click', closeSidebar);

    // ---- User dropdown -------------------------------------------------
    $('#user-menu-btn').on('click', function (e) {
        e.stopPropagation();
        $('#user-menu').toggleClass('hidden');
    });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#user-menu, #user-menu-btn').length) {
            $('#user-menu').addClass('hidden');
        }
    });

    // ---- Toasts --------------------------------------------------------
    function showToast(type, message) {
        var isSuccess = type === 'success';
        var title = isSuccess ? 'Success' : 'Alert';

        var $toast = $('<div>')
            .addClass('pointer-events-auto w-full overflow-hidden rounded-xl bg-white shadow-xl ring-1 ring-slate-200')
            .css({ opacity: 0, transform: 'translateX(24px)', transition: 'all .3s ease' })
            .html(
                '<div class="flex items-start gap-3 p-4">' +
                    '<span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-white ' +
                        (isSuccess ? 'bg-emerald-500' : 'bg-rose-500') + '">' +
                        (isSuccess
                            ? '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 6 9 17l-5-5"/></svg>'
                            : '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>') +
                    '</span>' +
                    '<div class="flex-1 pt-0.5">' +
                        '<p class="text-sm font-semibold text-slate-800">' + title + '</p>' +
                        '<p class="text-sm text-slate-600">' + message + '</p>' +
                    '</div>' +
                    '<button type="button" class="toast-close rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Dismiss">' +
                        '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>' +
                    '</button>' +
                '</div>'
            )
            .appendTo('#toast-stack');

        requestAnimationFrame(function () {
            $toast.css({ opacity: 1, transform: 'translateX(0)' });
        });

        $toast.find('.toast-close').on('click', function () { dismiss($toast); });
        setTimeout(function () { dismiss($toast); }, 5000);
    }

    function dismiss($toast) {
        if ($toast.data('closing')) { return; }
        $toast.data('closing', true);
        $toast.css({ opacity: 0, transform: 'translateX(24px)' });
        setTimeout(function () { $toast.remove(); }, 300);
    }

    if (window.busaFlashes) {
        window.busaFlashes.forEach(function (f) { showToast(f.type, f.message); });
    }
    window.busaFlashes = [];
    window.showToast = showToast;

    // ---- Confirmation dialogs ------------------------------------------
    $('form[data-confirm]').on('submit', function (e) {
        var message = $(this).attr('data-confirm');
        if (!window.confirm(message)) {
            e.preventDefault();
        }
    });

})(jQuery);
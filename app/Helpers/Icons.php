<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Minimal inline SVG icon set (stroke-based, Lucide-style).
 * Inline SVGs avoid an icon font/CDN dependency and render everywhere.
 */
final class Icons
{
    /** Returns the full <svg> markup for a named icon. */
    public static function render(string $name, string $class = 'h-5 w-5'): string
    {
        $paths = [
            'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
            'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
            'bed'      => '<path d="M2 9V3M2 21v-6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v6M2 15h20M6 21v-4M18 21v-4"/><circle cx="8" cy="8" r="2"/>',
            'tag'      => '<path d="M12 2H2v10l9.29 9.29a1 1 0 0 0 1.42 0l8.58-8.58a1 1 0 0 0 0-1.42z"/><circle cx="7" cy="7" r="1"/>',
            'users'    => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
            'user'     => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
            'shield'   => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
            'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
            'home'     => '<path d="M3 10.12V21a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V10.12L12 3z"/><path d="M9 22V12h6v10"/>',
            'key'      => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6M15.5 7.5l3 3L22 7l-3-3"/>',
            'wallet'   => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/>',
            'plus'     => '<path d="M12 5v14M5 12h14"/>',
            'search'   => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>',
            'edit'     => '<path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/>',
            'trash'    => '<path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
            'checkin'  => '<path d="M9 11H6a2 2 0 0 0-2 2v7h16v-7a2 2 0 0 0-2-2h-3M11 3h2a2 2 0 0 1 2 2v3H9V5a2 2 0 0 1 2-2z"/><path d="M9 13h6M9 17h6"/>',
            'checkout' => '<path d="M14 11H5a2 2 0 0 0-2 2v7h4M15 3l-2 2 2 2M9 3h2a2 2 0 0 1 2 2v3h4V5a2 2 0 0 1 2-2z"/>',
            'alert'    => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>',
            'check'    => '<path d="M20 6 9 17l-5-5"/>',
            'eye'      => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
            'sparkle'  => '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/>',
            'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        ];

        if (!isset($paths[$name])) {
            return '';
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" class="' . $class . '" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
            . $paths[$name] . '</svg>';
    }
}
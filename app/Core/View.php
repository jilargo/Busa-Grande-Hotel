<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal view renderer.
 *
 * Views are plain PHP files under /views. A view is rendered into a buffer,
 * then wrapped in a layout from /views/layouts. Data passed to the view is
 * extracted into local variables so templates read naturally (e.g. $rooms).
 */
final class View
{
    public static function render(string $name, array $data = [], ?string $layout = 'app'): void
    {
        $file = base_path('views/' . str_replace('.', '/', $name) . '.php');

        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$name}");
        }

        extract($data, EXTR_SKIP);
        unset($data);

        ob_start();
        require $file;
        $content = ob_get_clean();

        if ($layout === null || $layout === false) {
            echo $content;
            return;
        }

        $layoutFile = base_path('views/layouts/' . $layout . '.php');
        if (!is_file($layoutFile)) {
            throw new \RuntimeException("Layout not found: {$layout}");
        }

        require $layoutFile;
    }
}
<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Renders a view file (plain PHP, presentation only — no business logic
 * per CLAUDE.md's "Views contain presentation only"). No templating
 * engine dependency; `$data` is extracted into local variables visible
 * to the included file, matching the classic PHP-views approach the
 * project's framework-free constraint calls for.
 */
final class View
{
    public static function render(string $template, array $data = []): string
    {
        $path = base_path('resources/views/' . str_replace('.', '/', $template) . '.php');

        if (!is_file($path)) {
            throw new RuntimeException("View [{$template}] không tồn tại tại {$path}.");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $path;

        return (string) ob_get_clean();
    }

    /**
     * Renders $template as the $content of a layout, so pages don't
     * each re-declare <html>/<head>/nav/footer.
     */
    public static function renderWithLayout(string $layout, string $template, array $data = []): string
    {
        $content = self::render($template, $data);

        return self::render($layout, array_merge($data, ['content' => $content]));
    }

    /**
     * Escapes a value for safe HTML output. Every dynamic value printed
     * in a view must go through this — never echo raw user input
     * (XSS protection, per SECURITY_STANDARD in PROJECT.md).
     */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public static function asset(string $path): string
    {
        return '/assets/' . ltrim($path, '/');
    }

    public static function svg(string $name): string
    {
        $path = base_path("public/assets/svg/{$name}.svg");

        return is_file($path) ? (string) file_get_contents($path) : '';
    }
}

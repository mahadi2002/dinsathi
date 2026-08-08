<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Plain-PHP views. A template may declare its layout with:
 *   <?php $this->layout('layouts/app', ['title' => '…']); ?>
 * and the rendered template body is available to the layout as $content.
 */
final class View
{
    private static array $shared = [];
    private ?string $layout = null;
    private array $layoutData = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function render(string $template, array $data = []): string
    {
        return (new self())->run($template, $data);
    }

    private function run(string $template, array $data): string
    {
        $file = APP_ROOT . '/views/' . trim($template, '/') . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('View not found: ' . $template);
        }

        $data = array_merge(self::$shared, $data);

        ob_start();
        (function (string $__file, array $__data): void {
            extract($__data, EXTR_SKIP);
            require $__file;
        })->call($this, $file, $data);
        $content = (string) ob_get_clean();

        if ($this->layout !== null) {
            $layout = $this->layout;
            $this->layout = null;
            $content = self::render($layout, array_merge($data, $this->layoutData, ['content' => $content]));
        }

        return $content;
    }

    /** Called from inside a template. */
    private function layout(string $name, array $data = []): void
    {
        $this->layout     = $name;
        $this->layoutData = $data;
    }

    /** Include a partial from inside a template or layout. */
    public static function partial(string $template, array $data = []): string
    {
        $file = APP_ROOT . '/views/' . trim($template, '/') . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('Partial not found: ' . $template);
        }

        ob_start();
        (static function (string $__file, array $__data): void {
            extract($__data, EXTR_SKIP);
            require $__file;
        })($file, array_merge(self::$shared, $data));

        return (string) ob_get_clean();
    }
}

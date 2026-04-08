<?php

declare(strict_types=1);

final class View
{
    public static function render(string $view, array $data = []): string
    {
        $pageTitle = $data['pageTitle'] ?? app_config('name', 'Informal Marketplace');

        $content = self::renderContent($view, $data);
        $header = self::renderContent('layouts/header', array_merge($data, ['pageTitle' => $pageTitle]));
        $footer = self::renderContent('layouts/footer', $data);

        return $header . $content . $footer;
    }

    public static function renderContent(string $view, array $data = []): string
    {
        $path = BASE_PATH . '/app/views/' . $view . '.php';

        if (!is_file($path)) {
            throw new RuntimeException('View not found.');
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $path;
        return (string) ob_get_clean();
    }
}

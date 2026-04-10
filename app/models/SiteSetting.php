<?php

declare(strict_types=1);

final class SiteSetting
{
    private const DEFAULTS = [
        'bg_login' => '',
        'bg_home' => '',
        'bg_dashboard' => '',
        'theme_background_color' => '#f7f3ee',
        'theme_surface_color' => '#fffdf9',
        'theme_text_color' => '#1c1712',
        'theme_primary_color' => '#8e7558',
        'theme_secondary_color' => '#e9dfd2',
        'theme_mode' => 'light',
        'theme_font_preset' => 'inter',
        'theme_spacing_scale' => 'normal',
    ];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function tableExists(): bool
    {
        return Database::tableExists('site_settings');
    }

    public function get(string $key, ?string $default = null): ?string
    {
        if (!$this->tableExists()) {
            return self::DEFAULTS[$key] ?? $default;
        }

        $statement = $this->db->prepare('SELECT setting_value FROM site_settings WHERE setting_key = :setting_key LIMIT 1');
        $statement->execute(['setting_key' => $key]);
        $row = $statement->fetch();

        if ($row !== false && array_key_exists('setting_value', $row)) {
            return $row['setting_value'] !== null ? (string) $row['setting_value'] : null;
        }

        return self::DEFAULTS[$key] ?? $default;
    }

    public function set(string $key, string $value): void
    {
        if (!$this->tableExists()) {
            throw new RuntimeException('The site_settings table does not exist yet. Run the site settings migration first.');
        }

        $statement = $this->db->prepare('
            INSERT INTO site_settings (setting_key, setting_value)
            VALUES (:setting_key, :setting_value)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ');
        $statement->execute([
            'setting_key' => $key,
            'setting_value' => $value,
        ]);
    }

    public function defaults(): array
    {
        return self::DEFAULTS;
    }

    public function themeSettings(): array
    {
        $background = $this->sanitizeColor((string) $this->get('theme_background_color', self::DEFAULTS['theme_background_color']), self::DEFAULTS['theme_background_color']);
        $surface = $this->sanitizeColor((string) $this->get('theme_surface_color', self::DEFAULTS['theme_surface_color']), self::DEFAULTS['theme_surface_color']);
        $text = $this->sanitizeColor((string) $this->get('theme_text_color', self::DEFAULTS['theme_text_color']), self::DEFAULTS['theme_text_color']);
        $primary = $this->sanitizeColor((string) $this->get('theme_primary_color', self::DEFAULTS['theme_primary_color']), self::DEFAULTS['theme_primary_color']);
        $secondary = $this->sanitizeColor((string) $this->get('theme_secondary_color', self::DEFAULTS['theme_secondary_color']), self::DEFAULTS['theme_secondary_color']);
        $mode = strtolower((string) $this->get('theme_mode', self::DEFAULTS['theme_mode']));
        $mode = $mode === 'dark' ? 'dark' : 'light';
        $fontPreset = $this->sanitizeFontPreset((string) $this->get('theme_font_preset', self::DEFAULTS['theme_font_preset']));
        $spacingScale = $this->sanitizeSpacingScale((string) $this->get('theme_spacing_scale', self::DEFAULTS['theme_spacing_scale']));
        $fontConfig = $this->fontPresetConfig($fontPreset);
        $spacingConfig = $this->spacingScaleConfig($spacingScale);

        return [
            'background' => $background,
            'surface' => $surface,
            'surface_muted' => $this->mixColors($surface, $background, 0.6),
            'surface_strong' => $this->mixColors($secondary, $surface, 0.42),
            'border' => $this->mixColors($secondary, $background, 0.55),
            'border_strong' => $this->mixColors($primary, $secondary, 0.22),
            'text' => $text,
            'text_soft' => $this->mixColors($text, $background, 0.68),
            'text_faint' => $this->mixColors($text, $background, 0.48),
            'primary' => $primary,
            'primary_strong' => $this->shiftColor($primary, -24),
            'primary_soft' => $this->rgba($primary, 0.12),
            'secondary' => $secondary,
            'secondary_strong' => $this->shiftColor($secondary, -16),
            'secondary_soft' => $this->rgba($secondary, 0.22),
            'mode' => $mode,
            'surface_tint' => $this->rgba($primary, 0.08),
            'focus_ring' => $this->rgba($primary, 0.24),
            'font_preset' => $fontPreset,
            'font_label' => $fontConfig['label'],
            'font_sans' => $fontConfig['sans'],
            'font_display' => $fontConfig['display'],
            'font_embed_href' => $fontConfig['embed_href'],
            'spacing_scale' => $spacingScale,
            'section_space' => $spacingConfig['section_space'],
            'panel_padding' => $spacingConfig['panel_padding'],
            'shell_width' => $spacingConfig['shell_width'],
        ];
    }

    public function sanitizeFontPreset(string $preset): string
    {
        $preset = strtolower(trim($preset));

        return match ($preset) {
            'poppins', 'helvetica', 'manrope' => $preset,
            default => 'inter',
        };
    }

    public function sanitizeSpacingScale(string $scale): string
    {
        $scale = strtolower(trim($scale));

        return match ($scale) {
            'compact', 'spacious' => $scale,
            default => 'normal',
        };
    }

    public function fontPresetConfig(string $preset): array
    {
        $preset = $this->sanitizeFontPreset($preset);

        return match ($preset) {
            'manrope' => [
                'label' => 'Manrope',
                'sans' => '"Manrope", "Helvetica Neue", Arial, sans-serif',
                'display' => '"Manrope", "Helvetica Neue", Arial, sans-serif',
                'embed_href' => 'https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=swap',
            ],
            'poppins' => [
                'label' => 'Poppins',
                'sans' => '"Poppins", "Helvetica Neue", Arial, sans-serif',
                'display' => '"Poppins", "Helvetica Neue", Arial, sans-serif',
                'embed_href' => 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap',
            ],
            'helvetica' => [
                'label' => 'Helvetica',
                'sans' => '"Helvetica Neue", Helvetica, Arial, sans-serif',
                'display' => '"Helvetica Neue", Helvetica, Arial, sans-serif',
                'embed_href' => '',
            ],
            default => [
                'label' => 'Inter',
                'sans' => '"Inter", "Helvetica Neue", Arial, sans-serif',
                'display' => '"Inter", "Helvetica Neue", Arial, sans-serif',
                'embed_href' => 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap',
            ],
        };
    }

    public function spacingScaleConfig(string $scale): array
    {
        $scale = $this->sanitizeSpacingScale($scale);

        return match ($scale) {
            'compact' => [
                'section_space' => '4rem',
                'panel_padding' => '1.35rem',
                'shell_width' => '1040px',
            ],
            'spacious' => [
                'section_space' => '7rem',
                'panel_padding' => '2.15rem',
                'shell_width' => '1200px',
            ],
            default => [
                'section_space' => '5.5rem',
                'panel_padding' => '1.75rem',
                'shell_width' => '1120px',
            ],
        };
    }

    public function backgroundForRoute(string $route): array
    {
        $pageName = $this->pageNameForRoute($route);
        $key = 'bg_' . $pageName;

        return [
            'page_name' => $pageName,
            'setting_key' => $key,
            'path' => (string) $this->get($key, ''),
        ];
    }

    public function pageNameForRoute(string $route): string
    {
        $route = trim($route, '/');

        if ($route === '' || $route === 'home/index') {
            return 'home';
        }

        $mappedRoutes = [
            'auth/login' => 'login',
            'admin/dashboard' => 'dashboard',
            'tasker/dashboard' => 'dashboard',
        ];

        if (isset($mappedRoutes[$route])) {
            return $mappedRoutes[$route];
        }

        return $this->sanitizePageName(str_replace('/', '_', $route));
    }

    public function allBackgrounds(): array
    {
        $backgrounds = [];

        if ($this->tableExists()) {
            $statement = $this->db->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'bg\\_%' ORDER BY setting_key ASC");
            $statement->execute();

            foreach ($statement->fetchAll() as $row) {
                $backgrounds[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
            }
        }

        foreach (self::DEFAULTS as $key => $value) {
            if (str_starts_with($key, 'bg_') && !array_key_exists($key, $backgrounds)) {
                $backgrounds[$key] = (string) $value;
            }
        }

        ksort($backgrounds);

        return $backgrounds;
    }

    public function sanitizePageName(string $value): string
    {
        $value = strtolower(trim($value));
        $value = str_replace([' ', '/', '\\'], '_', $value);
        $value = preg_replace('/[^a-z0-9_-]/', '', $value) ?? '';
        $value = preg_replace('/_+/', '_', $value) ?? '';

        return trim($value, '_-');
    }

    public function sanitizeColor(string $color, string $fallback): string
    {
        $color = trim($color);

        if (preg_match('/^#[a-f0-9]{6}$/i', $color)) {
            return strtolower($color);
        }

        return strtolower($fallback);
    }

    private function rgba(string $color, float $alpha): string
    {
        $rgb = $this->hexToRgb($color);

        return sprintf('rgba(%d, %d, %d, %.3f)', $rgb['r'], $rgb['g'], $rgb['b'], $alpha);
    }

    private function shiftColor(string $color, int $amount): string
    {
        $rgb = $this->hexToRgb($color);

        $red = max(0, min(255, $rgb['r'] + $amount));
        $green = max(0, min(255, $rgb['g'] + $amount));
        $blue = max(0, min(255, $rgb['b'] + $amount));

        return sprintf('#%02x%02x%02x', $red, $green, $blue);
    }

    private function mixColors(string $first, string $second, float $weightFirst): string
    {
        $weightFirst = max(0, min(1, $weightFirst));
        $weightSecond = 1 - $weightFirst;
        $rgbFirst = $this->hexToRgb($first);
        $rgbSecond = $this->hexToRgb($second);

        return sprintf(
            '#%02x%02x%02x',
            (int) round(($rgbFirst['r'] * $weightFirst) + ($rgbSecond['r'] * $weightSecond)),
            (int) round(($rgbFirst['g'] * $weightFirst) + ($rgbSecond['g'] * $weightSecond)),
            (int) round(($rgbFirst['b'] * $weightFirst) + ($rgbSecond['b'] * $weightSecond))
        );
    }

    private function hexToRgb(string $color): array
    {
        $color = ltrim(trim($color), '#');

        if (strlen($color) === 3) {
            $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        }

        if (!preg_match('/^[a-f0-9]{6}$/i', $color)) {
            $color = '0e5b57';
        }

        return [
            'r' => hexdec(substr($color, 0, 2)),
            'g' => hexdec(substr($color, 2, 2)),
            'b' => hexdec(substr($color, 4, 2)),
        ];
    }
}

<?php

namespace Oobi\Laraberg\Services\GlobalStyles;

use Illuminate\Support\Arr;

/**
 * Loads and merges theme.json data from multiple origins.
 *
 * Mirrors WordPress's `WP_Theme_JSON_Resolver::get_merged_data()` merge strategy:
 *   1. Core defaults (Gutenberg's lib/theme.json shipped with this package)
 *   2. Application overrides (consuming app's theme.json, published config)
 *
 * Unlike WordPress, we don't merge block.json style data or user CPT data.
 * Those features can be added later if needed.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/lib/class-wp-theme-json-resolver-gutenberg.php
 */
class ThemeJson
{
    /**
     * The merged theme.json data.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Cached instance (singleton within a request).
     */
    protected static ?self $instance = null;

    public function __construct()
    {
        $this->data = $this->mergeOrigins();
    }

    /**
     * Get the singleton instance with merged data.
     */
    public static function instance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Clear the cached instance (useful in tests or after config changes).
     */
    public static function flush(): void
    {
        static::$instance = null;
    }

    /**
     * Merge all origins in order: core defaults → application overrides.
     *
     * @return array<string, mixed>
     */
    protected function mergeOrigins(): array
    {
        $core = $this->loadCoreDefaults();
        $app = $this->loadAppOverrides();

        return $this->deepMerge($core, $app);
    }

    /**
     * Load core defaults from the theme.json shipped with this package.
     * This is Gutenberg's `lib/theme.json`.
     *
     * @return array<string, mixed>
     */
    protected function loadCoreDefaults(): array
    {
        $path = dirname(__DIR__, 3) . '/config/theme.json';

        if (! file_exists($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?? [];
    }

    /**
     * Load application-level overrides.
     *
     * Checks these locations in order, using the first one found:
     *   1. Published config: config_path('laraberg-theme.json')
     *   2. Base path: base_path('theme.json')
     *
     * @return array<string, mixed>
     */
    protected function loadAppOverrides(): array
    {
        $paths = [];

        if (function_exists('config_path')) {
            $paths[] = config_path('laraberg-theme.json');
        }

        if (function_exists('base_path')) {
            $paths[] = base_path('theme.json');
        }

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return json_decode(file_get_contents($path), true) ?? [];
            }
        }

        return [];
    }

    /**
     * Deep merge two arrays, with $override values taking precedence.
     * Arrays with sequential integer keys (like palettes) are replaced entirely.
     * Associative arrays are recursively merged.
     *
     * @param array<string, mixed> $base
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    protected function deepMerge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (
                is_array($value)
                && isset($base[$key])
                && is_array($base[$key])
                && ! $this->isSequentialArray($value)
            ) {
                $base[$key] = $this->deepMerge($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /**
     * Check if an array has sequential integer keys (i.e., it's a list, not a map).
     */
    protected function isSequentialArray(array $arr): bool
    {
        if (empty($arr)) {
            return false;
        }

        return array_keys($arr) === range(0, count($arr) - 1);
    }

    /**
     * Get the full merged data.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Get a value from the merged data using dot notation.
     *
     * @param string $key Dot-notation path (e.g., 'settings.color.palette')
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->data, $key, $default);
    }

    /**
     * Get the layout settings (contentSize, wideSize).
     *
     * @return array{contentSize?: string, wideSize?: string}
     */
    public function layout(): array
    {
        return $this->get('settings.layout', []);
    }

    /**
     * Get the block gap value.
     */
    public function blockGap(): ?string
    {
        return $this->get('styles.spacing.blockGap');
    }

    /**
     * Get the color palette.
     *
     * @return array<int, array{name: string, slug: string, color: string}>
     */
    public function colorPalette(): array
    {
        return $this->get('settings.color.palette', []);
    }

    /**
     * Get the gradient presets.
     *
     * @return array<int, array{name: string, slug: string, gradient: string}>
     */
    public function gradients(): array
    {
        return $this->get('settings.color.gradients', []);
    }

    /**
     * Get the font size presets.
     *
     * @return array<int, array{name: string, slug: string, size: string}>
     */
    public function fontSizes(): array
    {
        return $this->get('settings.typography.fontSizes', []);
    }

    /**
     * Get the spacing scale presets.
     *
     * @return array<string, mixed>
     */
    public function spacingScale(): array
    {
        return $this->get('settings.spacing.spacingScale', []);
    }

    /**
     * Get shadow presets.
     *
     * @return array<int, array{name: string, slug: string, shadow: string}>
     */
    public function shadowPresets(): array
    {
        return $this->get('settings.shadow.presets', []);
    }

    /**
     * Whether block gap support is explicitly configured (not null).
     */
    public function hasBlockGapSupport(): bool
    {
        return $this->get('settings.spacing.blockGap') !== null;
    }
}

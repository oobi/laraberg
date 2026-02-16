<?php

namespace Oobi\Laraberg\Services\GlobalStyles;

/**
 * Generates CSS from theme.json data and layout definitions.
 *
 * This mirrors WordPress's `WP_Theme_JSON_Gutenberg::get_stylesheet()` behavior,
 * generating the three CSS sections that WordPress produces server-side:
 *
 *   1. **variables** — CSS custom properties (:root { --wp--preset--color--*, ... })
 *   2. **styles**    — Layout base styles (.is-layout-flex { display: flex }, root layout rules)
 *   3. **presets**   — Utility classes (.has-*-color, .has-*-font-size, etc.)
 *
 * Additionally generates admin CSS variables (--wp-admin-theme-color) that WordPress
 * includes from its admin SCSS but aren't in the npm packages.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/lib/class-wp-theme-json-gutenberg.php
 */
class GlobalStylesGenerator
{
    protected ThemeJson $themeJson;

    /**
     * Preset metadata defining how to generate CSS variables and classes.
     * Mirrors WP_Theme_JSON::PRESETS_METADATA.
     *
     * @var array<int, array{path: string, varPrefix: string, classes: array<int, array{classPrefix: string, property: string}>}>
     */
    protected static array $presetsMeta = [
        [
            'path' => 'settings.color.palette',
            'varPrefix' => '--wp--preset--color--',
            'classes' => [
                ['classPrefix' => 'has-%s-color', 'property' => 'color'],
                ['classPrefix' => 'has-%s-background-color', 'property' => 'background-color'],
                ['classPrefix' => 'has-%s-border-color', 'property' => 'border-color'],
            ],
        ],
        [
            'path' => 'settings.color.gradients',
            'varPrefix' => '--wp--preset--gradient--',
            'valueKey' => 'gradient',
            'classes' => [
                ['classPrefix' => 'has-%s-gradient-background', 'property' => 'background'],
            ],
        ],
        [
            'path' => 'settings.typography.fontSizes',
            'varPrefix' => '--wp--preset--font-size--',
            'valueKey' => 'size',
            'classes' => [
                ['classPrefix' => 'has-%s-font-size', 'property' => 'font-size'],
            ],
        ],
        [
            'path' => 'settings.typography.fontFamilies',
            'varPrefix' => '--wp--preset--font-family--',
            'valueKey' => 'fontFamily',
            'classes' => [
                ['classPrefix' => 'has-%s-font-family', 'property' => 'font-family'],
            ],
        ],
        [
            'path' => 'settings.spacing.spacingSizes',
            'varPrefix' => '--wp--preset--spacing--',
            'valueKey' => 'size',
            'classes' => [],
        ],
        [
            'path' => 'settings.shadow.presets',
            'varPrefix' => '--wp--preset--shadow--',
            'valueKey' => 'shadow',
            'classes' => [],
        ],
        [
            'path' => 'settings.dimensions.aspectRatios',
            'varPrefix' => '--wp--preset--aspect-ratio--',
            'valueKey' => 'ratio',
            'classes' => [],
        ],
    ];

    public function __construct(?ThemeJson $themeJson = null)
    {
        $this->themeJson = $themeJson ?? ThemeJson::instance();
    }

    /**
     * Generate the complete stylesheet.
     *
     * @param array<string> $types Which sections to include: 'variables', 'styles', 'presets', 'admin'
     * @return string Generated CSS
     */
    public function getStylesheet(array $types = ['variables', 'styles', 'presets', 'admin']): string
    {
        $css = '';

        if (in_array('admin', $types, true)) {
            $css .= $this->getAdminVariables();
        }

        if (in_array('variables', $types, true)) {
            $css .= $this->getPresetVariables();
        }

        if (in_array('styles', $types, true)) {
            $css .= $this->getLayoutStyles();
            $css .= $this->getRootLayoutRules();
        }

        if (in_array('presets', $types, true)) {
            $css .= $this->getPresetClasses();
        }

        return $css;
    }

    /**
     * Generate admin CSS variables.
     *
     * These are normally compiled from SCSS in WordPress admin but aren't
     * available via npm packages. We need to provide them for the editor UI.
     *
     * @see gutenberg/packages/base-styles/_default-custom-properties.scss
     */
    protected function getAdminVariables(): string
    {
        $color = config('laraberg.admin_color', '#007cba');
        $darker10 = $this->adjustLightness($color, -5);
        $darker20 = $this->adjustLightness($color, -10);

        $css = ":root {\n";
        $css .= "    --wp-admin-theme-color: {$color};\n";
        $css .= "    --wp-admin-theme-color--rgb: " . $this->hexToRgb($color) . ";\n";
        $css .= "    --wp-admin-theme-color-darker-10: {$darker10};\n";
        $css .= "    --wp-admin-theme-color-darker-10--rgb: " . $this->hexToRgb($darker10) . ";\n";
        $css .= "    --wp-admin-theme-color-darker-20: {$darker20};\n";
        $css .= "    --wp-admin-theme-color-darker-20--rgb: " . $this->hexToRgb($darker20) . ";\n";
        $css .= "    --wp-admin-border-width-focus: 2px;\n";
        $css .= "    --wp-block-synced-color: #7a00df;\n";
        $css .= "    --wp-block-synced-color--rgb: 122, 0, 223;\n";
        $css .= "    --wp-bound-block-color: var(--wp-block-synced-color);\n";
        $css .= "}\n";
        $css .= "@media (min-resolution: 192dpi) {\n";
        $css .= "    :root { --wp-admin-border-width-focus: 1.5px; }\n";
        $css .= "}\n";

        return $css;
    }

    /**
     * Generate CSS custom properties from presets.
     *
     * Produces rules like:
     *   :root { --wp--preset--color--black: #000000; --wp--preset--font-size--large: 36px; }
     *
     * Also generates layout-related CSS custom properties (content-size, wide-size, block-gap).
     *
     * @see WP_Theme_JSON_Gutenberg::get_css_variables()
     */
    protected function getPresetVariables(): string
    {
        $declarations = [];

        // Layout variables
        $layout = $this->themeJson->layout();
        if (! empty($layout['contentSize'])) {
            $declarations[] = "    --wp--style--global--content-size: {$layout['contentSize']}";
        }
        if (! empty($layout['wideSize'])) {
            $declarations[] = "    --wp--style--global--wide-size: {$layout['wideSize']}";
        }

        // Block gap
        $blockGap = $this->themeJson->blockGap();
        if ($blockGap !== null) {
            $declarations[] = "    --wp--style--block-gap: {$blockGap}";
        }

        // Preset variables from palette, font sizes, gradients, etc.
        foreach (static::$presetsMeta as $meta) {
            $items = $this->themeJson->get($meta['path'], []);
            $valueKey = $meta['valueKey'] ?? 'color';

            foreach ($items as $item) {
                if (! isset($item['slug'], $item[$valueKey])) {
                    continue;
                }
                $varName = $meta['varPrefix'] . $item['slug'];
                $declarations[] = "    {$varName}: {$item[$valueKey]}";
            }
        }

        // Spacing scale (generated from spacingScale config)
        $spacingScale = $this->themeJson->spacingScale();
        if (! empty($spacingScale) && ($spacingScale['steps'] ?? 0) > 0) {
            $generated = $this->generateSpacingScale($spacingScale);
            foreach ($generated as $slug => $value) {
                $declarations[] = "    --wp--preset--spacing--{$slug}: {$value}";
            }
        }

        if (empty($declarations)) {
            return '';
        }

        return ":root {\n" . implode(";\n", $declarations) . ";\n}\n";
    }

    /**
     * Generate layout base styles from layout definitions.
     *
     * This is the core of what WordPress generates server-side.
     * Mirrors `WP_Theme_JSON_Gutenberg::get_layout_styles()` when called for the root selector.
     *
     * Generates:
     *   - Display modes: `body .is-layout-flex { display: flex; }`
     *   - Base style rules: `.is-layout-flex { flex-wrap: wrap; align-items: center; }`
     *   - Spacing/gap rules: `:root :where(.is-layout-flex) { gap: 24px; }`
     *
     * @see WP_Theme_JSON_Gutenberg::get_layout_styles()
     */
    protected function getLayoutStyles(): string
    {
        $css = '';
        $definitions = LayoutDefinitions::get();
        $validDisplayModes = ['block', 'flex', 'grid'];
        $hasBlockGapSupport = $this->themeJson->hasBlockGapSupport();
        $blockGapValue = $this->themeJson->blockGap() ?? '0.5em';
        $hasContentSize = ! empty($this->themeJson->layout()['contentSize']);
        $hasWideSize = ! empty($this->themeJson->layout()['wideSize']);

        foreach ($definitions as $definition) {
            $className = $definition['className'] ?? null;
            if (empty($className)) {
                continue;
            }

            // 1. Output display mode (e.g., .is-layout-flex { display: flex; })
            // WordPress uses `body .is-layout-flex` for root selector context.
            if (
                ! empty($definition['displayMode'])
                && in_array($definition['displayMode'], $validDisplayModes, true)
            ) {
                $css .= "body .{$className} {\n";
                $css .= "    display: {$definition['displayMode']};\n";
                $css .= "}\n";
            }

            // 2. Output base style rules
            $baseStyles = $definition['baseStyles'] ?? [];
            foreach ($baseStyles as $rule) {
                if (! isset($rule['selector']) || empty($rule['rules'])) {
                    continue;
                }

                $declarations = [];
                foreach ($rule['rules'] as $property => $value) {
                    // Skip rules referencing content/wide size if not configured
                    if (
                        is_string($value)
                        && (str_contains($value, '--global--content-size') || str_contains($value, '--global--wide-size'))
                        && ! $hasContentSize
                        && ! $hasWideSize
                    ) {
                        continue;
                    }

                    if (is_string($value)) {
                        $declarations[] = "    {$property}: {$value}";
                    }
                }

                if (! empty($declarations)) {
                    $selector = ".{$className}{$rule['selector']}";
                    $css .= "{$selector} {\n" . implode(";\n", $declarations) . ";\n}\n";
                }
            }

            // 3. Output spacing/gap styles
            $spacingStyles = $definition['spacingStyles'] ?? [];
            if (! empty($spacingStyles) && ($hasBlockGapSupport || in_array($definition['name'], ['flex', 'grid']))) {
                foreach ($spacingStyles as $rule) {
                    if (! isset($rule['selector']) || empty($rule['rules'])) {
                        continue;
                    }

                    $declarations = [];
                    foreach ($rule['rules'] as $property => $value) {
                        $resolvedValue = is_string($value) ? $value : $blockGapValue;
                        $declarations[] = "    {$property}: {$resolvedValue}";
                    }

                    if (! empty($declarations)) {
                        // WordPress wraps spacing selectors in :root :where() for lower specificity control.
                        if ($hasBlockGapSupport) {
                            $selector = ":root :where(.{$className}){$rule['selector']}";
                        } else {
                            $selector = ":where(.{$className}{$rule['selector']})";
                        }
                        $css .= "{$selector} {\n" . implode(";\n", $declarations) . ";\n}\n";
                    }
                }
            }
        }

        return $css;
    }

    /**
     * Generate root layout rules (body margin reset, block gap on wp-site-blocks, etc.).
     *
     * @see WP_Theme_JSON_Gutenberg::get_root_layout_rules()
     */
    protected function getRootLayoutRules(): string
    {
        $css = '';
        $blockGap = $this->themeJson->blockGap();

        // Block gap on wp-site-blocks
        if ($blockGap !== null) {
            $css .= ".wp-site-blocks > * + * {\n";
            $css .= "    margin-block-start: {$blockGap};\n";
            $css .= "}\n";
        }

        // Editor canvas padding.
        // WordPress applies this via the admin theme; in Laraberg we inject it here
        // since there's no admin chrome wrapping the editor.
        $css .= ".is-root-container {\n";
        $css .= "    padding: 36px;\n";
        $css .= "    width: 100%;\n";
        $css .= "    box-sizing: border-box;\n";
        $css .= "}\n";

        return $css;
    }

    /**
     * Generate preset utility classes.
     *
     * Produces rules like:
     *   .has-black-color { color: var(--wp--preset--color--black) !important; }
     *   .has-large-font-size { font-size: var(--wp--preset--font-size--large) !important; }
     *
     * @see WP_Theme_JSON_Gutenberg::compute_preset_classes()
     */
    protected function getPresetClasses(): string
    {
        $css = '';

        foreach (static::$presetsMeta as $meta) {
            $items = $this->themeJson->get($meta['path'], []);

            foreach ($items as $item) {
                if (! isset($item['slug'])) {
                    continue;
                }

                $slug = $item['slug'];
                $varName = $meta['varPrefix'] . $slug;

                foreach ($meta['classes'] as $classDef) {
                    $className = sprintf($classDef['classPrefix'], $slug);
                    $property = $classDef['property'];
                    $css .= ".{$className} {\n";
                    $css .= "    {$property}: var({$varName}) !important;\n";
                    $css .= "}\n";
                }
            }
        }

        return $css;
    }

    /**
     * Generate spacing scale values from the spacing scale config.
     *
     * Mirrors WordPress's spacing scale generation from `settings.spacing.spacingScale`.
     *
     * @param array{operator: string, increment: float, steps: int, mediumStep: float, unit: string} $config
     * @return array<string, string> slug => value
     */
    protected function generateSpacingScale(array $config): array
    {
        $operator = $config['operator'] ?? '*';
        $increment = $config['increment'] ?? 1.5;
        $steps = $config['steps'] ?? 7;
        $mediumStep = $config['mediumStep'] ?? 1.5;
        $unit = $config['unit'] ?? 'rem';

        if ($steps < 1) {
            return [];
        }

        $values = [];

        // Calculate the medium step index (0-based, roughly in the middle)
        $mediumIndex = (int) ceil($steps / 2) - 1;

        for ($i = 0; $i < $steps; $i++) {
            $distance = $i - $mediumIndex;
            if ($operator === '*') {
                $value = $mediumStep * pow($increment, $distance);
            } else {
                $value = $mediumStep + ($increment * $distance);
            }

            // WordPress uses sequential slug numbers: 20, 30, 40, 50, 60, 70, 80
            $slug = (string) (($i + 2) * 10);
            $values[$slug] = round($value, 2) . $unit;
        }

        return $values;
    }

    /**
     * Adjust the lightness of a hex color.
     *
     * @param string $hex Hex color (e.g. '#007cba')
     * @param int $percent Percentage to adjust (-100 to 100)
     * @return string Adjusted hex color
     */
    protected function adjustLightness(string $hex, int $percent): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Convert to HSL
        $r /= 255;
        $g /= 255;
        $b /= 255;
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            switch ($max) {
                case $r:
                    $h = (($g - $b) / $d + ($g < $b ? 6 : 0)) / 6;
                    break;
                case $g:
                    $h = (($b - $r) / $d + 2) / 6;
                    break;
                default:
                    $h = (($r - $g) / $d + 4) / 6;
                    break;
            }
        }

        // Adjust lightness
        $l = max(0, min(1, $l + $percent / 100));

        // Convert back to RGB
        if ($s === 0) {
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = $this->hueToRgb($p, $q, $h + 1 / 3);
            $g = $this->hueToRgb($p, $q, $h);
            $b = $this->hueToRgb($p, $q, $h - 1 / 3);
        }

        return '#' . sprintf('%02x%02x%02x', (int) round($r * 255), (int) round($g * 255), (int) round($b * 255));
    }

    protected function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }

        return $p;
    }

    /**
     * Convert hex color to RGB string.
     */
    protected function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');

        return implode(', ', [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ]);
    }
}

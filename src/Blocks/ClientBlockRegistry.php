<?php

namespace Oobi\Laraberg\Blocks;

/**
 * Central registry for client-side Gutenberg blocks and categories.
 *
 * This is a singleton bound in the container — any Laravel package can
 * register blocks and categories in its ServiceProvider's boot() method.
 * A single @larabergBlocks Blade directive then outputs all the <script>
 * tags needed by the editor.
 *
 * Usage in an addon package's ServiceProvider:
 *
 *     use Oobi\Laraberg\Blocks\ClientBlockRegistry;
 *
 *     public function boot(): void
 *     {
 *         $registry = app(ClientBlockRegistry::class);
 *
 *         // Register a custom category (optional — blocks can use existing categories)
 *         $registry->registerCategory('my-addon', 'My Addon Blocks');
 *
 *         // Register blocks
 *         $registry->register(
 *             'my-addon/widget',
 *             __DIR__.'/../resources/js/blocks/widget.js'
 *         );
 *     }
 */
class ClientBlockRegistry
{
    /**
     * @var array<string, string> block name => absolute JS file path
     */
    protected array $blocks = [];

    /**
     * @var array<int, array{slug: string, title: string, icon: string|null}> custom categories
     */
    protected array $categories = [];

    /**
     * Register a client-side block.
     *
     * @param string $name   Block name (e.g. 'my-addon/widget')
     * @param string $jsPath Absolute path to the JS file
     */
    public function register(string $name, string $jsPath): void
    {
        $this->blocks[$name] = $jsPath;
    }

    /**
     * Register a custom block category.
     *
     * Categories are prepended to the inserter in the order registered.
     * Duplicate slugs are silently ignored (first registration wins).
     *
     * @param string      $slug  Category slug (e.g. 'my-addon')
     * @param string      $title Display title (e.g. 'My Addon Blocks')
     * @param string|null $icon  Optional dashicon name or null
     */
    public function registerCategory(string $slug, string $title, ?string $icon = null): void
    {
        foreach ($this->categories as $cat) {
            if ($cat['slug'] === $slug) {
                return;
            }
        }

        $this->categories[] = [
            'slug' => $slug,
            'title' => $title,
            'icon' => $icon,
        ];
    }

    /**
     * Get all registered blocks.
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->blocks;
    }

    /**
     * Get all registered categories.
     *
     * @return array<int, array{slug: string, title: string, icon: string|null}>
     */
    public function categories(): array
    {
        return $this->categories;
    }

    /**
     * Generate the <script> tags for all registered blocks +
     * the category bootstrap script.
     *
     * @param array<string, mixed> $options  Optional: ['nonce' => '...']
     */
    public static function scripts(array $options = []): string
    {
        $instance = app(static::class);

        if (empty($instance->blocks) && empty($instance->categories)) {
            return '';
        }

        $nonce = isset($options['nonce']) ? " nonce=\"{$options['nonce']}\"" : '';
        $prefix = config('laraberg.prefix', 'laraberg');
        $html = '';

        // 1. Inject config JSON (categories list for bootstrap script to read)
        $config = json_encode([
            'categories' => $instance->categories,
        ], JSON_UNESCAPED_SLASHES);
        $html .= "<script{$nonce}>window.LarabergBlocks = {$config};</script>\n";

        // 2. Bootstrap script (reads config, registers categories in Gutenberg)
        $bootstrapUrl = static::assetUrl(
            "{$prefix}/addons/bootstrap.js",
            __DIR__.'/../../resources/js/addon-bootstrap.js',
            'vendor/laraberg/js/addon-bootstrap.js'
        );
        $html .= "    <script src=\"{$bootstrapUrl}\"{$nonce}></script>\n";

        // 3. Individual block scripts
        foreach ($instance->blocks as $name => $jsPath) {
            $slug = str_replace('/', '-', $name);
            $blockUrl = static::assetUrl(
                "{$prefix}/addons/blocks/{$slug}.js",
                $jsPath,
                "vendor/laraberg/addons/blocks/{$slug}.js"
            );
            $html .= "    <script src=\"{$blockUrl}\"{$nonce}></script>\n";
        }

        return $html;
    }

    /**
     * Resolve asset URL: prefer published static file, fall back to route.
     */
    protected static function assetUrl(string $routeUri, string $distFile, string $publicRelPath): string
    {
        $publicFile = public_path($publicRelPath);

        if (file_exists($publicFile) && ! is_link(dirname($publicFile))) {
            $version = filemtime($publicFile);

            return asset($publicRelPath)."?v={$version}";
        }

        $url = (string) str($routeUri)->when(
            ! str($routeUri)->isUrl(),
            fn ($u) => $u->start('/')
        );

        if (file_exists($distFile)) {
            $version = filemtime($distFile);
            $url .= "?v={$version}";
        }

        return $url;
    }
}

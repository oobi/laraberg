<?php

namespace Oobi\Laraberg;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Oobi\Laraberg\Blocks\BlockParser;
use Oobi\Laraberg\Blocks\BlockTypeRegistry;
use Oobi\Laraberg\Blocks\ClientBlockRegistry;
use Oobi\Laraberg\Blocks\ContentRenderer;
use Oobi\Laraberg\Http\Controllers\AddonAssetController;
use Oobi\Laraberg\Http\Controllers\AssetController;
use Oobi\Laraberg\Services\OEmbedService;

class LarabergServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->publishes([__DIR__ . '/../config/laraberg.php' => config_path('laraberg.php')], 'config');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([__DIR__ . '/../public' => public_path('vendor/laraberg')], 'public');

        if (config('laraberg.use_package_routes')) {
            $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
        }

        $this->registerAssetRoutes();
        $this->registerAddonRoutes();
        $this->registerBladeDirectives();
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->app->singleton(Laraberg::class);

        $this->app->singleton(BlockTypeRegistry::class, function () {
            return BlockTypeRegistry::getInstance();
        });

        $this->app->singleton(ClientBlockRegistry::class);

        $this->app->alias(ContentRenderer::class, 'laraberg.renderer');
        $this->app->alias(BlockParser::class, 'laraberg.parser');
        $this->app->alias(OEmbedService::class, 'laraberg.embed');
        $this->app->alias(BlockTypeRegistry::class, 'laraberg.registry');
        $this->app->alias(ClientBlockRegistry::class, 'laraberg.addons');
    }

    /**
     * Register routes that serve the package's built assets (JS & CSS).
     *
     * If the assets have been published to public/vendor/laraberg/, the
     * web server serves them as static files and these routes are never hit.
     */
    protected function registerAssetRoutes(): void
    {
        $prefix = config('laraberg.prefix', 'laraberg');

        $reactRoute = Route::get("{$prefix}/js/react.min.js", [AssetController::class, 'react'])
            ->name('laraberg.asset.react');

        $reactDomRoute = Route::get("{$prefix}/js/react-dom.min.js", [AssetController::class, 'reactDom'])
            ->name('laraberg.asset.react-dom');

        $jsRoute = Route::get("{$prefix}/js/laraberg.js", [AssetController::class, 'js'])
            ->name('laraberg.asset.js');

        $jsChunkRoute = Route::get("{$prefix}/js/{chunk}", [AssetController::class, 'jsChunk'])
            ->where('chunk', '\d+\.laraberg\.js')
            ->name('laraberg.asset.js-chunk');

        $cssRoute = Route::get("{$prefix}/css/laraberg.css", [AssetController::class, 'css'])
            ->name('laraberg.asset.css');

        $globalStylesRoute = Route::get("{$prefix}/css/global-styles.css", [AssetController::class, 'globalStyles'])
            ->name('laraberg.asset.global-styles');

        $laraberg = app(Laraberg::class);
        $laraberg->setReactRouteUri($reactRoute->uri);
        $laraberg->setReactDomRouteUri($reactDomRoute->uri);
        $laraberg->setJsRouteUri($jsRoute->uri);
        $laraberg->setJsChunkRouteUri($jsChunkRoute->uri);
        $laraberg->setCssRouteUri($cssRoute->uri);
        $laraberg->setGlobalStylesRouteUri($globalStylesRoute->uri);
    }

    /**
     * Register routes that serve addon block JS files.
     *
     * These routes serve the bootstrap script and individual block
     * scripts registered by addon packages via ClientBlockRegistry.
     */
    protected function registerAddonRoutes(): void
    {
        $prefix = config('laraberg.prefix', 'laraberg');

        Route::get("{$prefix}/addons/bootstrap.js", [AddonAssetController::class, 'bootstrap'])
            ->name('laraberg.addon.bootstrap');

        Route::get("{$prefix}/addons/blocks/{name}.js", [AddonAssetController::class, 'block'])
            ->where('name', '[a-zA-Z0-9_-]+')
            ->name('laraberg.addon.block');
    }

    /**
     * Register Blade directives for including Laraberg assets.
     *
     * Usage in Blade:
     *   @larabergStyles      — outputs the <link> tag for CSS
     *   @larabergScripts     — outputs the <script> tag for JS
     *   @larabergScriptUrl   — outputs just the JS URL
     *   @larabergBlocks      — outputs all addon block <script> tags
     */
    protected function registerBladeDirectives(): void
    {
        Blade::directive('larabergReact', function (string $expression) {
            return "<?php echo \Oobi\Laraberg\Laraberg::reactTags({$expression}); ?>";
        });

        Blade::directive('larabergStyles', function (string $expression) {
            return "<?php echo \Oobi\Laraberg\Laraberg::cssTag({$expression}); ?>";
        });

        Blade::directive('larabergScripts', function (string $expression) {
            return "<?php echo \Oobi\Laraberg\Laraberg::jsTag({$expression}); ?>";
        });

        Blade::directive('larabergScriptUrl', function (string $expression) {
            return "<?php echo \Oobi\Laraberg\Laraberg::jsUrl({$expression}); ?>";
        });

        Blade::directive('larabergGlobalStyles', function (string $expression) {
            return "<?php echo \Oobi\Laraberg\Laraberg::globalStylesTag({$expression}); ?>";
        });

        Blade::directive('larabergBlocks', function (string $expression) {
            return "<?php echo \Oobi\Laraberg\Blocks\ClientBlockRegistry::scripts({$expression}); ?>";
        });
    }
}

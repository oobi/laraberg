<?php

namespace Oobi\Laraberg;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Oobi\Laraberg\Blocks\BlockParser;
use Oobi\Laraberg\Blocks\BlockTypeRegistry;
use Oobi\Laraberg\Blocks\ContentRenderer;
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

        $this->app->alias(ContentRenderer::class, 'laraberg.renderer');
        $this->app->alias(BlockParser::class, 'laraberg.parser');
        $this->app->alias(OEmbedService::class, 'laraberg.embed');
        $this->app->alias(BlockTypeRegistry::class, 'laraberg.registry');
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
        $laraberg->setJsRouteUri($jsRoute->uri);
        $laraberg->setJsChunkRouteUri($jsChunkRoute->uri);
        $laraberg->setCssRouteUri($cssRoute->uri);
        $laraberg->setGlobalStylesRouteUri($globalStylesRoute->uri);
    }

    /**
     * Register Blade directives for including Laraberg assets.
     *
     * Usage in Blade:
     *   @larabergStyles      — outputs the <link> tag for CSS
     *   @larabergScripts     — outputs the <script> tag for JS
     *   @larabergScriptUrl   — outputs just the JS URL
     */
    protected function registerBladeDirectives(): void
    {
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
    }
}

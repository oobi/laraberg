<?php

namespace Oobi\Laraberg;

use Oobi\Laraberg\Blocks\BlockTypeRegistry;

class Laraberg
{
    protected string $jsRouteUri = '';

    protected string $jsChunkRouteUri = '';

    protected string $cssRouteUri = '';

    /**
     * Set the JS route URI (called during boot).
     */
    public function setJsRouteUri(string $uri): void
    {
        $this->jsRouteUri = $uri;
    }

    /**
     * Set the JS chunk route URI pattern (called during boot).
     */
    public function setJsChunkRouteUri(string $uri): void
    {
        $this->jsChunkRouteUri = $uri;
    }

    /**
     * Set the CSS route URI (called during boot).
     */
    public function setCssRouteUri(string $uri): void
    {
        $this->cssRouteUri = $uri;
    }

    /**
     * Generate the <script> tag for the Laraberg JS.
     */
    public static function jsTag(array $options = []): string
    {
        $url = static::jsUrl($options);
        $nonce = isset($options['nonce']) ? " nonce=\"{$options['nonce']}\"" : '';

        return "<script src=\"{$url}\"{$nonce}></script>";
    }

    /**
     * Get the JS URL.
     */
    public static function jsUrl(array $options = []): string
    {
        $instance = app(static::class);

        return static::assetUrl(
            $instance->jsRouteUri,
            __DIR__.'/../public/js/laraberg.js',
            'vendor/laraberg/js/laraberg.js',
            $options
        );
    }

    /**
     * Generate the <link> tag for the Laraberg CSS.
     */
    public static function cssTag(array $options = []): string
    {
        $url = static::cssUrl($options);
        $nonce = isset($options['nonce']) ? " nonce=\"{$options['nonce']}\"" : '';

        return "<link rel=\"stylesheet\" href=\"{$url}\"{$nonce}>";
    }

    /**
     * Get the CSS URL.
     */
    public static function cssUrl(array $options = []): string
    {
        $instance = app(static::class);

        return static::assetUrl(
            $instance->cssRouteUri,
            __DIR__.'/../public/css/laraberg.css',
            'vendor/laraberg/css/laraberg.css',
            $options
        );
    }

    public static function registerBlockType(
        string $name,
        array $attributes = [],
        callable $renderCallback = null
    ): void {
        /** @var BlockTypeRegistry $registry */
        $registry = app(BlockTypeRegistry::class);
        $registry->register($name, $attributes, $renderCallback);
    }

    /**
     * Build the versioned asset URL.
     *
     * Priority:
     *  1. Published static assets in public/vendor/laraberg/ (production)
     *  2. Route-based serving (dev or when not published)
     */
    protected static function assetUrl(string $routeUri, string $distFile, string $publicRelPath, array $options = []): string
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

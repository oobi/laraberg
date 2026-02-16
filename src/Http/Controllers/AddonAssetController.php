<?php

namespace Oobi\Laraberg\Http\Controllers;

use Illuminate\Http\Response;
use Oobi\Laraberg\Blocks\ClientBlockRegistry;

/**
 * Serves JS files for addon blocks registered via ClientBlockRegistry.
 */
class AddonAssetController
{
    /**
     * Serve the addon bootstrap script (registers custom categories).
     */
    public function bootstrap(): Response
    {
        $path = __DIR__.'/../../../resources/js/addon-bootstrap.js';

        if (! file_exists($path)) {
            abort(404);
        }

        return response(file_get_contents($path), 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    /**
     * Serve an individual addon block's JS file.
     */
    public function block(string $name): Response
    {
        /** @var ClientBlockRegistry $registry */
        $registry = app(ClientBlockRegistry::class);
        $blocks = $registry->all();

        // Map slug back to block name: "thebuzz-alert-box" => "thebuzz/alert-box"
        $blockName = null;
        foreach ($blocks as $registeredName => $path) {
            if (str_replace('/', '-', $registeredName) === $name) {
                $blockName = $registeredName;
                break;
            }
        }

        if (! $blockName || ! isset($blocks[$blockName]) || ! file_exists($blocks[$blockName])) {
            abort(404);
        }

        return response(file_get_contents($blocks[$blockName]), 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}

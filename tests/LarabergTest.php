<?php

namespace Oobi\Laraberg\Test;

use Oobi\Laraberg\Laraberg;

class LarabergTest extends TestCase
{
    public function testServiceProviderIsRegistered(): void
    {
        $this->assertTrue(
            $this->app->providerIsLoaded(\Oobi\Laraberg\LarabergServiceProvider::class)
        );
    }

    public function testBlockTypeCanBeRegistered(): void
    {
        Laraberg::registerBlockType(
            'test/my-block',
            [],
            function ($attributes, $content) {
                return '<div>Test block</div>';
            }
        );

        $this->assertTrue(true);
    }
}

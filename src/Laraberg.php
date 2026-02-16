<?php

namespace Oobi\Laraberg;

use Oobi\Laraberg\Blocks\BlockTypeRegistry;

class Laraberg
{
    public static function registerBlockType(
        string $name,
        array $attributes = [],
        callable $renderCallback = null
    ): void {
        /** @var BlockTypeRegistry $registry */
        $registry = app(BlockTypeRegistry::class);
        $registry->register($name, $attributes, $renderCallback);
    }
}

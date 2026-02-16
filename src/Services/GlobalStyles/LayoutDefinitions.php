<?php

namespace Oobi\Laraberg\Services\GlobalStyles;

/**
 * Layout definitions for the block editor.
 *
 * This is a direct port of WordPress's `gutenberg_get_layout_definitions()` from
 * `gutenberg/lib/block-supports/layout.php`. It provides a common definition of
 * slugs, classnames, base styles, and spacing styles for each layout type.
 *
 * When Gutenberg updates its layout definitions, this class should be updated
 * to match. The JS definitions in `@wordpress/block-editor/src/layouts/definitions.js`
 * are the JS mirror of this same data.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/lib/block-supports/layout.php
 */
class LayoutDefinitions
{
    /**
     * Get layout definitions keyed by layout type.
     *
     * @return array<string, array{
     *     name: string,
     *     slug: string,
     *     className: string,
     *     displayMode?: string,
     *     baseStyles: array<int, array{selector: string, rules: array<string, string|null>}>,
     *     spacingStyles: array<int, array{selector: string, rules: array<string, string|null>}>
     * }>
     */
    public static function get(): array
    {
        return [
            'default' => [
                'name' => 'default',
                'slug' => 'flow',
                'className' => 'is-layout-flow',
                'baseStyles' => [
                    [
                        'selector' => ' > .alignleft',
                        'rules' => [
                            'float' => 'left',
                            'margin-inline-start' => '0',
                            'margin-inline-end' => '2em',
                        ],
                    ],
                    [
                        'selector' => ' > .alignright',
                        'rules' => [
                            'float' => 'right',
                            'margin-inline-start' => '2em',
                            'margin-inline-end' => '0',
                        ],
                    ],
                    [
                        'selector' => ' > .aligncenter',
                        'rules' => [
                            'margin-left' => 'auto !important',
                            'margin-right' => 'auto !important',
                        ],
                    ],
                ],
                'spacingStyles' => [
                    [
                        'selector' => ' > :first-child',
                        'rules' => [
                            'margin-block-start' => '0',
                        ],
                    ],
                    [
                        'selector' => ' > :last-child',
                        'rules' => [
                            'margin-block-end' => '0',
                        ],
                    ],
                    [
                        'selector' => ' > *',
                        'rules' => [
                            'margin-block-start' => null,
                            'margin-block-end' => '0',
                        ],
                    ],
                ],
            ],
            'constrained' => [
                'name' => 'constrained',
                'slug' => 'constrained',
                'className' => 'is-layout-constrained',
                'baseStyles' => [
                    [
                        'selector' => ' > .alignleft',
                        'rules' => [
                            'float' => 'left',
                            'margin-inline-start' => '0',
                            'margin-inline-end' => '2em',
                        ],
                    ],
                    [
                        'selector' => ' > .alignright',
                        'rules' => [
                            'float' => 'right',
                            'margin-inline-start' => '2em',
                            'margin-inline-end' => '0',
                        ],
                    ],
                    [
                        'selector' => ' > .aligncenter',
                        'rules' => [
                            'margin-left' => 'auto !important',
                            'margin-right' => 'auto !important',
                        ],
                    ],
                    [
                        'selector' => ' > :where(:not(.alignleft):not(.alignright):not(.alignfull))',
                        'rules' => [
                            'max-width' => 'var(--wp--style--global--content-size)',
                            'margin-left' => 'auto !important',
                            'margin-right' => 'auto !important',
                        ],
                    ],
                    [
                        'selector' => ' > .alignwide',
                        'rules' => [
                            'max-width' => 'var(--wp--style--global--wide-size)',
                        ],
                    ],
                ],
                'spacingStyles' => [
                    [
                        'selector' => ' > :first-child',
                        'rules' => [
                            'margin-block-start' => '0',
                        ],
                    ],
                    [
                        'selector' => ' > :last-child',
                        'rules' => [
                            'margin-block-end' => '0',
                        ],
                    ],
                    [
                        'selector' => ' > *',
                        'rules' => [
                            'margin-block-start' => null,
                            'margin-block-end' => '0',
                        ],
                    ],
                ],
            ],
            'flex' => [
                'name' => 'flex',
                'slug' => 'flex',
                'className' => 'is-layout-flex',
                'displayMode' => 'flex',
                'baseStyles' => [
                    [
                        'selector' => '',
                        'rules' => [
                            'flex-wrap' => 'wrap',
                            'align-items' => 'center',
                        ],
                    ],
                    [
                        // :is(*, div) instead of just * increases the specificity by 001.
                        'selector' => ' > :is(*, div)',
                        'rules' => [
                            'margin' => '0',
                        ],
                    ],
                ],
                'spacingStyles' => [
                    [
                        'selector' => '',
                        'rules' => [
                            'gap' => null,
                        ],
                    ],
                ],
            ],
            'grid' => [
                'name' => 'grid',
                'slug' => 'grid',
                'className' => 'is-layout-grid',
                'displayMode' => 'grid',
                'baseStyles' => [
                    [
                        // :is(*, div) instead of just * increases the specificity by 001.
                        'selector' => ' > :is(*, div)',
                        'rules' => [
                            'margin' => '0',
                        ],
                    ],
                ],
                'spacingStyles' => [
                    [
                        'selector' => '',
                        'rules' => [
                            'gap' => null,
                        ],
                    ],
                ],
            ],
        ];
    }
}

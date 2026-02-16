<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    */

    'use_package_routes' => true,
    'middlewares' => ['web', 'auth'],
    'prefix' => 'laraberg',

    /*
    |--------------------------------------------------------------------------
    | Embed settings
    |--------------------------------------------------------------------------
    */

    'embed' => [
        'maxwidth' => 1200,
        'maxheight' => 1200,

        'cache' => [
            'enabled' => true,
            'duration' => 86400,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Theme Color
    |--------------------------------------------------------------------------
    |
    | The primary accent color used by the editor (buttons, focus rings, etc).
    | Maps to --wp-admin-theme-color CSS custom property.
    |
    */

    'admin_color' => '#007cba',

];

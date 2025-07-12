<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the routes for the env-profile-manager package
    |
    */
    'route_prefix' => 'env-profile-manager',
    'api_prefix' => 'api/env-profile-manager',

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Configure middleware for web and API routes
    |
    */
    'middleware' => ['web', 'auth'],
    'api_middleware' => ['api', 'auth:sanctum'],

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | The layout to extend for the package views.
    | Set to null to use the package's default layout.
    | Example: 'layouts.app' to use your application's layout.
    | Default: 'env-profile-manager::layouts.default'.
    |
    */
    'layout' => 'env-profile-manager::layouts.default',

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    |
    | Configure backup settings for .env files
    |
    */
    'max_backups' => 10,

    /*
    |--------------------------------------------------------------------------
    | Enable Features
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features
    |
    */
    'features' => [
        'api' => true,
        'web_ui' => true,
        'backups' => true,
    ],
];

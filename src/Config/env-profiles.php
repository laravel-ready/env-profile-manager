<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the routes for the env-profiles package
    |
    */
    'route_prefix' => 'env-profiles',
    'api_prefix' => 'api/env-profiles',
    
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
    | Set to null to use a standalone page without extending any layout.
    |
    */
    'layout' => null,
    
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
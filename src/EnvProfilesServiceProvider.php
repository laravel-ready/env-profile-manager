<?php

namespace LaravelReady\EnvProfiles;

use Illuminate\Support\ServiceProvider;
use LaravelReady\EnvProfiles\Services\EnvFileService;

class EnvProfilesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/Config/env-profile-manager.php',
            'env-profile-manager'
        );

        $this->app->singleton(EnvFileService::class, function ($app) {
            return new EnvFileService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (config('env-profile-manager.features.web_ui', true)) {
            $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        }

        if (config('env-profile-manager.features.api', true)) {
            $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
        }

        $this->loadViewsFrom(__DIR__ . '/resources/views', 'env-profile-manager');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/Config/env-profile-manager.php' => config_path('env-profile-manager.php'),
            ], 'env-profile-manager-config');

            $this->publishes([
                __DIR__ . '/resources/views' => resource_path('views/vendor/env-profile-manager'),
            ], 'env-profile-manager-views');

            $this->publishes([
                __DIR__ . '/resources/js' => public_path('vendor/env-profile-manager/js'),
            ], 'env-profile-manager-assets');

            $this->publishes([
                __DIR__ . '/database/migrations' => database_path('migrations'),
            ], 'env-profile-manager-migrations');
        }

        $this->commands([
            Commands\PublishCommand::class,
        ]);
    }
}

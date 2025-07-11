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
            __DIR__.'/Config/env-profiles.php', 'env-profiles'
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
        if (config('env-profiles.features.web_ui', true)) {
            $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        }
        
        if (config('env-profiles.features.api', true)) {
            $this->loadRoutesFrom(__DIR__.'/routes/api.php');
        }
        
        $this->loadViewsFrom(__DIR__.'/resources/views', 'env-profiles');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/Config/env-profiles.php' => config_path('env-profiles.php'),
            ], 'env-profiles-config');

            $this->publishes([
                __DIR__.'/resources/views' => resource_path('views/vendor/env-profiles'),
            ], 'env-profiles-views');

            $this->publishes([
                __DIR__.'/resources/js' => public_path('vendor/env-profiles/js'),
            ], 'env-profiles-assets');

            $this->publishes([
                __DIR__.'/database/migrations' => database_path('migrations'),
            ], 'env-profiles-migrations');
        }

        $this->commands([
            Commands\PublishCommand::class,
        ]);
    }
}
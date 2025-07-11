<?php

namespace LaravelReady\EnvProfiles\Tests;

use LaravelReady\EnvProfiles\EnvProfilesServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Illuminate\Database\Eloquent\Factories\Factory;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'LaravelReady\\EnvProfiles\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            EnvProfilesServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('app.key', 'base64:'.base64_encode('32characterssecretkeyfortesting!'));
        
        // Set default package config
        config()->set('env-profiles.route_prefix', 'env-profiles');
        config()->set('env-profiles.api_prefix', 'api/env-profiles');
        config()->set('env-profiles.middleware', ['web']);
        config()->set('env-profiles.api_middleware', ['api']);
        config()->set('env-profiles.layout', null);
        config()->set('env-profiles.max_backups', 10);
        config()->set('env-profiles.features', [
            'api' => true,
            'web_ui' => true,
            'backups' => true,
        ]);
    }
    
    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../src/database/migrations');
    }

    protected function createTestEnvFile($content = null)
    {
        $envPath = base_path('.env');
        $testContent = $content ?: "APP_NAME=TestApp\nAPP_ENV=testing\nAPP_KEY=base64:test\nDB_CONNECTION=sqlite\nDB_DATABASE=:memory:";
        
        file_put_contents($envPath, $testContent);
        
        return $envPath;
    }

    protected function deleteTestEnvFile()
    {
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            unlink($envPath);
        }
    }
}
<?php

namespace LaravelReady\EnvProfiles\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use LaravelReady\EnvProfiles\EnvProfilesServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'LaravelReady\\EnvProfiles\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
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
        config()->set('app.key', 'base64:' . base64_encode('32characterssecretkeyfortesting!'));

        // Set default package config
        config()->set('env-profile-manager.route_prefix', 'env-profile-manager');
        config()->set('env-profile-manager.api_prefix', 'api/env-profile-manager');
        config()->set('env-profile-manager.middleware', ['web']);
        config()->set('env-profile-manager.api_middleware', ['api']);
        config()->set('env-profile-manager.layout', null);
        config()->set('env-profile-manager.max_backups', 10);
        config()->set('env-profile-manager.features', [
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

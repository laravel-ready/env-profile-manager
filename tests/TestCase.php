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

        $migration = include __DIR__.'/../src/database/migrations/2025_01_11_000000_create_env_profiles_table.php';
        $migration->up();
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
<?php

use Illuminate\Support\Facades\File;

it('executes the publish command successfully', function () {
    $this->artisan('env-profiles:publish')
        ->expectsOutput('Publishing Env Profiles resources...')
        ->expectsOutput('✅ Published configuration')
        ->expectsOutput('✅ Published assets') 
        ->expectsOutput('✅ Published migrations')
        ->expectsOutput('🎉 Env Profiles resources published successfully!')
        ->expectsOutput('Next steps:')
        ->expectsOutput('1. Run: php artisan migrate')
        ->assertSuccessful();
});

it('publishes all resources', function () {
    // Clean up before test
    File::deleteDirectory(public_path('vendor/env-profiles'));
    File::delete(config_path('env-profiles.php'));
    File::delete(database_path('migrations/2025_01_11_000000_create_env_profiles_table.php'));
    
    $this->artisan('env-profiles:publish')->assertSuccessful();
    
    // Check config was published
    expect(File::exists(config_path('env-profiles.php')))->toBeTrue();
    
    // Check assets were published
    expect(File::exists(public_path('vendor/env-profiles/js/app.js')))->toBeTrue();
    
    // Check migration was published
    $migrationFiles = File::glob(database_path('migrations/*_create_env_profiles_table.php'));
    expect($migrationFiles)->not->toBeEmpty();
    
    // Clean up after test
    File::deleteDirectory(public_path('vendor/env-profiles'));
    File::delete(config_path('env-profiles.php'));
    foreach ($migrationFiles as $file) {
        File::delete($file);
    }
});

it('handles when resources are already published', function () {
    // First publish
    $this->artisan('env-profiles:publish')->assertSuccessful();
    
    // Second publish should still succeed
    $this->artisan('env-profiles:publish')
        ->expectsOutput('Publishing Env Profiles resources...')
        ->assertSuccessful();
    
    // Clean up
    File::deleteDirectory(public_path('vendor/env-profiles'));
    File::delete(config_path('env-profiles.php'));
    $migrationFiles = File::glob(database_path('migrations/*_create_env_profiles_table.php'));
    foreach ($migrationFiles as $file) {
        File::delete($file);
    }
});

it('creates the correct directory structure for assets', function () {
    File::deleteDirectory(public_path('vendor/env-profiles'));
    
    $this->artisan('env-profiles:publish')->assertSuccessful();
    
    expect(File::isDirectory(public_path('vendor/env-profiles')))->toBeTrue()
        ->and(File::isDirectory(public_path('vendor/env-profiles/js')))->toBeTrue();
    
    // Clean up
    File::deleteDirectory(public_path('vendor/env-profiles'));
});
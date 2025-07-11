<?php

namespace LaravelReady\EnvProfiles\Commands;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    protected $signature = 'env-profiles:publish {--force : Overwrite any existing files}';

    protected $description = 'Publish all EnvProfiles resources';

    public function handle()
    {
        $this->info('Publishing EnvProfiles resources...');

        $params = [
            '--provider' => 'LaravelReady\EnvProfiles\EnvProfilesServiceProvider',
        ];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', array_merge($params, [
            '--tag' => 'env-profiles-config',
        ]));

        $this->call('vendor:publish', array_merge($params, [
            '--tag' => 'env-profiles-views',
        ]));

        $this->call('vendor:publish', array_merge($params, [
            '--tag' => 'env-profiles-assets',
        ]));

        $this->call('vendor:publish', array_merge($params, [
            '--tag' => 'env-profiles-migrations',
        ]));

        $this->info('EnvProfiles resources published successfully!');
        $this->line('');
        $this->line('Next steps:');
        $this->line('1. Run "php artisan migrate" to create the env_profiles table');
        $this->line('2. Visit /' . config('env-profiles.route_prefix', 'env-profiles') . ' to manage your environment profiles');
    }
}
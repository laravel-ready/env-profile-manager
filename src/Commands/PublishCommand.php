<?php

namespace LaravelReady\EnvProfiles\Commands;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    protected $signature = 'env-profile-manager:publish {--force : Overwrite any existing files}';

    protected $description = 'Publish all EnvProfiles resources';

    public function handle()
    {
        $this->info('Publishing Env Profiles resources...');

        $params = [
            '--provider' => 'LaravelReady\EnvProfiles\EnvProfilesServiceProvider',
        ];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', array_merge($params, [
            '--tag' => 'env-profile-manager-config',
        ]));
        $this->info('✅ Published configuration');

        $this->call('vendor:publish', array_merge($params, [
            '--tag' => 'env-profile-manager-views',
        ]));

        $this->call('vendor:publish', array_merge($params, [
            '--tag' => 'env-profile-manager-assets',
        ]));
        $this->info('✅ Published assets');

        $this->call('vendor:publish', array_merge($params, [
            '--tag' => 'env-profile-manager-migrations',
        ]));
        $this->info('✅ Published migrations');

        $this->info('🎉 Env Profiles resources published successfully!');
        $this->info('Next steps:');
        $this->info('1. Run: php artisan migrate');
        $this->line('2. Visit /' . config('env-profile-manager.route_prefix', 'env-profile-manager') . ' to manage your environment profiles');
    }
}
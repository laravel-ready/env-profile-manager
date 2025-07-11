<?php

namespace LaravelReady\EnvProfiles\Database\Factories;

use LaravelReady\EnvProfiles\Models\EnvProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnvProfileFactory extends Factory
{
    protected $model = EnvProfile::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->words(2, true) . ' Environment',
            'app_name' => $this->faker->optional(0.7)->company() . ' App',
            'content' => $this->generateEnvContent(),
            'is_active' => false,
        ];
    }

    /**
     * Indicate that the profile is active.
     */
    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
            ];
        });
    }

    /**
     * Indicate that the profile has no app name.
     */
    public function withoutAppName()
    {
        return $this->state(function (array $attributes) {
            return [
                'app_name' => null,
            ];
        });
    }

    /**
     * Generate realistic .env content
     */
    private function generateEnvContent()
    {
        $environment = $this->faker->randomElement(['local', 'staging', 'production']);
        $debug = $environment === 'production' ? 'false' : 'true';
        
        return implode("\n", [
            "APP_NAME=\"{$this->faker->company()}\"",
            "APP_ENV={$environment}",
            "APP_KEY=base64:" . base64_encode($this->faker->sha256()),
            "APP_DEBUG={$debug}",
            "APP_URL={$this->faker->url()}",
            "",
            "LOG_CHANNEL=stack",
            "LOG_DEPRECATIONS_CHANNEL=null",
            "LOG_LEVEL=debug",
            "",
            "DB_CONNECTION=mysql",
            "DB_HOST=127.0.0.1",
            "DB_PORT=3306",
            "DB_DATABASE={$this->faker->slug()}",
            "DB_USERNAME=root",
            "DB_PASSWORD=",
            "",
            "BROADCAST_DRIVER=log",
            "CACHE_DRIVER=file",
            "FILESYSTEM_DISK=local",
            "QUEUE_CONNECTION=sync",
            "SESSION_DRIVER=file",
            "SESSION_LIFETIME=120",
            "",
            "MAIL_MAILER=smtp",
            "MAIL_HOST=mailhog",
            "MAIL_PORT=1025",
            "MAIL_USERNAME=null",
            "MAIL_PASSWORD=null",
            "MAIL_ENCRYPTION=null",
            "MAIL_FROM_ADDRESS=\"{$this->faker->email()}\"",
            "MAIL_FROM_NAME=\"\${APP_NAME}\"",
        ]);
    }
}
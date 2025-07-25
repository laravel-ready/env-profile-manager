<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelReady\EnvProfiles\Models\EnvProfile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->envContent = "APP_NAME=Laravel\nAPP_ENV=local\nAPP_KEY=base64:key";
    $this->createTestEnvFile($this->envContent);

    config([
        'env-profile-manager.api_prefix' => 'api/env-profile-manager',
        'env-profile-manager.api_middleware' => ['api'],
        'env-profile-manager.features.api' => true,
    ]);
});

afterEach(function () {
    $this->deleteTestEnvFile();
});

describe('API Index', function () {
    it('returns all profiles and current env content', function () {
        $profiles = collect([
            ['name' => 'Production', 'app_name' => 'Prod App', 'is_active' => true],
            ['name' => 'Staging', 'app_name' => 'Stage App', 'is_active' => false],
        ])->each(fn ($data) => EnvProfile::create([
            ...$data,
            'content' => $this->envContent,
        ]));

        $response = $this->getJson('/api/env-profile-manager');

        $response->assertOk()
            ->assertJsonStructure([
                'profiles' => [
                    '*' => ['id', 'name', 'app_name', 'content', 'is_active', 'created_at', 'updated_at'],
                ],
                'current_env',
                'app_name',
            ])
            ->assertJsonCount(2, 'profiles')
            ->assertJson([
                'current_env' => $this->envContent,
                'app_name' => config('app.name'),
            ]);
    });

    it('returns empty profiles array when none exist', function () {
        $response = $this->getJson('/api/env-profile-manager');

        $response->assertOk()
            ->assertJsonCount(0, 'profiles');
    });

    it('is disabled when api feature is off', function () {
        $this->markTestSkipped('This test requires application restart which is not supported in the current test environment');
    });
});

describe('API Store', function () {
    it('can create a new profile', function () {
        $data = [
            'name' => 'API Profile',
            'app_name' => 'API App',
            'content' => 'API_ENV=true',
            'is_active' => false,
        ];

        $response = $this->postJson('/api/env-profile-manager', $data);

        $response->assertCreated()
            ->assertJson([
                'message' => 'Profile created successfully',
                'profile' => [
                    'name' => 'API Profile',
                    'app_name' => 'API App',
                    'content' => 'API_ENV=true',
                    'is_active' => false,
                ],
            ]);

        $this->assertDatabaseHas('env_profiles', [
            'name' => 'API Profile',
            'app_name' => 'API App',
        ]);
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/env-profile-manager', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'content']);
    });

    it('validates unique profile name', function () {
        EnvProfile::create([
            'name' => 'Existing',
            'content' => $this->envContent,
        ]);

        $response = $this->postJson('/api/env-profile-manager', [
            'name' => 'Existing',
            'content' => 'NEW_CONTENT',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('accepts profile without app_name', function () {
        $response = $this->postJson('/api/env-profile-manager', [
            'name' => 'No App Name',
            'content' => 'TEST=true',
        ]);

        $response->assertCreated();

        $profile = EnvProfile::where('name', 'No App Name')->first();
        expect($profile->app_name)->toBeNull();
    });
});

describe('API Show', function () {
    it('returns a specific profile', function () {
        $profile = EnvProfile::create([
            'name' => 'Test Profile',
            'app_name' => 'Test App',
            'content' => $this->envContent,
        ]);

        $response = $this->getJson("/api/env-profile-manager/{$profile->id}");

        $response->assertOk()
            ->assertJson([
                'id' => $profile->id,
                'name' => 'Test Profile',
                'app_name' => 'Test App',
                'content' => $this->envContent,
            ]);
    });

    it('returns 404 for non-existent profile', function () {
        $response = $this->getJson('/api/env-profile-manager/999');

        $response->assertNotFound();
    });
});

describe('API Update', function () {
    it('can update a profile', function () {
        $profile = EnvProfile::create([
            'name' => 'Original',
            'app_name' => 'Original App',
            'content' => $this->envContent,
        ]);

        $response = $this->putJson("/api/env-profile-manager/{$profile->id}", [
            'name' => 'Updated',
            'app_name' => 'Updated App',
            'content' => 'UPDATED=true',
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Profile updated successfully',
                'profile' => [
                    'name' => 'Updated',
                    'app_name' => 'Updated App',
                    'content' => 'UPDATED=true',
                ],
            ]);

        $profile->refresh();
        expect($profile->name)->toBe('Updated');
    });

    it('validates update data', function () {
        $profile = EnvProfile::create([
            'name' => 'Test',
            'content' => $this->envContent,
        ]);

        $response = $this->putJson("/api/env-profile-manager/{$profile->id}", [
            'name' => '',
            'content' => '',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'content']);
    });

    it('allows partial update with app_name', function () {
        $profile = EnvProfile::create([
            'name' => 'Test',
            'content' => $this->envContent,
        ]);

        $response = $this->putJson("/api/env-profile-manager/{$profile->id}", [
            'name' => 'Test',
            'app_name' => 'New App Name',
            'content' => $this->envContent,
        ]);

        $response->assertOk();
        expect($profile->fresh()->app_name)->toBe('New App Name');
    });
});

describe('API Delete', function () {
    it('can delete a profile', function () {
        $profile = EnvProfile::create([
            'name' => 'To Delete',
            'content' => $this->envContent,
        ]);

        $response = $this->deleteJson("/api/env-profile-manager/{$profile->id}");

        $response->assertOk()
            ->assertJson([
                'message' => 'Profile deleted successfully',
            ]);

        $this->assertDatabaseMissing('env_profiles', ['id' => $profile->id]);
    });

    it('returns 404 when deleting non-existent profile', function () {
        $response = $this->deleteJson('/api/env-profile-manager/999');

        $response->assertNotFound();
    });
});

describe('API Activate', function () {
    it('can activate a profile', function () {
        $profile = EnvProfile::create([
            'name' => 'To Activate',
            'content' => 'ACTIVATED=true',
        ]);

        $response = $this->postJson("/api/env-profile-manager/{$profile->id}/activate");

        $response->assertOk()
            ->assertJson([
                'message' => 'Profile activated and applied successfully',
                'profile' => [
                    'id' => $profile->id,
                    'is_active' => true,
                ],
            ]);

        expect($profile->fresh()->is_active)->toBeTrue()
            ->and(file_get_contents(base_path('.env')))->toBe('ACTIVATED=true');
    });
});

describe('API Current Env', function () {
    it('returns current env content', function () {
        $response = $this->getJson('/api/env-profile-manager/current-env');

        $response->assertOk()
            ->assertJson([
                'content' => $this->envContent,
            ]);
    });

    it('can update current env content', function () {
        $newContent = 'API_UPDATED=true';

        $response = $this->putJson('/api/env-profile-manager/current-env', [
            'content' => $newContent,
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => '.env file updated successfully',
            ]);

        expect(file_get_contents(base_path('.env')))->toBe($newContent);
    });

    it('validates content for env update', function () {
        $response = $this->putJson('/api/env-profile-manager/current-env', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    });
});

describe('API Middleware', function () {
    it('applies configured API middleware', function () {
        $this->markTestSkipped('This test requires application restart which is not supported in the current test environment');
    });

    it('respects custom API prefix', function () {
        $this->markTestSkipped('This test requires application restart which is not supported in the current test environment');
    });
});

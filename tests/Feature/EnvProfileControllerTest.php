<?php

use LaravelReady\EnvProfiles\Models\EnvProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->envContent = "APP_NAME=Laravel\nAPP_ENV=local\nAPP_KEY=base64:key";
    $this->createTestEnvFile($this->envContent);
    
    // Setup default config
    config([
        'env-profiles.route_prefix' => 'env-profiles',
        'env-profiles.middleware' => ['web'],
        'env-profiles.layout' => null,
    ]);
});

afterEach(function () {
    $this->deleteTestEnvFile();
});

describe('Web Routes', function () {
    it('can access the index page', function () {
        $response = $this->get('/env-profiles');
        
        $response->assertOk()
            ->assertViewIs('env-profiles::index')
            ->assertViewHas('profiles')
            ->assertViewHas('currentEnv', $this->envContent)
            ->assertViewHas('appName', config('app.name'));
    });
    
    it('passes profiles to the view', function () {
        $profiles = collect([
            ['name' => 'Production', 'app_name' => 'Prod App'],
            ['name' => 'Staging', 'app_name' => 'Stage App'],
        ])->each(fn ($data) => EnvProfile::create([
            ...$data,
            'content' => $this->envContent
        ]));
        
        $response = $this->get('/env-profiles');
        
        $viewProfiles = $response->viewData('profiles');
        expect($viewProfiles)->toHaveCount(2);
    });
    
    it('respects custom route prefix', function () {
        config(['env-profiles.route_prefix' => 'custom-env']);
        
        $this->get('/custom-env')->assertOk();
        $this->get('/env-profiles')->assertNotFound();
    });
    
    it('applies configured middleware', function () {
        config(['env-profiles.middleware' => ['web', 'auth']]);
        
        $response = $this->get('/env-profiles');
        
        // Should redirect to login when auth middleware is applied
        $response->assertRedirect();
    });
});

describe('Store Profile', function () {
    it('can create a new profile via web', function () {
        $response = $this->post('/env-profiles', [
            'name' => 'New Profile',
            'app_name' => 'New App',
            'content' => 'NEW_ENV=test',
            'is_active' => false,
        ]);
        
        $response->assertRedirect()
            ->assertSessionHas('success', 'Profile created successfully');
        
        $this->assertDatabaseHas('env_profiles', [
            'name' => 'New Profile',
            'app_name' => 'New App',
            'content' => 'NEW_ENV=test',
        ]);
    });
    
    it('validates required fields', function () {
        $response = $this->post('/env-profiles', []);
        
        $response->assertSessionHasErrors(['name', 'content']);
    });
    
    it('validates unique profile name', function () {
        EnvProfile::create([
            'name' => 'Existing',
            'content' => $this->envContent,
        ]);
        
        $response = $this->post('/env-profiles', [
            'name' => 'Existing',
            'content' => 'NEW_CONTENT=test',
        ]);
        
        $response->assertSessionHasErrors(['name']);
    });
});

describe('Update Profile', function () {
    it('can update an existing profile', function () {
        $profile = EnvProfile::create([
            'name' => 'Original',
            'app_name' => 'Original App',
            'content' => $this->envContent,
        ]);
        
        $response = $this->put("/env-profiles/{$profile->id}", [
            'name' => 'Updated',
            'app_name' => 'Updated App',
            'content' => 'UPDATED=true',
        ]);
        
        $response->assertRedirect()
            ->assertSessionHas('success', 'Profile updated successfully');
        
        $profile->refresh();
        expect($profile->name)->toBe('Updated')
            ->and($profile->app_name)->toBe('Updated App')
            ->and($profile->content)->toBe('UPDATED=true');
    });
    
    it('validates unique name excludes current profile', function () {
        $profile1 = EnvProfile::create([
            'name' => 'Profile 1',
            'content' => $this->envContent,
        ]);
        
        $profile2 = EnvProfile::create([
            'name' => 'Profile 2',
            'content' => $this->envContent,
        ]);
        
        // Should allow keeping the same name
        $response = $this->put("/env-profiles/{$profile1->id}", [
            'name' => 'Profile 1',
            'content' => 'UPDATED=true',
        ]);
        
        $response->assertRedirect()->assertSessionDoesntHaveErrors();
        
        // Should not allow using another profile's name
        $response = $this->put("/env-profiles/{$profile1->id}", [
            'name' => 'Profile 2',
            'content' => 'UPDATED=true',
        ]);
        
        $response->assertSessionHasErrors(['name']);
    });
});

describe('Delete Profile', function () {
    it('can delete a profile', function () {
        $profile = EnvProfile::create([
            'name' => 'To Delete',
            'content' => $this->envContent,
        ]);
        
        $response = $this->delete("/env-profiles/{$profile->id}");
        
        $response->assertRedirect()
            ->assertSessionHas('success', 'Profile deleted successfully');
        
        $this->assertDatabaseMissing('env_profiles', ['id' => $profile->id]);
    });
});

describe('Activate Profile', function () {
    it('can activate a profile and update env file', function () {
        $profile = EnvProfile::create([
            'name' => 'To Activate',
            'content' => 'ACTIVATED=true',
        ]);
        
        $response = $this->post("/env-profiles/{$profile->id}/activate");
        
        $response->assertRedirect()
            ->assertSessionHas('success', 'Profile activated and applied successfully');
        
        $profile->refresh();
        expect($profile->is_active)->toBeTrue()
            ->and(file_get_contents(base_path('.env')))->toBe('ACTIVATED=true');
    });
    
    it('deactivates other profiles when activating one', function () {
        $activeProfile = EnvProfile::create([
            'name' => 'Currently Active',
            'content' => $this->envContent,
            'is_active' => true,
        ]);
        
        $newProfile = EnvProfile::create([
            'name' => 'To Activate',
            'content' => 'NEW_ACTIVE=true',
        ]);
        
        $this->post("/env-profiles/{$newProfile->id}/activate");
        
        $activeProfile->refresh();
        $newProfile->refresh();
        
        expect($activeProfile->is_active)->toBeFalse()
            ->and($newProfile->is_active)->toBeTrue();
    });
});

describe('Current Env Management', function () {
    it('can get current env content', function () {
        $response = $this->get('/env-profiles/current-env');
        
        $response->assertOk()
            ->assertJson(['content' => $this->envContent]);
    });
    
    it('can update current env content', function () {
        $newContent = 'UPDATED_ENV=true';
        
        $response = $this->put('/env-profiles/current-env', [
            'content' => $newContent,
        ]);
        
        $response->assertOk()
            ->assertJson(['message' => '.env file updated successfully']);
        
        expect(file_get_contents(base_path('.env')))->toBe($newContent);
    });
    
    it('validates content is required for env update', function () {
        $response = $this->put('/env-profiles/current-env', []);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    });
});
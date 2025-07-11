<?php

use LaravelReady\EnvProfiles\Models\EnvProfile;

beforeEach(function () {
    $this->envContent = "APP_NAME=Laravel\nAPP_ENV=local\nAPP_KEY=base64:key\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1";
});

it('can create an env profile', function () {
    $profile = EnvProfile::create([
        'name' => 'Test Profile',
        'app_name' => 'Test App',
        'content' => $this->envContent,
    ]);

    expect($profile)->toBeInstanceOf(EnvProfile::class)
        ->and($profile->name)->toBe('Test Profile')
        ->and($profile->app_name)->toBe('Test App')
        ->and($profile->content)->toBe($this->envContent)
        ->and($profile->is_active)->toBeFalse();
});

it('can create a profile without app_name', function () {
    $profile = EnvProfile::create([
        'name' => 'Test Profile',
        'content' => $this->envContent,
    ]);

    expect($profile->app_name)->toBeNull();
});

it('can activate a profile', function () {
    $profile1 = EnvProfile::create([
        'name' => 'Profile 1',
        'content' => $this->envContent,
    ]);
    
    $profile2 = EnvProfile::create([
        'name' => 'Profile 2',
        'content' => $this->envContent,
    ]);

    $profile2->activate();

    expect($profile2->fresh()->is_active)->toBeTrue()
        ->and($profile1->fresh()->is_active)->toBeFalse();
});

it('deactivates other profiles when activating one', function () {
    $profiles = collect(range(1, 3))->map(fn ($i) => 
        EnvProfile::create([
            'name' => "Profile {$i}",
            'content' => $this->envContent,
            'is_active' => true,
        ])
    );

    $newProfile = EnvProfile::create([
        'name' => 'New Profile',
        'content' => $this->envContent,
    ]);

    $newProfile->activate();

    $profiles->each(fn ($profile) => 
        expect($profile->fresh()->is_active)->toBeFalse()
    );
    
    expect($newProfile->fresh()->is_active)->toBeTrue();
});

it('can deactivate a profile', function () {
    $profile = EnvProfile::create([
        'name' => 'Test Profile',
        'content' => $this->envContent,
        'is_active' => true,
    ]);

    $profile->deactivate();

    expect($profile->fresh()->is_active)->toBeFalse();
});

it('can get active profile using scope', function () {
    EnvProfile::create([
        'name' => 'Inactive Profile',
        'content' => $this->envContent,
        'is_active' => false,
    ]);

    $activeProfile = EnvProfile::create([
        'name' => 'Active Profile',
        'content' => $this->envContent,
        'is_active' => true,
    ]);

    $result = EnvProfile::active()->first();

    expect($result->id)->toBe($activeProfile->id);
});

it('can parse env content as array', function () {
    $content = "APP_NAME=Laravel\nAPP_ENV=local\n# This is a comment\nDB_CONNECTION=mysql\nEMPTY_VALUE=\nQUOTED_VALUE=\"quoted value\"\nSINGLE_QUOTED='single quoted'";
    
    $profile = EnvProfile::create([
        'name' => 'Test Profile',
        'content' => $content,
    ]);

    $array = $profile->getContentAsArray();

    expect($array)->toBe([
        'APP_NAME' => 'Laravel',
        'APP_ENV' => 'local',
        'DB_CONNECTION' => 'mysql',
        'EMPTY_VALUE' => '',
        'QUOTED_VALUE' => 'quoted value',
        'SINGLE_QUOTED' => 'single quoted',
    ]);
});

it('ignores comments and empty lines when parsing', function () {
    $content = "# Comment line\n\nAPP_NAME=Laravel\n\n# Another comment\nAPP_ENV=local\n\n";
    
    $profile = EnvProfile::create([
        'name' => 'Test Profile',
        'content' => $content,
    ]);

    $array = $profile->getContentAsArray();

    expect($array)->toHaveCount(2)
        ->and($array)->toHaveKey('APP_NAME')
        ->and($array)->toHaveKey('APP_ENV');
});

it('handles malformed env lines gracefully', function () {
    $content = "VALID_KEY=value\nINVALID_LINE_WITHOUT_EQUALS\nANOTHER_VALID=test";
    
    $profile = EnvProfile::create([
        'name' => 'Test Profile',
        'content' => $content,
    ]);

    $array = $profile->getContentAsArray();

    expect($array)->toBe([
        'VALID_KEY' => 'value',
        'ANOTHER_VALID' => 'test',
    ]);
});

it('enforces unique profile names', function () {
    EnvProfile::create([
        'name' => 'Unique Name',
        'content' => $this->envContent,
    ]);

    expect(fn () => EnvProfile::create([
        'name' => 'Unique Name',
        'content' => $this->envContent,
    ]))->toThrow(Exception::class);
});

it('casts is_active to boolean', function () {
    $profile = EnvProfile::create([
        'name' => 'Test Profile',
        'content' => $this->envContent,
        'is_active' => 1,
    ]);

    expect($profile->is_active)->toBeBool()
        ->and($profile->is_active)->toBeTrue();
});

it('can update profile content', function () {
    $profile = EnvProfile::create([
        'name' => 'Test Profile',
        'content' => $this->envContent,
    ]);

    $newContent = "APP_NAME=UpdatedApp\nAPP_ENV=production";
    $profile->update(['content' => $newContent]);

    expect($profile->fresh()->content)->toBe($newContent);
});

it('can update profile app_name', function () {
    $profile = EnvProfile::create([
        'name' => 'Test Profile',
        'app_name' => 'Original App',
        'content' => $this->envContent,
    ]);

    $profile->update(['app_name' => 'Updated App']);

    expect($profile->fresh()->app_name)->toBe('Updated App');
});

it('can delete a profile', function () {
    $profile = EnvProfile::create([
        'name' => 'Test Profile',
        'content' => $this->envContent,
    ]);

    $profileId = $profile->id;
    $profile->delete();

    expect(EnvProfile::find($profileId))->toBeNull();
});
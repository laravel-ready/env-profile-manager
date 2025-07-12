<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use LaravelReady\EnvProfiles\Http\Requests\StoreEnvProfileRequest;
use LaravelReady\EnvProfiles\Http\Requests\UpdateEnvProfileRequest;
use LaravelReady\EnvProfiles\Models\EnvProfile;

uses(RefreshDatabase::class);

describe('StoreEnvProfileRequest', function () {
    beforeEach(function () {
        $this->request = new StoreEnvProfileRequest();
    });

    it('authorizes any user', function () {
        expect($this->request->authorize())->toBeTrue();
    });

    it('has correct validation rules', function () {
        $rules = $this->request->rules();

        expect($rules)->toHaveKeys(['name', 'app_name', 'content', 'is_active'])
            ->and($rules['name'])->toBe('required|string|max:255|unique:env_profiles,name')
            ->and($rules['app_name'])->toBe('nullable|string|max:255')
            ->and($rules['content'])->toBe('required|string')
            ->and($rules['is_active'])->toBe('boolean');
    });

    it('validates required fields', function () {
        $validator = Validator::make([], $this->request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue()
            ->and($validator->errors()->has('content'))->toBeTrue()
            ->and($validator->errors()->has('app_name'))->toBeFalse()
            ->and($validator->errors()->has('is_active'))->toBeFalse();
    });

    it('validates name uniqueness', function () {
        EnvProfile::create([
            'name' => 'Existing Profile',
            'content' => 'TEST=true',
        ]);

        $validator = Validator::make([
            'name' => 'Existing Profile',
            'content' => 'NEW=true',
        ], $this->request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    it('validates name max length', function () {
        $validator = Validator::make([
            'name' => str_repeat('a', 256),
            'content' => 'TEST=true',
        ], $this->request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    it('validates app_name max length', function () {
        $validator = Validator::make([
            'name' => 'Test',
            'app_name' => str_repeat('a', 256),
            'content' => 'TEST=true',
        ], $this->request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('app_name'))->toBeTrue();
    });

    it('accepts valid data', function () {
        $validator = Validator::make([
            'name' => 'Valid Profile',
            'app_name' => 'Valid App',
            'content' => 'VALID=true',
            'is_active' => true,
        ], $this->request->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('accepts data without optional fields', function () {
        $validator = Validator::make([
            'name' => 'Valid Profile',
            'content' => 'VALID=true',
        ], $this->request->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('has custom error messages', function () {
        $messages = $this->request->messages();

        expect($messages)->toHaveKeys(['name.required', 'name.unique', 'content.required'])
            ->and($messages['name.required'])->toBe('Profile name is required.')
            ->and($messages['name.unique'])->toBe('A profile with this name already exists.')
            ->and($messages['content.required'])->toBe('Profile content is required.');
    });
});

describe('UpdateEnvProfileRequest', function () {
    beforeEach(function () {
        $this->profile = EnvProfile::create([
            'name' => 'Existing Profile',
            'content' => 'EXISTING=true',
        ]);

        $this->request = new UpdateEnvProfileRequest();
        $this->request->setRouteResolver(function () {
            return new class ($this->profile) {
                private $profile;

                public function __construct($profile)
                {
                    $this->profile = $profile;
                }

                public function parameter($name)
                {
                    return $name === 'profile' ? $this->profile : null;
                }
            };
        });
    });

    it('authorizes any user', function () {
        expect($this->request->authorize())->toBeTrue();
    });

    it('has correct validation rules', function () {
        $rules = $this->request->rules();

        expect($rules)->toHaveKeys(['name', 'app_name', 'content', 'is_active'])
            ->and($rules['name'])->toBeArray()
            ->and($rules['app_name'])->toBe('nullable|string|max:255')
            ->and($rules['content'])->toBe('required|string')
            ->and($rules['is_active'])->toBe('boolean');
    });

    it('allows updating with same name', function () {
        $validator = Validator::make([
            'name' => 'Existing Profile',
            'content' => 'UPDATED=true',
        ], $this->request->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('prevents using another profiles name', function () {
        EnvProfile::create([
            'name' => 'Another Profile',
            'content' => 'TEST=true',
        ]);

        $validator = Validator::make([
            'name' => 'Another Profile',
            'content' => 'UPDATED=true',
        ], $this->request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    it('validates required fields', function () {
        $validator = Validator::make([], $this->request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue()
            ->and($validator->errors()->has('content'))->toBeTrue();
    });

    it('has same custom error messages as store request', function () {
        $messages = $this->request->messages();

        expect($messages)->toHaveKeys(['name.required', 'name.unique', 'content.required'])
            ->and($messages['name.required'])->toBe('Profile name is required.')
            ->and($messages['name.unique'])->toBe('A profile with this name already exists.')
            ->and($messages['content.required'])->toBe('Profile content is required.');
    });

    it('accepts valid update data', function () {
        $validator = Validator::make([
            'name' => 'Updated Name',
            'app_name' => 'Updated App',
            'content' => 'UPDATED=true',
            'is_active' => false,
        ], $this->request->rules());

        expect($validator->passes())->toBeTrue();
    });
});

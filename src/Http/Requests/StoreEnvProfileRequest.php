<?php

namespace LaravelReady\EnvProfiles\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnvProfileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:env_profiles,name',
            'content' => 'required|string',
            'is_active' => 'boolean',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Profile name is required.',
            'name.unique' => 'A profile with this name already exists.',
            'content.required' => 'Profile content is required.',
        ];
    }
}
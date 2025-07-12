<?php

namespace LaravelReady\EnvProfiles\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnvProfileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('env_profiles', 'name')->ignore($this->route('profile')),
            ],
            'app_name' => 'nullable|string|max:255',
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

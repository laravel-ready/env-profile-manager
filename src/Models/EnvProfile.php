<?php

namespace LaravelReady\EnvProfiles\Models;

use Illuminate\Database\Eloquent\Model;

class EnvProfile extends Model
{
    protected $table = 'env_profiles';

    protected $fillable = [
        'name',
        'app_name',
        'content',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function activate()
    {
        self::where('is_active', true)->update(['is_active' => false]);
        
        $this->update(['is_active' => true]);
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    public function getContentAsArray()
    {
        $lines = explode("\n", $this->content);
        $envArray = [];

        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $envArray[trim($key)] = trim($value, '"\'');
            }
        }

        return $envArray;
    }
}
<?php

namespace LaravelReady\EnvProfiles\Services;

use Illuminate\Support\Facades\File;

class EnvFileService
{
    protected $envPath;

    public function __construct()
    {
        $this->envPath = base_path('.env');
    }

    public function read()
    {
        if (!File::exists($this->envPath)) {
            return '';
        }

        return File::get($this->envPath);
    }

    public function write($content)
    {
        $this->backup();
        
        File::put($this->envPath, $content);
        
        $this->clearConfigCache();
    }

    public function backup()
    {
        if (!File::exists($this->envPath)) {
            return;
        }

        $backupPath = $this->envPath . '.backup.' . date('Y-m-d-H-i-s');
        File::copy($this->envPath, $backupPath);
        
        $this->cleanOldBackups();
    }

    protected function cleanOldBackups()
    {
        $backupFiles = File::glob(base_path('.env.backup.*'));
        $maxBackups = config('env-profiles.max_backups', 10);
        
        if (count($backupFiles) > $maxBackups) {
            usort($backupFiles, function ($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            $filesToDelete = array_slice($backupFiles, 0, count($backupFiles) - $maxBackups);
            
            foreach ($filesToDelete as $file) {
                File::delete($file);
            }
        }
    }

    protected function clearConfigCache()
    {
        if (function_exists('artisan')) {
            artisan()->call('config:clear');
        }
    }

    public function parseEnv($content)
    {
        $lines = explode("\n", $content);
        $env = [];

        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $env[trim($key)] = $this->parseValue(trim($value));
            }
        }

        return $env;
    }

    protected function parseValue($value)
    {
        $value = trim($value);
        
        if (preg_match('/^"(.*)"\s*$/', $value, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/^\'(.*)\'\s*$/', $value, $matches)) {
            return $matches[1];
        }
        
        return $value;
    }
}
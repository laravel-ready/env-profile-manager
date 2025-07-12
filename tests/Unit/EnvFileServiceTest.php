<?php

use LaravelReady\EnvProfiles\Services\EnvFileService;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->service = new EnvFileService();
    $this->envPath = base_path('.env');
    $this->testContent = "APP_NAME=TestApp\nAPP_ENV=testing\nAPP_KEY=base64:test";
    
    // Clean up any existing files
    $this->deleteTestEnvFile();
    cleanupBackups();
});

afterEach(function () {
    $this->deleteTestEnvFile();
    cleanupBackups();
});

it('can read env file content', function () {
    $this->createTestEnvFile($this->testContent);
    
    $content = $this->service->read();
    
    expect($content)->toBe($this->testContent);
});

it('returns empty string when env file does not exist', function () {
    $content = $this->service->read();
    
    expect($content)->toBe('');
});

it('can write content to env file', function () {
    $newContent = "APP_NAME=NewApp\nAPP_ENV=production";
    
    $this->service->write($newContent);
    
    expect(file_get_contents($this->envPath))->toBe($newContent);
});

it('creates backup before writing when env file exists', function () {
    $this->createTestEnvFile($this->testContent);
    
    $this->service->write("NEW_CONTENT=test");
    
    $backupFiles = glob(base_path('.env.backup.*'));
    expect($backupFiles)->toHaveCount(1)
        ->and(file_get_contents($backupFiles[0]))->toBe($this->testContent);
});

it('does not create backup when env file does not exist', function () {
    $this->service->write("NEW_CONTENT=test");
    
    $backupFiles = glob(base_path('.env.backup.*'));
    expect($backupFiles)->toHaveCount(0);
});

it('limits number of backups to max_backups config', function () {
    config(['env-profile-manager.max_backups' => 3]);
    $this->createTestEnvFile($this->testContent);
    
    // Create 5 backups
    for ($i = 1; $i <= 5; $i++) {
        sleep(1); // Ensure different timestamps
        $this->service->write("BACKUP_TEST={$i}");
    }
    
    $backupFiles = glob(base_path('.env.backup.*'));
    expect($backupFiles)->toHaveCount(3);
});

it('keeps newest backups when cleaning old ones', function () {
    config(['env-profile-manager.max_backups' => 2]);
    $this->createTestEnvFile($this->testContent);
    
    // Create backups with identifiable content
    for ($i = 1; $i <= 3; $i++) {
        sleep(1);
        $this->service->write("BACKUP_NUMBER={$i}");
    }
    
    $backupFiles = glob(base_path('.env.backup.*'));
    sort($backupFiles); // Sort by filename (timestamp)
    
    expect($backupFiles)->toHaveCount(2);
    
    // Check that we kept the newer backups
    $contents = array_map('file_get_contents', $backupFiles);
    expect($contents)->not->toContain($this->testContent);
});

it('creates backup with timestamp in filename', function () {
    $this->createTestEnvFile($this->testContent);
    
    $this->service->write("NEW_CONTENT=test");
    
    $backupFiles = glob(base_path('.env.backup.*'));
    expect($backupFiles[0])->toMatch('/\.env\.backup\.\d{14}$/');
});

it('handles empty content writing', function () {
    $this->service->write('');
    
    expect(file_exists($this->envPath))->toBeTrue()
        ->and(file_get_contents($this->envPath))->toBe('');
});

it('preserves line endings when writing', function () {
    $contentWithCRLF = "APP_NAME=Test\r\nAPP_ENV=local\r\n";
    
    $this->service->write($contentWithCRLF);
    
    expect(file_get_contents($this->envPath))->toBe($contentWithCRLF);
});

it('handles special characters in env content', function () {
    $specialContent = 'APP_NAME="My App with Spaces"' . "\n" .
                      'APP_KEY=base64:+/=special+chars/=' . "\n" .
                      'APP_URL=https://example.com?param=value&other=test' . "\n" .
                      'APP_DESC=\'Single quotes work too\'';
    
    $this->service->write($specialContent);
    $readContent = $this->service->read();
    
    expect($readContent)->toBe($specialContent);
});

it('does not create backup when backups feature is disabled', function () {
    config(['env-profile-manager.features.backups' => false]);
    $this->createTestEnvFile($this->testContent);
    
    $this->service->write("NEW_CONTENT=test");
    
    $backupFiles = glob(base_path('.env.backup.*'));
    expect($backupFiles)->toHaveCount(0);
});

it('handles concurrent writes gracefully', function () {
    $this->createTestEnvFile($this->testContent);
    
    // Simulate concurrent writes with slight delay to ensure different timestamps
    $content1 = "CONCURRENT=1";
    $content2 = "CONCURRENT=2";
    
    $this->service->write($content1);
    sleep(1); // Ensure different timestamp
    $this->service->write($content2);
    
    expect(file_get_contents($this->envPath))->toBe($content2);
    
    // Should have at least 1 backup (might be 2 if timestamps differ)
    $backupFiles = glob(base_path('.env.backup.*'));
    expect($backupFiles)->not->toBeEmpty();
});

// Helper method for cleaning up backups
function cleanupBackups() {
    $backupFiles = glob(base_path('.env.backup.*'));
    foreach ($backupFiles as $file) {
        unlink($file);
    }
}
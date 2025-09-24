<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class EnvFileService
{
    private string $envPath;

    public function __construct()
    {
        $this->envPath = base_path('.env');
    }

    /**
     * Add or update a key-value pair in the .env file
     */
    public function set(string $key, string $value, bool $backup = true): bool
    {
        try {
            if ($backup) {
                $this->createBackup();
            }

            $envContent = $this->getEnvContent();
            $updatedContent = $this->updateOrAddKey($envContent, $key, $value);
            
            File::put($this->envPath, $updatedContent);
            
            // Clear config cache to reload environment variables
            // Temporarily disabled to prevent web request issues
            // $this->clearConfigCache();
            \Log::info('Skipped config cache clearing to prevent web request issues');
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to update .env file: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Add multiple key-value pairs to the .env file
     */
    public function setMultiple(array $keyValues, bool $backup = true): bool
    {
        try {
            if ($backup) {
                $this->createBackup();
            }

            $envContent = $this->getEnvContent();
            
            foreach ($keyValues as $key => $value) {
                $envContent = $this->updateOrAddKey($envContent, $key, $value);
            }
            
            File::put($this->envPath, $envContent);
            // $this->clearConfigCache(); // Temporarily disabled
            \Log::info('Skipped config cache clearing for setMultiple');
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to update .env file: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a value from the .env file
     */
    public function get(string $key): ?string
    {
        $envContent = $this->getEnvContent();
        
        if (preg_match("/^{$key}=(.*)$/m", $envContent, $matches)) {
            return trim($matches[1], '"\'');
        }
        
        return null;
    }

    /**
     * Check if a key exists in the .env file
     */
    public function has(string $key): bool
    {
        $envContent = $this->getEnvContent();
        return preg_match("/^{$key}=/m", $envContent) === 1;
    }

    /**
     * Remove a key from the .env file
     */
    public function remove(string $key, bool $backup = true): bool
    {
        try {
            if ($backup) {
                $this->createBackup();
            }

            $envContent = $this->getEnvContent();
            $envContent = preg_replace("/^{$key}=.*$/m", '', $envContent);
            
            // Remove empty lines
            $envContent = preg_replace("/\n\n+/", "\n\n", $envContent);
            
            File::put($this->envPath, $envContent);
            // $this->clearConfigCache(); // Temporarily disabled
            \Log::info('Skipped config cache clearing for remove');
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to remove key from .env file: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Add a new encryption key with automatic versioning
     */
    public function addEncryptionKey(string $key, string $cipher = 'aes-256-cbc'): array
    {
        try {
            $nextVersion = $this->getNextKeyVersion();
            $keyName = "BACKUP_KEY_{$nextVersion}";
            
            \Log::info("Adding encryption key: {$keyName}");
            
            // Add the new key
            $success = $this->set($keyName, $key);
            
            if ($success) {
                \Log::info("Successfully added encryption key: {$keyName}");
                return [
                    'success' => true,
                    'version' => strtolower($nextVersion),
                    'key_name' => $keyName,
                    'message' => "New encryption key {$nextVersion} added to .env file"
                ];
            }
            
            \Log::error("Failed to add encryption key to .env file");
            return [
                'success' => false,
                'message' => 'Failed to add encryption key to .env file'
            ];
        } catch (\Exception $e) {
            \Log::error("Error adding encryption key: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error adding encryption key: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Activate a specific encryption key version
     */
    public function activateEncryptionKey(string $version): bool
    {
        $keyName = "BACKUP_KEY_" . strtoupper($version);
        
        // Check if the key exists
        if (!$this->has($keyName)) {
            return false;
        }
        
        // Update the current key pointer and last rotation timestamp
        return $this->setMultiple([
            'BACKUP_KEY_CURRENT' => $version,
            'BACKUP_LAST_KEY_ROTATION' => now()->toISOString()
        ]);
    }

    /**
     * Get all encryption keys from .env file
     */
    public function getEncryptionKeys(): array
    {
        $envContent = $this->getEnvContent();
        $keys = [];
        
        if (preg_match_all('/^BACKUP_KEY_(V\d+)=(.*)$/m', $envContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $version = strtolower($match[1]);
                $keys[$version] = [
                    'version' => $version,
                    'key' => trim($match[2], '"\''),
                    'is_current' => $version === $this->get('BACKUP_KEY_CURRENT')
                ];
            }
        }
        
        return $keys;
    }

    /**
     * Create a backup of the .env file
     */
    public function createBackup(): string
    {
        $backupPath = $this->envPath . '.backup.' . date('Y-m-d_H-i-s');
        File::copy($this->envPath, $backupPath);
        
        // Keep only the last 10 backups
        $this->cleanupOldBackups();
        
        return $backupPath;
    }

    /**
     * Restore from a backup file
     */
    public function restoreFromBackup(string $backupPath): bool
    {
        if (!File::exists($backupPath)) {
            return false;
        }
        
        try {
            File::copy($backupPath, $this->envPath);
            // $this->clearConfigCache(); // Temporarily disabled
            \Log::info('Skipped config cache clearing for restore backup');
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to restore .env backup: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get list of available backups
     */
    public function getBackups(): array
    {
        $backupFiles = glob($this->envPath . '.backup.*');
        
        return array_map(function ($file) {
            return [
                'path' => $file,
                'name' => basename($file),
                'created_at' => date('Y-m-d H:i:s', filemtime($file)),
                'size' => filesize($file)
            ];
        }, $backupFiles);
    }

    /**
     * Get the content of the .env file
     */
    private function getEnvContent(): string
    {
        if (!File::exists($this->envPath)) {
            throw new \Exception('.env file not found');
        }
        
        return File::get($this->envPath);
    }

    /**
     * Update or add a key-value pair in the env content
     */
    private function updateOrAddKey(string $content, string $key, string $value): string
    {
        // Escape special characters in the value
        $escapedValue = $this->escapeEnvValue($value);
        $newLine = "{$key}={$escapedValue}";
        
        // Check if key already exists
        if (preg_match("/^{$key}=.*$/m", $content)) {
            // Update existing key
            $content = preg_replace("/^{$key}=.*$/m", $newLine, $content);
        } else {
            // Add new key - find the right section or add at the end
            $content = $this->addKeyToAppropriateSection($content, $key, $newLine);
        }
        
        return $content;
    }

    /**
     * Add a key to the appropriate section in the .env file
     */
    private function addKeyToAppropriateSection(string $content, string $key, string $newLine): string
    {
        // Define sections and their patterns
        $sections = [
            'BACKUP_KEY_' => '# encryption keys',
            'BACKUP_ENCRYPTION_' => '# encryption keys',
            'BACKUP_LAST_' => '# encryption keys',
            'BACKUP_' => '# backup settings',
            'DB_' => '# database',
            'MAIL_' => '# mail',
        ];
        
        foreach ($sections as $prefix => $sectionComment) {
            if (str_starts_with($key, $prefix)) {
                return $this->addToSection($content, $sectionComment, $newLine);
            }
        }
        
        // If no specific section found, add at the end
        return rtrim($content) . "\n{$newLine}\n";
    }

    /**
     * Add a line to a specific section
     */
    private function addToSection(string $content, string $sectionComment, string $newLine): string
    {
        // Look for the section comment
        if (strpos($content, $sectionComment) !== false) {
            // Find the end of the section (next comment or end of file)
            $pattern = '/(' . preg_quote($sectionComment, '/') . '.*?)(\n\n# |$)/s';
            
            if (preg_match($pattern, $content, $matches)) {
                $sectionContent = $matches[1];
                $replacement = $sectionContent . "\n{$newLine}";
                
                return str_replace($sectionContent, $replacement, $content);
            }
        }
        
        // If section doesn't exist, create it
        return rtrim($content) . "\n\n{$sectionComment}\n{$newLine}\n";
    }

    /**
     * Escape special characters in env values
     */
    private function escapeEnvValue(string $value): string
    {
        // For encryption keys (base64 values), don't wrap in quotes
        if (str_starts_with($value, 'base64:')) {
            return $value;
        }
        
        // If value contains spaces, quotes, or special characters, wrap in quotes
        if (preg_match('/[\s"\'#=]/', $value)) {
            return '"' . str_replace('"', '\\"', $value) . '"';
        }
        
        return $value;
    }

    /**
     * Get the next available key version
     */
    private function getNextKeyVersion(): string
    {
        $keys = $this->getEncryptionKeys();
        $versions = array_keys($keys);
        
        // Extract version numbers and find the highest
        $versionNumbers = array_map(function($version) {
            return (int) str_replace('v', '', $version);
        }, $versions);
        
        $nextVersion = empty($versionNumbers) ? 1 : max($versionNumbers) + 1;
        return "V{$nextVersion}";
    }

    /**
     * Clear configuration cache
     */
    private function clearConfigCache(): void
    {
        try {
            \Log::info('Attempting to clear config cache');
            
            // Skip config:clear in web requests as it can cause issues
            if (app()->runningInConsole()) {
                Artisan::call('config:clear');
                \Log::info('Config cache cleared via Artisan');
            } else {
                // For web requests, just clear the config cache manually
                app('config')->set('backup.last_key_rotation', null);
                \Log::info('Config cache cleared manually for web request');
            }
            
            // Reload environment variables
            if (function_exists('opcache_reset')) {
                opcache_reset();
                \Log::info('OPcache reset');
            }
        } catch (\Exception $e) {
            \Log::error('Failed to clear config cache: ' . $e->getMessage());
        }
    }

    /**
     * Clean up old backup files (keep only last 10)
     */
    private function cleanupOldBackups(): void
    {
        $backupFiles = glob($this->envPath . '.backup.*');
        
        if (count($backupFiles) > 10) {
            // Sort by modification time (oldest first)
            usort($backupFiles, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Remove oldest files
            $filesToRemove = array_slice($backupFiles, 0, count($backupFiles) - 10);
            foreach ($filesToRemove as $file) {
                File::delete($file);
            }
        }
    }
}

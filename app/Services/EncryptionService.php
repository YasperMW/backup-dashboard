<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Encryption\Encrypter;
use Carbon\Carbon;

class EncryptionService
{
    const ENCRYPTION_TYPES = [
        'aes-128-cbc' => 'AES-128',
        'aes-256-cbc' => 'AES-256',
        'aes-128-gcm' => 'AES-128-GCM',
        'aes-256-gcm' => 'AES-256-GCM'
    ];

    /**
     * Get the current encryption key and version
     */
    public function getCurrentKey(): array
    {
        $currentVersion = env('BACKUP_KEY_CURRENT', 'v1');
        $key = env("BACKUP_KEY_{$currentVersion}");
        
        if (!$key) {
            throw new \Exception("Current backup key {$currentVersion} not found in environment");
        }

        return [
            'version' => $currentVersion,
            'key' => $key,
            'cipher' => $this->getEncryptionType()
        ];
    }

    /**
     * Get all available encryption keys
     */
    public function getAllKeys(): array
    {
        $keys = [];
        $envVars = $_ENV ?? [];
        
        foreach ($envVars as $key => $value) {
            if (preg_match('/^BACKUP_KEY_(V\d+)$/', $key, $matches)) {
                $version = strtolower($matches[1]);
                $keys[$version] = [
                    'version' => $version,
                    'key' => $value,
                    'is_current' => $version === env('BACKUP_KEY_CURRENT', 'v1')
                ];
            }
        }

        return $keys;
    }

    /**
     * Encrypt data using current key
     */
    public function encrypt(string $data): array
    {
        $keyInfo = $this->getCurrentKey();
        $encrypter = $this->createEncrypter($keyInfo['key'], $keyInfo['cipher']);
        
        $encrypted = $encrypter->encrypt($data);
        
        return [
            'data' => $encrypted,
            'key_version' => $keyInfo['version'],
            'cipher' => $keyInfo['cipher'],
            'encrypted_at' => now()->toISOString()
        ];
    }

    /**
     * Decrypt data using specified key version
     */
    public function decrypt(array $encryptedData): string
    {
        $keyVersion = $encryptedData['key_version'] ?? env('BACKUP_KEY_CURRENT', 'v1');
        $cipher = $encryptedData['cipher'] ?? $this->getEncryptionType();
        
        $key = env("BACKUP_KEY_{$keyVersion}");
        if (!$key) {
            throw new \Exception("Encryption key {$keyVersion} not found");
        }

        $encrypter = $this->createEncrypter($key, $cipher);
        return $encrypter->decrypt($encryptedData['data']);
    }

    /**
     * Check if key rotation is needed based on configuration
     */
    public function isKeyRotationNeeded(): bool
    {
        $frequency = config('backup.key_rotation_frequency', '30_days');
        $lastRotation = env('BACKUP_LAST_KEY_ROTATION') ?: config('backup.last_key_rotation');
        
        if (!$lastRotation) {
            return true; // No rotation recorded, rotation needed
        }

        $lastRotationDate = Carbon::parse($lastRotation);
        $rotationInterval = $this->getRotationInterval($frequency);
        
        return $lastRotationDate->addDays($rotationInterval)->isPast();
    }

    /**
     * Generate a new encryption key
     */
    public function generateNewKey(string $cipher = null): string
    {
        $cipher = $cipher ?? $this->getEncryptionType();
        $keyLength = $this->getKeyLength($cipher);
        
        return 'base64:' . base64_encode(random_bytes($keyLength));
    }

    /**
     * Get the next key version
     */
    public function getNextKeyVersion(): string
    {
        $keys = $this->getAllKeys();
        $versions = array_keys($keys);
        
        // Extract version numbers and find the highest
        $versionNumbers = array_map(function($version) {
            return (int) str_replace('v', '', $version);
        }, $versions);
        
        $nextVersion = max($versionNumbers) + 1;
        return "v{$nextVersion}";
    }

    /**
     * Get current encryption type from config
     */
    public function getEncryptionType(): string
    {
        return config('backup.encryption_type', 'aes-256-cbc');
    }

    /**
     * Get available encryption types
     */
    public function getAvailableEncryptionTypes(): array
    {
        return self::ENCRYPTION_TYPES;
    }

    /**
     * Create an encrypter instance
     */
    private function createEncrypter(string $key, string $cipher): Encrypter
    {
        // Remove base64: prefix if present
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return new Encrypter($key, $cipher);
    }

    /**
     * Get key length for cipher
     */
    private function getKeyLength(string $cipher): int
    {
        return match($cipher) {
            'aes-128-cbc', 'aes-128-gcm' => 16,
            'aes-256-cbc', 'aes-256-gcm' => 32,
            default => 32
        };
    }

    /**
     * Get rotation interval in days
     */
    private function getRotationInterval(string $frequency): int
    {
        return match($frequency) {
            '7_days' => 7,
            '30_days' => 30,
            '90_days' => 90,
            default => 30
        };
    }

    /**
     * Get key rotation status for display
     */
    public function getKeyRotationStatus(): array
    {
        $frequency = config('backup.key_rotation_frequency', '30_days');
        $lastRotation = env('BACKUP_LAST_KEY_ROTATION') ?: config('backup.last_key_rotation');
        $isNeeded = $this->isKeyRotationNeeded();
        
        $status = [
            'frequency' => $frequency,
            'last_rotation' => $lastRotation,
            'is_needed' => $isNeeded,
            'current_key' => $this->getCurrentKey(),
            'total_keys' => count($this->getAllKeys())
        ];

        if ($lastRotation) {
            $lastRotationDate = Carbon::parse($lastRotation);
            $nextRotation = $lastRotationDate->addDays($this->getRotationInterval($frequency));
            $status['next_rotation'] = $nextRotation->toISOString();
            $status['days_until_rotation'] = now()->diffInDays($nextRotation, false);
        }

        return $status;
    }
}

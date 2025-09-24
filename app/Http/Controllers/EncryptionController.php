<?php

namespace App\Http\Controllers;

use App\Services\EncryptionService;
use App\Services\EnvFileService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

class EncryptionController extends Controller
{
    public function __construct(
        private EncryptionService $encryptionService,
        private EnvFileService $envFileService
    ) {}

    /**
     * Get current encryption configuration
     */
    public function getConfig(): JsonResponse
    {
        try {
            $config = [
                'encryption_type' => $this->encryptionService->getEncryptionType(),
                'key_rotation_frequency' => config('backup.key_rotation_frequency', '30_days'),
                'available_types' => $this->encryptionService->getAvailableEncryptionTypes(),
                'key_status' => $this->encryptionService->getKeyRotationStatus()
            ];

            return response()->json($config);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load encryption configuration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update encryption configuration
     */
    public function updateConfig(Request $request): JsonResponse
    {
        $request->validate([
            'encryption_type' => 'required|string|in:aes-128-cbc,aes-256-cbc,aes-128-gcm,aes-256-gcm',
            'key_rotation_frequency' => 'required|string|in:7_days,30_days,90_days'
        ]);

        try {
            // Update configuration
            Config::set('backup.encryption_type', $request->encryption_type);
            Config::set('backup.key_rotation_frequency', $request->key_rotation_frequency);

            // Save to config file
            $this->updateConfigFile([
                'encryption_type' => $request->encryption_type,
                'key_rotation_frequency' => $request->key_rotation_frequency
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Encryption configuration updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update encryption configuration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a new encryption key
     */
    public function generateKey(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'cipher' => 'sometimes|string|in:aes-128-cbc,aes-256-cbc,aes-128-gcm,aes-256-gcm',
                'auto_add' => 'sometimes|boolean'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed for generateKey', [
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        }

        try {
            $cipher = $request->cipher ?? $this->encryptionService->getEncryptionType();
            $newKey = $this->encryptionService->generateNewKey($cipher);
            $nextVersion = $this->encryptionService->getNextKeyVersion();
            $autoAdd = $request->boolean('auto_add', false);

            if ($autoAdd) {
                // Automatically add the key to .env file
                \Log::info("Attempting to auto-add key to .env", ['cipher' => $cipher]);
                $result = $this->envFileService->addEncryptionKey($newKey, $cipher);
                \Log::info("EnvFileService result", $result);
                
                if ($result['success']) {
                    $version = $result['version'];
                    \Log::info("About to return success response", ['version' => $version]);
                    
                    $response = [
                        'success' => true,
                        'key' => $newKey,
                        'version' => $version,
                        'cipher' => $cipher,
                        'auto_added' => true,
                        'message' => $result['message'],
                        'instructions' => [
                            "✓ Key automatically added to .env file as BACKUP_KEY_" . strtoupper($version),
                            "",
                            "To activate this key, you can:",
                            "1. Use the 'Activate Key' button below, or",
                            "2. Select it from the dropdown and click 'Activate'"
                        ]
                    ];
                    
                    \Log::info("Returning response", $response);
                    return response()->json($response);
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => $result['message']
                    ], 500);
                }
            }

            return response()->json([
                'success' => true,
                'key' => $newKey,
                'version' => $nextVersion,
                'cipher' => $cipher,
                'auto_added' => false,
                'instructions' => [
                    "Add this to your .env file:",
                    "BACKUP_KEY_{$nextVersion}={$newKey}",
                    "",
                    "To activate this key, update:",
                    "BACKUP_KEY_CURRENT={$nextVersion}"
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to generate encryption key', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate new key: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activate an encryption key
     */
    public function activateKey(Request $request): JsonResponse
    {
        $request->validate([
            'version' => 'required|string|regex:/^v\d+$/'
        ]);

        try {
            $success = $this->envFileService->activateEncryptionKey($request->version);
            
            if ($success) {
                // Also update the config cache to ensure rotation check uses new timestamp
                $this->updateConfigFile([
                    'last_key_rotation' => now()->toISOString()
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => "Encryption key {$request->version} activated successfully",
                    'current_key' => $request->version
                ]);
            }
            
            return response()->json([
                'success' => false,
                'error' => "Failed to activate key {$request->version}. Key may not exist."
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to activate key: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get key rotation status
     */
    public function getKeyStatus(): JsonResponse
    {
        try {
            $status = $this->encryptionService->getKeyRotationStatus();
            $allKeys = $this->encryptionService->getAllKeys();

            return response()->json([
                'status' => $status,
                'keys' => $allKeys,
                'rotation_needed' => $this->encryptionService->isKeyRotationNeeded()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get key status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if key rotation is needed (without generating keys)
     */
    public function checkRotation(): JsonResponse
    {
        try {
            $isNeeded = $this->encryptionService->isKeyRotationNeeded();
            $status = $this->encryptionService->getKeyRotationStatus();

            return response()->json([
                'rotation_needed' => $isNeeded,
                'status' => $status,
                'message' => $isNeeded 
                    ? 'Key rotation is needed based on your configured schedule.'
                    : 'Key rotation is not needed at this time.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to check key rotation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update configuration file
     */
    private function updateConfigFile(array $config): void
    {
        $configPath = config_path('backup.php');
        
        if (!File::exists($configPath)) {
            // Create backup config file if it doesn't exist
            $defaultConfig = [
                'encryption_type' => 'aes-256-cbc',
                'key_rotation_frequency' => '30_days',
                'last_key_rotation' => null
            ];
            
            $configContent = "<?php\n\nreturn " . var_export(array_merge($defaultConfig, $config), true) . ";\n";
            File::put($configPath, $configContent);
        } else {
            // Update existing config
            $currentConfig = include $configPath;
            $updatedConfig = array_merge($currentConfig, $config);
            
            $configContent = "<?php\n\nreturn " . var_export($updatedConfig, true) . ";\n";
            File::put($configPath, $configContent);
        }
    }
}

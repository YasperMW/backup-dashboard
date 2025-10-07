<?php

namespace App\Http\Controllers;

use App\Notifications\BackupCompleted;
use App\Notifications\BackupFailed;
use App\Notifications\RestoreCompleted;
use App\Notifications\IntegrityCheckFailed;
use App\Notifications\ScheduleCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\BackupSourceDirectory;
use App\Models\BackupDestinationDirectory;
use App\Models\BackupHistory;
use App\Models\BackupConfiguration;
use App\Models\BackupSchedule;
use App\Services\LinuxBackupService;
use App\Models\Agent;

class BackupController extends Controller
{
    // Show the backup management page
    public function showManagement(Request $request)
    {
        $backupConfig = BackupConfiguration::first();
        $sourceDirectories = BackupSourceDirectory::all()->pluck('path');
        $destinationDirectories = BackupDestinationDirectory::all()->pluck('path');
        $schedules = BackupSchedule::all();
        if ($request->has('fragment')) {
            $fragment = $request->query('fragment');
            if ($fragment === 'source') {
                return response()->view('backup.partials.source-list');
            } elseif ($fragment === 'destination') {
                return response()->view('backup.partials.destination-list');
            }
        }
        return view('backup.management', [
            'backupConfig' => $backupConfig,
            'sourceDirectories' => $sourceDirectories,
            'destinationDirectories' => $destinationDirectories,
            'schedules' => $schedules,
        ]);
    }

    // Add a new source directory
    public function addSourceDirectory(Request $request)
    {
        $request->validate([
            'path' => 'required|string|unique:backup_source_directories,path',
        ]);
        
        $path = $request->input('path');
        BackupSourceDirectory::create(['path' => $path]);
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('backup.management')->with('status', 'Source directory path added successfully!');
    }

    // Delete a source directory
    public function deleteSourceDirectory($id, Request $request)
    {
        BackupSourceDirectory::findOrFail($id)->delete();
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('backup.management')->with('status', 'Directory removed successfully!');
    }

    // Add a new destination directory
    public function addDestinationDirectory(Request $request)
    {
        $request->validate([
            'path' => 'required|string|unique:backup_destination_directories,path',
        ]);
        
        $path = $request->input('path');
        BackupDestinationDirectory::create(['path' => $path]);
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('backup.management')->with('status', 'Destination directory path added successfully!');
    }

    // Delete a destination directory
    public function deleteDestinationDirectory($id, Request $request)
    {
        BackupDestinationDirectory::findOrFail($id)->delete();
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('backup.management')->with('status', 'Destination directory removed successfully!');
    }

    /**
     * Check if a source path is valid for backup
     * This handles various path formats including absolute and relative paths
     */
    private function isValidSource($path) {
        // If it's a network path (starting with \\ or //), accept it
        if (str_starts_with($path, '\\\\') || str_starts_with($path, '//')) {
            return true;
        }
        
        // Convert forward slashes to backslashes for Windows compatibility
        $normalizedPath = str_replace('/', DIRECTORY_SEPARATOR, $path);
        
        // Check if it's an absolute path
        if (str_starts_with($normalizedPath, DIRECTORY_SEPARATOR) || 
            (strlen($normalizedPath) > 1 && $normalizedPath[1] === ':')) {
            return true; // Accept any absolute path
        }
        
        // Check if it's a relative path that exists
        $absolutePath = base_path($normalizedPath);
        return is_dir($absolutePath);
    }

    // Handle the backup form submission
    public function startBackup(Request $request)
    {
        // Ensure upload limits are set for large backups
        ini_set('max_execution_time', 3600); // 1 hour
        ini_set('upload_max_filesize', '4096M');
        ini_set('post_max_size', '4096M');
        ini_set('memory_limit', '4096M');
        ini_set('max_input_time', 3600);
        
        // Validate the request
        $validated = $request->validate([
            'source_directory' => 'required|exists:backup_source_directories,path',
            'destination_directory' => 'required|exists:backup_destination_directories,path',
            'backup_type' => 'required|in:full,incremental',
            'storage_location' => 'required|string',
        ]);
        
        // Get the source and destination directory IDs
        $source = BackupSourceDirectory::where('path', $validated['source_directory'])->first();
        $destination = BackupDestinationDirectory::where('path', $validated['destination_directory'])->first();
        
        // Find an available agent for the authenticated user
        $agent = Agent::where('status', 'online')
            ->where('user_id', auth()->id())
            ->first();
        
        if (!$agent) {
            return back()->withErrors(['backup' => 'No available agents registered to your account are online. Please register an agent or bring it online.']);
        }
        
        // Create a new backup job
        $backupJob = BackupJob::create([
            'agent_id' => $agent->id,
            'user_id' => auth()->id(),
            'name' => 'Manual Backup - ' . now()->format('Y-m-d H:i:s'),
            'source_path' => $source->path,
            'destination_path' => $destination->path,
            'backup_type' => $validated['backup_type'],
            'status' => 'pending',
            'options' => [
                'storage_location' => $validated['storage_location']
            ],
            'started_at' => now(),
        ]);
        
        // Notify user
        $user = auth()->user();
        $user->notify(new BackupStarted($backupJob));
        
        return back()->with('status', 'Backup job has been queued and will start soon.');
        $request->validate([
            'source_directories'    => 'required|array',
            'storage_location'      => 'nullable|string', // 'local', 'remote', or 'both'
            'destination_directory' => 'nullable|string', // for local backups
            'backup_type'           => 'nullable|string',
            'compression_level'     => 'nullable|string',
        ]);

        $sources         = $request->input('source_directories');
        $storageLocation = $request->input('storage_location', 'both'); // default to both
        $backupType      = $request->input('backup_type', BackupConfiguration::first()->backup_type ?? 'full');
        $compressionLevel= $request->input('compression_level', BackupConfiguration::first()->compression_level ?? 'none');

        $keyVersion = $this->getCurrentKeyVersion();
        $compressionMap = [
            'none'   => \ZipArchive::CM_STORE,
            'low'    => \ZipArchive::CM_DEFLATE,
            'medium' => \ZipArchive::CM_DEFLATE,
            'high'   => \ZipArchive::CM_DEFLATE,
        ];
        $compressionOptions = [
            'none'   => 0,
            'low'    => 1,
            'medium' => 6,
            'high'   => 9,
        ];

        $allSuccess = true;
        $errorMsg   = null;

        foreach ($sources as $src) {
            if (!$this->isValidSource($src)) {
                \Log::warning("Skipping invalid source path: {$src}");
                continue;
            }

            $dirName   = basename($src);
            $timestamp = date('Ymd_His');
            $tmpDir    = sys_get_temp_dir();
            $zipFile   = $tmpDir . DIRECTORY_SEPARATOR . "{$dirName}_{$backupType}_{$timestamp}.zip";

            try {
            // 1️⃣ Create ZIP
            $zip = new \ZipArchive();
            if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \Exception("Failed to create zip file for {$src}");
            }

            // Normalize the source path for consistent handling
            $normalizedSrc = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $src);
            
            // If it's a relative path, make it absolute relative to the base path
            if (!str_starts_with($normalizedSrc, DIRECTORY_SEPARATOR) && 
                !(strlen($normalizedSrc) > 1 && $normalizedSrc[1] === ':')) {
                $normalizedSrc = base_path($normalizedSrc);
            }
            
            // Use the directory iterator with appropriate options
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $normalizedSrc, 
                    \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveDirectoryIterator::CURRENT_AS_FILEINFO
                ),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($files as $file) {
                $filePath = $file->getRealPath();
                // Calculate the relative path based on the original source
                $relativePath = ltrim(str_replace('\\', '/', substr($filePath, strlen($normalizedSrc))), '/');

                if ($file->isDir()) {
                    $zip->addEmptyDir($relativePath);
                    continue;
                }

                $zip->addFile($filePath, $relativePath);

                if ($compressionLevel !== 'none') {
                    $zip->setCompressionName(
                        $relativePath,
                        $compressionMap[$compressionLevel],
                        $compressionOptions[$compressionLevel]
                    );
                }
            }

            $zip->close();

            // 2️⃣ Encrypt ZIP
            $encryptedFile = $zipFile . '.enc';
            $this->encryptFile($zipFile, $encryptedFile, $keyVersion);
            \File::delete($zipFile);

            $encSize = \File::size($encryptedFile);
            $encHash = hash_file('sha256', $encryptedFile);

            // 3️⃣ Remote backup (optional per storage_location)
            if (in_array($storageLocation, ['both', 'remote'], true)) {
                try {
                    $linuxBackup = new \App\Services\LinuxBackupService();
                    $remoteFile  = $linuxBackup->uploadFile($encryptedFile);

                    $historyRemote = BackupHistory::create([
                        'user_id'               => auth()->id(),
                        'source_directory'      => $src,
                        'destination_directory' => config('backup.remote_path'),
                        'destination_type'      => 'remote',
                        'filename'              => basename($remoteFile),
                        'size'                  => $encSize,
                        'status'                => 'completed',
                        'backup_type'           => $backupType,
                        'compression_level'     => $compressionLevel,
                        'key_version'           => $keyVersion,
                        'started_at'            => now(),
                        'completed_at'          => now(),
                        'integrity_hash'        => $encHash,
                        'integrity_verified_at' => now(),
                    ]);

                    // Notify user
                    auth()->user()->notify(new \App\Notifications\BackupCompleted($historyRemote));

                    \Log::info("✅ Remote backup completed", ['file' => $remoteFile]);
                } catch (\Exception $e) {
                    $allSuccess = false;
                    $errorMsg   = $e->getMessage();

                    // Log and notify failure
                    \Log::error("❌ Remote backup failed", ['error' => $e->getMessage()]);
                    $historyFailed = BackupHistory::create([
                        'user_id'               => auth()->id(),
                        'source_directory'      => $src,
                        'destination_directory' => config('backup.remote_path'),
                        'destination_type'      => 'remote',
                        'filename'              => basename($encryptedFile),
                        'status'                => 'failed',
                        'backup_type'           => $backupType,
                        'compression_level'     => $compressionLevel,
                        'key_version'           => $keyVersion,
                        'started_at'            => now(),
                        'completed_at'          => now(),
                        'error_message'         => $e->getMessage(),
                    ]);
                    auth()->user()->notify(new \App\Notifications\BackupFailed($historyFailed, $e->getMessage()));
                }
            }
            

            // 4️⃣ Local backup (optional per storage_location)
            if (in_array($storageLocation, ['both', 'local'], true)) {
                try {
                    $localDestination = $request->input('destination_directory', storage_path('app/backups'));
                    if (!\File::exists($localDestination)) {
                        \File::makeDirectory($localDestination, 0755, true);
                    }

                    $finalLocalFile = $localDestination . DIRECTORY_SEPARATOR . basename($encryptedFile);
                    \File::copy($encryptedFile, $finalLocalFile);

                    $historyLocal = BackupHistory::create([
                        'user_id'               => auth()->id(),
                        'source_directory'      => $src,
                        'destination_directory' => $localDestination,
                        'destination_type'      => 'local',
                        'filename'              => basename($finalLocalFile),
                        'size'                  => $encSize,
                        'status'                => 'completed',
                        'backup_type'           => $backupType,
                        'compression_level'     => $compressionLevel,
                        'key_version'           => $keyVersion,
                        'started_at'            => now(),
                        'completed_at'          => now(),
                        'integrity_hash'        => $encHash,
                        'integrity_verified_at' => now(),
                    ]);

                    // Notify user
                    auth()->user()->notify(new \App\Notifications\BackupCompleted($historyLocal));

                    \Log::info("✅ Local backup completed", ['file' => $finalLocalFile]);
                } catch (\Exception $e) {
                    $allSuccess = false;
                    $errorMsg   = $e->getMessage();

                    \Log::error("❌ Local backup failed", ['error' => $e->getMessage()]);
                    $historyFailed = BackupHistory::create([
                        'user_id'               => auth()->id(),
                        'source_directory'      => $src,
                        'destination_directory' => $localDestination,
                        'destination_type'      => 'local',
                        'filename'              => basename($encryptedFile),
                        'status'                => 'failed',
                        'backup_type'           => $backupType,
                        'compression_level'     => $compressionLevel,
                        'key_version'           => $keyVersion,
                        'started_at'            => now(),
                        'completed_at'          => now(),
                        'error_message'         => $e->getMessage(),
                    ]);
                    auth()->user()->notify(new \App\Notifications\BackupFailed($historyFailed, $e->getMessage()));
                }
            }
            

            // 5️⃣ Clean up temp encrypted file (always)
            if (is_file($encryptedFile)) {
                \File::delete($encryptedFile);
            }

        } catch (\Exception $e) {
            $allSuccess = false;
            $errorMsg   = $e->getMessage();
            \Log::error("❌ Backup failed", ['source' => $src, 'error' => $e->getMessage()]);
        }
    }

    if ($request->ajax() || $request->wantsJson()) {
        return $allSuccess
            ? response()->json(['success' => true])
            : response()->json(['success' => false, 'message' => $errorMsg ?: 'Backup failed.']);
    }

    return $allSuccess
        ? redirect()->back()->with('status', 'Backup completed successfully!')
        : redirect()->back()->withErrors(['backup' => 'Backup failed: ' . ($errorMsg ?: 'Unknown error')]);
}


    // Key management helpers
    private function getEncryptionKeys()
    {
        return config('backup.encryption_keys', []);
    }
    public function getCurrentKeyVersion()
    {
        return config('backup.current_key_version', 'v1');
    }
    private function getKeyByVersion($version)
    {
        $keys = $this->getEncryptionKeys();
        if (!isset($keys[$version])) {
            \Log::error('Encryption key version not found', [
                'requested_version' => $version,
                'available_versions' => array_keys($keys)
            ]);
            throw new \Exception('Encryption key version not found: ' . $version);
        }

        $keyData = $keys[$version];
        \Log::info('Using encryption key', [
            'version' => $version,
            'key_length' => strlen($keyData),
            'key_preview' => substr($keyData, 0, 20) . '...'
        ]);

        return base64_decode(preg_replace('/^base64:/', '', $keyData));
    }

    // Helper: Encrypt a file with AES-256-CBC
    public function encryptFile($inputPath, $outputPath, $keyVersion)
    {
        $key = $this->getKeyByVersion($keyVersion);
        $iv = random_bytes(16);
        $plaintext = file_get_contents($inputPath);
        $ciphertext = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($ciphertext === false) {
            throw new \Exception('Encryption failed');
        }
        file_put_contents($outputPath, $iv . $ciphertext);
    }

    // (Pruned) Obsolete server-side restore helpers removed: decryptFile*, copyFileStream,
    // convertToBytes, tryIncreaseMemoryLimit. Restores now run on the agent.

    // Create a new backup schedule
    
    public function createSchedule(Request $request)
{
    $request->validate([
        'frequency' => 'required|in:daily,weekly,monthly',
        'time' => 'required',
        'source_directories' => 'required|array',
        'destination_directory' => 'required|string',
    ]);

    $daysOfWeek = null;
    if ($request->frequency === 'weekly') {
        $daysOfWeek = is_array($request->days_of_week) ? implode(',', $request->days_of_week) : null;
    }

    try {
        $schedule = \App\Models\BackupSchedule::create([
            'frequency' => $request->frequency,
            'time' => $request->time,
            'days_of_week' => $daysOfWeek,
            'source_directories' => $request->source_directories,
            'destination_directory' => $request->destination_directory,
            'enabled' => true,
            'retention_days' => $request->retention_days,
            'max_backups' => $request->max_backups,
            'user_id' => auth()->id(),
        ]);

        // ✅ Notify user about successful schedule creation
        auth()->user()->notify(new \App\Notifications\ScheduleCreated($schedule));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Backup schedule created successfully!']);
        }

        return redirect()->route('backup.management')->with('status', 'Backup schedule created successfully!');
    } catch (\Exception $e) {
        // ✅ Notify user about failure
        auth()->user()->notify(new \App\Notifications\ScheduleFailed($e->getMessage()));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }

        return redirect()->route('backup.management')->withErrors(['schedule' => $e->getMessage()]);
    }
}


    // Get the current backup configuration (for AJAX or settings page)
    public function getBackupConfig()
    {
        $config = BackupConfiguration::first();
        if (!$config) {
            $config = BackupConfiguration::create([]); // use defaults
        }
        return response()->json($config);
    }

    // Update the backup configuration (from settings page)
    public function updateBackupConfig(Request $request)
    {
        $request->validate([
            'storage_location' => 'required|string',
            'backup_type' => 'required|string',
            'compression_level' => 'required|string',
            'retention_period' => 'required|integer|min:1',
        ]);
        $config = BackupConfiguration::first();
        if (!$config) {
            $config = new BackupConfiguration();
        }
        $config->fill($request->only(['storage_location', 'backup_type', 'compression_level', 'retention_period']));
        $config->save();
        return response()->json(['success' => true, 'config' => $config]);
    }

    public function getBackupHistoryFragment()
    {
        return response()->view('backup.history-table');
    }

    // Return schedule table fragment for AJAX
    public function getScheduleTableFragment()
    {
        $schedules = BackupSchedule::all();
        return view('backup.schedule-table', ['schedules' => $schedules]);
    }

    /**
     * Return filtered backups for AJAX filtering.
     */
    public function filterBackups(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $type = $request->input('type');
        $query = BackupHistory::query();
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($type) {
            $query->where('backup_type', $type);
        }
        $linux = new \App\Services\LinuxBackupService();
        $manualOffline = session('manual_offline', false);
        $actuallyOffline = !$linux->isReachable(5);
        $isSystemOffline = $manualOffline || $actuallyOffline;
        $remotePath = config('backup.remote_path');
        $backups = $query->orderByDesc('created_at')->get()->map(function($b) use ($linux, $remotePath, $isSystemOffline) {
            $destDirNorm = rtrim(str_replace('\\', '/', $b->destination_directory ?? ''), '/');
            $remotePathNorm = rtrim(str_replace('\\', '/', $remotePath ?? ''), '/');
            $isRemote = ($b->destination_type === 'remote') || ($remotePathNorm && $destDirNorm === $remotePathNorm);
            $localFullPath = $b->destination_directory . DIRECTORY_SEPARATOR . $b->filename;
            $remoteFullPath = str_replace('\\', '/', $localFullPath);
            // Determine exists based on offline/online and destination type
            if ($isRemote) {
                if ($isSystemOffline) {
                    $exists = null; // unknown due to no connection
                } else {
                    // First try with full path
                    $exists = $linux->exists($remoteFullPath);
                    // If not found, try with just the filename in the default remote path
                    if (!$exists) {
                        $filenameOnly = basename($remoteFullPath);
                        $exists = $linux->exists($linux->getRemotePath() . '/' . $filenameOnly);
                    }
                }
            } else {
                $exists = file_exists($localFullPath);
            }
            return [
                'id' => $b->id,
                'created_at' => $b->created_at->toDateTimeString(),
                'backup_type' => $b->backup_type,
                'filename' => $b->filename,
                'size' => $b->size,
                'status' => $b->status,
                'destination_directory' => $b->destination_directory,
                'destination_type' => $b->destination_type,
                'exists' => $exists,
                'connection' => $manualOffline ? 'offline' : 'online',
                'is_remote' => $isRemote,
                'is_offline' => $isSystemOffline && $isRemote,
            ];
        })->values();
        return response()->json(['success' => true, 'backups' => $backups]);
    }

    /**
     * Restore a backup using the client agent. Queues an agent job and returns job_id.
     */
    public function restoreBackup(Request $request)
    {
        $request->validate([
            'backup_id' => 'required|exists:backup_histories,id',
            'restore_path' => 'required|string',
            'overwrite' => 'nullable|boolean',
            'preserve_permissions' => 'nullable|boolean',
            'key_version' => 'nullable|string',
        ]);

        $history = BackupHistory::findOrFail($request->backup_id);
        // Find an available agent for the authenticated user
        $agent = Agent::where('status', 'online')
            ->where('user_id', auth()->id())
            ->first();
        if (!$agent) {
            return response()->json(['success' => false, 'message' => 'No online agents registered to your account'], 422);
        }

        // Encryption config from env (versioned keys)
        $algo = env('BACKUP_ENCRYPTION_ALGO', 'AES-256-CBC');
        $currentKeyVersion = strtolower((string) ($request->input('key_version') ?? env('BACKUP_KEY_CURRENT')));
        $envKeyName = 'BACKUP_KEY_' . strtoupper($currentKeyVersion);
        $selectedKey = env($envKeyName);
        if (!$selectedKey) {
            return response()->json(['success' => false, 'message' => "Encryption key for version '{$currentKeyVersion}' not set in .env ({$envKeyName})"], 422);
        }
        if (str_starts_with($selectedKey, 'base64:')) {
            $selectedKey = substr($selectedKey, 7);
        }

        // Determine archive location (local vs remote)
        $isRemote = ($history->destination_type === 'remote');
        $archive = [
            'type' => $isRemote ? 'remote' : 'local',
            'directory' => $history->destination_directory,
            'filename' => $history->filename,
        ];

        // Build job options for restore action
        $options = [
            'action' => 'restore',
            'encryption' => [
                'enabled' => true,
                'algorithm' => $algo,
                'password' => $selectedKey,
                'key_version' => $currentKeyVersion,
            ],
            'archive' => $archive,
            'restore' => [
                'path' => $request->input('restore_path'),
                'overwrite' => (bool) $request->input('overwrite', false),
                'preserve_permissions' => (bool) $request->input('preserve_permissions', false),
            ],
            // Remote target for download when archive.type == remote
            'remote' => [
                'host' => config('backup.linux_host') ?? env('BACKUP_LINUX_HOST'),
                'user' => config('backup.linux_user') ?? env('BACKUP_LINUX_USER'),
                'pass' => config('backup.linux_pass') ?? env('BACKUP_LINUX_PASS'),
                'path' => config('backup.remote_path') ?? env('BACKUP_LINUX_PATH'),
            ],
        ];

        // Create a restore job for the agent
        $job = \App\Models\BackupJob::create([
            'agent_id' => $agent->id,
            'user_id' => auth()->id(),
            'name' => 'Restore - ' . ($history->filename ?? 'backup') . ' - ' . now()->format('Y-m-d H:i:s'),
            'source_path' => $history->destination_directory, // archive location (local path for local backups)
            'destination_path' => $request->input('restore_path'),
            'backup_type' => $history->backup_type ?? 'full',
            'status' => 'pending',
            'options' => $options,
            'started_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Restore job queued', 'data' => ['job_id' => $job->id]]);
    }


    /**
     * Verify the integrity of a backup by recalculating its hash.
     */
    public function verifyBackupIntegrity(Request $request)
        {
            $request->validate([
                'backup_id' => 'required|exists:backup_histories,id',
            ]);

            $backup = BackupHistory::findOrFail($request->backup_id);
            $encPath = $backup->destination_directory . DIRECTORY_SEPARATOR . $backup->filename;

            $remotePath = config('backup.remote_path');
            $remotePathNorm = rtrim(str_replace('\\', '/', $remotePath ?? ''), '/');
            $destDirNorm = rtrim(str_replace('\\', '/', $backup->destination_directory ?? ''), '/');
            $isRemote = ($backup->destination_type === 'remote') || ($remotePathNorm && $destDirNorm === $remotePathNorm);
            $encLocalPath = $encPath;
            if ($isRemote) {
                $linux = new \App\Services\LinuxBackupService();
                $remoteEncPath = str_replace('\\', '/', $encPath);
                $remoteExists = $linux->exists($remoteEncPath);
                $remoteDownloadPath = $remoteEncPath;
                if (!$remoteExists && $remotePathNorm) {
                    $altRemotePath = $remotePathNorm . '/' . $backup->filename;
                    $remoteExists = $linux->exists($altRemotePath);
                    if ($remoteExists) {
                        $remoteDownloadPath = $altRemotePath;
                    }
                }
                if (!$remoteExists) {
                    return response()->json(['success' => false, 'message' => 'Remote backup file not found.']);
                }
                $tmpEnc = tempnam(sys_get_temp_dir(), 'remote_enc_') . '.enc';
                if (!$linux->downloadFile($remoteDownloadPath, $tmpEnc)) {
                    return response()->json(['success' => false, 'message' => 'Failed to download remote backup file.']);
                }
                $encLocalPath = $tmpEnc;
            } else if (!file_exists($encPath)) {
                return response()->json(['success' => false, 'message' => 'Backup file not found.']);
            }

            // Calculate hash and check integrity
            $hash = hash_file('sha256', $encLocalPath);
            $match = $backup->integrity_hash === $hash;

            if ($match) {
                $backup->integrity_verified_at = now();
                $backup->save();
            } else {
                // Notify user that integrity check failed
                auth()->user()->notify(new \App\Notifications\IntegrityCheckFailed($backup));
            }

            $resp = [
                'success' => $match,
                'message' => $match
                    ? 'Backup is authentic and has not been tampered with.'
                    : 'WARNING: Backup file has been changed or corrupted since creation! Integrity check failed.',
                'expected_hash' => $backup->integrity_hash,
                'actual_hash' => $hash,
            ];
            if (isset($tmpEnc) && file_exists($tmpEnc)) {
                @unlink($tmpEnc);
            }
            return response()->json($resp);
        }

} 

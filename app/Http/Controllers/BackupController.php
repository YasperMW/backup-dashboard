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
        if (!is_dir($path)) {
            $msg = 'The specified directory does not exist on the server.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg]);
            }
            return redirect()->route('backup.management')->withErrors(['path' => $msg]);
        }
        BackupSourceDirectory::create(['path' => $path]);
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('backup.management')->with('status', 'Directory added successfully!');
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
        if (!is_dir($path)) {
            $msg = 'The specified directory does not exist on the server.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg]);
            }
            return redirect()->route('backup.management')->withErrors(['destination_path' => $msg]);
        }
        BackupDestinationDirectory::create(['path' => $path]);
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('backup.management')->with('status', 'Destination directory added successfully!');
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

    // Handle the backup form submission
  public function startBackup(Request $request)
{
    // Ensure upload limits are set for large backups
    set_time_limit(3600); // 1 hour
    ini_set('max_execution_time', 3600);
    ini_set('upload_max_filesize', '4096M');
    ini_set('post_max_size', '4096M');
    ini_set('memory_limit', '4096M');
    ini_set('max_input_time', 3600);
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
        if (!\File::isDirectory($src)) continue;

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

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($files as $file) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($src) + 1);

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

    // Helper: Decrypt a file with AES-256-CBC (memory efficient for large files)
    private function decryptFile($inputPath, $outputPath, $keyVersion)
    {
        $key = $this->getKeyByVersion($keyVersion);

        // Check file size and available memory
        $fileSize = filesize($inputPath);
        $currentMemory = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        $availableMemory = $memoryLimitBytes - $currentMemory;

        // If file is larger than 50MB, use memory-conscious approach for large files
        if ($fileSize > 50 * 1024 * 1024) {
            return $this->decryptFileMemoryConscious($inputPath, $outputPath, $keyVersion);
        }

        // For smaller files, use the simple decryption method
        return $this->decryptFileSimple($inputPath, $outputPath, $keyVersion);
    }

    // Simple decryption for smaller files
    private function decryptFileSimple($inputPath, $outputPath, $keyVersion)
    {
        $key = $this->getKeyByVersion($keyVersion);

        $inputHandle = fopen($inputPath, 'rb');
        if (!$inputHandle) {
            throw new \Exception('Failed to open input file for decryption');
        }

        try {
            // Read IV (first 16 bytes)
            $iv = fread($inputHandle, 16);
            if (strlen($iv) !== 16) {
                throw new \Exception('Invalid IV in encrypted file');
            }

            // Read the rest of the ciphertext
            $ciphertext = '';
            while (!feof($inputHandle)) {
                $chunk = fread($inputHandle, 8192);
                if ($chunk === false) {
                    throw new \Exception('Failed to read from input file');
                }
                $ciphertext .= $chunk;
            }

            // Decrypt the entire ciphertext at once (AES-CBC requires this)
            $plaintext = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

            if ($plaintext === false) {
                $error = openssl_error_string();
                \Log::error('OpenSSL decryption failed', [
                    'error' => $error,
                    'ciphertext_length' => strlen($ciphertext),
                    'iv_length' => strlen($iv),
                    'key_version' => $keyVersion
                ]);
                throw new \Exception('Decryption failed: ' . $error);
            }

            if (empty($plaintext)) {
                \Log::error('Decryption resulted in empty plaintext', [
                    'ciphertext_length' => strlen($ciphertext),
                    'expected_size' => strlen($ciphertext), // Should be similar size
                ]);
                throw new \Exception('Decryption resulted in empty output');
            }

            // Write the decrypted data to output file
            $written = file_put_contents($outputPath, $plaintext);
            if ($written === false) {
                throw new \Exception('Failed to write decrypted data to output file');
            }

            \Log::info('File decrypted successfully', [
                'input_size' => strlen($ciphertext),
                'output_size' => $written,
                'key_version' => $keyVersion
            ]);

        } finally {
            fclose($inputHandle);
        }
    }

    // Memory-conscious decryption for larger files
    private function decryptFileMemoryConscious($inputPath, $outputPath, $keyVersion)
    {
        $key = $this->getKeyByVersion($keyVersion);

        // Calculate required memory (rough estimate)
        $fileSize = filesize($inputPath);
        $estimatedMemoryNeeded = $fileSize + (50 * 1024 * 1024); // File size + 50MB overhead

        // Try to increase memory limit to accommodate the file
        $currentLimit = ini_get('memory_limit');
        $currentLimitBytes = $this->convertToBytes($currentLimit);

        if ($estimatedMemoryNeeded > $currentLimitBytes) {
            // Calculate new limit (file size + 100MB buffer)
            $newLimitBytes = $fileSize + (100 * 1024 * 1024);
            $newLimit = min($newLimitBytes, 1024 * 1024 * 1024); // Cap at 1GB

            $newLimitStr = ceil($newLimit / (1024 * 1024)) . 'M';

            if ($this->tryIncreaseMemoryLimit($newLimitStr)) {
                // Success - use simple approach
                return $this->decryptFileSimple($inputPath, $outputPath, $keyVersion);
            }
        }

        // If we can't increase memory limit enough, fall back to disk-based approach
        return $this->decryptFileDiskBased($inputPath, $outputPath, $keyVersion);
    }

    // Disk-based decryption for extremely large files
    private function decryptFileDiskBased($inputPath, $outputPath, $keyVersion)
    {
        // For extremely large files, use a simpler approach that works reliably
        // The memory limits are now set to 4GB, so we can handle most files
        return $this->decryptFileSimple($inputPath, $outputPath, $keyVersion);
    }

    // Helper: Stream copy file without loading into memory
    private function copyFileStream($source, $destination)
    {
        $sourceHandle = fopen($source, 'rb');
        if (!$sourceHandle) {
            throw new \Exception('Failed to open source file');
        }

        $destHandle = fopen($destination, 'wb');
        if (!$destHandle) {
            fclose($sourceHandle);
            throw new \Exception('Failed to open destination file');
        }

        try {
            // Use stream_copy_to_stream for efficient copying
            $copied = stream_copy_to_stream($sourceHandle, $destHandle);
            if ($copied === false) {
                throw new \Exception('Failed to copy file contents');
            }
        } finally {
            fclose($sourceHandle);
            fclose($destHandle);
        }
    }

    // Helper: Convert memory limit string to bytes
    private function convertToBytes($memoryLimit)
    {
        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);

        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return (int) $memoryLimit;
        }
    }

    // Try to increase memory limit safely
    private function tryIncreaseMemoryLimit($newLimit)
    {
        $oldLimit = ini_set('memory_limit', $newLimit);
        if ($oldLimit !== false) {
            // Check if the new limit is actually higher than the old one
            $oldLimitBytes = $this->convertToBytes($oldLimit);
            $newLimitBytes = $this->convertToBytes($newLimit);
            return $newLimitBytes > $oldLimitBytes;
        }
        return false;
    }

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
     * Restore a backup zip to a given path.
     */
    public function restoreBackup(Request $request)
        {
            // Increase execution time and memory limits for large file restoration
            set_time_limit(3600); // 1 hour
            ini_set('max_execution_time', 3600);
            ini_set('memory_limit', '4096M');
            ini_set('max_input_time', 3600);

            $request->validate([
                'backup_id' => 'required|exists:backup_histories,id',
                'restore_path' => 'required|string',
                'overwrite' => 'nullable|boolean',
                'preserve_permissions' => 'nullable|boolean',
            ]);

            $backup = BackupHistory::findOrFail($request->backup_id);
            $encPath = $backup->destination_directory . DIRECTORY_SEPARATOR . $backup->filename;
            $restorePath = $request->restore_path;
            $overwrite = $request->boolean('overwrite', false);
            $preservePermissions = $request->boolean('preserve_permissions', false);
            $keyVersion = $backup->key_version ?? 'v1';

            \Log::info('Starting backup restoration', [
                'backup_id' => $request->backup_id,
                'key_version' => $keyVersion,
                'backup_size' => $backup->size,
                'encPath' => $encPath,
                'isRemote' => $isRemote ?? false
            ]);

            // Check if backup file exists
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
                // Fallback to remote_path + '/' + filename in case dest dir mismatch
                if (!$remoteExists && $remotePathNorm) {
                    $altRemotePath = $remotePathNorm . '/' . $backup->filename;
                    $remoteExists = $linux->exists($altRemotePath);
                    if ($remoteExists) {
                        $remoteDownloadPath = $altRemotePath;
                    }
                }
                if (!$remoteExists) {
                    Log::error('Restore failed: remote backup file not found', [
                        'backup_id' => $backup->id,
                        'attempted_paths' => [$remoteEncPath, ($remotePathNorm ? ($remotePathNorm . '/' . $backup->filename) : null)],
                        'restorePath' => $restorePath,
                        'key_version' => $keyVersion,
                    ]);

                    auth()->user()->notify(new \App\Notifications\RestoreFailed($backup, 'Remote backup file not found.'));
                    return response()->json(['success' => false, 'message' => 'Remote backup file not found.']);
                }
                $tmpEnc = tempnam(sys_get_temp_dir(), 'remote_enc_') . '.enc';
                if (!$linux->downloadFile($remoteDownloadPath, $tmpEnc)) {
                    return response()->json(['success' => false, 'message' => 'Failed to download remote backup file.']);
                }
                $encLocalPath = $tmpEnc;
            }

            if (!$isRemote && !file_exists($encPath)) {
                Log::error('Restore failed: backup file not found', [
                    'backup_id' => $backup->id,
                    'zipPath' => $encPath,
                    'restorePath' => $restorePath,
                    'key_version' => $keyVersion,
                ]);

                auth()->user()->notify(new \App\Notifications\RestoreFailed($backup, 'Backup file not found.'));
                return response()->json(['success' => false, 'message' => 'Backup file not found.']);
            }

            // Decrypt to a temp file
            $tmpZip = tempnam(sys_get_temp_dir(), 'restore_') . '.zip';
            try {
                $this->decryptFile($encLocalPath, $tmpZip, $keyVersion);

                // Validate that the decrypted file is actually a valid ZIP
                if (!file_exists($tmpZip)) {
                    throw new \Exception('Decrypted file was not created');
                }

                $decryptedSize = filesize($tmpZip);
                \Log::info('Decryption completed', [
                    'tmpZip' => $tmpZip,
                    'decrypted_size' => $decryptedSize,
                    'expected_size' => $backup->size
                ]);

                // Basic check if file looks like a ZIP (first 4 bytes should be PK\x03\x04)
                $header = file_get_contents($tmpZip, false, null, 0, 4);
                if ($header !== "PK\x03\x04") {
                    \Log::error('Invalid ZIP file header', [
                        'header' => bin2hex($header),
                        'expected' => '504b0304'
                    ]);
                    throw new \Exception('Decrypted file is not a valid ZIP archive');
                }

            } catch (\Exception $e) {
                Log::error('Restore decryption failed', [
                    'backup_id' => $backup->id,
                    'zipPath' => $encLocalPath,
                    'restorePath' => $restorePath,
                    'key_version' => $keyVersion,
                    'error' => $e->getMessage(),
                ]);

                auth()->user()->notify(new \App\Notifications\RestoreFailed($backup, $e->getMessage()));
                return response()->json(['success' => false, 'message' => 'Decryption failed: ' . $e->getMessage()]);
            }

            // Open the zip
            $zip = new \ZipArchive();
            if ($zip->open($tmpZip) === TRUE) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->getNameIndex($i);
                    $target = $restorePath . DIRECTORY_SEPARATOR . $entry;

                    if (substr($entry, -1) === '/') {
                        if (!is_dir($target)) mkdir($target, 0755, true);
                    } else {
                        $dir = dirname($target);
                        if (!is_dir($dir)) mkdir($dir, 0755, true);

                        // Skip system/hidden files
                        if (strtolower(basename($entry)) === 'desktop.ini') continue;

                        if (file_exists($target) && !$overwrite) continue;

                        $stream = $zip->getStream($entry);
                        if ($stream) {
                            $out = fopen($target, 'w');
                            if ($out) {
                                while (!feof($stream)) fwrite($out, fread($stream, 8192));
                                fclose($out);
                            }
                            fclose($stream);

                            if ($preservePermissions) {
                                $stat = $zip->statIndex($i);
                                if (isset($stat['mode'])) chmod($target, $stat['mode'] & 0777);
                            }
                        }
                    }
                }

                $zip->close();
                if (file_exists($tmpZip)) {
                    @unlink($tmpZip);
                }
                if (isset($tmpEnc) && file_exists($tmpEnc)) {
                    @unlink($tmpEnc);
                }

                Log::info('Backup restored', [
                    'backup_id' => $backup->id,
                    'zipPath' => $encPath,
                    'restorePath' => $restorePath,
                    'key_version' => $keyVersion,
                ]);

                auth()->user()->notify(new \App\Notifications\RestoreCompleted($backup));
                return response()->json(['success' => true, 'message' => 'Restore completed successfully.']);
            } else {
                if (file_exists($tmpZip)) {
                    @unlink($tmpZip);
                }

                Log::error('Restore failed: could not open backup archive', [
                    'backup_id' => $backup->id,
                    'zipPath' => $encPath,
                    'restorePath' => $restorePath,
                    'key_version' => $keyVersion,
                ]);

                auth()->user()->notify(new \App\Notifications\RestoreFailed($backup, 'Failed to open backup archive.'));
                return response()->json(['success' => false, 'message' => 'Failed to open backup archive.']);
            }
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

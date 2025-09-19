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

            // 3️⃣ Remote backup
                try {
                    $linuxBackup = new \App\Services\LinuxBackupService();
                    $remoteFile  = $linuxBackup->uploadFile($encryptedFile, config('backup.remote_path'));

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
            

            // 4️⃣ Local backup
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
            

            // 5️⃣ Clean up temp encrypted file
            if (in_array($storageLocation, ['both', 'remote'])) {
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
            throw new \Exception('Encryption key version not found: ' . $version);
        }
        return base64_decode(preg_replace('/^base64:/', '', $keys[$version]));
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

    // Helper: Decrypt a file with AES-256-CBC
    private function decryptFile($inputPath, $outputPath, $keyVersion)
    {
        $key = $this->getKeyByVersion($keyVersion);
        $data = file_get_contents($inputPath);
        $iv = substr($data, 0, 16);
        $ciphertext = substr($data, 16);
        $plaintext = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($plaintext === false) {
            throw new \Exception('Decryption failed');
        }
        file_put_contents($outputPath, $plaintext);
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
        $backups = $query->orderByDesc('created_at')->get()->map(function($b) {
            return [
                'id' => $b->id,
                'created_at' => $b->created_at->toDateTimeString(),
                'backup_type' => $b->backup_type,
                'filename' => $b->filename,
                'size' => $b->size,
                'status' => $b->status,
            ];
        })->values();
        return response()->json(['success' => true, 'backups' => $backups]);
    }

    /**
     * Restore a backup zip to a given path.
     */
    public function restoreBackup(Request $request)
        {
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

            // Check if backup file exists
            if (!file_exists($encPath)) {
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
                $this->decryptFile($encPath, $tmpZip, $keyVersion);
            } catch (\Exception $e) {
                Log::error('Restore decryption failed', [
                    'backup_id' => $backup->id,
                    'zipPath' => $encPath,
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
                unlink($tmpZip);

                Log::info('Backup restored', [
                    'backup_id' => $backup->id,
                    'zipPath' => $encPath,
                    'restorePath' => $restorePath,
                    'key_version' => $keyVersion,
                ]);

                auth()->user()->notify(new \App\Notifications\RestoreCompleted($backup));
                return response()->json(['success' => true, 'message' => 'Restore completed successfully.']);
            } else {
                unlink($tmpZip);

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

            if (!file_exists($encPath)) {
                return response()->json(['success' => false, 'message' => 'Backup file not found.']);
            }

            // Calculate hash and check integrity
            $hash = hash_file('sha256', $encPath);
            $match = $backup->integrity_hash === $hash;

            if ($match) {
                $backup->integrity_verified_at = now();
                $backup->save();
            } else {
                // Notify user that integrity check failed
                auth()->user()->notify(new \App\Notifications\IntegrityCheckFailed($backup));
            }

            return response()->json([
                'success' => $match,
                'message' => $match
                    ? 'Backup is authentic and has not been tampered with.'
                    : 'WARNING: Backup file has been changed or corrupted since creation! Integrity check failed.',
                'expected_hash' => $backup->integrity_hash,
                'actual_hash' => $hash,
            ]);
        }

} 

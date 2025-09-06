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
            'source_directories' => 'required|array',
            'storage_location' => 'required|string',
            // destination_directory is only required for local
        ]);
        $sources = $request->input('source_directories');
        $storageLocation = $request->input('storage_location');
        $backupType = BackupConfiguration::first()->backup_type ?? 'full';
        if ($request->has('backup_type')) {
            $backupType = $request->input('backup_type');
        }
        if ($storageLocation === 'local') {
            $request->validate([
                'destination_directory' => 'required|string',
            ]);
            $destination = $request->input('destination_directory');
        } else {
            $destination = $storageLocation;
        }
        if ($storageLocation === 'local' && !File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }
        $compressionLevel = BackupConfiguration::first()->compression_level ?? 'none';
        if ($request->has('compression_level')) {
            $compressionLevel = $request->input('compression_level');
        }
        // Map compression level to ZipArchive constants
        $compressionMap = [
            'none' => \ZipArchive::CM_STORE,
            'low' => \ZipArchive::CM_DEFLATE,
            'medium' => \ZipArchive::CM_DEFLATE,
            'high' => \ZipArchive::CM_DEFLATE,
        ];
        $compressionOptions = [
            'none' => 0,
            'low' => 1,
            'medium' => 6,
            'high' => 9,
        ];
        $allSuccess = true;
        $errorMsg = null;
        $keyVersion = $this->getCurrentKeyVersion();
        foreach ($sources as $src) {
            if (File::isDirectory($src)) {
                $dirName = basename($src);
                $timestamp = date('Ymd_His');
                $tmpDir = sys_get_temp_dir();
                $zipFile = $tmpDir . DIRECTORY_SEPARATOR . $dirName . '_' . $backupType . '_' . $timestamp . '.zip';
                // Find the latest manifest in the destination
                $manifestPattern = $destination . DIRECTORY_SEPARATOR . $dirName . '_manifest_*.json.enc';
                $manifestFiles = glob($manifestPattern);
                $lastManifest = [];
                $latestManifest = null;
                if ($manifestFiles) {
                    // Sort by filename (timestamp in name)
                    usort($manifestFiles, function($a, $b) {
                        return strcmp($a, $b);
                    });
                    $latestManifest = end($manifestFiles);
                }
                // Decrypt latest manifest if it exists
                if ($latestManifest && File::exists($latestManifest)) {
                    $tmpManifest = $tmpDir . DIRECTORY_SEPARATOR . uniqid('manifest_', true) . '.json';
                    try {
                        $this->decryptFile($latestManifest, $tmpManifest, $keyVersion);
                        $lastManifest = json_decode(File::get($tmpManifest), true) ?: [];
                    } catch (\Exception $e) {
                        Log::warning('Failed to decrypt previous manifest, treating as full backup', [
                            'manifest' => $latestManifest,
                            'error' => $e->getMessage(),
                        ]);
                        $lastManifest = [];
                    }
                    if (File::exists($tmpManifest)) {
                        File::delete($tmpManifest);
                    }
                }
                $history = BackupHistory::create([
                    'user_id' => auth()->id(),
                    'source_directory' => $src,
                    'destination_directory' => $destination,
                    'filename' => basename($zipFile) . '.enc',
                    'status' => 'pending',
                    'started_at' => now(),
                    'backup_type' => $backupType,
                    'compression_level' => $compressionLevel,
                    'key_version' => $keyVersion,
                ]);
                try {
                    $zip = new \ZipArchive();
                    if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                        $newManifest = [];
                        $files = new \RecursiveIteratorIterator(
                            new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS),
                            \RecursiveIteratorIterator::SELF_FIRST
                        );
                        foreach ($files as $file) {
                            $filePath = $file->getRealPath();
                            $relativePath = substr($filePath, strlen($src) + 1);
                            $include = false;
                            if ($file->isDir()) {
                                $zip->addEmptyDir($relativePath);
                                continue;
                            }
                            $mtime = $file->getMTime();
                            $newManifest[$relativePath] = $mtime;
                            if ($backupType === 'full') {
                                $include = true;
                            } elseif ($backupType === 'incremental') {
                                $include = !isset($lastManifest[$relativePath]) || $mtime > $lastManifest[$relativePath];
                            } elseif ($backupType === 'differential') {
                                $include = !isset($lastManifest[$relativePath]) || $mtime > ($lastManifest[$relativePath] ?? 0);
                            }
                            if ($include) {
                                if ($compressionLevel === 'none') {
                                    $zip->addFile($filePath, $relativePath);
                                } else {
                                    $zip->addFile($filePath, $relativePath);
                                    $zip->setCompressionName($relativePath, $compressionMap[$compressionLevel], $compressionOptions[$compressionLevel]);
                                }
                            }
                        }
                        $zip->close();
                        // Write new manifest to temp file, then encrypt and store in destination with timestamp
                        $tmpManifestOut = $tmpDir . DIRECTORY_SEPARATOR . uniqid('manifest_out_', true) . '.json';
                        File::put($tmpManifestOut, json_encode($newManifest));
                        $manifestName = $dirName . '_manifest_' . $timestamp . '.json.enc';
                        $manifestPath = $destination . DIRECTORY_SEPARATOR . $manifestName;
                        $tmpManifestEnc = $tmpManifestOut . '.enc';
                        $this->encryptFile($tmpManifestOut, $tmpManifestEnc, $keyVersion);
                        File::move($tmpManifestEnc, $manifestPath);
                        File::delete($tmpManifestOut);
                        $size = File::size($zipFile);
                        $hash = hash_file('sha256', $zipFile);
                        // Encrypt the zip file in temp dir
                        $encryptedFile = $tmpDir . DIRECTORY_SEPARATOR . basename($zipFile) . '.enc';
                        $this->encryptFile($zipFile, $encryptedFile, $keyVersion);
                        File::delete($zipFile); // Remove unencrypted file
                        // Move encrypted file to destination
                        $finalEncryptedFile = $destination . DIRECTORY_SEPARATOR . basename($encryptedFile);
                        File::move($encryptedFile, $finalEncryptedFile);
                        $encSize = File::size($finalEncryptedFile);
                        $encHash = hash_file('sha256', $finalEncryptedFile);
                        $history->update([
                            'size' => $encSize,
                            'status' => 'completed',
                            'completed_at' => now(),
                            'integrity_hash' => $encHash,
                            'integrity_verified_at' => now(),
                            'filename' => basename($finalEncryptedFile),
                        ]);

                        // Notify the user of successful backup
                        auth()->user()->notify(new \App\Notifications\BackupCompleted($history));

                        Log::info('Backup completed (encrypted)', [
                            'source' => $src,
                            'destination' => $destination,
                            'filename' => basename($finalEncryptedFile),
                            'type' => $backupType,
                            'compression' => $compressionLevel,
                            'key_version' => $keyVersion,
                        ]);
                    } else {
                        throw new \Exception('Failed to create zip file.');
                    }
                } catch (\Exception $e) {
                    // Clean up temp files if they exist
                    if (isset($zipFile) && file_exists($zipFile)) {
                        File::delete($zipFile);
                    }
                    $encTemp = isset($encryptedFile) ? $encryptedFile : null;
                    if ($encTemp && file_exists($encTemp)) {
                        File::delete($encTemp);
                    }
                    if (isset($tmpManifestOut) && file_exists($tmpManifestOut)) {
                        File::delete($tmpManifestOut);
                    }
                    if (isset($tmpManifestEnc) && file_exists($tmpManifestEnc)) {
                        File::delete($tmpManifestEnc);
                    }
                    $history->update([
                        'status' => 'failed',
                        'completed_at' => now(),
                        'error_message' => $e->getMessage(),
                    ]);

                    // Notify the user of failed backup
                    auth()->user()->notify(new \App\Notifications\BackupFailed($history, $e->getMessage()));


                    Log::error('Backup failed', [
                        'source' => $src,
                        'destination' => $destination,
                        'filename' => isset($finalEncryptedFile) ? basename($finalEncryptedFile) : (isset($zipFile) ? basename($zipFile) : null),
                        'type' => $backupType,
                        'compression' => $compressionLevel,
                        'error' => $e->getMessage(),
                        'key_version' => $keyVersion,
                    ]);
                    $allSuccess = false;
                    $errorMsg = $e->getMessage();
                }
            }
        }
        if ($request->ajax() || $request->wantsJson()) {
            if ($allSuccess) {
                return response()->json(['success' => true]);
            } else {
                return response()->json(['success' => false, 'message' => $errorMsg ?: 'Backup failed.']);
            }
        }
        if ($allSuccess) {
            return redirect()->back()->with('status', 'Backup completed successfully!');
        } else {
            return redirect()->back()->withErrors(['backup' => 'Backup failed: ' . ($errorMsg ?: 'Unknown error')]);
        }
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

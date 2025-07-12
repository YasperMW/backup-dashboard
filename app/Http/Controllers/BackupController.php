<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
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
        foreach ($sources as $src) {
            if (File::isDirectory($src)) {
                $dirName = basename($src);
                $timestamp = date('Ymd_His');
                $zipFile = $destination . DIRECTORY_SEPARATOR . $dirName . '_' . $backupType . '_' . $timestamp . '.zip';
                $history = BackupHistory::create([
                    'source_directory' => $src,
                    'destination_directory' => $destination,
                    'filename' => basename($zipFile),
                    'status' => 'pending',
                    'started_at' => now(),
                    'backup_type' => $backupType,
                    'compression_level' => $compressionLevel,
                ]);
                try {
                    $zip = new \ZipArchive();
                    if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                        $manifestPath = $destination . DIRECTORY_SEPARATOR . $dirName . '_last_backup_manifest.json';
                        $lastManifest = File::exists($manifestPath) ? json_decode(File::get($manifestPath), true) : [];
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
                        File::put($manifestPath, json_encode($newManifest));
                        $size = File::size($zipFile);
                        $hash = hash_file('sha256', $zipFile);
                        $history->update([
                            'size' => $size,
                            'status' => 'completed',
                            'completed_at' => now(),
                            'integrity_hash' => $hash,
                            'integrity_verified_at' => now(),
                        ]);
                    } else {
                        throw new \Exception('Failed to create zip file.');
                    }
                } catch (\Exception $e) {
                    $history->update([
                        'status' => 'failed',
                        'completed_at' => now(),
                        'error_message' => $e->getMessage(),
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
        return redirect()->back()->with('status', $allSuccess ? 'Backup completed successfully!' : 'Backup failed.');
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
            \App\Models\BackupSchedule::create([
                'frequency' => $request->frequency,
                'time' => $request->time,
                'days_of_week' => $daysOfWeek,
                'source_directories' => $request->source_directories,
                'destination_directory' => $request->destination_directory,
                'enabled' => true,
                'retention_days' => $request->retention_days,
                'max_backups' => $request->max_backups,
            ]);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true]);
            }
            return redirect()->route('backup.management')->with('status', 'Backup schedule created successfully!');
        } catch (\Exception $e) {
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
} 
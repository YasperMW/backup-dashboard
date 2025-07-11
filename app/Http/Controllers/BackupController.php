<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Models\BackupSourceDirectory;
use App\Models\BackupDestinationDirectory;
use App\Models\BackupHistory;

class BackupController extends Controller
{
    // Show the backup management page
    public function showManagement()
    {
        $sourceDirectories = BackupSourceDirectory::pluck('path')->toArray();
        $destinationDirectories = BackupDestinationDirectory::pluck('path')->toArray();
        return view('backup.management', compact('sourceDirectories', 'destinationDirectories'));
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
            'destination_directory' => 'required|string',
        ]);
        $sources = $request->input('source_directories');
        $destination = $request->input('destination_directory');
        if (!File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }
        $allSuccess = true;
        $errorMsg = null;
        foreach ($sources as $src) {
            if (File::isDirectory($src)) {
                $dirName = basename($src);
                $timestamp = date('Ymd_His');
                $zipFile = $destination . DIRECTORY_SEPARATOR . $dirName . '_' . $timestamp . '.zip';
                $history = BackupHistory::create([
                    'source_directory' => $src,
                    'destination_directory' => $destination,
                    'filename' => basename($zipFile),
                    'status' => 'pending',
                    'started_at' => now(),
                ]);
                try {
                    $zip = new \ZipArchive();
                    if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                        $files = new \RecursiveIteratorIterator(
                            new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS),
                            \RecursiveIteratorIterator::SELF_FIRST
                        );
                        foreach ($files as $file) {
                            $filePath = $file->getRealPath();
                            $relativePath = substr($filePath, strlen($src) + 1);
                            if ($file->isDir()) {
                                $zip->addEmptyDir($relativePath);
                            } else {
                                $zip->addFile($filePath, $relativePath);
                            }
                        }
                        $zip->close();
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

    public function getBackupHistoryFragment()
    {
        return response()->view('backup.history-table');
    }
} 
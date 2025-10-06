<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\BackupJob;
use App\Models\BackupSourceDirectory;
use App\Models\BackupDestinationDirectory;
use App\Notifications\BackupStarted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Models\BackupHistory;

class BackupRequestController extends Controller
{
    /**
     * Handle the backup form submission
     */
    public function startBackup(Request $request)
    {
        // Set higher limits for large backups
        ini_set('max_execution_time', 3600); // 1 hour
        ini_set('upload_max_filesize', '4096M');
        ini_set('post_max_size', '4096M');
        ini_set('memory_limit', '4096M');
        
        // Validate the request
        $validated = $request->validate([
            'source_directories'    => 'required|array',
            'storage_location'      => 'nullable|string',
            'destination_directory' => 'nullable|string',
            'backup_type'          => 'nullable|string|in:full,incremental',
            'key_version'           => 'nullable|string',
        ]);
        
        // Get the first source directory (for now, we'll handle one at a time)
        $sourcePath = $validated['source_directories'][0] ?? null;
        
        if (!$sourcePath) {
            return $this->respond($request, false, 'Please select at least one source directory.');
        }

    /**
     * Queue a file existence check on the agent for the given backup history record
     */
    public function queueFileExistenceCheck(Request $request, int $historyId)
    {
        $history = BackupHistory::findOrFail($historyId);

        // Choose the most recently online agent
        $agentQuery = Agent::query()->where('status','online');
        if (Schema::hasColumn('agents','last_seen_at')) {
            $agentQuery->orderByDesc('last_seen_at');
        } elseif (Schema::hasColumn('agents','last_seen')) {
            $agentQuery->orderByDesc('last_seen');
        }
        $agent = $agentQuery->first();
        if (!$agent) {
            return response()->json(['success' => false, 'message' => 'No online agent available to perform file check.'], 422);
        }

        $dir = $history->destination_directory ?? '';
        $filename = $history->filename ?? '';
        if (!$dir || !$filename) {
            return response()->json(['success' => false, 'message' => 'History record missing destination or filename.'], 422);
        }

        $job = BackupJob::create([
            'agent_id' => $agent->id,
            'user_id' => auth()->id(),
            'name' => 'File Check - ' . $filename,
            'source_path' => $dir,
            'destination_path' => $dir,
            'backup_type' => 'full',
            'status' => 'pending',
            'options' => [
                'type' => 'file_check',
                'file' => [
                    'directory' => $dir,
                    'filename' => $filename,
                ],
            ],
            'started_at' => now(),
        ]);

        return response()->json(['success' => true, 'data' => ['job_id' => $job->id]]);
    }
        
        // Get the source directory record
        $source = BackupSourceDirectory::where('path', $sourcePath)->first();
        
        if (!$source) {
            return $this->respond($request, false, 'Selected source directory not found.');
        }
        
        // Get or create destination directory
        $destinationPath = $validated['destination_directory'] ?? storage_path('app/backups');
        $destination = BackupDestinationDirectory::firstOrCreate(
            ['path' => $destinationPath],
            ['description' => 'Auto-created destination']
        );
        
        // Find an available agent
        $agent = Agent::where('status', 'online')->first();
        
        if (!$agent) {
            return $this->respond($request, false, 'No available agents to process this backup.');
        }
        
        // Build mandatory encryption config from env (versioned keys)
        $algo = env('BACKUP_ENCRYPTION_ALGO', 'AES-256-CBC');
        $currentKeyVersion = strtolower((string) ($validated['key_version'] ?? env('BACKUP_KEY_CURRENT')));
        // Resolve key from env by version, e.g. BACKUP_KEY_V2 when key_version='v2'
        $envKeyName = 'BACKUP_KEY_' . strtoupper($currentKeyVersion);
        $selectedKey = env($envKeyName);

        if (!$selectedKey) {
            return $this->respond($request, false, "Encryption is required but the key for version '{$currentKeyVersion}' (env {$envKeyName}) is not set in .env");
        }

        // If the key is prefixed with base64:, remove the prefix and keep the base64 string as the password text.
        if (str_starts_with($selectedKey, 'base64:')) {
            $selectedKey = substr($selectedKey, 7);
        }

        // Create a new backup job
        $backupJob = BackupJob::create([
            'agent_id' => $agent->id,
            'user_id' => auth()->id(),
            'name' => 'Backup - ' . basename($sourcePath) . ' - ' . now()->format('Y-m-d H:i:s'),
            'source_path' => $source->path,
            'destination_path' => $destination->path,
            'backup_type' => $validated['backup_type'] ?? 'full',
            'status' => 'pending',
            'options' => [
                'storage_location' => $validated['storage_location'] ?? 'local',
                'encryption' => [
                    'enabled' => true,
                    'algorithm' => $algo,
                    // Use password (PBKDF2) mode by passing the base64 key string as the password text
                    'password' => $selectedKey,
                    'key_version' => $currentKeyVersion,
                ],
                // Remote target (used when storage_location is 'remote' or 'both')
                'remote' => [
                    'host' => env('BACKUP_LINUX_HOST'),
                    'user' => env('BACKUP_LINUX_USER'),
                    'pass' => env('BACKUP_LINUX_PASS'),
                    'path' => env('BACKUP_LINUX_PATH'),
                ],
            ],
            'started_at' => now(),
        ]);
        
        // Notify user
        $user = auth()->user();
        if ($user) {
            $user->notify(new BackupStarted($backupJob));
        }
        
        return $this->respond($request, true, 'Backup job has been queued and will start soon.', [
            'job_id' => $backupJob->id,
            'status' => $backupJob->status,
            'agent' => $agent->name,
        ]);
    }

    /**
     * Get backup job status (JSON when requested via AJAX)
     */
    public function getBackupStatus(Request $request, int $jobId)
    {
        $userId = auth()->id();
        $job = BackupJob::where('id', $jobId)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->firstOrFail();

        $data = [
            'id' => $job->id,
            'name' => $job->name,
            'status' => $job->status,
            'error' => $job->error,
            'files_processed' => (int) $job->files_processed,
            'size_processed' => (int) $job->size_processed,
            'backup_path' => $job->backup_path,
            'started_at' => optional($job->started_at)->toIso8601String(),
            'completed_at' => optional($job->completed_at)->toIso8601String(),
            // expose progress details (e.g., phase: pending/downloading/decrypting/extracting)
            'progress' => is_array($job->options) ? ($job->options['progress'] ?? null) : (data_get($job->options, 'progress')),
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'data' => $data]);
        }

        return back()->with('status', 'Job status fetched');
    }

    /**
     * Check if any agent is online for initiating backup/restore actions
     */
    public function checkAgentOnline(Request $request)
    {
        try {
            // Consider agent online if either:
            // - status = 'online', OR
            // - last_seen_at (or last_seen) is within the last 15 minutes
            $hasLastSeenAt = Schema::hasColumn('agents', 'last_seen_at');
            $hasLastSeen   = Schema::hasColumn('agents', 'last_seen');
            $recentCutoff  = now()->subMinutes(15);
            $query = Agent::query();
            $query->where('status', 'online');
            if ($hasLastSeenAt) {
                $query->orWhere('last_seen_at', '>=', $recentCutoff);
            } elseif ($hasLastSeen) {
                $query->orWhere('last_seen', '>=', $recentCutoff);
            }
            $online = $query->exists();
            // Debug log to help diagnose environment mismatches
            try {
                Log::debug('Agent online check', [
                    'online' => $online,
                    'status_online_count' => Agent::where('status','online')->count(),
                    'recent_last_seen_at_count' => $hasLastSeenAt ? Agent::where('last_seen_at','>=', $recentCutoff)->count() : null,
                    'recent_last_seen_count'    => (!$hasLastSeenAt && $hasLastSeen) ? Agent::where('last_seen','>=', $recentCutoff)->count() : null,
                ]);
            } catch (\Throwable $e) {}

            return response()->json([
                'success' => true,
                'data' => [ 'online' => (bool) $online ]
            ]);
        } catch (\Throwable $e) {
            Log::error('checkAgentOnline failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => true,
                'data' => [ 'online' => false ]
            ], 200);
        }
    }

    private function respond(Request $request, bool $success, string $message, array $data = [])
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => $success,
                'message' => $message,
                'data' => $data,
            ], $success ? 200 : 422);
        }
        
        return $success
            ? back()->with('status', $message)
            : back()->withErrors(['backup' => $message]);
    }
}

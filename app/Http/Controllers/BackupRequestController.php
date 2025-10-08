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
        
        // Get the source directory record
        $source = BackupSourceDirectory::where('path', $sourcePath)->first();
        
        if (!$source) {
            return $this->respond($request, false, 'Selected source directory not found.');
        }
        
        // Get or create destination directory
        // Treat empty string as not provided so we can fall back to default when remote-only is selected
        $destinationInput = $validated['destination_directory'] ?? null;
        $destinationPath = $destinationInput !== null && $destinationInput !== ''
            ? $destinationInput
            : storage_path('app/backups');
        $destination = BackupDestinationDirectory::firstOrCreate(
            ['path' => $destinationPath],
            ['description' => 'Auto-created destination']
        );
        
        // Find an available agent registered to the authenticated user
        $agent = Agent::where('status', 'online')
            ->where('user_id', auth()->id())
            ->first();
        
        if (!$agent) {
            return $this->respond($request, false, 'No available agents registered to your account are online.');
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
     * Queue a remote file existence check via the agent (uses agent Paramiko instead of PHP SFTP)
     */
    public function queueRemoteFileCheck(Request $request, int $historyId)
    {
        $history = \App\Models\BackupHistory::findOrFail($historyId);

        // Choose the most recently seen agent belonging to the authenticated user
        $agentQuery = Agent::query()->where('user_id', auth()->id());
        if (Schema::hasColumn('agents','last_seen_at')) {
            $agentQuery->orderByDesc('last_seen_at');
        } elseif (Schema::hasColumn('agents','last_seen')) {
            $agentQuery->orderByDesc('last_seen');
        } else {
            $agentQuery->where('status','online');
        }
        $agent = $agentQuery->first();
        if (!$agent) {
            return response()->json(['success' => false, 'message' => 'No agent registered to your account is available to perform remote check.'], 422);
        }

        $dir = $history->destination_directory ?? '';
        // Normalize to POSIX-style for SFTP
        $dir = str_replace('\\', '/', $dir);
        $dir = rtrim($dir, '/');
        $filename = $history->filename ?? '';
        if (!$dir) {
            return response()->json(['success' => false, 'message' => 'Destination directory missing on history record.'], 422);
        }
        if (!$filename) {
            return response()->json(['success' => false, 'message' => 'Backup filename missing on history record.'], 422);
        }

        // Pull remote credentials from config, but use the history's directory as path to be precise
        $remote = [
            'host' => config('backup.linux_host'),
            'user' => config('backup.linux_user'),
            'pass' => config('backup.linux_pass'),
            'path' => $history->destination_directory ?: (config('backup.remote_path') ?? ''),
        ];
        if (!$remote['host'] || !$remote['user']) {
            return response()->json(['success' => false, 'message' => 'Remote credentials not configured.'], 422);
        }

        $job = BackupJob::create([
            'agent_id' => $agent->id,
            'user_id' => auth()->id(),
            'name' => 'Remote File Check - ' . ($filename ?: basename($dir)),
            'source_path' => $dir,
            'destination_path' => $dir,
            'backup_type' => 'full',
            'status' => 'pending',
            'options' => [
                'type' => 'remote_file_check',
                'file' => [
                    'directory' => $dir,
                    'filename' => $filename,
                ],
                // Ensure the agent has exact directory context and the filename
                'remote' => array_merge($remote, ['filename' => $filename]),
            ],
            'started_at' => now(),
        ]);

        return response()->json(['success' => true, 'data' => ['job_id' => $job->id]]);
    }

    /**
     * Queue a file existence check on the agent for the given backup history record
     */
    public function queueFileExistenceCheck(Request $request, int $historyId)
    {
        $history = BackupHistory::findOrFail($historyId);

        // Choose the most recently online agent belonging to the authenticated user
        $agentQuery = Agent::query()->where('status','online')->where('user_id', auth()->id());
        if (Schema::hasColumn('agents','last_seen_at')) {
            $agentQuery->orderByDesc('last_seen_at');
        } elseif (Schema::hasColumn('agents','last_seen')) {
            $agentQuery->orderByDesc('last_seen');
        }
        $agent = $agentQuery->first();
        if (!$agent) {
            return response()->json(['success' => false, 'message' => 'No online agent registered to your account is available to perform file check.'], 422);
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

    /**
     * Check if any agent is online for initiating backup/restore actions
     */
    public function checkAgentOnline(Request $request)
    {
        try {
            // Cache-only approach: AgentTaskController@heartbeat writes
            //  - agent:{id}:last_seen_at (timestamp)
            //  - agents:active_ids (array of ids)
            $activeIds = \Cache::get('agents:active_ids', []);
            $now = time();
            $ttlSeconds = 5; // consider online if heartbeat within last 5s
            $online = false;
            foreach ($activeIds as $aid) {
                $ts = \Cache::get("agent:{$aid}:last_seen_at");
                if (is_numeric($ts) && ($now - (int)$ts) <= $ttlSeconds) {
                    $online = true;
                    break;
                }
            }
            return response()->json(['success' => true, 'data' => ['online' => $online]]);
        } catch (\Throwable $e) {
            Log::error('checkAgentOnline cache check failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => true, 'data' => ['online' => false]]);
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\BackupJob;
use App\Models\BackupSourceDirectory;
use App\Models\BackupDestinationDirectory;
use App\Notifications\BackupStarted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BackupRequestController extends Controller
{
    /**
     * Start a new backup job
     */
    public function startBackup(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'source_id' => 'required|exists:backup_source_directories,id',
            'destination_id' => 'required|exists:backup_destination_directories,id',
            'backup_type' => 'required|in:full,incremental',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'options' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get source and destination details
        $source = BackupSourceDirectory::findOrFail($request->source_id);
        $destination = BackupDestinationDirectory::findOrFail($request->destination_id);

        // Find an available agent (in a real app, you'd have agent selection logic)
        $agent = Agent::where('status', 'online')->first();
        
        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'No available agents to process this backup'
            ], 503);
        }

        // Create a new backup job
        $backupJob = BackupJob::create([
            'agent_id' => $agent->id,
            'user_id' => $user->id,
            'name' => $request->name ?? 'Backup ' . now()->format('Y-m-d H:i:s'),
            'description' => $request->description,
            'source_path' => $source->path,
            'destination_path' => $destination->path,
            'backup_type' => $request->backup_type,
            'status' => 'pending',
            'options' => $request->options ?? [],
            'started_at' => now(),
        ]);

        // Notify user that backup has started
        $user->notify(new BackupStarted($backupJob));

        return response()->json([
            'success' => true,
            'message' => 'Backup job created successfully',
            'data' => [
                'job_id' => $backupJob->id,
                'status' => $backupJob->status,
                'agent' => $agent->name
            ]
        ]);
    }

    /**
     * Get backup job status
     */
    public function getBackupStatus($jobId)
    {
        $user = Auth::user();
        
        $job = BackupJob::where('id', $jobId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $job->id,
                'name' => $job->name,
                'status' => $job->status,
                'progress' => $this->calculateProgress($job),
                'started_at' => $job->started_at,
                'completed_at' => $job->completed_at,
                'error' => $job->error,
                'files_processed' => $job->files_processed,
                'size_processed' => $job->size_processed,
                'backup_path' => $job->backup_path,
            ]
        ]);
    }

    /**
     * List user's backup jobs
     */
    public function listBackupJobs(Request $request)
    {
        $user = Auth::user();
        
        $jobs = BackupJob::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $jobs
        ]);
    }

    /**
     * Calculate backup progress percentage
     */
    private function calculateProgress(BackupJob $job)
    {
        if ($job->status === 'completed') {
            return 100;
        }

        if ($job->status === 'failed' || $job->status === 'cancelled') {
            return 0;
        }

        // In a real implementation, you might have a better way to calculate progress
        // For now, we'll just return a simple percentage based on files processed
        if ($job->total_files > 0) {
            return min(99, (int) (($job->files_processed / $job->total_files) * 100));
        }

        return 0;
    }
}

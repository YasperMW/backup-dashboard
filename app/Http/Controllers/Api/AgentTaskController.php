<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\BackupJob;
use App\Models\BackupHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class AgentTaskController extends Controller
{
    /**
     * Agent heartbeat: mark agent as online and update last_seen_at
     */
    public function heartbeat(Request $request)
    {
        $agent = $request->user('agent');
        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Update presence fields
        $agent->status = 'online';
        // Support either last_seen_at or last_seen schema
        if (\Schema::hasColumn('agents', 'last_seen_at')) {
            $agent->last_seen_at = now();
        } elseif (\Schema::hasColumn('agents', 'last_seen')) {
            $agent->last_seen = now();
        }
        $agent->save();

        // Write a cache heartbeat (no DB needed for availability checks)
        try {
            Cache::put("agent:{$agent->id}:last_seen_at", now()->timestamp, 300);
            // Track active agent IDs (simple array). Keep for 1 day.
            $key = 'agents:active_ids';
            $ids = Cache::get($key, []);
            if (!in_array($agent->id, $ids, true)) {
                $ids[] = $agent->id;
                Cache::put($key, $ids, 86400);
            }
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Heartbeat received',
            'data' => [
                'id' => $agent->id,
                'status' => $agent->status,
                'last_seen_at' => method_exists($agent, 'getAttribute') ? ($agent->getAttribute('last_seen_at') ?? $agent->getAttribute('last_seen')) : null,
            ]
        ]);
    }

    /**
     * Get pending tasks for the agent
     */
    public function getTasks(Request $request)
    {
        $agent = $request->user('agent');
        
        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Get pending tasks for this agent
        $tasks = BackupJob::where('agent_id', $agent->id)
            ->where('status', 'pending')
            ->get()
            ->map(function($job) {
                $type = data_get($job->options, 'type');
                if (!$type) {
                    $type = data_get($job->options, 'action', 'backup');
                }
                return [
                    'id' => $job->id,
                    'type' => $type,
                    'name' => $job->name,
                    'source_path' => $job->source_path,
                    'destination_path' => $job->destination_path,
                    'backup_type' => $job->backup_type,
                    'options' => $job->options ?? [],
                    'created_at' => $job->created_at->toIso8601String(),
                    'updated_at' => $job->updated_at->toIso8601String()
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $tasks
        ]);
    }

    /**
     * Update task status
     */
    public function updateTaskStatus(Request $request, $taskId)
    {
        try {
            $agent = $request->user('agent');
            
            if (!$agent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,in_progress,completed,failed',
                'error' => 'nullable|string',
                'files_processed' => 'nullable|integer',
                'size_processed' => 'nullable|integer',
                'details' => 'nullable|array',
                // final artifact metadata (optional but used when completed)
                'backup_path' => 'nullable|string',
                'size' => 'nullable|integer',
                'checksum' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $task = BackupJob::where('id', $taskId)
                ->where('agent_id', $agent->id)
                ->firstOrFail();

            $task->status = $request->input('status');
            $task->error = $request->input('error');
            if ($request->filled('files_processed')) {
                $task->files_processed = (int) $request->input('files_processed');
            }
            if ($request->filled('size_processed')) {
                $task->size_processed = (int) $request->input('size_processed');
            }
            // Persist final artifact info when provided
            if ($request->filled('backup_path')) {
                $task->backup_path = $request->input('backup_path');
            }
            if ($request->filled('size')) {
                $task->size = (int) $request->input('size');
            }
            if ($request->filled('checksum')) {
                $task->checksum = $request->input('checksum');
            }
            // Persist progress details into options for UI polling
            if (is_array($request->input('details'))) {
                $opts = is_array($task->options) ? $task->options : [];
                $opts['progress'] = $request->input('details');
                $task->options = $opts;
            }
            $task->completed_at = in_array($request->input('status'), ['completed','failed']) ? now() : null;
            $task->save();

            // If the task is done, write to backup_histories
            if (in_array($task->status, ['completed','failed'])) {
                $options = is_array($task->options) ? $task->options : [];
                $enc = $options['encryption'] ?? [];
                $compression = $options['compression_level'] ?? 'none';
                // Prefer remote artifact details if provided by agent
                $remotePath = $request->input('remote_path');
                $storageLocation = $options['storage_location'] ?? 'local';

                // Helper to build history payload
                $buildHistory = function(string $destDir, string $destType, ?string $fileName) use ($task, $compression, $enc, $request) {
                    return [
                        'source_directory' => $task->source_path,
                        'destination_directory' => $destDir,
                        'destination_type' => $destType,
                        'filename' => $fileName,
                        'size' => $task->size ?? $request->input('size'),
                        'status' => $task->status,
                        'backup_type' => $task->backup_type,
                        'compression_level' => $compression,
                        'key_version' => $enc['key_version'] ?? null,
                        'started_at' => $task->started_at,
                        'completed_at' => $task->completed_at,
                        'integrity_hash' => $task->checksum ?? $request->input('checksum'),
                        'integrity_verified_at' => null,
                        'error_message' => $task->status === 'failed' ? ($task->error ?: $request->input('error')) : null,
                    ];
                };

                try {
                    if ($storageLocation === 'both') {
                        // Create local entry if we have a local artifact
                        if ($task->backup_path) {
                            $localDir = $task->destination_path;
                            $localFile = basename($task->backup_path);
                            BackupHistory::create($buildHistory($localDir, 'local', $localFile));
                        }
                        // Create remote entry if remote_path provided
                        if (is_string($remotePath) && $remotePath !== '') {
                            $remoteDir = str_replace('\\', '/', dirname($remotePath));
                            $remoteFile = basename($remotePath);
                            BackupHistory::create($buildHistory($remoteDir, 'remote', $remoteFile));
                        }
                    } else {
                        // Single entry (local or remote)
                        if (is_string($remotePath) && $remotePath !== '' && $storageLocation === 'remote') {
                            $destDir = str_replace('\\', '/', dirname($remotePath));
                            $fileName = basename($remotePath);
                            BackupHistory::create($buildHistory($destDir, 'remote', $fileName));
                        } else {
                            $destDir = $task->destination_path;
                            $fileName = $task->backup_path ? basename($task->backup_path) : null;
                            $destType = $storageLocation;
                            BackupHistory::create($buildHistory($destDir, $destType, $fileName));
                        }
                    }
                } catch (\Throwable $ex) {
                    \Log::error('Failed to write backup history', [
                        'taskId' => $taskId,
                        'error' => $ex->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Task status updated',
                'data' => [
                    'id' => $task->id,
                    'status' => $task->status,
                    'files_processed' => $task->files_processed,
                    'size_processed' => $task->size_processed,
                    'completed_at' => $task->completed_at,
                ]
            ]);
        } catch (\Throwable $e) {
            \Log::error('Agent status update failed', [
                'taskId' => $taskId,
                'agentId' => optional($request->user('agent'))->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Server error updating task status',
            ], 500);
        }
    }

    /**
     * Upload backup file
     */
    public function uploadBackup(Request $request)
    {
        $agent = $request->user('agent');
        
        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:backup_jobs,id',
            'file' => 'required|file',
            'checksum' => 'required|string',
            'size' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $task = BackupJob::where('id', $request->task_id)
            ->where('agent_id', $agent->id)
            ->firstOrFail();

        // Store the uploaded file
        $path = $request->file('file')->store('backups/' . $task->id, 'local');

        // Update task with file information
        $task->backup_path = $path;
        $task->checksum = $request->checksum;
        $task->size = $request->size;
        $task->save();

        return response()->json([
            'success' => true,
            'message' => 'Backup uploaded successfully',
            'path' => $path
        ]);
    }

    /**
     * Get agent information
     */
    public function getAgentInfo(Request $request)
    {
        $agent = $request->user('agent');
        
        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $agent->id,
                'name' => $agent->name,
                'hostname' => $agent->hostname,
                'os' => $agent->os,
                'version' => $agent->version,
                'last_seen' => $agent->last_seen,
                'status' => $agent->status,
                'created_at' => $agent->created_at->toIso8601String(),
                'updated_at' => $agent->updated_at->toIso8601String()
            ]
        ]);
    }
}

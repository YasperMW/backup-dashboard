<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\BackupJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

class AgentController extends Controller
{
    /**
     * Register a new agent
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'hostname' => 'required|string|max:255',
            'os' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:50',
            'capabilities' => 'nullable|array',
        ]);

        // Create a new agent
        $agent = new Agent([
            'name' => $request->name,
            'hostname' => $request->hostname,
            'os' => $request->os,
            'version' => $request->version,
            'capabilities' => $request->capabilities,
            'token' => Agent::generateToken(),
            'ip_address' => $request->ip(),
            'user_id' => Auth::id(),
        ]);

        $agent->save();

        return response()->json([
            'success' => true,
            'agent' => $agent->makeVisible('token'),
        ]);
    }

    /**
     * Get pending backup tasks for the agent
     */
    public function getTasks(Request $request)
    {
        $agent = $request->user('agent');
        
        // Mark agent as online
        $agent->markAsOnline();

        // Get pending tasks for this agent
        $tasks = BackupJob::with('user')
            ->where('agent_id', $agent->id)
            ->where('status', BackupJob::STATUS_PENDING)
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'name' => $task->name,
                    'description' => $task->description,
                    'source_path' => $task->source_path,
                    'destination_path' => $task->destination_path,
                    'type' => $task->type,
                    'options' => $task->options,
                    'created_at' => $task->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'tasks' => $tasks,
        ]);
    }

    /**
     * Update task status
     */
    public function updateTaskStatus(Request $request, $taskId)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,failed,cancelled',
            'error' => 'nullable|string',
            'files_processed' => 'nullable|integer',
            'size_processed' => 'nullable|integer',
        ]);

        $agent = $request->user('agent');
        $task = BackupJob::where('agent_id', $agent->id)->findOrFail($taskId);

        switch ($request->status) {
            case 'in_progress':
                $task->markAsInProgress();
                break;
            case 'completed':
                $task->markAsCompleted([
                    'files_processed' => $request->files_processed,
                    'size_processed' => $request->size_processed,
                ]);
                break;
            case 'failed':
                $task->markAsFailed($request->error);
                break;
            default:
                $task->update(['status' => $request->status]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Upload backup file
     */
    public function uploadBackup(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:backup_jobs,id',
            'backup' => 'required|file|max:102400', // 100MB max
        ]);

        $agent = $request->user('agent');
        $task = BackupJob::where('agent_id', $agent->id)->findOrFail($request->task_id);

        // Store the uploaded file
        $path = $request->file('backup')->store('backups/' . date('Y/m/d'));
        
        // Update task with file path
        $task->update([
            'destination_path' => $path,
        ]);

        return response()->json([
            'success' => true,
            'path' => $path,
        ]);
    }

    /**
     * Get agent details
     */
    public function getAgentDetails(Request $request)
    {
        $agent = $request->user('agent');
        return response()->json([
            'success' => true,
            'agent' => $agent->makeHidden(['token']),
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

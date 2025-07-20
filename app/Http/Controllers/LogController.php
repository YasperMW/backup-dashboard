<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BackupHistory;
use Illuminate\Support\Facades\Auth;
use App\Models\LoginLog;

class LogController extends Controller
{
    public function index(Request $request)
    {
        // Fetch login/logout logs
        $loginLogs = LoginLog::query();
        // Filters for login logs
        if ($request->filled('start_date')) {
            $loginLogs->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $loginLogs->whereDate('created_at', '<=', $request->end_date);
        }
        if ($request->filled('type')) {
            $loginLogs->where('type', $request->type);
        }
        if ($request->filled('severity')) {
            // No severity for login logs, skip
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $loginLogs->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }
        $loginLogs = $loginLogs->get()->map(function($log) {
            return [
                'timestamp' => $log->created_at,
                'type' => ucfirst($log->type ?? 'login'),
                'severity' => $log->status === 'failed' ? 'Error' : 'Info',
                'message' => $log->status === 'failed' ? 'Login/Logout failed' : 'Login/Logout successful',
                'source' => $log->ip_address,
                'user' => $log->email,
                'log_type' => 'login',
            ];
        });

        // Fetch backup logs
        $backupLogs = BackupHistory::query();
        if ($request->filled('start_date')) {
            $backupLogs->whereDate('started_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $backupLogs->whereDate('started_at', '<=', $request->end_date);
        }
        if ($request->filled('type')) {
            // No type field for backup logs, skip
        }
        if ($request->filled('severity')) {
            $backupLogs->where('status', $request->severity);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $backupLogs->where(function ($q) use ($search) {
                $q->where('error_message', 'like', "%{$search}%")
                  ->orWhere('filename', 'like', "%{$search}%")
                  ->orWhere('source_directory', 'like', "%{$search}%");
            });
        }
        $backupLogs = $backupLogs->get()->map(function($log) {
            return [
                'timestamp' => $log->started_at ?? $log->created_at,
                'type' => 'Backup',
                'severity' => ucfirst($log->status),
                'message' => $log->error_message ?? 'Backup completed successfully.',
                'source' => $log->source_directory,
                'user' => $log->user_id,
                'log_type' => 'backup',
            ];
        });

        // Merge and sort logs by timestamp descending
        $logs = $loginLogs->merge($backupLogs)->sortByDesc('timestamp')->values();

        return view('logs.logs', ['logs' => $logs]);
    }

}

//in short, this needs to be reviewed


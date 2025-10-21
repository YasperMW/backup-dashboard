<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LoginLog;
use App\Models\BackupHistory;
use App\Models\FailedJob;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $logs = collect();

        $isAdmin = auth()->check() && auth()->user()->role === 'admin';

        // LOGIN LOGS (admins only)
        if ($isAdmin) {
            $loginLogs = LoginLog::when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
                                 ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
                                 ->get()
                                 ->map(fn($log) => [
                                     'timestamp' => $log->created_at,
                                     'type'      => ucfirst($log->type ?? 'login'),
                                     'severity'  => $log->status === 'failed' ? 'error' : 'info',
                                     'message'   => $log->status === 'failed' ? 'Login failed' : 'Login successful',
                                     'source'    => $log->ip_address,
                                     'user'      => $log->email,
                                     'log_type'  => 'login',
                                 ]);

            $logs = $logs->merge($loginLogs);
        }

        
        // BACKUP LOGS
        
        $backupLogs = BackupHistory::when(!$isAdmin, fn($q) => $q->where('user_id', auth()->id()))
                                   ->when($request->start_date, fn($q) => $q->whereDate('started_at', '>=', $request->start_date))
                                   ->when($request->end_date, fn($q) => $q->whereDate('started_at', '<=', $request->end_date))
                                   ->get()
                                   ->map(fn($log) => [
                                       'timestamp' => $log->started_at,
                                       'type'      => 'Backup',
                                       'severity'  => match(strtolower($log->status)) {
                                           'failed' => 'error',
                                           'completed' => 'info',
                                           'pending' => 'warning',
                                           default => 'info',
                                       },
                                       'message'   => $log->error_message ?? 'Backup completed successfully',
                                       'source'    => $log->source_directory,
                                       'user'      => 'N/A',
                                       'log_type'  => 'backup',
                                   ]);

        $logs = $logs->merge($backupLogs);

        // FAILED JOBS (admins only)
        if ($isAdmin) {
            $failedJobs = FailedJob::when($request->start_date, fn($q) => $q->whereDate('failed_at', '>=', $request->start_date))
                                   ->when($request->end_date, fn($q) => $q->whereDate('failed_at', '<=', $request->end_date))
                                   ->get()
                                   ->map(fn($log) => [
                                       'timestamp' => $log->failed_at,
                                       'type'      => 'Failed Job',
                                       'severity'  => 'error',
                                       'message'   => $log->exception,
                                       'source'    => $log->connection,
                                       'user'      => 'N/A',
                                       'log_type'  => 'failed_job',
                                   ]);

            $logs = $logs->merge($failedJobs);
        }

        
        // KEYWORD SEARCH
        
        if ($request->filled('search')) {
            $keyword = strtolower($request->search);
            $logs = $logs->filter(fn($log) =>
                str_contains(strtolower($log['message']), $keyword) ||
                str_contains(strtolower($log['user']), $keyword) ||
                str_contains(strtolower($log['source']), $keyword)
            );
        }

        
        // TYPE FILTER
        
        if ($request->filled('type') && $request->type !== 'all') {
            $logs = $logs->where('log_type', $request->type);
        }

        
        // SEVERITY FILTER
        
        if ($request->filled('severity') && $request->severity !== 'all') {
            $logs = $logs->where('severity', strtolower($request->severity));
        }

        
        // SORT DESCENDING
        
        $logs = $logs->sortByDesc('timestamp')->values();

        return view('logs.logs', compact('logs'));
    }
}

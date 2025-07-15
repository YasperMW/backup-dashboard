<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class LogsController extends Controller
{
    public function index()
    {
        return view('logs.logs');
    }

    /**
     * Fetch logs as JSON for AJAX table (with filters)
     */
    public function fetchLogs(Request $request)
    {
        $file = storage_path('logs/laravel.log');
        if (!file_exists($file)) {
            return response()->json(['success' => true, 'logs' => []]);
        }
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = [];
        foreach ($lines as $i => $line) {
            // Try to parse standard Laravel log format: [YYYY-MM-DD HH:MM:SS] env.level: message
            if (preg_match('/^\[(.*?)\] (\w+)\.(\w+): (.*)$/', $line, $m)) {
                $timestamp = $m[1];
                $type = $m[2];
                $severity = ucfirst($m[3]);
                $message = $m[4];
                $source = Str::contains($message, 'Backup') ? 'Backup Service' : (Str::contains($message, 'Auth') ? 'Auth Service' : 'System');
                $logs[] = [
                    'id' => $i,
                    'timestamp' => $timestamp,
                    'type' => $type,
                    'severity' => $severity,
                    'message' => $message,
                    'source' => $source,
                    'line' => $i,
                ];
            }
        }
        // Filtering
        $from = $request->input('from');
        $to = $request->input('to');
        $logSource = $request->input('source');
        $severity = $request->input('severity');
        $search = $request->input('search');
        $logs = collect($logs)->filter(function($log) use ($from, $to, $logSource, $severity, $search) {
            if ($from && $log['timestamp'] < $from) return false;
            if ($to && $log['timestamp'] > $to) return false;
            if ($logSource && $logSource !== 'all' && $log['source'] !== $logSource) return false;
            if ($severity && strtolower($log['severity']) !== strtolower($severity) && $severity !== 'all') return false;
            if ($search && !Str::contains(strtolower($log['message']), strtolower($search))) return false;
            return true;
        })->values()->all();
        return response()->json(['success' => true, 'logs' => $logs]);
    }

    /**
     * Download/export logs as a file
     */
    public function export()
    {
        $file = storage_path('logs/laravel.log');
        if (!file_exists($file)) {
            return abort(404);
        }
        return response()->download($file, 'system-logs.txt');
    }

    /**
     * Clear the log file
     */
    public function clear()
    {
        $file = storage_path('logs/laravel.log');
        file_put_contents($file, '');
        return response()->json(['success' => true]);
    }

    /**
     * Get details for a specific log entry (by line number)
     */
    public function details(Request $request)
    {
        $line = $request->input('line');
        $file = storage_path('logs/laravel.log');
        if (!file_exists($file)) {
            return response()->json(['success' => false, 'message' => 'Log file not found.']);
        }
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!isset($lines[$line])) {
            return response()->json(['success' => false, 'message' => 'Log entry not found.']);
        }
        $entry = $lines[$line];
        // Try to parse stack trace if present in next lines
        $stack = '';
        for ($i = $line + 1; $i < count($lines); $i++) {
            if (preg_match('/^\[.*?\]/', $lines[$i])) break;
            $stack .= $lines[$i] . "\n";
        }
        return response()->json(['success' => true, 'entry' => $entry, 'stack' => trim($stack)]);
    }
} 
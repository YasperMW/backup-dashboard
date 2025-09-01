<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BackupHistory;

class DashboardController extends Controller
{
    public function index()
    {
        // Get backup statistics
        $totalBackups = \App\Models\BackupHistory::count();
        $successfulBackups = \App\Models\BackupHistory::where('status', 'completed')->count();
        $failedBackups = \App\Models\BackupHistory::where('status', 'failed')->count();
        $storageUsed = \App\Models\BackupHistory::sum('size'); // in bytes
        $storageUsedFormatted = $this->formatBytes($storageUsed);

        // --- Chart Data: Backup History (all time, by month) ---
        $first = \App\Models\BackupHistory::orderBy('created_at')->first();
        $last = \App\Models\BackupHistory::orderByDesc('created_at')->first();
        if ($first && $last) {
            $start = \Carbon\Carbon::parse($first->created_at)->startOfMonth();
            $end = \Carbon\Carbon::parse($last->created_at)->startOfMonth();
            $months = collect();
            for ($date = $start->copy(); $date <= $end; $date->addMonth()) {
                $months->push($date->format('Y-m'));
            }
        } else {
            $months = collect();
        }
        $labels = $months->map(function($m) {
            return date('M Y', strtotime($m.'-01'));
        });
        $successData = $months->map(function($m) {
            return \App\Models\BackupHistory::where('status', 'completed')
                ->whereYear('created_at', substr($m, 0, 4))
                ->whereMonth('created_at', substr($m, 5, 2))
                ->count();
        });
        $failedData = $months->map(function($m) {
            return \App\Models\BackupHistory::where('status', 'failed')
                ->whereYear('created_at', substr($m, 0, 4))
                ->whereMonth('created_at', substr($m, 5, 2))
                ->count();
        });

        // --- Chart Data: Storage Usage ---
        $quotaBytes = 1024 * 1024 * 1024 * 1024; // 1 TB default
        $used = $storageUsed;
        $free = max($quotaBytes - $used, 0);
        $storageChartData = [
            'used' => round($used / (1024*1024*1024), 2), // in GB
            'free' => round($free / (1024*1024*1024), 2), // in GB
        ];

        // --- Chart Data: Backup Size Trend (last 6 months) ---
        $sizeData = $months->map(function($m) {
            return \App\Models\BackupHistory::whereYear('created_at', substr($m, 0, 4))
                ->whereMonth('created_at', substr($m, 5, 2))
                ->sum('size') / (1024*1024*1024); // in GB
        });

        // --- Chart Data: Backup Type Distribution ---
        $typeLabels = ['Full', 'Incremental', 'Differential'];
        $typeKeys = ['full', 'incremental', 'differential'];
        $typeCounts = collect($typeKeys)->map(function($type) {
            return \App\Models\BackupHistory::where('backup_type', $type)->count();
        });

        // --- Chart Data: Backup Status Distribution ---
        $statusLabels = ['Completed', 'Failed', 'Pending'];
        $statusKeys = ['completed', 'failed', 'pending'];
        $statusCounts = collect($statusKeys)->map(function($status) {
            return \App\Models\BackupHistory::where('status', $status)->count();
        });

        return view('dashboard', [
            'totalBackups' => $totalBackups,
            'successfulBackups' => $successfulBackups,
            'failedBackups' => $failedBackups,
            'storageUsed' => $storageUsedFormatted,
            'chartLabels' => $labels,
            'chartSuccessData' => $successData,
            'chartFailedData' => $failedData,
            'storageChartData' => $storageChartData,
            'sizeTrendData' => $sizeData,
            'typeLabels' => $typeLabels,
            'typeCounts' => $typeCounts,
            'statusLabels' => $statusLabels,
            'statusCounts' => $statusCounts,
        ]);
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
} 

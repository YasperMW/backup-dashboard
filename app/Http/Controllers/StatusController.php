<?php

namespace App\Http\Controllers;

use App\Services\LinuxBackupService;
use Illuminate\Http\JsonResponse;

class StatusController extends Controller
{
    public function __construct(private LinuxBackupService $linux)
    {
    }

    /**
     * Lightweight health check for remote backup host reachability.
     */
    public function remoteHost(): JsonResponse
    {
        $online = $this->linux->isReachable(15);
        return response()->json([
            'online' => $online,
            'host' => config('backup.linux_host'),
            'port' => 22,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Toggle the manual offline state using server-side session.
     */
    public function toggleOffline(): JsonResponse
    {
        $currentState = session('manual_offline', false);
        $newState = !$currentState;
        
        session(['manual_offline' => $newState]);
        
        return response()->json([
            'success' => true,
            'offline' => $newState,
            'message' => $newState ? 'System set to offline mode' : 'System set to online mode'
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use Illuminate\Http\Request;

class LoginLogController extends Controller
{
    /**
     * Show the login logs page.
     */
    public function index()
    {
        return view('logs.login-logs');
    }

    /**
     * Fetch login logs for AJAX table with filters.
     */
    public function fetch(Request $request)
    {
        $query = LoginLog::query();
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('type') && $request->input('type') !== 'all') {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->input('email') . '%');
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }
        $logs = $query->orderByDesc('created_at')->get();
        return response()->json(['success' => true, 'logs' => $logs]);
    }
} 
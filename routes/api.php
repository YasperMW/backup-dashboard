<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\BackupRequestController;
use App\Http\Controllers\Api\AgentTaskController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/login', [LoginController::class, 'login']);

// Protected routes
// User authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // User info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Backup management
    Route::prefix('backup')->group(function () {
        Route::post('/start', [BackupRequestController::class, 'startBackup']);
        Route::get('/status/{jobId}', [BackupRequestController::class, 'getBackupStatus']);
        Route::get('/jobs', [BackupRequestController::class, 'listBackupJobs']);
    });
});


// Agent API Routes
Route::prefix('agent')->group(function () {
    // Public routes (no auth required)
    Route::post('/register', [AgentController::class, 'register'])->middleware('auth:sanctum');
    
    // Protected routes (require agent token via custom middleware class)
    Route::middleware([\App\Http\Middleware\AuthenticateAgent::class])->group(function () {
        // Agent information
        Route::get('/me', [AgentTaskController::class, 'getAgentInfo']);
        // Agent heartbeat
        Route::post('/heartbeat', [AgentTaskController::class, 'heartbeat']);
        
        // Backup tasks
        Route::get('/tasks', [AgentTaskController::class, 'getTasks']);
        Route::post('/tasks/{taskId}/status', [AgentTaskController::class, 'updateTaskStatus']);
        Route::post('/backup/upload', [AgentTaskController::class, 'uploadBackup']);
    });
});

<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

Route::get('/test-password-reset', function (Request $request) {
    // Test database connection
    try {
        DB::connection()->getPdo();
        Log::info('Database connection successful');
    } catch (\Exception $e) {
        Log::error('Database connection failed', ['error' => $e->getMessage()]);
        return 'Database connection failed: ' . $e->getMessage();
    }

    // Test user lookup
    $email = 'yankhochisale4@gmail.com';
    $user = \App\Models\User::where('email', $email)->first();
    
    if (!$user) {
        Log::error('User not found', ['email' => $email]);
        return 'User not found';
    }
    
    Log::info('User found', ['user_id' => $user->id, 'email' => $user->email]);
    
    // Test password update
    try {
        $newPassword = 'test1234';
        $user->password = \Illuminate\Support\Facades\Hash::make($newPassword);
        $saved = $user->save();
        
        if ($saved) {
            Log::info('Password updated successfully', ['user_id' => $user->id]);
            return 'Password updated successfully';
        } else {
            Log::error('Failed to save user', ['user_id' => $user->id]);
            return 'Failed to save user';
        }
    } catch (\Exception $e) {
        Log::error('Error updating password', [
            'user_id' => $user->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return 'Error: ' . $e->getMessage();
    }
});

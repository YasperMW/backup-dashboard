<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class AuthenticateAgent
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            throw new AuthenticationException('No authentication token provided.', ['agent']);
        }

        // Try to find agent with hashed token
        $agent = \App\Models\Agent::where('token', hash('sha256', $token))->first();

        // If not found with hashed token, try with plain text (for backward compatibility)
        if (!$agent) {
            $agent = \App\Models\Agent::where('token', $token)->first();
            
            // If found with plain text, update to hashed version
            if ($agent) {
                $agent->token = hash('sha256', $token);
                $agent->save();
            }
        }

        if (!$agent) {
            throw new AuthenticationException('Invalid authentication token.', ['agent']);
        }

        // Mark agent as active
        $agent->markAsOnline();
        
        // Add agent to request for controller access
        $request->merge(['agent' => $agent]);
        
        // Set the authenticated user for the request
        Auth::guard('agent')->setUser($agent);

        return $next($request);
    }
}

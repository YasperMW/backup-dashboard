<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class UserAgentController extends Controller
{
    /**
     * Return the logged-in user's registered agents as JSON.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $agents = $user->agents()
            ->latest('created_at')
            ->get(['id', 'name', 'hostname', 'os', 'status', 'version', 'last_seen_at', 'created_at']);

        return response()->json([
            'success' => true,
            'agents' => $agents,
        ]);
    }

    /**
     * Soft-delete an agent belonging to the logged-in user.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $agent = $user->agents()->where('id', $id)->firstOrFail();
        $agent->delete();

        return response()->json([
            'success' => true,
            'message' => 'Agent deleted successfully',
        ]);
    }
}

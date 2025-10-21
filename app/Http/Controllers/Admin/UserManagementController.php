<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::orderBy('id')
            ->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function updateRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', 'in:user,admin'],
        ]);

        $user->role = $data['role'];
        $user->save();

        return redirect()->route('admin.users.index')->with('status', 'Role updated for '.$user->email);
    }

    public function destroy(Request $request, User $user)
    {
        if (Auth::id() === $user->id) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User deleted: '.$user->email);
    }
}

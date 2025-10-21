@extends('layouts.dashboard')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-semibold text-gray-800 mb-4">User Management</h1>

    @if (session('status'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-3 rounded bg-red-100 text-red-800">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow-md p-4">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left">Number</th>
                        <th class="px-4 py-2 text-left">First Name</th>
                        <th class="px-4 py-2 text-left">Last Name</th>
                        <th class="px-4 py-2 text-left">Email</th>
                        <th class="px-4 py-2 text-left">Verified</th>
                        <th class="px-4 py-2 text-left">Role</th>
                        <th class="px-4 py-2 text-left">Created</th>
                        <th class="px-4 py-2 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($users as $user)
                        <tr class="text-gray-800">
                            <td class="px-4 py-2">{{ $user->id }}</td>
                            <td class="px-4 py-2">{{ $user->firstname }}</td>
                            <td class="px-4 py-2">{{ $user->lastname }}</td>
                            <td class="px-4 py-2">{{ $user->email }}</td>
                            <td class="px-4 py-2">{{ $user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i') : '—' }}</td>
                            <td class="px-4 py-2">
                                <form action="{{ route('admin.users.updateRole', $user) }}" method="POST" class="flex items-center space-x-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role" class="border rounded px-2 py-1 text-sm">
                                        <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>user</option>
                                        <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>admin</option>
                                    </select>
                                    <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded text-xs">Save</button>
                                </form>
                            </td>
                            <td class="px-4 py-2">{{ $user->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-2">
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Delete user {{ $user->email }}? This cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded text-xs" {{ auth()->id() === $user->id ? 'disabled' : '' }}>Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-6 text-center text-gray-500">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $users->links() }}</div>
    </div>
</div>
@endsection

@extends('layouts.dashboard')

@section('content')
<div class="max-w-3xl mx-auto bg-white shadow-lg rounded-2xl p-6 mt-8 border border-gray-200">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">🔔 Notifications</h2>

        {{-- Mark All as Read --}}
        <form action="{{ route('notifications.markAllAsRead') }}" method="POST">
            @csrf
            <button 
                type="submit" 
                class="px-3 py-1 text-sm font-medium text-red-600 border border-red-300 rounded-lg hover:bg-red-50 transition">
                Mark all as read
            </button>
        </form>
    </div>

    {{-- Notifications List --}}
    <ul class="space-y-3">
        @forelse($notifications as $notification)
            @php
                // You can extend this to check for "type" in $notification->data if available
                $isUnread = $notification->read_at === null;
            @endphp

            <li class="p-4 rounded-lg shadow-sm transition 
                @if($isUnread)
                    border-l-4 border-blue-500 bg-blue-50/40 
                @else
                    border border-gray-200 hover:bg-gray-50
                @endif">
                
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-semibold text-gray-900">
                            {{ $notification->data['title'] ?? 'No Title' }}
                        </p>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ $notification->data['message'] ?? 'No message' }}
                        </p>
                        <span class="text-xs text-gray-400 mt-2 block">
                            {{ $notification->created_at->diffForHumans() }}
                        </span>
                    </div>

                    {{-- Right side: status / action --}}
                    @if($isUnread)
                        <form action="{{ route('notifications.markAsRead', $notification->id) }}" method="POST">
                            @csrf
                            <button 
                                type="submit" 
                                class="ml-4 text-xs font-medium text-blue-600 hover:underline hover:text-blue-800 transition">
                                Mark as read
                            </button>
                        </form>
                    @else
                        <span class="ml-4 text-xs text-green-600 font-medium">✔ Read</span>
                    @endif
                </div>
            </li>
        @empty
            <li class="py-6 text-center text-gray-500 text-sm">
                No notifications found 🎉
            </li>
        @endforelse
    </ul>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
</div>
@endsection

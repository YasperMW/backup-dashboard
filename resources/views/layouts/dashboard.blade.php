<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SafeguardX') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6; /* Light gray background */
        }
        /* Custom scrollbar for sidebar if content overflows */
        .sidebar-nav::-webkit-scrollbar {
            width: 8px;
        }
        .sidebar-nav::-webkit-scrollbar-thumb {
            background-color: #4a5568; /* Darker gray for thumb */
            border-radius: 4px;
        }
        .sidebar-nav::-webkit-scrollbar-track {
            background-color: #2d3748; /* Even darker gray for track */
        }

        .custom-content-scrollbar::-webkit-scrollbar {
            width: 8px;
        }
        .custom-content-scrollbar::-webkit-scrollbar-thumb {
            background-color: #a0aec0; /* Light gray for thumb, matching the image */
            border-radius: 4px;
        }
        .custom-content-scrollbar::-webkit-scrollbar-track {
            background-color: #edf2f7; /* Lighter background for track, matching the image */
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden">
    <!-- Include Navigation Component -->
    <x-navigation />

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col bg-gray-100 overflow-hidden">
        <!-- Top Navigation/Header -->
        <header class="flex items-center justify-between bg-gray-800 text-white py-4 px-3 border-b border-gray-700">
            <!-- Left side: Notification Bell -->
            <div class="flex items-center">
                <a href="#" class="relative focus:outline-none">
                    <svg class="w-4 h-4 text-gray-400 hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </a>
            </div>

            <!-- Right side: User Profile/Avatar with Dropdown -->
            <div class="flex items-center space-x-6">
                <div class="relative" x-data="{ open: false, profileModal: false }">
                    <button @click="open = !open" class="flex items-center space-x-2 focus:outline-none">
                        <span class="w-7 h-7 flex items-center justify-center rounded-full border-2 border-gray-400 bg-gray-600 text-white font-semibold uppercase">
                            {{ strtoupper(mb_substr(Auth::user()->firstname, 0, 1) . mb_substr(Auth::user()->lastname, 0, 1)) }}
                        </span>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="open"
                         @click.away="open = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-48 bg-gray-800 rounded-lg shadow-lg py-1 z-50">

                        <!-- User Info -->
                        <div class="px-4 py-2 border-b border-gray-700">
                            <p class="text-sm font-medium text-white">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-gray-400">{{ Auth::user()->email }}</p>
                        </div>

                        <!-- Menu Items -->
                        <a href="#" @click.prevent="profileModal = true" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                           View Profile
                        </a>
                       
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                                Sign Out
                            </button>
                        </form>
                    </div>

                    <!-- Profile Modal -->
                    <div x-show="profileModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                        <div @click.away="profileModal = false" class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
                            <button @click="profileModal = false" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                            <h2 class="text-2xl font-semibold mb-4 text-gray-800">User Profile</h2>
                            <div class="space-y-2">
                                <div><span class="font-semibold text-gray-700">First Name:</span> <span class="text-gray-900">{{ Auth::user()->firstname }}</span></div>
                                <div><span class="font-semibold text-gray-700">Last Name:</span> <span class="text-gray-900">{{ Auth::user()->lastname }}</span></div>
                                <div><span class="font-semibold text-gray-700">Email:</span> <span class="text-gray-900">{{ Auth::user()->email }}</span></div>
                                <div><span class="font-semibold text-gray-700">Created At:</span> <span class="text-gray-900">{{ Auth::user()->created_at->format('F j, Y, g:i a') }}</span></div>
                                <!-- Add more fields as needed -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-auto">
            {{ $slot }}
        </main>
    </div>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html> 
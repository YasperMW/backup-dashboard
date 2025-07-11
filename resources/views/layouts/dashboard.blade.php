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
        <header class="flex items-center justify-between bg-gray-800 text-white py-2 px-3 border-b border-gray-700">
            <!-- Search Bar -->
            <div class="flex items-center bg-gray-700/50 backdrop-blur-sm rounded-lg px-2.5 py-1 w-64 transition-all duration-200 hover:bg-gray-700/70 focus-within:bg-gray-700/70">
                <input type="text" placeholder="Search..." class="bg-transparent text-white placeholder-gray-400 focus:outline-none w-full text-sm">
                <button type="submit" class="ml-2 focus:outline-none">
                    <svg class="w-3.5 h-3.5 text-gray-400 hover:text-white transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </button>
            </div>

            <!-- Right side icons (Notification and Profile) -->
            <div class="flex items-center space-x-6">
                <!-- Notification Bell -->
                <a href="#" class="relative focus:outline-none">
                    <svg class="w-4 h-4 text-gray-400 hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </a>

                <!-- User Profile/Avatar with Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center space-x-2 focus:outline-none">
                        <img src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" class="w-7 h-7 rounded-full border-2 border-gray-400">
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
                        <a href="{{ route('settings.general') }}" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                           General Settings
                        </a>
                       
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                                Sign Out
                            </button>
                        </form>
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
</body>
</html> 
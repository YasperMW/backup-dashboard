@extends('layouts.dashboard')

@section('content')
    <div class="flex h-full">
        <!-- Settings Navigation Sidebar -->
        <div x-data="{ open: true }" class="relative h-full">
            <!-- Toggle Button -->
            <button @click="open = !open" class="absolute -left-4 top-4 z-10 bg-gray-800 text-white p-2 rounded-full shadow-lg hover:bg-gray-700 transition-colors">
                <svg x-show="!open" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <svg x-show="open" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>

            <!-- Sidebar -->
            <div x-show="open" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="-translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-300"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="-translate-x-full"
                 class="w-64 bg-gray-800 h-full flex flex-col border-l border-gray-700">
                <div class="p-4 pl-8 border-b border-gray-700">
                    <h2 class="text-lg font-bold text-white">Settings</h2>
                </div>
                <nav class="flex-1 overflow-y-auto">
                    <div class="p-4 space-y-2">
                        <a href="{{ route('settings.security') }}"
                            class="block p-3 rounded-lg transition duration-200 {{ request()->routeIs('settings.security') ? 'bg-gray-900 text-white font-semibold' : 'hover:bg-gray-700 text-gray-300' }}">
                            Security Settings
                        </a>
                        <a href="{{ route('settings.backup-configuration') }}"
                            class="block p-3 rounded-lg transition duration-200 {{ request()->routeIs('settings.backup-configuration') ? 'bg-gray-900 text-white font-semibold' : 'hover:bg-gray-700 text-gray-300' }}">
                           Backup Configuration
                        </a>
                      
                     
                        
                    </div>
                </nav>
            </div>
        </div>

        <!-- Settings Content -->
        <div class="flex-1 overflow-y-auto custom-content-scrollbar p-6">
            {{ $slot }}
        </div>
    </div>
 @endsection

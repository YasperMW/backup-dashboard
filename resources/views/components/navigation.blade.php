<!-- Sidebar -->
<aside class="flex flex-col bg-gray-800 text-white w-64 h-full">
    <!-- Logo/App Name -->
    <div class="flex items-center p-4 border-b border-gray-700">
        <span class="text-2xl font-semibold tracking-wide">{{ config('app.name', 'SafeguardX') }}</span>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-grow sidebar-nav overflow-y-auto p-4">
        <ul class="space-y-4">
            <li>
                <a href="{{ route('dashboard') }}" class="flex items-center p-3 rounded-lg transition duration-200 {{ request()->routeIs('dashboard') ? 'bg-gray-900 text-white font-semibold' : 'hover:bg-gray-700 text-gray-300' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Home
                </a>
            </li>
            <li>
                <a href="{{ route('backup.management') }}" class="flex items-center p-3 rounded-lg transition duration-200 {{ request()->routeIs('backup.management') ? 'bg-gray-900 text-white font-semibold' : 'hover:bg-gray-700 text-gray-300' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Backup Management
                </a>
            </li>
            <li>
                <a href="{{ route('recovery.index') }}" class="flex items-center p-3 rounded-lg transition duration-200 {{ request()->routeIs('recovery.*') ? 'bg-gray-900 text-white font-semibold' : 'hover:bg-gray-700 text-gray-300' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Recovery
                </a>
            </li>
            <li>
                <a href="{{ route('logs.index') }}" class="flex items-center p-3 rounded-lg transition duration-200 {{ request()->routeIs('logs.*') ? 'bg-gray-900 text-white font-semibold' : 'hover:bg-gray-700 text-gray-300' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    System Logs
                </a>
            </li>
        </ul>
    </nav>

    <!-- Settings Button at the bottom -->
    <div class="p-4 border-t border-gray-700">
        <ul>
            <li>
                <a href="{{ route('settings.backup-configuration') }}" class="flex items-center p-3 rounded-lg transition duration-200 {{ request()->routeIs('settings.*') ? 'bg-gray-900 text-white font-semibold' : 'hover:bg-gray-700 text-gray-300' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Settings
                </a>
            </li>
            <li>
                <button type="button"
                    x-data="statusToggle('{{ route('status.remote-host') }}', '{{ route('status.toggle-offline') }}', {{ session('manual_offline', false) ? 'true' : 'false' }})"
                    x-init="loadManual(); if (manualOffline) { stopPolling(); online = false; } else { check(); startPolling(); }"
                    @visibilitychange.window="if (document.hidden) { stopPolling() } else { startPolling(); poll(); }"
                    @beforeunload.window="stopPolling()"
                    @click.prevent="!checking && toggle()"
                    :class="checking ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer'"
                    class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-700/50 transition-all duration-200 text-gray-300 w-full group"
                >
                    <div class="flex items-center">
                        <div class="relative p-2 rounded-lg transition-colors duration-200"
                             :class="online === null
                                ? 'bg-gray-500/10 text-gray-400'
                                : ((online && !manualOffline) ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500')">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div class="ml-3 text-left">
                            <span class="font-medium block
                                {{ session('manual_offline', false) ? 'text-red-500' : 'text-gray-400' }}"
                                  :class="online === null
                                    ? 'text-gray-400'
                                    : ((online && !manualOffline) ? 'text-green-500' : 'text-red-500')"
                                  x-text="online === null ? 'Checking…' : ((online && !manualOffline) ? 'Online' : 'Offline')">
                                  {{ session('manual_offline', false) ? 'Offline' : 'Checking…' }}
                            </span>
                            <span class="text-xs text-gray-400" x-show="checking">Validating…</span>
                            <span class="text-xs text-gray-400" x-show="!checking" x-cloak>
                                <span x-show="manualOffline">Click to go Online</span>
                                <span x-show="!manualOffline">Click to go Offline</span>
                            </span>
                            <span class="text-xs text-red-400" x-show="lastAttemptFailed" x-transition.opacity.duration.300ms aria-live="polite">
                                Could not connect to {{ config('backup.linux_host') }}:22
                            </span>
                        </div>
                    </div>
                    <div class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200"
                         :class="(online && !manualOffline) ? 'bg-green-500' : 'bg-red-500'">
                        <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow-lg transition-transform duration-200"
                              :class="(online && !manualOffline) ? 'translate-x-6' : 'translate-x-1'"></span>
                    </div>
                </button>
            </li>
        </ul>
    </div>
    <script>
    window.statusToggle = function(statusUrl, toggleUrl, initialManual) {
        return {
            statusUrl: statusUrl,
            toggleUrl: toggleUrl,
            online: null,
            manualOffline: !!initialManual,
            checking: false,
            intervalId: null,
            currentCheckId: 0,
            lastAttemptFailed: false,
            check() {
                this.checking = true;
                // show neutral state while validating
                this.online = null;
                const checkId = ++this.currentCheckId;
                const url = this.statusUrl + (this.statusUrl.includes('?') ? '&' : '?') + 't=' + Date.now();
                return fetch(url, { cache: 'no-store', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.ok ? r.json() : Promise.reject())
                    .then(d => {
                        if (checkId !== this.currentCheckId) { return; }
                        // If user forced offline during the request, keep offline
                        if (this.manualOffline) { this.online = false; return; }
                        this.online = !!d.online;
                        this.lastAttemptFailed = !this.online;
                        if (this.lastAttemptFailed) { setTimeout(() => { this.lastAttemptFailed = false; }, 5000); }
                    })
                    .catch(() => {
                        if (checkId !== this.currentCheckId) { return; }
                        this.online = false;
                        this.lastAttemptFailed = true;
                        setTimeout(() => { this.lastAttemptFailed = false; }, 5000);
                    })
                    .finally(() => {
                        if (checkId !== this.currentCheckId) { return; }
                        this.checking = false;
                    });
            },
            poll() { if (!this.manualOffline) { this.check(); } },
            startPolling() { this.stopPolling(); this.intervalId = setInterval(() => this.poll(), 10000); },
            stopPolling() { if (this.intervalId) { clearInterval(this.intervalId); this.intervalId = null; } },
            async saveManual() {
                try {
                    const response = await fetch(this.toggleUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!response.ok) {
                        console.error('Failed to save offline state');
                    }
                } catch (e) {
                    console.error('Error saving offline state:', e);
                }
            },
            loadManual() {
                // State is now managed server-side and passed as initialManual parameter
                // No need to load from cookies or localStorage
            },
            async toggle() {
                if (!this.manualOffline) {
                    // Going offline
                    this.stopPolling();
                    this.online = false;
                    this.manualOffline = true;
                    await this.saveManual();
                } else {
                    // Attempting to go online
                    this.manualOffline = false; // Temporarily set to false to allow check() to work
                    await this.check();
                    
                    if (this.online) {
                        // Success - stay online and resume polling
                        this.startPolling();
                    } else {
                        // Failed - go back to manual offline
                        this.manualOffline = true;
                        this.online = false;
                    }
                    await this.saveManual();
                }
            }
        }
    }
    </script>
</aside>
@extends('layouts.dashboard')

@section('content')
    <div class="p-6">
        
        <!-- Log Filters Section -->
        <form method="GET" action="{{ route('logs.index') }}">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Log Filters</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

                    <!-- Date Range Start -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Date Range End -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Log Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Log Type</label>
                        <select name="type"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Types</option>
                            <option value="login" {{ request('type') == 'login' ? 'selected' : '' }}>Login</option>
                            <option value="backup" {{ request('type') == 'backup' ? 'selected' : '' }}>Backup</option>
                            <option value="failed_job" {{ request('type') == 'failed_job' ? 'selected' : '' }}>Failed Jobs</option>
                        </select>
                    </div>

                    <!-- Unified Severity -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Severity</label>
                        <select name="severity"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Severities</option>
                            <option value="info" {{ request('severity') == 'info' ? 'selected' : '' }}>Info</option>
                            <option value="error" {{ request('severity') == 'error' ? 'selected' : '' }}>Error</option>
                            <option value="warning" {{ request('severity') == 'warning' ? 'selected' : '' }}>Warning</option>
                        </select>
                    </div>

                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search email, IP, filename, directory..."
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>
        </form>

        <!-- Logs Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-800">Application Logs</h2>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Severity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $log['timestamp'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $log['type'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $severity = strtolower($log['severity']);
                                        $color = match($severity) {
                                            'completed' => 'bg-green-100 text-green-800',
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'failed' => 'bg-red-100 text-red-800',
                                            'error' => 'bg-red-100 text-red-800',
                                            'info' => 'bg-blue-100 text-blue-800',
                                            default => 'bg-gray-100 text-gray-800',
                                        };
                                        $label = ucfirst($severity);
                                    @endphp
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $color }}">
                                        {{ $label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $log['message'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $log['source'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $log['user'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <button class="text-blue-600 hover:text-blue-900" onclick='showLogDetails(@json($log))'>
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center px-6 py-4 text-sm text-gray-500">
                                    No logs available.
                                </td>
                            </tr>
                        @endforelse

                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <button class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </button>
                        <button class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                        </button>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium">{{ $logs->count() }}</span> {{ Str::plural('result', $logs->count()) }}
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <button class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    Previous
                                </button>
                                <button class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    1
                                </button>
                                <button class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    2
                                </button>
                                <button class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    3
                                </button>
                                <button class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    Next
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Log Details Modal -->
        <div id="logDetailsModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full relative">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Log Details</h3>
                    <button class="text-gray-400 hover:text-gray-500" onclick="closeLogDetails()">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div id="logDetailsContent" class="space-y-4">
                    <!-- Details will be injected here -->
                </div>
            </div>
        </div>

        <script>
            function showLogDetails(log) {
                let html = '';
                for (const [key, value] of Object.entries(log)) {
                    html += `<div><h4 class='text-sm font-medium text-gray-500'>${key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</h4><p class='mt-1 text-sm text-gray-900'>${value ?? ''}</p></div>`;
                }
                document.getElementById('logDetailsContent').innerHTML = html;
                document.getElementById('logDetailsModal').classList.remove('hidden');
            }
            function closeLogDetails() {
                document.getElementById('logDetailsModal').classList.add('hidden');
            }
            // Close modal on background click
            document.getElementById('logDetailsModal').addEventListener('click', function(e) {
                if (e.target === this) closeLogDetails();
            });

            // Auto-submit form on filter change
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.querySelector('form[action="{{ route('logs.index') }}"]');
                if (!form) return;

                const filterElements = form.querySelectorAll('input[name], select[name]');

                filterElements.forEach(element => {
                    element.addEventListener('change', () => {
                        form.submit();
                    });
                });
            });
        </script>
    </div>
@endsection

<x-dashboard-layout>
    <div class="p-6">
        <!-- Log Filters Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Log Filters</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Date Range -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                    <div class="flex space-x-2">
                        <input type="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <input type="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
                <!-- Log Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Log Type</label>
                    <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option>All Types</option>
                        <option>System</option>
                        <option>Backup</option>
                        <option>Security</option>
                        <option>Error</option>
                    </select>
                </div>
                <!-- Severity Level -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Severity Level</label>
                    <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option>All Levels</option>
                        <option>Critical</option>
                        <option>Error</option>
                        <option>Warning</option>
                        <option>Info</option>
                        <option>Debug</option>
                    </select>
                </div>
                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <div class="relative">
                        <input type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pl-10" placeholder="Search logs...">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <button class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Apply Filters
                </button>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-800">System Logs</h2>
                    <div class="flex space-x-2">
                        <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                            Export
                        </button>
                        <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                            Clear Logs
                        </button>
                    </div>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-03-15 14:30:45</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">System</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Critical
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">Database connection failed</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Database Service</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button class="text-blue-600 hover:text-blue-900">View Details</button>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-03-15 14:29:30</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Backup</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Warning
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">Backup size exceeds threshold</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Backup Service</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button class="text-blue-600 hover:text-blue-900">View Details</button>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-03-15 14:28:15</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Security</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Info
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">User login successful</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Auth Service</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button class="text-blue-600 hover:text-blue-900">View Details</button>
                            </td>
                        </tr>
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
                                Showing <span class="font-medium">1</span> to <span class="font-medium">3</span> of <span class="font-medium">12</span> results
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

        <!-- Log Details Modal (Hidden by default) -->
        <div class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Log Details</h3>
                    <button class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Timestamp</h4>
                        <p class="mt-1 text-sm text-gray-900">2024-03-15 14:30:45</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Type</h4>
                        <p class="mt-1 text-sm text-gray-900">System</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Severity</h4>
                        <p class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                Critical
                            </span>
                        </p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Message</h4>
                        <p class="mt-1 text-sm text-gray-900">Database connection failed</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Source</h4>
                        <p class="mt-1 text-sm text-gray-900">Database Service</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Stack Trace</h4>
                        <pre class="mt-1 text-sm text-gray-900 bg-gray-50 p-4 rounded-md overflow-x-auto">
Error: Connection refused
    at Database.connect (/app/services/database.js:45:12)
    at async BackupService.initialize (/app/services/backup.js:23:5)
    at async BackupService.startBackup (/app/services/backup.js:67:8)
                        </pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout> 
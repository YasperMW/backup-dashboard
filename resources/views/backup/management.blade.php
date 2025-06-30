<x-dashboard-layout>
    <div class="p-6">
        <!-- Backup Configuration Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Backup Configuration</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Storage Location -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Storage Location</label>
                    <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option>Local Storage</option>
                        <option>Amazon S3</option>
                        <option>Google Cloud Storage</option>
                        <option>Azure Blob Storage</option>
                    </select>
                </div>
                <!-- Backup Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Backup Type</label>
                    <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option>Full Backup</option>
                        <option>Incremental Backup</option>
                        <option>Differential Backup</option>
                    </select>
                </div>
                <!-- Compression Level -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Compression Level</label>
                    <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option>None</option>
                        <option>Low</option>
                        <option>Medium</option>
                        <option>High</option>
                    </select>
                </div>
                <!-- Retention Period -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Retention Period (days)</label>
                    <input type="number" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" value="30">
                </div>
            </div>
            <div class="mt-4">
                <button class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Save Configuration
                </button>
            </div>
        </div>

        <!-- Backup Schedule Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Backup Schedule</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Schedule Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Schedule Type</label>
                    <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option>Daily</option>
                        <option>Weekly</option>
                        <option>Monthly</option>
                        <option>Custom</option>
                    </select>
                </div>
                <!-- Time -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Time</label>
                    <input type="time" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <!-- Days (for weekly) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Days</label>
                    <div class="flex space-x-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" class="rounded border-gray-300 text-blue-500 focus:ring-blue-500">
                            <span class="ml-2">Mon</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" class="rounded border-gray-300 text-blue-500 focus:ring-blue-500">
                            <span class="ml-2">Wed</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" class="rounded border-gray-300 text-blue-500 focus:ring-blue-500">
                            <span class="ml-2">Fri</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <button class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Save Schedule
                </button>
            </div>
        </div>

        <!-- Manual Backup Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Manual Backup</h2>
            <div class="flex items-center space-x-4">
                <button class="bg-green-500 text-white px-6 py-2 rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Start Backup Now
                </button>
                <span class="text-sm text-gray-500">Last backup: 2 hours ago</span>
            </div>
        </div>

        <!-- Backup History Table -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Backup History</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-03-15 14:30</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Full</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2.4 GB</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Completed
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button class="text-blue-600 hover:text-blue-900 mr-3">Restore</button>
                                <button class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-03-14 14:30</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Incremental</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">156 MB</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Completed
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button class="text-blue-600 hover:text-blue-900 mr-3">Restore</button>
                                <button class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-03-13 14:30</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Full</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2.3 GB</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Failed
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button class="text-blue-600 hover:text-blue-900 mr-3">Retry</button>
                                <button class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="mt-4 flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <span class="font-medium">1</span> to <span class="font-medium">3</span> of <span class="font-medium">12</span> results
                </div>
                <div class="flex space-x-2">
                    <button class="px-3 py-1 border rounded-md text-sm text-gray-600 hover:bg-gray-50">Previous</button>
                    <button class="px-3 py-1 border rounded-md text-sm text-gray-600 hover:bg-gray-50">Next</button>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout> 
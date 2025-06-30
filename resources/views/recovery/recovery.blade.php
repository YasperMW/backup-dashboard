<x-dashboard-layout>
    <div class="p-6">
        <!-- Backup Selection Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Select Backup</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Backup Date Range -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                    <div class="flex space-x-2">
                        <input type="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <input type="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
                <!-- Backup Type Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Backup Type</label>
                    <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option>All Types</option>
                        <option>Full Backup</option>
                        <option>Incremental Backup</option>
                        <option>Differential Backup</option>
                    </select>
                </div>
            </div>

            <!-- Backup List -->
            <div class="mt-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Select</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="radio" name="backup" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-03-15 14:30</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Full</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2.4 GB</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Available
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="radio" name="backup" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-03-14 14:30</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Incremental</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">156 MB</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Available
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Restore Configuration Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Restore Configuration</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Restore Path -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Restore Path</label>
                    <div class="flex space-x-2">
                        <input type="text" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="/path/to/restore">
                        <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                            Browse
                        </button>
                    </div>
                </div>
                <!-- Restore Options -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Restore Options</label>
                    <div class="space-y-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Overwrite existing files</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Preserve file permissions</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Backup Preview</h2>
            <div class="border rounded-lg p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-medium text-gray-900">Selected Backup Contents</h3>
                    <button class="text-blue-600 hover:text-blue-900 text-sm">Expand All</button>
                </div>
                <div class="space-y-2">
                    <!-- File Tree -->
                    <div class="pl-4">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                            <span class="text-sm text-gray-700">Documents/</span>
                        </div>
                        <div class="pl-6">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span class="text-sm text-gray-700">report.pdf (2.1 MB)</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span class="text-sm text-gray-700">presentation.pptx (4.5 MB)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-4">
            <button class="px-6 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                Cancel
            </button>
            <button class="px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Start Restore
            </button>
        </div>

        <!-- Restore Progress Modal (Hidden by default) -->
        <div class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
            <div class="bg-white rounded-lg p-6 max-w-md w-full">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Restore in Progress</h3>
                <div class="mb-4">
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: 45%"></div>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">45% Complete</p>
                </div>
                <div class="text-sm text-gray-700">
                    <p>Restoring: Documents/report.pdf</p>
                    <p class="mt-1">Estimated time remaining: 2 minutes</p>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout> 
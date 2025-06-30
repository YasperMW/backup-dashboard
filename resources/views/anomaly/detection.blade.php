<x-dashboard-layout>
    <div class="p-6">
        <!-- Detection Configuration Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Detection Configuration</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Sensitivity Level -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Detection Sensitivity</label>
                    <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option>Low</option>
                        <option>Medium</option>
                        <option>High</option>
                        <option>Custom</option>
                    </select>
                </div>
                <!-- Detection Method -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Detection Method</label>
                    <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option>Statistical Analysis</option>
                        <option>Machine Learning</option>
                        <option>Pattern Recognition</option>
                        <option>Hybrid</option>
                    </select>
                </div>
                <!-- Alert Threshold -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Alert Threshold (%)</label>
                    <input type="number" min="0" max="100" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" value="75">
                </div>
                <!-- Monitoring Interval -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Monitoring Interval</label>
                    <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option>1 minute</option>
                        <option>5 minutes</option>
                        <option>15 minutes</option>
                        <option>30 minutes</option>
                        <option>1 hour</option>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <button class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Save Configuration
                </button>
            </div>
        </div>

        <!-- Rule Management Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Detection Rules</h2>
                <button class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Add New Rule
                </button>
            </div>
            <div class="space-y-4">
                <!-- Rule 1 -->
                <div class="border rounded-lg p-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-medium text-gray-900">Unusual Backup Size</h3>
                            <p class="text-sm text-gray-500">Detect when backup size deviates by more than 50% from average</p>
                        </div>
                        <div class="flex space-x-2">
                            <button class="text-blue-600 hover:text-blue-900">Edit</button>
                            <button class="text-red-600 hover:text-red-900">Delete</button>
                        </div>
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Condition:</span>
                            <span class="text-gray-900">Size > 150% of average</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Severity:</span>
                            <span class="text-red-600 font-medium">High</span>
                        </div>
                    </div>
                </div>

                <!-- Rule 2 -->
                <div class="border rounded-lg p-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-medium text-gray-900">Failed Backup Pattern</h3>
                            <p class="text-sm text-gray-500">Alert on consecutive backup failures</p>
                        </div>
                        <div class="flex space-x-2">
                            <button class="text-blue-600 hover:text-blue-900">Edit</button>
                            <button class="text-red-600 hover:text-red-900">Delete</button>
                        </div>
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Condition:</span>
                            <span class="text-gray-900">3+ consecutive failures</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Severity:</span>
                            <span class="text-yellow-600 font-medium">Medium</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts Table -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Recent Alerts</h2>
                <div class="flex space-x-2">
                    <button class="px-3 py-1 border rounded-md text-sm text-gray-600 hover:bg-gray-50">Acknowledge All</button>
                    <button class="px-3 py-1 border rounded-md text-sm text-gray-600 hover:bg-gray-50">Clear All</button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alert Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Severity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-03-15 15:30</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Unusual Backup Size</td>
                            <td class="px-6 py-4 text-sm text-gray-900">Backup size increased by 200% from average</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    High
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Pending
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button class="text-blue-600 hover:text-blue-900 mr-3">Acknowledge</button>
                                <button class="text-red-600 hover:text-red-900">Dismiss</button>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-03-15 14:45</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Failed Backup Pattern</td>
                            <td class="px-6 py-4 text-sm text-gray-900">3 consecutive backup failures detected</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Medium
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Acknowledged
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button class="text-gray-400 cursor-not-allowed mr-3">Acknowledge</button>
                                <button class="text-red-600 hover:text-red-900">Dismiss</button>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2024-03-15 13:20</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Storage Space</td>
                            <td class="px-6 py-4 text-sm text-gray-900">Storage usage approaching threshold (85%)</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    Low
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Resolved
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button class="text-gray-400 cursor-not-allowed mr-3">Acknowledge</button>
                                <button class="text-red-600 hover:text-red-900">Dismiss</button>
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
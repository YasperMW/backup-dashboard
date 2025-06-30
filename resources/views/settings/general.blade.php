<x-settings-layout>
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-medium text-gray-900">General Settings</h2>
            <p class="mt-1 text-sm text-gray-600">Configure your general application settings.</p>
        </div>

        <div class="space-y-6">
            <!-- Application Settings -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-base font-medium text-gray-900 mb-4">Application Settings</h3>
                <div class="space-y-4">
                    <div>
                        <label for="app_name" class="block text-sm font-medium text-gray-700">Application Name</label>
                        <input type="text" name="app_name" id="app_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
                        <select name="timezone" id="timezone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="UTC">UTC</option>
                            <option value="America/New_York">Eastern Time</option>
                            <option value="America/Chicago">Central Time</option>
                            <option value="America/Denver">Mountain Time</option>
                            <option value="America/Los_Angeles">Pacific Time</option>
                        </select>
                    </div>
                    <div>
                        <label for="date_format" class="block text-sm font-medium text-gray-700">Date Format</label>
                        <select name="date_format" id="date_format" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="Y-m-d">YYYY-MM-DD</option>
                            <option value="m/d/Y">MM/DD/YYYY</option>
                            <option value="d/m/Y">DD/MM/YYYY</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Display Settings -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-base font-medium text-gray-900 mb-4">Display Settings</h3>
                <div class="space-y-4">
                    <div>
                        <label for="theme" class="block text-sm font-medium text-gray-700">Theme</label>
                        <select name="theme" id="theme" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="light">Light</option>
                            <option value="dark">Dark</option>
                            <option value="system">System</option>
                        </select>
                    </div>
                    <div>
                        <label for="items_per_page" class="block text-sm font-medium text-gray-700">Items Per Page</label>
                        <select name="items_per_page" id="items_per_page" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</x-settings-layout> 
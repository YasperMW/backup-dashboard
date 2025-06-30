<x-settings-layout>
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-medium text-gray-900">Security Configuration</h2>
            <p class="mt-1 text-sm text-gray-600">Configure advanced security settings for your application.</p>
        </div>

        <!-- File Storage -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-base font-medium text-gray-900 mb-4">File Storage</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">Write Once Read Many (WORM)</h4>
                        <p class="mt-1 text-sm text-gray-500">Prevent changes to saved files after initial write.</p>
                    </div>
                    <div class="flex items-center">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Anomaly Detection -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-base font-medium text-gray-900 mb-4">Anomaly Detection</h3>
            <div class="space-y-4">
                <div>
                    <label for="monitoring_frequency" class="block text-sm font-medium text-gray-700">Monitoring Frequency</label>
                    <p class="mt-1 text-sm text-gray-500">Configure how often the system checks for unusual activity.</p>
                    <select id="monitoring_frequency" name="monitoring_frequency" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="5_minutes">Every 5 minutes</option>
                        <option value="10_minutes">Every 10 minutes</option>
                        <option value="30_minutes">Every 30 minutes</option>
                        <option value="1_hour">Every hour</option>
                        <option value="6_hours">Every 6 hours</option>
                    </select>
                </div>
                <div>
                    <label for="sensitivity_level" class="block text-sm font-medium text-gray-700">Detection Sensitivity</label>
                    <p class="mt-1 text-sm text-gray-500">Adjust how sensitive the system is to detecting anomalies.</p>
                    <select id="sensitivity_level" name="sensitivity_level" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="low">Low (Fewer alerts)</option>
                        <option value="medium">Medium (Balanced)</option>
                        <option value="high">High (More alerts)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Encryption -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-base font-medium text-gray-900 mb-4">Encryption</h3>
            <div class="space-y-4">
                <div>
                    <label for="encryption_type" class="block text-sm font-medium text-gray-700">Encryption Type</label>
                    <p class="mt-1 text-sm text-gray-500">Choose the encryption algorithm for your data.</p>
                    <select id="encryption_type" name="encryption_type" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="aes256">AES-256 (More secure)</option>
                        <option value="aes128">AES-128 (Faster)</option>
                    </select>
                </div>
                <div>
                    <label for="key_change_frequency" class="block text-sm font-medium text-gray-700">Key Rotation Frequency</label>
                    <p class="mt-1 text-sm text-gray-500">How often to change encryption keys.</p>
                    <select id="key_change_frequency" name="key_change_frequency" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="7_days">Every 7 days</option>
                        <option value="30_days">Every 30 days</option>
                        <option value="90_days">Every 90 days</option>
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
</x-settings-layout> 
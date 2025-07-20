<x-settings-layout>
    <h2 class="text-2xl font-bold text-white mb-6">Security Settings</h2>
    <p class="text-gray-300 mb-8">Easily manage key security features.</p>

    <!-- File Storage -->
    <div class="mb-6">
        <h3 class="text-xl font-semibold text-white mb-2">File Storage</h3>
        <p class="text-gray-400 text-sm mb-4">Prevent changes to saved files (WORM)</p>
    </div>

    <!-- Encryption -->
    <div class="mb-8">
        <h3 class="text-xl font-semibold text-white mb-2">Encryption</h3>
        <p class="text-gray-400 text-sm mb-4">How your data is protected</p>

        <label for="encryption_type" class="block text-gray-300 text-sm font-medium mb-2">Encryption Type</label>
        <select id="encryption_type" name="encryption_type" class="w-full px-4 py-3 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-green-500 transition duration-200 mb-4">
            <option value="aes256">AES-256 (More secure)</option>
            <option value="aes128">AES-128 (Faster)</option>
        </select>

        <label for="key_change_frequency" class="block text-gray-300 text-sm font-medium mb-2">How often to change the keys</label>
        <select id="key_change_frequency" name="key_change_frequency" class="w-full px-4 py-3 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-green-500 transition duration-200">
            <option value="7_days">Every 7 days</option>
            <option value="30_days">Every 30 days</option>
            <option value="90_days">Every 90 days</option>
        </select>
    </div>

    <!-- Save Changes Button -->
    <div class="flex justify-end">
        <x-primary-button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-8 rounded-lg shadow-md transition duration-200">
            Save Changes
        </x-primary-button>
    </div>

    @include('profile.multi-factor-authentication-settings')
</x-settings-layout>

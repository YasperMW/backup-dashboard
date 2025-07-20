<x-settings-layout>
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-medium text-gray-900">Security Settings</h2>
            <p class="mt-1 text-sm text-gray-600">Manage your account security and authentication settings.</p>
        </div>

        <!-- Multi-Factor Authentication -->
        @include('profile.partials.two-factor-authentication-form')

        <!-- Password Settings -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-base font-medium text-gray-900 mb-4">Password Settings</h3>
            <div class="space-y-4">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                    <input type="password" name="current_password" id="current_password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                    <input type="password" name="new_password" id="new_password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Update Password
                    </button>
                </div>
            </div>
        </div>

        <!-- Session Management -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-base font-medium text-gray-900 mb-4">Session Management</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">Current Session</h4>
                        <p class="mt-1 text-sm text-gray-500">Windows 10 - Chrome - 192.168.1.1</p>
                    </div>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                        Active
                    </span>
                </div>
                <div class="flex justify-end">
                    <button type="button" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                        Logout Other Devices
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-settings-layout> 
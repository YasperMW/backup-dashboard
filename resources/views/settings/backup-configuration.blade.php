<x-settings-layout>
    @php
        $manualOffline = session('manual_offline', false);
        $linux = new \App\Services\LinuxBackupService();
        $actuallyOffline = $manualOffline ? false : !$linux->isReachable(5);
        $isSystemOffline = $manualOffline || $actuallyOffline;
    @endphp
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

        <!-- Encryption -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-base font-medium text-gray-900 mb-4">Encryption Settings</h3>
            <form id="encryption-config-form">
                <div class="space-y-6">
                    <div>
                        <label for="encryption_type" class="block text-sm font-medium text-gray-700">Encryption Type</label>
                        <p class="mt-1 text-sm text-gray-500">Choose the encryption algorithm for your backup data.</p>
                        <select id="encryption_type" name="encryption_type" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="aes-256-cbc">AES-256-CBC (Recommended)</option>
                            <option value="aes-128-cbc">AES-128-CBC (Faster)</option>
                            <option value="aes-256-gcm">AES-256-GCM (More secure)</option>
                            <option value="aes-128-gcm">AES-128-GCM (Fast + secure)</option>
                        </select>
                    </div>
                    <div>
                        <label for="key_rotation_frequency" class="block text-sm font-medium text-gray-700">Key Rotation Frequency</label>
                        <p class="mt-1 text-sm text-gray-500">How often to automatically rotate encryption keys.</p>
                        <select id="key_rotation_frequency" name="key_rotation_frequency" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="7_days">Every 7 days</option>
                            <option value="30_days">Every 30 days (Recommended)</option>
                            <option value="90_days">Every 90 days</option>
                        </select>
                    </div>
                    
                    <!-- Key Status Section -->
                    <div class="border-t pt-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Encryption Key Status</h4>
                        <div id="key-status-container" class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Current Key</p>
                                    <p class="text-xs text-gray-500" id="current-key-info">Loading...</p>
                                </div>
                                <div class="flex space-x-2">
                                    <button type="button" id="check-rotation-btn" class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200">
                                        Check Rotation
                                    </button>
                                    <button type="button" id="generate-key-btn" class="px-3 py-1 text-xs bg-green-100 text-green-700 rounded-md hover:bg-green-200">
                                        Generate New Key
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Key Selection Section -->
                            <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Active Key Selection</p>
                                    <p class="text-xs text-gray-500">Choose which encryption key to use</p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <select id="key-version-select" class="text-sm border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Loading keys...</option>
                                    </select>
                                    <button type="button" id="activate-selected-key-btn" class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200" disabled>
                                        Activate
                                    </button>
                                </div>
                            </div>
                            
                            <div id="rotation-status" class="hidden p-3 rounded-lg">
                                <!-- Rotation status will be populated here -->
                            </div>
                            <div id="new-key-display" class="hidden p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <!-- New key instructions will be displayed here -->
                            </div>
                            <div id="notification-area" class="hidden">
                                <!-- Notifications will be displayed here -->
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-4">
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Save Encryption Settings
                        </button>
                        <span id="encryption-config-status" class="text-sm text-green-600"></span>
                    </div>
                </div>
            </form>
        </div>

        <!-- Backup Configuration Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 mt-10">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Backup Configuration</h2>
            @if($isSystemOffline)
                <div class="bg-orange-50 border border-orange-200 text-orange-800 px-4 py-3 rounded mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-orange-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm">
                                <strong>System is offline:</strong> Only local storage options are available. Remote and cloud storage options are disabled until the system is back online.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
            <form id="backup-config-form">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Storage Location -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Storage Location (used by Scheduled Backups)</label>
                        <select id="storage_location" name="storage_location" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" {{ $isSystemOffline ? 'data-offline="true"' : '' }}>
                            <option value="local">Local only</option>
                            @if(!$isSystemOffline)
                                <option value="remote">Remote server only</option>
                                <option value="both">Both (Local + Remote)</option>
                                <option disabled>──────────</option>
                                <option value="s3" disabled title="Not implemented yet">Amazon S3 (coming soon)</option>
                                <option value="gcs" disabled title="Not implemented yet">Google Cloud Storage (coming soon)</option>
                                <option value="azure" disabled title="Not implemented yet">Azure Blob Storage (coming soon)</option>
                            @endif
                        </select>
                        @if($isSystemOffline)
                            <p class="mt-1 text-xs text-orange-600">⚠️ System is offline - only local storage is available.</p>
                        @endif
                    </div>
                    <!-- Backup Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Backup Type</label>
                        <select id="backup_type" name="backup_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="full">Full Backup</option>
                            <option value="incremental">Incremental Backup</option>
                            <option value="differential">Differential Backup</option>
                        </select>
                    </div>
                    <!-- Compression Level -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Compression Level</label>
                        <select id="compression_level" name="compression_level" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="none">None</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <!-- Retention Period -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Retention Period (days)</label>
                        <input type="number" id="retention_period" name="retention_period" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" min="1">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Save Configuration
                    </button>
                    <span id="backup-config-status" class="ml-4 text-green-600"></span>
                </div>
            </form>
        </div>
        <script>
document.addEventListener('DOMContentLoaded', function() {
    const isSystemOffline = {{ $isSystemOffline ? 'true' : 'false' }};
    
    // Load encryption configuration
    loadEncryptionConfig();
    loadKeyStatus();

    // Notification system
    function showNotification(message, type = 'success', duration = 5000) {
        const notificationArea = document.getElementById('notification-area');
        const notificationId = 'notification-' + Date.now();
        
        const colors = {
            success: 'bg-green-50 border-green-200 text-green-800',
            error: 'bg-red-50 border-red-200 text-red-800',
            warning: 'bg-yellow-50 border-yellow-200 text-yellow-800',
            info: 'bg-blue-50 border-blue-200 text-blue-800'
        };
        
        const icons = {
            success: '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />',
            error: '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />',
            warning: '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />',
            info: '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />',
        };
        
        const notification = document.createElement('div');
        notification.id = notificationId;
        notification.className = `mb-3 p-4 border rounded-lg ${colors[type]} transition-all duration-300 transform translate-x-full`;
        notification.innerHTML = `
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        ${icons[type]}
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium">${message}</p>
                </div>
                <div class="ml-4 flex-shrink-0">
                    <button type="button" onclick="dismissNotification('${notificationId}')" class="inline-flex text-gray-400 hover:text-gray-600">
                        <span class="sr-only">Close</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        `;
        
        notificationArea.appendChild(notification);
        notificationArea.classList.remove('hidden');
        
        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 10);
        
        // Auto-dismiss after duration
        if (duration > 0) {
            setTimeout(() => {
                dismissNotification(notificationId);
            }, duration);
        }
    }

    window.dismissNotification = function(notificationId) {
        const notification = document.getElementById(notificationId);
        if (notification) {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                notification.remove();
                // Hide notification area if no notifications left
                const notificationArea = document.getElementById('notification-area');
                if (notificationArea.children.length === 0) {
                    notificationArea.classList.add('hidden');
                }
            }, 300);
        }
    };
    
    // Load current config
    fetch('/backup/config')
        .then(res => res.json())
        .then(cfg => {
            const storageLocation = cfg.storage_location || 'local';
            
            // If system is offline, force local storage only
            if (isSystemOffline && (storageLocation === 'remote' || storageLocation === 'both')) {
                document.getElementById('storage_location').value = 'local';
            } else {
                document.getElementById('storage_location').value = storageLocation;
            }
            
            document.getElementById('backup_type').value = cfg.backup_type || 'full';
            document.getElementById('compression_level').value = cfg.compression_level || 'none';
            document.getElementById('retention_period').value = cfg.retention_period || 30;
        });
    // Save config
    document.getElementById('backup-config-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        let storageLocation = document.getElementById('storage_location').value;
        
        // If system is offline, force local storage only
        if (isSystemOffline && (storageLocation === 'remote' || storageLocation === 'both')) {
            storageLocation = 'local';
            document.getElementById('storage_location').value = 'local';
        }
        
        const data = {
            storage_location: storageLocation,
            backup_type: document.getElementById('backup_type').value,
            compression_level: document.getElementById('compression_level').value,
            retention_period: document.getElementById('retention_period').value
        };
        fetch('/backup/config', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(resp => {
            if (resp.success) {
                document.getElementById('backup-config-status').textContent = 'Saved!';
                setTimeout(() => document.getElementById('backup-config-status').textContent = '', 2000);
            }
        });
    });

    // Encryption configuration functions
    function loadEncryptionConfig() {
        fetch('{{ route("encryption.config.get") }}')
            .then(res => res.json())
            .then(config => {
                document.getElementById('encryption_type').value = config.encryption_type || 'aes-256-cbc';
                document.getElementById('key_rotation_frequency').value = config.key_rotation_frequency || '30_days';
            })
            .catch(err => console.error('Failed to load encryption config:', err));
    }

    function loadKeyStatus() {
        fetch('{{ route("encryption.key-status") }}')
            .then(res => res.json())
            .then(data => {
                const currentKeyInfo = document.getElementById('current-key-info');
                const keyVersionSelect = document.getElementById('key-version-select');
                const status = data.status;
                
                let statusText = `Version: ${status.current_key.version.toUpperCase()}, `;
                statusText += `Cipher: ${status.current_key.cipher}, `;
                statusText += `Keys: ${status.total_keys}`;
                
                if (status.days_until_rotation !== undefined) {
                    if (status.days_until_rotation > 0) {
                        statusText += `, Next rotation: ${status.days_until_rotation} days`;
                    } else {
                        statusText += `, Rotation overdue!`;
                        currentKeyInfo.className = 'text-xs text-red-500';
                    }
                }
                
                currentKeyInfo.textContent = statusText;
                
                // Populate key selection dropdown
                keyVersionSelect.innerHTML = '';
                keyVersionSelect.dataset.currentKey = status.current_key.version;
                
                if (data.keys && Object.keys(data.keys).length > 0) {
                    Object.entries(data.keys).forEach(([version, keyInfo]) => {
                        const option = document.createElement('option');
                        option.value = version;
                        option.textContent = `${version.toUpperCase()}${keyInfo.is_current ? ' (Current)' : ''}`;
                        if (keyInfo.is_current) {
                            option.selected = true;
                        }
                        keyVersionSelect.appendChild(option);
                    });
                } else {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'No keys available';
                    keyVersionSelect.appendChild(option);
                }
                
                // Update activate button state
                const activateBtn = document.getElementById('activate-selected-key-btn');
                activateBtn.disabled = true; // Disabled by default since current key is selected
                
                // Show rotation status if needed
                if (data.rotation_needed) {
                    showRotationAlert();
                }
            })
            .catch(err => console.error('Failed to load key status:', err));
    }

    function showRotationAlert() {
        const rotationStatus = document.getElementById('rotation-status');
        rotationStatus.className = 'p-3 bg-orange-50 border border-orange-200 rounded-lg';
        rotationStatus.innerHTML = `
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-orange-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-orange-800">
                        <strong>Key rotation needed:</strong> Your encryption key should be rotated according to your configured schedule.
                    </p>
                </div>
            </div>
        `;
        rotationStatus.classList.remove('hidden');
    }

    // Encryption form submission
    document.getElementById('encryption-config-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            encryption_type: document.getElementById('encryption_type').value,
            key_rotation_frequency: document.getElementById('key_rotation_frequency').value
        };
        
        fetch('{{ route("encryption.config.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(formData)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('encryption-config-status').textContent = 'Saved!';
                setTimeout(() => document.getElementById('encryption-config-status').textContent = '', 2000);
                loadKeyStatus(); // Refresh key status
            } else {
                showNotification('Error: ' + (data.error || 'Failed to save encryption settings'), 'error');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showNotification('Failed to save encryption settings', 'error');
        });
    });

    // Check rotation button
    document.getElementById('check-rotation-btn').addEventListener('click', function() {
        this.disabled = true;
        this.textContent = 'Checking...';
        
        fetch('{{ route("encryption.check-rotation") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.rotation_needed) {
                // Show rotation needed message, but don't auto-generate key
                const rotationStatus = document.getElementById('rotation-status');
                rotationStatus.className = 'p-3 bg-orange-50 border border-orange-200 rounded-lg';
                rotationStatus.innerHTML = `
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-orange-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-orange-800">
                                <strong>Key rotation is needed!</strong> Your encryption key should be rotated according to your configured schedule. Click "Generate New Key" to create a new key.
                            </p>
                        </div>
                    </div>
                `;
                rotationStatus.classList.remove('hidden');
                showNotification('Key rotation is needed based on your schedule', 'warning');
            } else {
                const rotationStatus = document.getElementById('rotation-status');
                rotationStatus.className = 'p-3 bg-green-50 border border-green-200 rounded-lg';
                rotationStatus.innerHTML = `
                    <p class="text-sm text-green-800">✓ Key rotation is not needed at this time.</p>
                `;
                rotationStatus.classList.remove('hidden');
                showNotification('Key rotation is not needed at this time', 'success', 3000);
            }
            loadKeyStatus(); // Refresh status
        })
        .catch(err => {
            console.error('Error:', err);
            showNotification('Failed to check rotation status', 'error');
        })
        .finally(() => {
            this.disabled = false;
            this.textContent = 'Check Rotation';
        });
    });

    // Generate new key button (manual only)
    document.getElementById('generate-key-btn').addEventListener('click', function() {
        generateKey(false, this);
    });

    // Key selection functionality
    document.getElementById('activate-selected-key-btn').addEventListener('click', function() {
        const selectedVersion = document.getElementById('key-version-select').value;
        if (selectedVersion) {
            activateKey(selectedVersion);
        }
    });

    // Enable/disable activate button based on selection
    document.getElementById('key-version-select').addEventListener('change', function() {
        const activateBtn = document.getElementById('activate-selected-key-btn');
        const currentKey = this.dataset.currentKey;
        const selectedKey = this.value;
        
        activateBtn.disabled = !selectedKey || selectedKey === currentKey;
    });

    function generateKey(autoAdd, buttonElement) {
        buttonElement.disabled = true;
        const originalText = buttonElement.textContent;
        buttonElement.textContent = 'Generating...';
        
        const cipher = document.getElementById('encryption_type').value;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        // Debug info (can be removed later)
        console.log('CSRF Token:', csrfToken ? 'Present' : 'Missing');
        console.log('Cipher:', cipher);
        
        if (!csrfToken) {
            showNotification('CSRF token not found. Please refresh the page.', 'error');
            buttonElement.disabled = false;
            buttonElement.textContent = originalText;
            return;
        }
        
        fetch('{{ route("encryption.generate-key") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ 
                cipher: cipher,
                auto_add: true  // Auto-add to .env file
            })
        })
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                showNewKeyInstructions(data.key, data.version, data.instructions, data.auto_added);
                if (data.auto_added) {
                    showNotification(`New encryption key ${data.version.toUpperCase()} added to .env file!`, 'success');
                    loadKeyStatus(); // Refresh key status and dropdown
                } else {
                    showNotification(`New encryption key ${data.version.toUpperCase()} generated successfully!`, 'info');
                }
            } else {
                console.error('API Error:', data.error);
                showNotification('Error: ' + (data.error || 'Failed to generate new key'), 'error');
            }
        })
        .catch(err => {
            console.error('Fetch Error:', err);
            console.error('Error details:', err.message);
            showNotification('Network error: ' + err.message, 'error');
        })
        .finally(() => {
            buttonElement.disabled = false;
            buttonElement.textContent = originalText;
        });
    }

    function showNewKeyInstructions(key, version, instructions, autoAdded = false) {
        const newKeyDisplay = document.getElementById('new-key-display');
        
        let instructionsList = '';
        if (instructions && Array.isArray(instructions)) {
            instructionsList = instructions.map(inst => inst ? `<div class="text-sm">${inst}</div>` : '<br>').join('');
        } else {
            // Default instructions for manual process
            if (autoAdded) {
                instructionsList = `
                    <div class="text-sm">✅ Key successfully added to .env file as:</div>
                    <div class="text-sm font-mono bg-gray-100 p-2 rounded mt-1">BACKUP_KEY_${version.toUpperCase()}=${key.substring(0, 20)}...</div>
                    <br>
                    <div class="text-sm">Click "Activate Key" below to make this your current encryption key.</div>
                `;
            } else {
                instructionsList = `
                    <div class="text-sm">Add this to your .env file:</div>
                    <div class="text-sm font-mono bg-gray-100 p-2 rounded mt-1">BACKUP_KEY_${version.toUpperCase()}=${key}</div>
                    <br>
                    <div class="text-sm">To activate this key, update:</div>
                    <div class="text-sm font-mono bg-gray-100 p-2 rounded mt-1">BACKUP_KEY_CURRENT=${version}</div>
                `;
            }
        }
        
        // Determine the color scheme based on whether it was auto-added
        const colorScheme = autoAdded ? 'green' : 'yellow';
        const bgColor = autoAdded ? 'bg-green-50 border-green-200' : 'bg-yellow-50 border-yellow-200';
        const iconColor = autoAdded ? 'text-green-400' : 'text-yellow-400';
        const textColor = autoAdded ? 'text-green-800' : 'text-yellow-800';
        const buttonColor = autoAdded ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200';
        
        const title = autoAdded ? 'New Encryption Key Added to .env' : 'New Encryption Key Generated';
        
        // Add activate button if key was auto-added
        const activateButton = autoAdded ? `
            <button type="button" onclick="activateKey('${version}')" class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 ml-2">
                Activate Key
            </button>
        ` : '';
        
        newKeyDisplay.className = `p-3 ${bgColor} border rounded-lg`;
        newKeyDisplay.innerHTML = `
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 ${iconColor}" viewBox="0 0 20 20" fill="currentColor">
                        ${autoAdded 
                            ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />'
                            : '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />'
                        }
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <h4 class="text-sm font-medium ${textColor}">${title}</h4>
                    <div class="mt-2 ${textColor.replace('800', '700')}">
                        ${instructionsList}
                    </div>
                    <div class="mt-3">
                        <button type="button" onclick="copyToClipboard('${key}')" class="px-3 py-1 text-xs ${buttonColor} rounded-md">
                            Copy Key
                        </button>
                        ${activateButton}
                    </div>
                </div>
            </div>
        `;
        newKeyDisplay.classList.remove('hidden');
    }

    // Function to activate a key
    window.activateKey = function(version) {

        fetch('{{ route("encryption.activate-key") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ version: version })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                loadKeyStatus(); // Refresh key status and dropdown
                
                // Hide the new key display
                document.getElementById('new-key-display').classList.add('hidden');
            } else {
                showNotification('Error: ' + (data.error || 'Failed to activate key'), 'error');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            showNotification('Failed to activate key', 'error');
        });
    };

    window.copyToClipboard = function(text) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('Key copied to clipboard!', 'success', 3000);
        }).catch(err => {
            console.error('Failed to copy:', err);
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showNotification('Key copied to clipboard!', 'success', 3000);
        });
    };
});
</script>

        <!-- Backup Schedule Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Backup Schedule</h2>
            @if($isSystemOffline)
                <div class="bg-orange-50 border border-orange-200 text-orange-800 px-4 py-3 rounded mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-orange-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm">
                                <strong>System is offline:</strong> Scheduled backups will only work for local destinations. Remote backups will be skipped until the system is back online.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
            <form method="POST" action="{{ route('backup.schedule.create') }}" class="mb-6" id="schedule-create-form">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Frequency</label>
                        <select name="frequency" id="frequency" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="toggleDaysOfWeek(this.value)">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Time</label>
                        <input type="time" name="time" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                    </div>
                    <div id="daysOfWeekDiv" style="display:none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Days of Week (for Weekly)</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="days_of_week[]" value="{{ $day }}" class="rounded border-gray-300 text-blue-500 focus:ring-blue-500">
                                    <span class="ml-2">{{ $day }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Retention Days <span class="text-xs text-gray-400">(blank = global)</span></label>
                        <input type="number" name="retention_days" min="1" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. 30" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Max Backups <span class="text-xs text-gray-400">(blank = global)</span></label>
                        <input type="number" name="max_backups" min="1" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. 10" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Source Directories</label>
                        <select name="source_directories[]" multiple required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach(App\Models\BackupSourceDirectory::pluck('path') as $dir)
                                <option value="{{ $dir }}">{{ $dir }}</option>
                            @endforeach
                        </select>
                        <small class="text-gray-500">Hold Ctrl (Windows) or Cmd (Mac) to select multiple directories.</small>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Destination Directory</label>
                        <select name="destination_directory" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach(App\Models\BackupDestinationDirectory::pluck('path') as $dir)
                                <option value="{{ $dir }}">{{ $dir }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" id="create-schedule-btn" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Create Schedule
                    </button>
                    <span id="schedule-progress" class="ml-4 text-blue-500 hidden">Creating schedule...</span>
                </div>
            </form>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Existing Schedules</h3>
            <table class="min-w-full divide-y divide-gray-200 mb-2">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Frequency</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Retention Days</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Max Backups</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source(s)</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enabled</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach(App\Models\BackupSchedule::all() as $schedule)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ ucfirst($schedule->frequency) }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $schedule->time }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $schedule->days_of_week ?? '-' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $schedule->retention_days ?? '-' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $schedule->max_backups ?? '-' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ is_array($schedule->source_directories) ? implode(', ', $schedule->source_directories) : $schedule->source_directories }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $schedule->destination_directory }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $schedule->enabled ? 'Yes' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <script>
                function toggleDaysOfWeek(val) {
                    document.getElementById('daysOfWeekDiv').style.display = (val === 'weekly') ? '' : 'none';
                }
                document.addEventListener('DOMContentLoaded', function() {
                    toggleDaysOfWeek(document.getElementById('frequency').value);
                });
            </script>
        </div>

       

        <!-- End Backup Configuration UI -->
    </div>
</x-settings-layout> 
@extends('layouts.dashboard')
    <style>
        html, body {
            height: 100%;
            min-height: 100%;
            overflow-y: auto !important;
        }
        body {
            position: static !important;
        }
        .overflow-y-auto, .overflow-x-auto {
            overflow: auto !important;
        }
    </style>
    @section('content')
    @php
        $manualOffline = session('manual_offline', false);
        $linux = new \App\Services\LinuxBackupService();
        $actuallyOffline = $manualOffline ? false : !$linux->isReachable(5);
        $isSystemOffline = $manualOffline || $actuallyOffline;
    @endphp
    <div class="p-6">
        @if ($errors->has('backup'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ $errors->first('backup') }}
            </div>
        @endif
        @if (session('status'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('status') }}
            </div>
        @endif

    
        <!-- Backup Configuration Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Backup Configuration</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Storage Location -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Storage Location</label>
                    <input type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-100" value="{{ $backupConfig->storage_location ?? 'local' }}" readonly>
                </div>
                <!-- Backup Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Backup Type</label>
                    <input type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-100" value="{{ $backupConfig->backup_type ?? 'full' }}" readonly>
                </div>
                <!-- Compression Level -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Compression Level</label>
                    <input type="text" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-100" value="{{ $backupConfig->compression_level ?? 'none' }}" readonly>
                </div>
                <!-- Retention Period -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Retention Period (days)</label>
                    <input type="number" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-100" value="{{ $backupConfig->retention_period ?? 30 }}" readonly>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('settings.backup-configuration') }}" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Edit in Settings</a>
            </div>
        </div>
       
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
            <!-- Create Backup Schedule Form -->
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
                            @foreach($sourceDirectories as $dir)
                                <option value="{{ $dir }}">{{ $dir }}</option>
                            @endforeach
                        </select>
                        <small class="text-gray-500">Hold Ctrl (Windows) or Cmd (Mac) to select multiple directories.</small>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Destination Directory</label>
                        <select name="destination_directory" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="" disabled selected hidden>Select a destination directory</option>
                            @foreach($destinationDirectories as $dir)
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
            <!-- List Existing Schedules -->
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Existing Schedules</h3>
            <div id="schedule-table-container">
                @include('backup.schedule-table', ['schedules' => $schedules])
            </div>
            <script>
                function toggleDaysOfWeek(val) {
                    document.getElementById('daysOfWeekDiv').style.display = (val === 'weekly') ? '' : 'none';
                }
                document.addEventListener('DOMContentLoaded', function() {
                    toggleDaysOfWeek(document.getElementById('frequency').value);
                });
            </script>
        </div>

        


        <!-- Manual Backup Section -->
        <div id="manual-backup" class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Manual Backup</h2>
            <!-- Source Directories Section -->
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Source Directories</h3>
            <form method="POST" action="{{ route('backup.addSourceDirectory') }}" class="mb-4 flex items-center space-x-2">
                @csrf
                <input type="text" name="path" placeholder="/absolute/path/to/directory" required class="w-1/2 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                <button type="submit" class="bg-green-500 text-white px-3 py-2 rounded-md hover:bg-green-600">Add Directory</button>
            </form>
            <div id="source-directory-list-container">
            <ul class="mb-4">
                @foreach(App\Models\BackupSourceDirectory::all() as $dir)
                    <li class="flex items-center justify-between py-1">
                        <span>{{ $dir->path }}</span>
                        <form method="POST" action="{{ route('backup.deleteSourceDirectory', $dir->id) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 ml-2">Delete</button>
                        </form>
                    </li>
                @endforeach
            </ul>
            </div>
            <!-- Destination Directories Section (only for local) -->
            <div id="add-destination-group">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Destination Directories (Local Only)</h3>
                <form method="POST" action="{{ route('backup.addDestinationDirectory') }}" class="mb-4 flex items-center space-x-2">
                    @csrf
                    <input type="text" name="path" placeholder="/absolute/path/to/destination" required class="w-1/2 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    <button type="submit" class="bg-green-500 text-white px-3 py-2 rounded-md hover:bg-green-600">Add Destination</button>
                </form>
                <div id="destination-directory-list-container">
                <ul class="mb-4">
                    @foreach(App\Models\BackupDestinationDirectory::all() as $dir)
                        <li class="flex items-center justify-between py-1">
                            <span>{{ $dir->path }}</span>
                            <form method="POST" action="{{ route('backup.deleteDestinationDirectory', $dir->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 ml-2">Delete</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
                </div>
            </div>
            <form method="POST" action="{{ route('backup.start') }}" id="manual-backup-form">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Storage Location</label>
                    <select id="manual_storage_location" name="storage_location" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="local" {{ $isSystemOffline ? 'selected' : '' }}>Local only</option>
                        @if(!$isSystemOffline)
                            <option value="remote">Remote server only</option>
                            <option value="both" selected>Both (Local + Remote)</option>
                            <option disabled>──────────</option>
                            <option value="s3" disabled title="Not implemented yet">Amazon S3 (coming soon)</option>
                            <option value="gcs" disabled title="Not implemented yet">Google Cloud Storage (coming soon)</option>
                            <option value="azure" disabled title="Not implemented yet">Azure Blob Storage (coming soon)</option>
                            <option value="b2" disabled title="Not implemented yet">Backblaze B2 (coming soon)</option>
                            <option value="dropbox" disabled title="Not implemented yet">Dropbox (coming soon)</option>
                        @endif
                    </select>
                    @if($isSystemOffline)
                        <p class="mt-1 text-xs text-orange-600">⚠️ System is offline - only local storage is available.</p>
                    @else
                        <p class="mt-1 text-xs text-gray-500">Cloud providers are shown for visibility and will be enabled in a future update.</p>
                    @endif
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Backup Type</label>
                    <select name="backup_type" id="manual-backup-type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="full">Full Backup</option>
                        <option value="incremental">Incremental Backup</option>
                        <option value="differential">Differential Backup</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Compression Level</label>
                    <select name="compression_level" id="manual-compression-level" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="none">None</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Source Directories to Backup</label>
                    <select name="source_directories[]" multiple required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($sourceDirectories as $dir)
                            <option value="{{ $dir }}">{{ $dir }}</option>
                        @endforeach
                    </select>
                    <small class="text-gray-500">Hold Ctrl (Windows) or Cmd (Mac) to select multiple directories.</small>
                </div>
                <div class="mb-4" id="manual-destination-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Destination Directory</label>
                    <select name="destination_directory" id="manual-destination-select" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="" disabled selected hidden>Select a destination directory</option>
                        @foreach($destinationDirectories as $dir)
                            <option value="{{ $dir }}">{{ $dir }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4 hidden" id="manual-cloud-group"></div>
                <button type="submit" id="start-backup-btn" class="bg-green-500 text-white px-6 py-2 rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Start Backup Now
                </button>
                @php
                    $last = \App\Models\BackupHistory::where('status','completed')->orderByDesc('completed_at')->first();
                    $lastAt = null;
                    if ($last) {
                        if (!empty($last->completed_at)) {
                            $lastAt = \Carbon\Carbon::parse($last->completed_at);
                        } elseif (!empty($last->created_at)) {
                            $lastAt = \Carbon\Carbon::parse($last->created_at);
                        }
                    }
                @endphp
                <span class="text-sm text-gray-500">Last backup: <span id="last-backup-relative">{{ $lastAt ? $lastAt->diffForHumans() : 'never' }}</span></span>
                <div id="backup-progress" class="mt-4 hidden flex items-center">
                    <svg class="animate-spin h-5 w-5 text-blue-500 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    <span id="backup-progress-message">Backup in progress...</span>
                </div>
            </form>
        </div>

        <!-- Backup History Table -->
        <div id="backup-history" class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Backup History</h2>
            <div id="backup-history-table-container">
                @include('backup.history-table')
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
 @endsection
<!-- Custom Delete Confirmation Modal -->
<div id="delete-confirm-modal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="px-6 py-4">
                <div class="flex items-start">
                    <div class="mx-auto shrink-0 flex items-center justify-center w-12 h-12 rounded-full bg-red-100 sm:mx-0 sm:w-10 sm:h-10">
                        <svg class="w-6 h-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div class="ms-4 text-start">
                        <h3 class="text-lg font-medium text-gray-900" id="delete-modal-title">Confirm Deletion</h3>
                        <div class="mt-2 text-sm text-gray-600" id="delete-modal-message">Are you sure you want to delete this item?</div>
                    </div>
                </div>
            </div>
            <div class="flex flex-row justify-end gap-2 px-6 py-4 bg-gray-100">
                <button id="delete-modal-cancel" type="button" class="px-4 py-2 text-sm rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">Cancel</button>
                <button id="delete-modal-confirm" type="button" class="px-4 py-2 text-sm rounded-md bg-red-600 text-white hover:bg-red-700">Delete</button>
            </div>
        </div>
    </div>
    
    <!-- Focus trap sentinel -->
    <button class="sr-only" aria-hidden="true">close</button>
    
    <style>
        /* simple focus lock for keyboard users */
        #delete-confirm-modal:focus-within { outline: none; }
    </style>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isSystemOffline = {{ $isSystemOffline ? 'true' : 'false' }};
    
    function updateManualBackupDestination() {
        const storage = document.getElementById('manual_storage_location').value;
        const destGroup = document.getElementById('manual-destination-group');
        const cloudGroup = document.getElementById('manual-cloud-group');
        const addDestGroup = document.getElementById('add-destination-group');
        
        // If system is offline, force local storage only
        if (isSystemOffline && (storage === 'remote' || storage === 'both')) {
            document.getElementById('manual_storage_location').value = 'local';
            return updateManualBackupDestination(); // Recursive call with corrected value
        }
        
        if (storage === 'local' || storage === 'both') {
            destGroup.classList.remove('hidden');
            addDestGroup.classList.remove('hidden');
            cloudGroup.classList.add('hidden');
        } else if (storage === 'remote') {
            destGroup.classList.add('hidden');
            addDestGroup.classList.add('hidden');
            cloudGroup.classList.add('hidden');
        }
    }
    document.getElementById('manual_storage_location').addEventListener('change', updateManualBackupDestination);
    updateManualBackupDestination();

    // Manual Backup AJAX (already present)
    const form = document.getElementById('manual-backup-form');
    const progressDiv = document.getElementById('backup-progress');
    const progressMsg = document.getElementById('backup-progress-message');
    const submitBtn = document.getElementById('start-backup-btn');

    // Simple poller that reuses the existing animation UI
    function pollBackupStatus(jobId) {
        const pollIntervalMs = 2000;
        const maxAttempts = 180; // ~6 minutes
        let attempts = 0;

        function tick() {
            attempts++;
            fetch(`/backup/status/${jobId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(json => {
                if (!json.success) throw new Error('Status fetch failed');
                const data = json.data || {};

                // Keep the same spinner visible and update message text only
                progressDiv.classList.remove('hidden');
                const parts = [`Status: ${data.status}`];
                if (data.files_processed) parts.push(`Files: ${data.files_processed}`);
                if (data.size_processed) parts.push(`Size: ${Math.round((data.size_processed||0)/1024/1024)} MB`);
                if (data.error) parts.push(`Error: ${data.error}`);
                progressMsg.textContent = parts.join(' • ');

                if (data.status === 'completed' || data.status === 'failed') {
                    submitBtn.disabled = false;
                    progressDiv.classList.add('hidden');
                    if (data.status === 'completed') {
                        showToast('Backup completed successfully!', 'success');
                        if (typeof refreshBackupHistoryTable === 'function') {
                            refreshBackupHistoryTable();
                        }
                        const lastSpan = document.getElementById('last-backup-relative');
                        if (lastSpan) lastSpan.textContent = 'just now';
                    } else {
                        showToast(data.error ? `Backup failed: ${data.error}` : 'Backup failed.', 'error');
                    }
                } else if (attempts < maxAttempts) {
                    setTimeout(tick, pollIntervalMs);
                } else {
                    // Stop polling after timeout but keep UI usable
                    submitBtn.disabled = false;
                    progressDiv.classList.add('hidden');
                    showToast('Stopping status polling due to timeout.', 'warning');
                }
            })
            .catch(() => {
                if (attempts < maxAttempts) {
                    setTimeout(tick, pollIntervalMs);
                } else {
                    submitBtn.disabled = false;
                    progressDiv.classList.add('hidden');
                    showToast('Error polling backup status.', 'error');
                }
            });
        }

        tick();
    }

    async function ensureAgentOnline() {
        try {
            const res = await fetch('/backup/check-agent', { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
            const j = await res.json();
            return j && j.success && j.data && j.data.online;
        } catch { return false; }
    }

    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            submitBtn.disabled = true;
            const online = await ensureAgentOnline();
            if (!online) {
                submitBtn.disabled = false;
                showToast('No online agents found. Please start the agent and try again.', 'error');
                return;
            }
            // Show the same animation under the button
            progressDiv.classList.remove('hidden');
            progressMsg.textContent = 'Creating backup job...';

            const formData = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.job_id) {
                    // Start polling without changing the animation block
                    pollBackupStatus(data.data.job_id);
                } else {
                    submitBtn.disabled = false;
                    progressDiv.classList.add('hidden');
                    showToast(data.message || 'Backup failed.', 'error');
                }
            })
            .catch(() => {
                submitBtn.disabled = false;
                progressDiv.classList.add('hidden');
                showToast('Backup failed. Please try again.', 'error');
            });
        });
    }

    // AJAX for Add Source Directory
    document.querySelectorAll('form[action$="backup.addSourceDirectory"]').forEach(function(addForm) {
        const spinner = document.createElement('span');
        spinner.className = 'ml-2 hidden animate-spin inline-block w-5 h-5 border-2 border-blue-400 border-t-transparent rounded-full align-middle';
        addForm.querySelector('button[type="submit"]').after(spinner);
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = addForm.querySelector('button[type="submit"]');
            btn.disabled = true;
            spinner.classList.remove('hidden');
            const formData = new FormData(addForm);
            fetch(addForm.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': addForm.querySelector('input[name="_token"]').value
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                spinner.classList.add('hidden');
                if (data.success) {
                    showToast('Directory added successfully!', 'success');
                    refreshSourceDirectoryList();
                } else {
                    showToast(data.message || 'Failed to add directory.', 'error');
                }
            })
            .catch(() => {
                btn.disabled = false;
                spinner.classList.add('hidden');
                showToast('Failed to add directory.', 'error');
            });
        });
    });

    // Custom modal helpers
    const modal = document.getElementById('delete-confirm-modal');
    const modalMsg = document.getElementById('delete-modal-message');
    const modalTitle = document.getElementById('delete-modal-title');
    const modalCancel = document.getElementById('delete-modal-cancel');
    const modalConfirm = document.getElementById('delete-modal-confirm');
    let pendingDeleteForm = null;
    let pendingDeleteType = '';

    function openDeleteModal(message, title) {
        modalMsg.textContent = message || 'Are you sure you want to delete this item?';
        modalTitle.textContent = title || 'Confirm Deletion';
        modal.classList.remove('hidden');
        // basic focus move
        modalConfirm.focus();
    }
    function closeDeleteModal() {
        modal.classList.add('hidden');
        pendingDeleteForm = null;
        pendingDeleteType = '';
    }
    modalCancel.addEventListener('click', closeDeleteModal);

    function performDelete(form) {
        const btn = form.querySelector('button[type="submit"]');
        if (btn) btn.disabled = true;
        const formData = new FormData(form);
        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': (form.querySelector('input[name="_token"]').value)
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (btn) btn.disabled = false;
            if (data.success) {
                if (pendingDeleteType === 'source') {
                    showToast('Directory removed successfully!', 'success');
                    if (typeof refreshSourceDirectoryList === 'function') {
                        refreshSourceDirectoryList();
                    }
                } else if (pendingDeleteType === 'destination') {
                    showToast('Destination removed successfully!', 'success');
                    if (typeof refreshDestinationDirectoryList === 'function') {
                        refreshDestinationDirectoryList();
                    }
                } else {
                    showToast('Deleted successfully!', 'success');
                }
            } else {
                showToast(data.message || 'Failed to delete.', 'error');
            }
        })
        .catch(() => {
            if (btn) btn.disabled = false;
            showToast('Failed to delete.', 'error');
        })
        .finally(() => closeDeleteModal());
    }

    modalConfirm.addEventListener('click', function() {
        if (pendingDeleteForm) {
            performDelete(pendingDeleteForm);
        } else {
            closeDeleteModal();
        }
    });

    // Intercept deletes and open modal
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form && form.matches('form[action*="/backup/source-directory/"]')) {
            e.preventDefault();
            pendingDeleteForm = form;
            pendingDeleteType = 'source';
            openDeleteModal('Are you sure you want to remove this directory?', 'Remove Source Directory');
        }
        if (form && form.matches('form[action*="/backup/destination-directory/"]')) {
            e.preventDefault();
            pendingDeleteForm = form;
            pendingDeleteType = 'destination';
            openDeleteModal('Are you sure you want to remove this destination directory?', 'Remove Destination Directory');
        }
    }, true);

    // AJAX for Add Destination Directory
    document.querySelectorAll('form[action$="backup.addDestinationDirectory"]').forEach(function(addForm) {
        const spinner = document.createElement('span');
        spinner.className = 'ml-2 hidden animate-spin inline-block w-5 h-5 border-2 border-blue-400 border-t-transparent rounded-full align-middle';
        addForm.querySelector('button[type="submit"]').after(spinner);
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = addForm.querySelector('button[type="submit"]');
            btn.disabled = true;
            spinner.classList.remove('hidden');
            const formData = new FormData(addForm);
            fetch(addForm.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': addForm.querySelector('input[name="_token"]').value
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                spinner.classList.add('hidden');
                if (data.success) {
                    showToast('Destination added successfully!', 'success');
                    refreshDestinationDirectoryList();
                } else {
                    showToast(data.message || 'Failed to add destination.', 'error');
                }
            })
            .catch(() => {
                btn.disabled = false;
                spinner.classList.add('hidden');
                showToast('Failed to add destination.', 'error');
            });
        });
    });

    // (Destination deletion is handled by the same modal interception above)

    // AJAX for Schedule Creation
    const scheduleForm = document.getElementById('schedule-create-form');
    const scheduleBtn = document.getElementById('create-schedule-btn');
    const scheduleProgress = document.getElementById('schedule-progress');
    if (scheduleForm) {
        scheduleForm.addEventListener('submit', function(e) {
            e.preventDefault();
            scheduleBtn.disabled = true;
            scheduleProgress.classList.remove('hidden');
            const formData = new FormData(scheduleForm);
            fetch(scheduleForm.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': scheduleForm.querySelector('input[name="_token"]').value
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                scheduleBtn.disabled = false;
                scheduleProgress.classList.add('hidden');
                if (data.success) {
                    showToast('Backup schedule created successfully!', 'success');
                    refreshScheduleTable();
                } else {
                    showToast(data.message || 'Failed to create schedule.', 'error');
                }
            })
            .catch(() => {
                scheduleBtn.disabled = false;
                scheduleProgress.classList.add('hidden');
                showToast('Failed to create schedule.', 'error');
            });
        });
    }

    function showToast(message, type) {
        let toast = document.createElement('div');
        toast.textContent = message;
        toast.className = 'fixed top-5 right-5 z-50 px-4 py-2 rounded shadow text-white ' + (type === 'success' ? 'bg-green-500' : 'bg-red-500');
        document.body.appendChild(toast);
        setTimeout(() => { toast.remove(); }, 3000);
    }

    function refreshBackupHistoryTable() {
        const container = document.getElementById('backup-history-table-container');
        if (!container) return;
        container.innerHTML = '<div class="text-center py-4"><span class="animate-spin inline-block w-6 h-6 border-4 border-blue-400 border-t-transparent rounded-full"></span> Loading...</div>';
        fetch('/backup/history/fragment', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            container.innerHTML = html;
        })
        .catch(() => {
            container.innerHTML = '<div class="text-red-500">Failed to load backup history.</div>';
        });
    }

    function refreshScheduleTable() {
        const container = document.getElementById('schedule-table-container');
        if (!container) return;
        container.innerHTML = '<div class="text-center py-4"><span class="animate-spin inline-block w-6 h-6 border-4 border-blue-400 border-t-transparent rounded-full"></span> Loading...</div>';
        fetch('/backup/schedule/fragment', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            container.innerHTML = html;
        })
        .catch(() => {
            container.innerHTML = '<div class="text-red-500">Failed to load schedule table.</div>';
        });
    }

    // Refresh Source Directory List
    function refreshSourceDirectoryList() {
        fetch('/backup/management?fragment=source')
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContainer = doc.querySelector('#source-directory-list-container');
                if (newContainer) {
                    const oldContainer = document.querySelector('#source-directory-list-container');
                    if (oldContainer) oldContainer.replaceWith(newContainer);
                }
            });
    }
    // Refresh Destination Directory List
    function refreshDestinationDirectoryList() {
        fetch('/backup/management?fragment=destination')
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContainer = doc.querySelector('#destination-directory-list-container');
                if (newContainer) {
                    const oldContainer = document.querySelector('#destination-directory-list-container');
                    if (oldContainer) oldContainer.replaceWith(newContainer);
                }
            });
    }
});
</script> 

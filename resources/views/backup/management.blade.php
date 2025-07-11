<x-dashboard-layout>
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
                    @foreach(\App\Models\BackupSchedule::all() as $schedule)
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

        

        <!-- Manual Backup Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Manual Backup</h2>
            <!-- Add Source Directory Form -->
            <form method="POST" action="{{ route('backup.addSourceDirectory') }}" class="mb-4 flex items-center space-x-2">
                @csrf
                <input type="text" name="path" placeholder="/absolute/path/to/directory" required class="w-1/2 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                <button type="submit" class="bg-green-500 text-white px-3 py-2 rounded-md hover:bg-green-600">Add Directory</button>
            </form>
            <!-- List of Source Directories with Delete Option -->
            <ul class="mb-4">
                @foreach(App\Models\BackupSourceDirectory::all() as $dir)
                    <li class="flex items-center justify-between py-1">
                        <span>{{ $dir->path }}</span>
                        <form method="POST" action="{{ route('backup.deleteSourceDirectory', $dir->id) }}" onsubmit="return confirm('Are you sure you want to remove this directory?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 ml-2">Delete</button>
                        </form>
                    </li>
                @endforeach
            </ul>
            <!-- Add Destination Directory Form -->
            <form method="POST" action="{{ route('backup.addDestinationDirectory') }}" class="mb-4 flex items-center space-x-2">
                @csrf
                <input type="text" name="path" placeholder="/absolute/path/to/destination" required class="w-1/2 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                <button type="submit" class="bg-green-500 text-white px-3 py-2 rounded-md hover:bg-green-600">Add Destination</button>
            </form>
            <!-- List of Destination Directories with Delete Option -->
            <ul class="mb-4">
                @foreach(App\Models\BackupDestinationDirectory::all() as $dir)
                    <li class="flex items-center justify-between py-1">
                        <span>{{ $dir->path }}</span>
                        <form method="POST" action="{{ route('backup.deleteDestinationDirectory', $dir->id) }}" onsubmit="return confirm('Are you sure you want to remove this destination directory?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 ml-2">Delete</button>
                        </form>
                    </li>
                @endforeach
            </ul>
            <form method="POST" action="{{ route('backup.start') }}" id="manual-backup-form">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Source Directories to Backup</label>
                    <select name="source_directories[]" multiple required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($sourceDirectories as $dir)
                            <option value="{{ $dir }}">{{ $dir }}</option>
                        @endforeach
                    </select>
                    <small class="text-gray-500">Hold Ctrl (Windows) or Cmd (Mac) to select multiple directories.</small>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Destination Directory</label>
                    <select name="destination_directory" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($destinationDirectories as $dir)
                            <option value="{{ $dir }}">{{ $dir }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" id="start-backup-btn" class="bg-green-500 text-white px-6 py-2 rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Start Backup Now
                </button>
                <span class="text-sm text-gray-500">Last backup: 2 hours ago</span>
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
        <div class="bg-white rounded-lg shadow-md p-6">
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
</x-dashboard-layout> 
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manual Backup AJAX (already present)
    const form = document.getElementById('manual-backup-form');
    const progressDiv = document.getElementById('backup-progress');
    const progressMsg = document.getElementById('backup-progress-message');
    const submitBtn = document.getElementById('start-backup-btn');

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            progressDiv.classList.remove('hidden');
            progressMsg.textContent = 'Backup in progress...';
            submitBtn.disabled = true;

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
                progressDiv.classList.add('hidden');
                submitBtn.disabled = false;
                if (data.success) {
                    showToast('Backup completed successfully!', 'success');
                    refreshBackupHistoryTable();
                } else {
                    showToast(data.message || 'Backup failed.', 'error');
                }
            })
            .catch(err => {
                progressDiv.classList.add('hidden');
                submitBtn.disabled = false;
                showToast('Backup failed. Please try again.', 'error');
            });
        });
    }

    // AJAX for Add Source Directory
    document.querySelectorAll('form[action$="backup.addSourceDirectory"]').forEach(function(addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = addForm.querySelector('button[type="submit"]');
            btn.disabled = true;
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
                if (data.success) {
                    showToast('Directory added successfully!', 'success');
                    refreshBackupHistoryTable();
                } else {
                    showToast(data.message || 'Failed to add directory.', 'error');
                }
            })
            .catch(() => {
                btn.disabled = false;
                showToast('Failed to add directory.', 'error');
            });
        });
    });

    // AJAX for Delete Source Directory
    document.querySelectorAll('form[action*="backup.deleteSourceDirectory"]').forEach(function(delForm) {
        delForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to remove this directory?')) return;
            const btn = delForm.querySelector('button[type="submit"]');
            btn.disabled = true;
            const formData = new FormData(delForm);
            fetch(delForm.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': delForm.querySelector('input[name="_token"]').value
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                if (data.success) {
                    showToast('Directory removed successfully!', 'success');
                    refreshBackupHistoryTable();
                } else {
                    showToast(data.message || 'Failed to remove directory.', 'error');
                }
            })
            .catch(() => {
                btn.disabled = false;
                showToast('Failed to remove directory.', 'error');
            });
        });
    });

    // AJAX for Add Destination Directory
    document.querySelectorAll('form[action$="backup.addDestinationDirectory"]').forEach(function(addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = addForm.querySelector('button[type="submit"]');
            btn.disabled = true;
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
                if (data.success) {
                    showToast('Destination added successfully!', 'success');
                    refreshBackupHistoryTable();
                } else {
                    showToast(data.message || 'Failed to add destination.', 'error');
                }
            })
            .catch(() => {
                btn.disabled = false;
                showToast('Failed to add destination.', 'error');
            });
        });
    });

    // AJAX for Delete Destination Directory
    document.querySelectorAll('form[action*="backup.deleteDestinationDirectory"]').forEach(function(delForm) {
        delForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to remove this destination directory?')) return;
            const btn = delForm.querySelector('button[type="submit"]');
            btn.disabled = true;
            const formData = new FormData(delForm);
            fetch(delForm.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': delForm.querySelector('input[name="_token"]').value
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                if (data.success) {
                    showToast('Destination removed successfully!', 'success');
                    refreshBackupHistoryTable();
                } else {
                    showToast(data.message || 'Failed to remove destination.', 'error');
                }
            })
            .catch(() => {
                btn.disabled = false;
                showToast('Failed to remove destination.', 'error');
            });
        });
    });

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
                    refreshBackupHistoryTable();
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
});
</script> 
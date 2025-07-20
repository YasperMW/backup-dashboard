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

        <!-- Backup Configuration Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 mt-10">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Backup Configuration</h2>
            <form id="backup-config-form">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Storage Location -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Storage Location</label>
                        <select id="storage_location" name="storage_location" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="local">Local Storage</option>
                            <option value="s3">Amazon S3</option>
                            <option value="gcs">Google Cloud Storage</option>
                            <option value="azure">Azure Blob Storage</option>
                        </select>
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
    // Load current config
    fetch('/backup/config')
        .then(res => res.json())
        .then(cfg => {
            document.getElementById('storage_location').value = cfg.storage_location || 'local';
            document.getElementById('backup_type').value = cfg.backup_type || 'full';
            document.getElementById('compression_level').value = cfg.compression_level || 'none';
            document.getElementById('retention_period').value = cfg.retention_period || 30;
        });
    // Save config
    document.getElementById('backup-config-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const data = {
            storage_location: document.getElementById('storage_location').value,
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
});
</script>

        <!-- Backup Schedule Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Backup Schedule</h2>
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
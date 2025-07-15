<style>
    .hidden-row {
        display: none;
    }
</style>
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
                        <input id="filter-date-from" type="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <input id="filter-date-to" type="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
                <!-- Backup Type Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Backup Type</label>
                    <select id="filter-type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Types</option>
                        <option value="full">Full Backup</option>
                        <option value="incremental">Incremental Backup</option>
                        <option value="differential">Differential Backup</option>
                    </select>
                </div>
            </div>
            <!-- Backup List -->
            <div class="mt-6">
                <div class="overflow-x-auto">
                    <div id="backup-list-loading" class="text-center text-gray-500 py-4 hidden">
                        <span class="animate-spin inline-block w-6 h-6 border-4 border-blue-400 border-t-transparent rounded-full"></span> Loading backups...
                    </div>
                    <table id="backup-list-table" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Select</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Filename</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody id="backup-list-tbody" class="bg-white divide-y divide-gray-200">
                            @php
                                $backups = \App\Models\BackupHistory::orderByDesc('created_at')->get();
                                $showLimit = 5;
                            @endphp
                            @foreach($backups as $i => $history)
                            @php
                                $filePath = $history->destination_directory . DIRECTORY_SEPARATOR . $history->filename;
                                $fileExists = file_exists($filePath);
                            @endphp
                            <tr class="{{ $i >= $showLimit ? 'hidden-row' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="radio" name="backup_id" value="{{ $history->id }}" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" @if(!$fileExists) disabled @endif>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $history->created_at }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $history->backup_type ?? 'Full' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $history->filename }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $history->size ? number_format($history->size / 1048576, 2) . ' MB' : '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if(!$fileExists)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-200 text-gray-800">Missing</span>
                                    @elseif($history->status === 'completed')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Available</span>
                                    @elseif($history->status === 'failed')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        @if($backups->count() > $showLimit)
                        <tfoot>
                        <tr>
                            <td colspan="6" class="text-center py-2">
                                <button id="view-more-backups" class="text-blue-600 hover:underline">View More</button>
                            </td>
                        </tr>
                        </tfoot>
                        @endif
                    </table>
                    <div id="backup-list-empty" class="text-gray-400 text-center py-4 hidden">No backups found for the selected filters.</div>
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
                        <input id="restore-path-input" type="text" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="/path/to/restore">
                        <button id="browse-restore-path" type="button" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                            Browse
                        </button>
                        <input id="restore-path-picker" type="file" style="display:none" webkitdirectory directory />
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
        <!-- (Backup Preview section removed) -->

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-4 mt-4">
            <button id="verify-integrity-btn" class="px-6 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                Verify Integrity
            </button>
            <button id="start-restore-btn" class="px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Start Restore
            </button>
        </div>
        <script>
        function showToast(message, type) {
            let toast = document.createElement('div');
            toast.textContent = message;
            toast.className = 'fixed top-5 right-5 z-50 px-4 py-2 rounded shadow text-white ' + (type === 'success' ? 'bg-green-500' : 'bg-red-500');
            document.body.appendChild(toast);
            setTimeout(() => { toast.remove(); }, 3000);
        }
        function showRestoreProgressModal() {
            document.getElementById('restore-progress-modal').classList.remove('hidden');
            updateRestoreProgress(10, 'Starting...', 'Preparing files...');
            let percent = 10;
            let interval = setInterval(() => {
                if (percent < 90) {
                    percent += Math.floor(Math.random() * 10) + 1;
                    if (percent > 90) percent = 90;
                    updateRestoreProgress(percent, 'Restoring files...', 'Restoring...');
                }
            }, 400);
            return interval;
        }
        function hideRestoreProgressModal() {
            document.getElementById('restore-progress-modal').classList.add('hidden');
        }
        function updateRestoreProgress(percent, text, file) {
            document.getElementById('restore-progress-bar').style.width = percent + '%';
            document.getElementById('restore-progress-text').textContent = text;
            document.getElementById('restore-progress-file').textContent = file;
        }
        
        const showLimit = 5;

        function renderBackupList(backups) {
            const tbody = document.getElementById('backup-list-tbody');
            tbody.innerHTML = '';
            let hasMore = backups.length > showLimit;
            backups.forEach((b, i) => {
                const tr = document.createElement('tr');
                tr.className = i >= showLimit ? 'hidden-row' : '';
                tr.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="radio" name="backup_id" value="${b.id}" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" ${b.status !== 'completed' ? 'disabled' : ''}>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${b.created_at}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${b.backup_type || 'Full'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${b.filename || ''}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${b.size ? (b.size/1048576).toFixed(2) + ' MB' : '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        ${b.status === 'completed' ? '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Available</span>' : b.status === 'failed' ? '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Failed</span>' : '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>'}
                    </td>
                `;
                tbody.appendChild(tr);
            });

            // Add or remove the View More button
            let tfoot = tbody.parentElement.querySelector('tfoot');
            if (tfoot) tfoot.remove();
            if (hasMore) {
                const table = document.getElementById('backup-list-table');
                tfoot = document.createElement('tfoot');
                tfoot.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-2">
                            <button id="view-more-backups" class="text-blue-600 hover:underline">View More</button>
                        </td>
                    </tr>
                `;
                table.appendChild(tfoot);
                document.getElementById('view-more-backups').addEventListener('click', function() {
                    document.querySelectorAll('.hidden-row').forEach(function(row) {
                        row.classList.remove('hidden-row');
                    });
                    this.style.display = 'none';
                });
            }
            document.getElementById('backup-list-empty').style.display = backups.length ? 'none' : '';
        }

        function fetchFilteredBackups() {
            document.getElementById('backup-list-loading').style.display = '';
            document.getElementById('backup-list-empty').style.display = 'none';
            const from = document.getElementById('filter-date-from').value;
            const to = document.getElementById('filter-date-to').value;
            const type = document.getElementById('filter-type').value;
            fetch('/backup/filter', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ from, to, type })
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('backup-list-loading').style.display = 'none';
                if (data.success) {
                    renderBackupList(data.backups);
                } else {
                    renderBackupList([]);
                }
            })
            .catch(() => {
                document.getElementById('backup-list-loading').style.display = 'none';
                renderBackupList([]);
            });
        }

        document.getElementById('filter-date-from').addEventListener('change', fetchFilteredBackups);
        document.getElementById('filter-date-to').addEventListener('change', fetchFilteredBackups);
        document.getElementById('filter-type').addEventListener('change', fetchFilteredBackups);

        // Initial setup for View More on page load
        // (If you want to support View More on initial render, keep this block)
        document.addEventListener('DOMContentLoaded', function() {
            var btn = document.getElementById('view-more-backups');
            if (btn) {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.hidden-row').forEach(function(row) {
                        row.classList.remove('hidden-row');
                    });
                    btn.style.display = 'none';
                });
            }
        });
        document.getElementById('start-restore-btn').addEventListener('click', function() {
            const selected = document.querySelector('input[name="backup_id"]:checked');
            const restorePath = document.querySelector('input[placeholder="/path/to/restore"]').value;
            const overwrite = document.querySelectorAll('input[type="checkbox"]')[0].checked;
            const preserve = document.querySelectorAll('input[type="checkbox"]')[1].checked;
            if (!selected) {
                showToast('Please select a backup to restore.', 'error');
                return;
            }
            if (!restorePath) {
                showToast('Please enter a restore path.', 'error');
                return;
            }
            this.disabled = true;
            let interval = showRestoreProgressModal();
            fetch('/backup/restore', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    backup_id: selected.value,
                    restore_path: restorePath,
                    overwrite: overwrite,
                    preserve_permissions: preserve
                })
            })
            .then(res => res.json())
            .then(data => {
                clearInterval(interval);
                updateRestoreProgress(100, 'Finishing...', 'Finalizing restore...');
                setTimeout(() => {
                    hideRestoreProgressModal();
                    this.disabled = false;
                    showToast(data.message, data.success ? 'success' : 'error');
                }, 800);
            })
            .catch(() => {
                clearInterval(interval);
                hideRestoreProgressModal();
                this.disabled = false;
                showToast('Restore failed.', 'error');
            });
        });
        document.getElementById('verify-integrity-btn').addEventListener('click', function() {
            const selected = document.querySelector('input[name="backup_id"]:checked');
            if (!selected) {
                showToast('Please select a backup to verify.', 'error');
                return;
            }
            this.disabled = true;
            fetch('/backup/verify-integrity', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    backup_id: selected.value
                })
            })
            .then(res => res.json())
            .then(data => {
                this.disabled = false;
                showToast(data.message, data.success ? 'success' : 'error');
            })
            .catch(() => {
                this.disabled = false;
                showToast('Verification failed.', 'error');
            });
        });
        document.getElementById('browse-restore-path').addEventListener('click', function() {
            document.getElementById('restore-path-picker').click();
        });
        document.getElementById('restore-path-picker').addEventListener('change', function(e) {
            if (this.files.length > 0) {
                // Get the directory path from the first file
                const fullPath = this.files[0].webkitRelativePath || this.files[0].name;
                const dir = fullPath.split('/')[0];
                document.getElementById('restore-path-input').value = '/' + dir;
            }
        });
        document.querySelectorAll('input[name="backup_id"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // No preview logic here as preview is removed
            });
        });
        </script>

        <!-- Restore Progress Modal (Hidden by default) -->
        <div id="restore-progress-modal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Restore in Progress</h3>
                <div class="mb-4">
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div id="restore-progress-bar" class="bg-blue-600 h-2.5 rounded-full" style="width: 10%"></div>
                    </div>
                    <p id="restore-progress-text" class="text-sm text-gray-500 mt-2">Starting...</p>
                </div>
                <div class="text-sm text-gray-700">
                    <p id="restore-progress-file">Preparing files...</p>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout> 
<style>
    .hidden-row {
        display: none;
    }
    .no-connection {
        opacity: 0.7;
        pointer-events: none;
    }
</style>
@extends('layouts.dashboard')
@section('content')
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody id="backup-list-tbody" class="bg-white divide-y divide-gray-200">
                            @php
                                $backups = \App\Models\BackupHistory::orderByDesc('created_at')->get();
                                $showLimit = 5;
                                $linux = new \App\Services\LinuxBackupService();
                                $manualOffline = session('manual_offline', false);
                                $actuallyOffline = $manualOffline ? false : !$linux->isReachable(5); // Skip connectivity check if manually offline
                                $isSystemOffline = $manualOffline || $actuallyOffline; // Either manual or actual offline
                                $remotePath = config('backup.remote_path');
                                $remotePathNorm = $remotePath ? rtrim(str_replace('\\', '/', $remotePath), '/') : '';
                            @endphp
                            @foreach($backups as $i => $history)
                            @php
                                $filePath = $history->destination_directory . DIRECTORY_SEPARATOR . $history->filename;
                                $destDirNorm = rtrim(str_replace('\\', '/', $history->destination_directory ?? ''), '/');
                                $isRemote = ($history->destination_type === 'remote') || ($remotePathNorm && $destDirNorm === $remotePathNorm);
                                $isOffline = $isSystemOffline && $isRemote;
                                
                                $fileExists = false;
                                if ($isOffline) {
                                    $fileExists = null; // unknown; no connection
                                } else if ($isRemote) {
                                    $remoteFilePath = str_replace('\\', '/', $filePath);
                                    $fileExists = $linux->exists($remoteFilePath);
                                    if (!$fileExists && $remotePathNorm) {
                                        $fallbackRemote = $remotePathNorm . '/' . $history->filename;
                                        $fileExists = $linux->exists($fallbackRemote);
                                    }
                                } else {
                                    $fileExists = file_exists($filePath);
                                }
                            @endphp
                            <tr class="{{ $i >= $showLimit ? 'hidden-row' : '' }} {{ $isOffline ? 'no-connection' : '' }}" @if($isOffline) title="No connection to remote server" @endif>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="radio" name="backup_id" value="{{ $history->id }}" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" @if(!$fileExists || $isOffline) disabled @endif>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $history->created_at }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $history->backup_type ?? 'Full' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $history->filename }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $history->size ? number_format($history->size / 1048576, 2) . ' MB' : '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @php
                                        $destType = $history->destination_type ?? null;
                                        if (!$destType) {
                                            $remotePath = config('backup.remote_path');
                                            $destDirNorm = rtrim(str_replace('\\', '/', $history->destination_directory ?? ''), '/');
                                            $remotePathNorm = rtrim(str_replace('\\', '/', $remotePath ?? ''), '/');
                                            $destType = ($remotePathNorm && $destDirNorm === $remotePathNorm) ? 'remote' : 'local';
                                        }
                                    @endphp
                                    <span class="inline-flex items-center gap-2">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ ($destType === 'remote') ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">{{ ucfirst($destType) }}</span>
                                        <span class="text-gray-500">{{ $history->destination_directory }}</span>
                                        @if($destType === 'remote')
                                            <span class="px-2 inline-flex text-[10px] leading-5 font-medium rounded-full bg-blue-50 text-blue-700 border border-blue-200" title="Remote file checked via SFTP at {{ config('backup.linux_host') }}">SFTP</span>
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($isRemote && $fileExists === null)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-200 text-gray-800">No connection</span>
                                    @elseif(!$fileExists)
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
                            <td colspan="7" class="text-center py-2">
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
                    <p class="mt-1 text-sm text-gray-500">For Docker: Use container paths like /tmp/restore_out. See Docker section below for host file copying.</p>
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
            // Start with a neutral state; actual text will be driven by agent polling
            updateRestoreProgress(5, 'Pending', '');
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
        const manualOffline = {{ session('manual_offline', false) ? 'true' : 'false' }};
        const actuallyOffline = {{ $actuallyOffline ? 'true' : 'false' }};
        const isSystemOffline = manualOffline || actuallyOffline;
        const remotePath = '{{ config('backup.remote_path') }}';
        const remotePathNorm = remotePath ? remotePath.replace(/\\/g, '/').replace(/\/+$/, '') : '';

        function renderBackupList(backups) {
            const tbody = document.getElementById('backup-list-tbody');
            tbody.innerHTML = '';
            let hasMore = backups.length > showLimit;
            backups.forEach((b, i) => {
                const destDirNorm = b.destination_directory ? b.destination_directory.replace(/\\/g, '/').replace(/\/+$/, '') : '';
                const isRemote = b.is_remote !== undefined ? b.is_remote : (b.destination_type === 'remote' || (remotePathNorm && destDirNorm === remotePathNorm));
                const isOffline = isSystemOffline && isRemote;
                
                const tr = document.createElement('tr');
                tr.className = i >= showLimit ? 'hidden-row' : '';
                if (isOffline) {
                    tr.classList.add('no-connection');
                    tr.title = 'No connection to remote server';
                }
                
                tr.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="radio" name="backup_id" value="${b.id}" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" ${(!b.exists || b.status !== 'completed' || isOffline) ? 'disabled' : ''}>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${b.created_at}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${b.backup_type || 'Full'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${b.filename || ''}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${b.size ? (b.size/1048576).toFixed(2) + ' MB' : '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${isRemote ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'}">${isRemote ? 'Remote' : 'Local'}</span>
                        <span class="text-gray-500 ml-2">${b.destination_directory || ''}</span>
                        ${isRemote ? '<span class="ml-2 px-2 inline-flex text-[10px] leading-5 font-medium rounded-full bg-blue-50 text-blue-700 border border-blue-200" title="Remote file checked via SFTP">SFTP</span>' : ''}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        ${(isOffline && isRemote) || (isRemote && b.exists === null)
                            ? '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-200 text-gray-800">No connection</span>'
                            : b.exists === false
                                ? '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Missing</span>'
                                : b.status === 'completed' && b.exists === true
                                    ? '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Available</span>'
                                    : b.status === 'failed'
                                        ? '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Failed</span>'
                                        : '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>'}
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
                        <td colspan="7" class="text-center py-2">
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
        function pollRestoreStatus(jobId, onDone) {
            const pollMs = 2000;
            const maxAttempts = 180; // ~6 minutes
            let attempts = 0;
            function tick() {
                attempts++;
                fetch(`/backup/status/${jobId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
                .then(r => r.json())
                .then(json => {
                    if (!json.success) throw new Error('status failed');
                    const d = json.data || {};
                    const phase = d.progress && d.progress.phase ? d.progress.phase : null;
                    const txt = `Status: ${d.status || ''}${phase ? ' • Phase: ' + phase : ''}`;
                    const file = d.backup_path ? `File: ${d.backup_path}` : '';
                    updateRestoreProgress( Math.min(95, 10 + attempts), txt, file );
                    if (d.status === 'completed' || d.status === 'failed') {
                        onDone(d);
                    } else if (attempts < maxAttempts) {
                        setTimeout(tick, pollMs);
                    } else {
                        onDone({ status: 'failed', error: 'Polling timeout' });
                    }
                })
                .catch(() => {
                    if (attempts < maxAttempts) setTimeout(tick, pollMs);
                    else onDone({ status: 'failed', error: 'Polling error' });
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

        document.getElementById('start-restore-btn').addEventListener('click', async function() {
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
            const online = await ensureAgentOnline();
            if (!online) {
                this.disabled = false;
                showToast('No online agents found. Please start the agent and try again.', 'error');
                return;
            }
            showRestoreProgressModal();
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
                if (data.success && data.data && data.data.job_id) {
                    pollRestoreStatus(data.data.job_id, (d) => {
                        const phase = d.progress && d.progress.phase ? d.progress.phase : null;
                        const finalText = d.status === 'completed' ? 'Completed' : `Failed${phase ? ' • Phase: ' + phase : ''}`;
                        updateRestoreProgress(100, finalText, d.backup_path || '');
                        setTimeout(() => {
                            hideRestoreProgressModal();
                            this.disabled = false;
                            if (d.status === 'completed') {
                                showToast('Restore completed successfully', 'success');
                            } else {
                                showToast(d.error || 'Restore failed', 'error');
                            }
                        }, 600);
                    });
                } else {
                    hideRestoreProgressModal();
                    this.disabled = false;
                    showToast(data.message || 'Failed to queue restore', 'error');
                }
            })
            .catch(() => {
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
 @endsection

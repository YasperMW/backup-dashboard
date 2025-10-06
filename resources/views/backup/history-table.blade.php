<style>
    .hidden-row {
        display: none;
    }
    .no-connection {
        opacity: 0.7;
        pointer-events: none;
    }
</style>
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Filename</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Integrity Hash</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Compression</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
            </tr>
        </thead>
        @php
            $backups = \App\Models\BackupHistory::orderByDesc('created_at')->get();
            $showLimit = 5;
            // Agent-driven checks only: do not gate UI by Linux reachability
            $remotePath = config('backup.remote_path');
            $remotePathNorm = $remotePath ? rtrim(str_replace('\\', '/', $remotePath), '/') : '';
        @endphp
        <tbody class="bg-white divide-y divide-gray-200" id="backup-history-tbody">
            @foreach($backups as $i => $history)
                @php
    $destDirNorm = rtrim(str_replace('\\', '/', $history->destination_directory ?? ''), '/');
    $isRemote = ($history->destination_type === 'remote') || ($remotePathNorm && $destDirNorm === $remotePathNorm);
@endphp
<tr class="backup-row{{ $i >= $showLimit ? ' hidden-row' : '' }}" data-history-id="{{ $history->id }}" data-is-remote="{{ $isRemote ? '1' : '0' }}">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $history->created_at }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $history->source_directory }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        @php
                            $destType = $isRemote ? 'remote' : 'local';
                        @endphp
                        <span class="inline-flex items-center gap-2">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ ($destType === 'remote') ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">{{ ucfirst($destType) }}</span>
                            <span class="text-gray-600">{{ $history->destination_directory }}</span>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $history->filename }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $history->size ? number_format($history->size / 1048576, 2) . ' MB' : '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($history->status === 'failed')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-200 text-gray-800">Unknown</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">{{ $history->integrity_hash ?? '-' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-900">{{ $history->compression_level ?? 'none' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-900">{{ $history->backup_type ?? 'full' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-900">
                        <button data-history-id="{{ $history->id }}" class="verify-file-btn px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50">Verify on Agent</button>
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
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('view-more-backups');
    if (btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.hidden-row').forEach(function(row) {
                row.classList.remove('hidden-row');
            });
document.addEventListener('DOMContentLoaded', function() {
    // Auto-verify for first 5 visible rows (both local and remote) via agent
    const rows = Array.from(document.querySelectorAll('#backup-history-tbody tr')).filter(tr => !tr.classList.contains('hidden-row'));
    const toCheck = rows.slice(0, 5);
    let index = 0;
    function next() {
        if (index >= toCheck.length) return;
        const tr = toCheck[index++];
        const btn = tr.querySelector('.verify-file-btn');
        if (btn && !btn.disabled) {
            btn.click();
        }
        // Stagger requests
        setTimeout(next, 800);
    }
    // Start after a brief delay to allow page to settle
    setTimeout(next, 1200);
});
            btn.style.display = 'none';
        });
    }
});
document.addEventListener('DOMContentLoaded', function() {
    // Attach click handlers for per-row verify buttons
    document.querySelectorAll('.verify-file-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const historyId = this.getAttribute('data-history-id');
            const row = this.closest('tr');
            const statusCell = row.querySelectorAll('td')[5];
            const badge = statusCell.querySelector('span');
            const originalText = badge ? badge.textContent : '';
            this.disabled = true;
            if (badge) {
                badge.className = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-200 text-gray-800';
                badge.textContent = 'Checking...';
            }
            const isRemote = row.getAttribute('data-is-remote') === '1';
            const endpoint = isRemote ? `/backup/history/${historyId}/remote-file-check` : `/backup/history/${historyId}/file-check`;
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            }).then(r => r.json()).then(json => {
                if (!json.success || !json.data || !json.data.job_id) throw new Error(json.message || 'Queue failed');
                const jobId = json.data.job_id;
                // Poll status until completed
                const pollMs = 1500; let attempts = 0; const maxAttempts = 40;
                function poll() {
                    attempts++;
                    fetch(`/backup/status/${jobId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
                        .then(r => r.json()).then(sj => {
                            if (!sj.success) throw new Error('Status fetch failed');
                            const d = sj.data || {};
                            if (d.status === 'completed' || d.status === 'failed') {
                                const exists = d.progress && typeof d.progress.exists !== 'undefined' ? !!d.progress.exists : null;
                                if (badge) {
                                    if (exists === true) {
                                        badge.className = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800';
                                        badge.textContent = 'Completed';
                                    } else if (exists === false) {
                                        badge.className = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-200 text-gray-800';
                                        badge.textContent = 'Missing';
                                    } else {
                                        badge.className = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800';
                                        badge.textContent = d.status === 'completed' ? 'Completed' : 'Failed';
                                    }
                                }
                                btn.disabled = false;
                            } else if (attempts < maxAttempts) {
                                setTimeout(poll, pollMs);
                            } else {
                                if (badge) {
                                    badge.className = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-200 text-gray-800';
                                    badge.textContent = originalText || 'Unknown';
                                }
                                btn.disabled = false;
                            }
                        }).catch(() => {
                            if (attempts < maxAttempts) setTimeout(poll, pollMs);
                            else { if (badge) { badge.textContent = 'Error'; } btn.disabled = false; }
                        });
                }
                poll();
            }).catch(err => {
                if (badge) {
                    badge.className = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800';
                    badge.textContent = 'Failed';
                }
                this.disabled = false;
            });
        });
    });
});
</script>
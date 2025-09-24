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
            </tr>
        </thead>
        @php
            $backups = \App\Models\BackupHistory::orderByDesc('created_at')->get();
            $showLimit = 5;
            $manualOffline = session('manual_offline', false);
            $linux = new \App\Services\LinuxBackupService();
            $actuallyOffline = $manualOffline ? false : !$linux->isReachable(5); // Skip connectivity check if manually offline
            $isSystemOffline = $manualOffline || $actuallyOffline;
            $remotePath = config('backup.remote_path');
            $remotePathNorm = $remotePath ? rtrim(str_replace('\\', '/', $remotePath), '/') : '';
        @endphp
        <tbody class="bg-white divide-y divide-gray-200" id="backup-history-tbody">
            @foreach($backups as $i => $history)
                @php
    $destDirNorm = rtrim(str_replace('\\', '/', $history->destination_directory ?? ''), '/');
    $isRemote = ($history->destination_type === 'remote') || ($remotePathNorm && $destDirNorm === $remotePathNorm);
    $isOffline = $isSystemOffline && $isRemote;
@endphp
<tr class="backup-row{{ $i >= $showLimit ? ' hidden-row' : '' }} {{ $isOffline ? 'no-connection' : '' }}" @if($isOffline) title="No connection to remote server" @endif>
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
                        @php
                            $filePath = $history->destination_directory . DIRECTORY_SEPARATOR . $history->filename;
                            
                            if ($isOffline) {
                                $fileExists = null; // unknown; no connection
                            } else if ($isRemote) {
                                $remoteFilePath = str_replace('\\', '/', $filePath);
                                $fileExists = $linux->exists($remoteFilePath);
                                if (!$fileExists && $remotePathNorm) {
                                    $fallback = $remotePathNorm . '/' . $history->filename;
                                    $fileExists = $linux->exists($fallback);
                                }
                            } else {
                                $fileExists = file_exists($filePath);
                            }
                        @endphp
                        @if($isRemote && $fileExists === null)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-200 text-gray-800">No connection</span>
                        @elseif(!$fileExists)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-200 text-gray-800">Missing</span>
                        @elseif($history->status === 'completed')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Completed</span>
                        @elseif($history->status === 'failed')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Failed</span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">{{ $history->integrity_hash ?? '-' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-900">{{ $history->compression_level ?? 'none' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-900">{{ $history->backup_type ?? 'full' }}</td>
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
            btn.style.display = 'none';
        });
    }
});
</script> 
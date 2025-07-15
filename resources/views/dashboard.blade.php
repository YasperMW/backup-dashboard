<x-dashboard-layout>
    <div class="p-6">
        <!-- Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Total Backups Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-gray-600 text-sm">Total Backups</h2>
                        <p class="text-2xl font-semibold text-gray-800">{{ number_format($totalBackups) }}</p>
                    </div>
                </div>
            </div>

            <!-- Successful Backups Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-gray-600 text-sm">Successful</h2>
                        <p class="text-2xl font-semibold text-gray-800">{{ number_format($successfulBackups) }}</p>
                    </div>
                </div>
            </div>

            <!-- Failed Backups Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-gray-600 text-sm">Failed</h2>
                        <p class="text-2xl font-semibold text-gray-800">{{ number_format($failedBackups) }}</p>
                    </div>
                </div>
            </div>

            <!-- Storage Used Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-gray-600 text-sm">Storage Used</h2>
                        <p class="text-2xl font-semibold text-gray-800">{{ $storageUsed }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Backup History Chart -->
            <div class="bg-white rounded-lg shadow-md p-2 flex flex-col items-center justify-center" style="width:560px; height:350px; max-width:100%;">
                <h3 class="text-lg font-semibold text-gray-800 mb-2 text-center">Backup History</h3>
                <div style="width:90%; height:90%; display:flex; align-items:center; justify-content:center;">
                    <canvas id="backupHistoryChart" width="350" height="300" style="display:block;"></canvas>
                </div>
            </div>
            <!-- Storage Usage Chart -->
            <div class="bg-white rounded-lg shadow-md p-2 flex flex-col items-center justify-center" style="width:560px; height:350px; max-width:100%;">
                <h3 class="text-lg font-semibold text-gray-800 mb-2 text-center">Storage Usage</h3>
                <div style="width:90%; height:90%; display:flex; align-items:center; justify-content:center;">
                    <canvas id="storageUsageChart" width="300" height="300" style="display:block;"></canvas>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Backup Size Trend Chart -->
            <div class="bg-white rounded-lg shadow-md p-2 flex flex-col items-center justify-center" style="width:350px; height:350px; max-width:100%;">
                <h3 class="text-lg font-semibold text-gray-800 mb-2 text-center">Backup Size Trend (GB)</h3>
                <div style="width:90%; height:90%; display:flex; align-items:center; justify-content:center;">
                    <canvas id="backupSizeTrendChart" width="300" height="300" style="display:block;"></canvas>
                </div>
            </div>
            <!-- Backup Type Distribution Chart -->
            <div class="bg-white rounded-lg shadow-md p-2 flex flex-col items-center justify-center" style="width:350px; height:350px; max-width:100%;">
                <h3 class="text-lg font-semibold text-gray-800 mb-2 text-center">Backup Type Distribution</h3>
                <div style="width:90%; height:90%; display:flex; align-items:center; justify-content:center;">
                    <canvas id="backupTypeChart" width="300" height="300" style="display:block;"></canvas>
                </div>
            </div>
            <!-- Backup Status Distribution Chart -->
            <div class="bg-white rounded-lg shadow-md p-2 flex flex-col items-center justify-center" style="width:350px; height:350px; max-width:100%;">
                <h3 class="text-lg font-semibold text-gray-800 mb-2 text-center">Backup Status Distribution</h3>
                <div style="width:90%; height:90%; display:flex; align-items:center; justify-content:center;">
                    <canvas id="backupStatusChart" width="300" height="300" style="display:block;"></canvas>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Links</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="#" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-6 h-6 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <span class="text-gray-700">Create New Backup</span>
                </a>
                <a href="#" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-6 h-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span class="text-gray-700">View All Backups</span>
                </a>
                <a href="#" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-6 h-6 text-purple-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="text-gray-700">Settings</span>
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    console.log('DASHBOARD CHART SCRIPT LOADED', Math.random());
    // Debug: Output chart data to console
    console.log('chartLabels:', @json($chartLabels));
    console.log('chartSuccessData:', @json($chartSuccessData));
    console.log('chartFailedData:', @json($chartFailedData));
    console.log('sizeTrendData:', @json($sizeTrendData));
    console.log('typeLabels:', @json($typeLabels));
    console.log('typeCounts:', @json($typeCounts));
    console.log('statusLabels:', @json($statusLabels));
    console.log('statusCounts:', @json($statusCounts));
    console.log('storageChartData:', @json($storageChartData));

    // Backup History Chart
    var backupHistoryCanvas = document.getElementById('backupHistoryChart');
    if (backupHistoryCanvas) {
        var backupHistoryCtx = backupHistoryCanvas.getContext('2d');
        new Chart(backupHistoryCtx, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Successful Backups',
                    data: @json($chartSuccessData),
                    borderColor: 'rgb(34, 197, 94)',
                    tension: 0.1
                }, {
                    label: 'Failed Backups',
                    data: @json($chartFailedData),
                    borderColor: 'rgb(239, 68, 68)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: false,
                maintainAspectRatio: true,
                animation: false
            }
        });
    }

    // Storage Usage Chart
    var storageUsageCanvas = document.getElementById('storageUsageChart');
    if (storageUsageCanvas) {
        var storageUsageCtx = storageUsageCanvas.getContext('2d');
        new Chart(storageUsageCtx, {
            type: 'doughnut',
            data: {
                labels: ['Used (GB)', 'Free (GB)'],
                datasets: [{
                    data: [{{ $storageChartData['used'] }}, {{ $storageChartData['free'] }}],
                    backgroundColor: [
                        'rgb(147, 51, 234)',
                        'rgb(229, 231, 235)'
                    ]
                }]
            },
            options: {
                responsive: false,
                maintainAspectRatio: true,
                animation: false
            }
        });
    }

    // Backup Size Trend Chart
    var backupSizeTrendCanvas = document.getElementById('backupSizeTrendChart');
    if (backupSizeTrendCanvas) {
        var backupSizeTrendCtx = backupSizeTrendCanvas.getContext('2d');
        new Chart(backupSizeTrendCtx, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Backup Size (GB)',
                    data: @json($sizeTrendData),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                responsive: false,
                maintainAspectRatio: true,
                animation: false
            }
        });
    }

    // Backup Type Distribution Chart
    var backupTypeCanvas = document.getElementById('backupTypeChart');
    if (backupTypeCanvas) {
        var backupTypeCtx = backupTypeCanvas.getContext('2d');
        new Chart(backupTypeCtx, {
            type: 'pie',
            data: {
                labels: @json($typeLabels),
                datasets: [{
                    data: @json($typeCounts),
                    backgroundColor: [
                        'rgb(34,197,94)', // green
                        'rgb(59,130,246)', // blue
                        'rgb(251,191,36)' // yellow
                    ]
                }]
            },
            options: {
                responsive: false,
                maintainAspectRatio: true,
                animation: false
            }
        });
    }

    // Backup Status Distribution Chart
    var backupStatusCanvas = document.getElementById('backupStatusChart');
    if (backupStatusCanvas) {
        var backupStatusCtx = backupStatusCanvas.getContext('2d');
        new Chart(backupStatusCtx, {
            type: 'pie',
            data: {
                labels: @json($statusLabels),
                datasets: [{
                    data: @json($statusCounts),
                    backgroundColor: [
                        'rgb(34,197,94)', // green
                        'rgb(239,68,68)', // red
                        'rgb(251,191,36)' // yellow
                    ]
                }]
            },
            options: {
                responsive: false,
                maintainAspectRatio: true,
                animation: false
            }
        });
    }
    </script>
</x-dashboard-layout>

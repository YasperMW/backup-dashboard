<x-dashboard-layout>
    <div class="p-6">
        <div class="mb-6">
            <div class="flex border-b border-gray-200">
                <button id="tab-system-logs" class="px-4 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600 focus:outline-none">System Logs</button>
                <button id="tab-login-logs" class="ml-4 px-4 py-2 text-sm font-medium text-gray-600 hover:text-blue-600 focus:outline-none">Login Logs</button>
            </div>
        </div>
        <div id="system-logs-section">
            <!-- Log Filters Section -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Log Filters</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Date Range -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                        <div class="flex space-x-2">
                            <input type="date" id="system-filter-date-from" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <input type="date" id="system-filter-date-to" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <br>
                    <br>
                    <!-- Source -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Source</label>
                        <select id="system-filter-source" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="all">All Sources</option>
                            <option value="System">System</option>
                            <option value="Backup Service">Backup Service</option>
                            <option value="Auth Service">Auth Service</option>
                        </select>
                    </div>
                    <!-- Severity Level -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Severity Level</label>
                        <select id="system-filter-severity" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="all">All Levels</option>
                            <option value="critical">Critical</option>
                            <option value="error">Error</option>
                            <option value="warning">Warning</option>
                            <option value="info">Info</option>
                            <option value="debug">Debug</option>
                        </select>
                    </div>
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <div class="relative">
                            <input type="text" id="system-filter-search" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pl-10" placeholder="Search logs...">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-800">System Logs</h2>
                        <div class="flex space-x-2">
                            <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                Export
                            </button>
                            <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                Clear Logs
                            </button>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Severity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="logs-table-tbody">
                            <tr id="logs-empty" style="display: none;">
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No logs found.</td>
                            </tr>
                            <tr id="logs-loading" style="display: none;">
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Loading logs...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <button class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </button>
                            <button class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </button>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing <span class="font-medium">1</span> to <span class="font-medium">3</span> of <span class="font-medium">12</span> results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <button class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        Previous
                                    </button>
                                    <button class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        1
                                    </button>
                                    <button class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        2
                                    </button>
                                    <button class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        3
                                    </button>
                                    <button class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        Next
                                    </button>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="login-logs-section" style="display:none;">
            @include('logs.login-logs')
        </div>

        <!-- Log Details Modal (Hidden by default) -->
        <div class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Log Details</h3>
                    <button class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Timestamp</h4>
                        <p class="mt-1 text-sm text-gray-900">2024-03-15 14:30:45</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Type</h4>
                        <p class="mt-1 text-sm text-gray-900">System</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Severity</h4>
                        <p class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                Critical
                            </span>
                        </p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Message</h4>
                        <p class="mt-1 text-sm text-gray-900">Database connection failed</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Source</h4>
                        <p class="mt-1 text-sm text-gray-900">Database Service</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Stack Trace</h4>
                        <pre class="mt-1 text-sm text-gray-900 bg-gray-50 p-4 rounded-md overflow-x-auto">
Error: Connection refused
    at Database.connect (/app/services/database.js:45:12)
    at async BackupService.initialize (/app/services/backup.js:23:5)
    at async BackupService.startBackup (/app/services/backup.js:67:8)
                        </pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
<script>
function renderLogsTable(logs) {
    const tbody = document.getElementById('logs-table-tbody');
    tbody.innerHTML = '';
    const emptyEl = document.getElementById('logs-empty');
    if (!logs.length) {
        if (emptyEl) emptyEl.style.display = '';
        return;
    } else {
        if (emptyEl) emptyEl.style.display = 'none';
    }
    for (const log of logs) {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${log.timestamp}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${log.type}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${log.severity === 'Critical' ? 'bg-red-100 text-red-800' : log.severity === 'Warning' ? 'bg-yellow-100 text-yellow-800' : log.severity === 'Info' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                    ${log.severity}
                </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-900">${log.message}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${log.source}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <button class="text-blue-600 hover:text-blue-900 view-details-btn" data-line="${log.line}">View Details</button>
            </td>
        `;
        tbody.appendChild(tr);
    }
    document.querySelectorAll('.view-details-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            fetch('/logs/details', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ line: this.dataset.line })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showLogDetailsModal(data.entry, data.stack);
                }
            });
        });
    });
}
function fetchLogs() {
    const loadingEl = document.getElementById('logs-loading');
    const emptyEl = document.getElementById('logs-empty');
    if (loadingEl) loadingEl.style.display = '';
    if (emptyEl) emptyEl.style.display = 'none';
    const from = document.getElementById('system-filter-date-from')?.value;
    const to = document.getElementById('system-filter-date-to')?.value;
    const source = document.getElementById('system-filter-source')?.value;
    const severity = document.getElementById('system-filter-severity')?.value;
    const search = document.getElementById('system-filter-search')?.value;
    fetch('/logs/fetch', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ from, to, source, severity, search })
    })
    .then(res => res.json())
    .then(data => {
        if (loadingEl) loadingEl.style.display = 'none';
        if (data.success) {
            renderLogsTable(data.logs);
        } else {
            renderLogsTable([]);
        }
    })
    .catch(() => {
        if (loadingEl) loadingEl.style.display = 'none';
        renderLogsTable([]);
    });
}
function showLogDetailsModal(entry, stack) {
    const modal = document.querySelector('.fixed.inset-0.bg-gray-500');
    modal.classList.remove('hidden');
    // Parse entry
    let timestamp = '', type = '', severity = '', message = '', source = '';
    const m = entry.match(/^\[(.*?)\] (\w+)\.(\w+): (.*)$/);
    if (m) {
        timestamp = m[1];
        type = m[2];
        severity = m[3];
        message = m[4];
        source = message.includes('Backup') ? 'Backup Service' : (message.includes('Auth') ? 'Auth Service' : 'System');
    }
    modal.querySelector('p.text-sm.text-gray-900').textContent = timestamp;
    modal.querySelectorAll('p.text-sm.text-gray-900')[1].textContent = type;
    modal.querySelector('span.px-2').textContent = severity.charAt(0).toUpperCase() + severity.slice(1);
    modal.querySelector('span.px-2').className = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' + (severity === 'critical' ? 'bg-red-100 text-red-800' : severity === 'warning' ? 'bg-yellow-100 text-yellow-800' : severity === 'info' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800');
    modal.querySelectorAll('p.text-sm.text-gray-900')[2].textContent = message;
    modal.querySelectorAll('p.text-sm.text-gray-900')[3].textContent = source;
    modal.querySelector('pre').textContent = stack || 'No stack trace.';
}
function safeAddEventListener(id, event, handler) {
    const el = document.getElementById(id);
    if (el) {
        el.addEventListener(event, handler);
    }
}
function initSystemLogsEvents() {
    safeAddEventListener('system-filter-date-from', 'change', fetchLogs);
    safeAddEventListener('system-filter-date-to', 'change', fetchLogs);
    safeAddEventListener('system-filter-source', 'change', fetchLogs);
    safeAddEventListener('system-filter-severity', 'change', fetchLogs);
    safeAddEventListener('system-filter-search', 'input', fetchLogs);
    const exportBtn = document.querySelector('.bg-gray-100.text-gray-700');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            window.location.href = '/logs/export';
        });
    }
    const clearBtns = document.querySelectorAll('.bg-gray-100.text-gray-700');
    if (clearBtns.length > 1) {
        clearBtns[1].addEventListener('click', function() {
            if (confirm('Are you sure you want to clear all logs?')) {
                fetch('/logs/clear', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(() => fetchLogs());
            }
        });
    }
}
const modalCloseBtn = document.querySelector('.fixed.inset-0.bg-gray-500 button');
if (modalCloseBtn) {
    modalCloseBtn.addEventListener('click', function() {
        const modal = document.querySelector('.fixed.inset-0.bg-gray-500');
        if (modal) modal.classList.add('hidden');
    });
}
// --- LOGIN LOGS JS (moved from included file) ---
function showToast(message) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}
function renderLoginLogsTable(logs) {
    const tbody = document.getElementById('login-logs-table-tbody');
    tbody.innerHTML = '';
    const emptyEl = document.getElementById('login-logs-empty');
    if (!logs.length) {
        if (emptyEl) emptyEl.style.display = '';
        return;
    } else {
        if (emptyEl) emptyEl.style.display = 'none';
    }
    for (const log of logs) {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-blue-50 transition';
        tr.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${log.created_at ? new Date(log.created_at).toLocaleString() : ''}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${log.name || ''}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${log.email}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${log.ip_address || ''}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold ${log.status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                    ${log.status === 'success' ? `<svg class='h-4 w-4 mr-1 text-green-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 13l4 4L19 7'/></svg>` : `<svg class='h-4 w-4 mr-1 text-red-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'/></svg>`}
                    ${log.status.charAt(0).toUpperCase() + log.status.slice(1)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold ${log.type === 'logout' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'}">
                    ${log.type === 'logout' ? `<svg class='h-4 w-4 mr-1 text-yellow-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M17 16l4-4m0 0l-4-4m4 4H7'/></svg>` : `<svg class='h-4 w-4 mr-1 text-blue-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M7 8V6a4 4 0 118 0v2'/></svg>`}
                    ${log.type ? log.type.charAt(0).toUpperCase() + log.type.slice(1) : 'Login'}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <button class="text-blue-600 hover:text-blue-900 view-login-log-btn focus:outline-none" data-log='${JSON.stringify(log)}' aria-label="View details">View Details</button>
            </td>
        `;
        tbody.appendChild(tr);
    }
    document.querySelectorAll('.view-login-log-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const log = JSON.parse(this.dataset.log);
            showLoginLogModal(log);
        });
    });
}
function fetchLoginLogs() {
    const loadingEl = document.getElementById('login-logs-loading');
    const emptyEl = document.getElementById('login-logs-empty');
    if (!loadingEl || !emptyEl) return;
    loadingEl.style.display = '';
    emptyEl.style.display = 'none';
    const from = document.getElementById('filter-date-from')?.value || '';
    const to = document.getElementById('filter-date-to')?.value || '';
    const status = document.getElementById('filter-status')?.value || 'all';
    const type = document.getElementById('filter-type')?.value || 'all';
    const email = document.getElementById('filter-email')?.value || '';
    const name = document.getElementById('filter-name')?.value || '';
    fetch('/login-logs/fetch', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ from, to, status, type, email, name })
    })
    .then(res => res.json())
    .then(data => {
        if (loadingEl) loadingEl.style.display = 'none';
        if (data.success) {
            renderLoginLogsTable(data.logs);
        } else {
            renderLoginLogsTable([]);
            showToast('Failed to fetch logs.');
        }
    })
    .catch(() => {
        if (loadingEl) loadingEl.style.display = 'none';
        renderLoginLogsTable([]);
        showToast('Failed to fetch logs.');
    });
}
function initLoginLogsEvents() {
    safeAddEventListener('apply-filters', 'click', fetchLoginLogs);
    safeAddEventListener('clear-filters', 'click', function() {
        if (document.getElementById('filter-date-from')) document.getElementById('filter-date-from').value = '';
        if (document.getElementById('filter-date-to')) document.getElementById('filter-date-to').value = '';
        if (document.getElementById('filter-status')) document.getElementById('filter-status').value = 'all';
        if (document.getElementById('filter-type')) document.getElementById('filter-type').value = 'all';
        if (document.getElementById('filter-email')) document.getElementById('filter-email').value = '';
        if (document.getElementById('filter-name')) document.getElementById('filter-name').value = '';
        fetchLoginLogs();
    });
    safeAddEventListener('filter-date-from', 'change', fetchLoginLogs);
    safeAddEventListener('filter-date-to', 'change', fetchLoginLogs);
    safeAddEventListener('filter-status', 'change', fetchLoginLogs);
    safeAddEventListener('filter-type', 'change', fetchLoginLogs);
    safeAddEventListener('filter-email', 'input', function(e) {
        if (this.value.length === 0 || this.value.length > 2) fetchLoginLogs();
    });
    safeAddEventListener('filter-name', 'input', function(e) {
        if (this.value.length === 0 || this.value.length > 2) fetchLoginLogs();
    });
    safeAddEventListener('close-login-log-modal', 'click', function() {
        if (document.getElementById('login-log-modal')) document.getElementById('login-log-modal').classList.add('hidden');
    });
    const modal = document.getElementById('login-log-modal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) this.classList.add('hidden');
        });
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal) modal.classList.add('hidden');
    });
}
// --- END LOGIN LOGS JS ---
const tabSystemLogs = document.getElementById('tab-system-logs');
const tabLoginLogs = document.getElementById('tab-login-logs');
if (tabSystemLogs) {
    tabSystemLogs.addEventListener('click', function() {
        const sysSection = document.getElementById('system-logs-section');
        const loginSection = document.getElementById('login-logs-section');
        if (sysSection) sysSection.style.display = '';
        if (loginSection) loginSection.style.display = 'none';
        this.classList.add('text-blue-600', 'border-blue-600');
        this.classList.remove('text-gray-600');
        if (tabLoginLogs) {
            tabLoginLogs.classList.remove('text-blue-600', 'border-blue-600');
            tabLoginLogs.classList.add('text-gray-600');
        }
    });
}
if (tabLoginLogs) {
    tabLoginLogs.addEventListener('click', function() {
        const sysSection = document.getElementById('system-logs-section');
        const loginSection = document.getElementById('login-logs-section');
        if (sysSection) sysSection.style.display = 'none';
        if (loginSection) loginSection.style.display = '';
        this.classList.add('text-blue-600', 'border-blue-600');
        this.classList.remove('text-gray-600');
        if (tabSystemLogs) {
            tabSystemLogs.classList.remove('text-blue-600', 'border-blue-600');
            tabSystemLogs.classList.add('text-gray-600');
        }
        // Initialize login logs events and fetch logs only when tab is shown
        if (!window.loginLogsInitialized) {
            initLoginLogsEvents();
            window.loginLogsInitialized = true;
        }
        // Ensure DOM is updated before fetching logs
        setTimeout(fetchLoginLogs, 0);
    });
}
// Optionally, initialize system logs on load
window.addEventListener('DOMContentLoaded', fetchLogs);
</script> 
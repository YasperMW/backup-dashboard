<div class="p-6">
        <!-- Login Log Filters Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Login/Logout Log Filters</h2>
            <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
                <!-- Date Range -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                    <div class="flex space-x-2">
                        <input type="date" id="login-filter-date-from" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <input type="date" id="login-filter-date-to" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="login-filter-status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="all">All</option>
                        <option value="success">Success</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
                <!-- Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                    <select id="login-filter-type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="all">All</option>
                        <option value="login">Login</option>
                        <option value="logout">Logout</option>
                    </select>
                </div>
                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="text" id="login-filter-email" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Search email...">
                </div>
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                    <input type="text" id="login-filter-name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Search name...">
                </div>
                <div class="flex items-end space-x-2">
                    <!-- Remove Apply button, keep only Clear -->
                    <button id="clear-filters" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 w-full">Clear</button>
                </div>
            </div>
        </div>

        <!-- Login Logs Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-800">Login/Logout Logs</h2>
                </div>
            </div>
            <div class="overflow-x-auto max-h-[60vh]">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0 z-10">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="login-logs-table-tbody">
                        <tr id="login-logs-empty" style="display: none;">
                            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="h-10 w-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 014-4h3m4 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>No login/logout logs found.</span>
                                </div>
                            </td>
                        </tr>
                        <tr id="login-logs-loading" style="display: none;">
                            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                <svg class="animate-spin h-6 w-6 text-blue-500 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
                                <span class="block mt-2">Loading logs...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Log Details Modal (Hidden by default) -->
        <div class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" id="login-log-modal">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full relative">
                <button class="absolute top-2 right-2 text-gray-400 hover:text-gray-500 focus:outline-none" id="close-login-log-modal" aria-label="Close modal">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Login/Logout Log Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="login-log-modal-content">
                    <!-- Details will be injected here -->
                </div>
            </div>
        </div>
        <!-- Toast Notification -->
        <div id="toast" class="fixed bottom-6 right-6 z-50 hidden bg-red-500 text-white px-4 py-2 rounded shadow-lg"></div>
    </div>
<script>
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
    const from = document.getElementById('login-filter-date-from').value;
    const to = document.getElementById('login-filter-date-to').value;
    const status = document.getElementById('login-filter-status').value;
    const type = document.getElementById('login-filter-type').value;
    const email = document.getElementById('login-filter-email').value;
    const name = document.getElementById('login-filter-name').value;
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
document.getElementById('clear-filters').addEventListener('click', function() {
    document.getElementById('login-filter-date-from').value = '';
    document.getElementById('login-filter-date-to').value = '';
    document.getElementById('login-filter-status').value = 'all';
    document.getElementById('login-filter-type').value = 'all';
    document.getElementById('login-filter-email').value = '';
    document.getElementById('login-filter-name').value = '';
    fetchLoginLogs();
});
function safeAddEventListener(id, eventType, callback) {
    const element = document.getElementById(id);
    if (element) {
        element.addEventListener(eventType, callback);
    }
}
safeAddEventListener('login-filter-date-from', 'change', fetchLoginLogs);
safeAddEventListener('login-filter-date-to', 'change', fetchLoginLogs);
safeAddEventListener('login-filter-status', 'change', fetchLoginLogs);
safeAddEventListener('login-filter-type', 'change', fetchLoginLogs);
safeAddEventListener('login-filter-email', 'input', fetchLoginLogs);
safeAddEventListener('login-filter-name', 'input', fetchLoginLogs);
function showLoginLogModal(log) {
    const modal = document.getElementById('login-log-modal');
    const content = document.getElementById('login-log-modal-content');
    content.innerHTML = `
        <div>
            <h4 class="text-sm font-medium text-gray-500">Timestamp</h4>
            <p class="mt-1 text-sm text-gray-900">${log.created_at ? new Date(log.created_at).toLocaleString() : ''}</p>
        </div>
        <div>
            <h4 class="text-sm font-medium text-gray-500">Name</h4>
            <p class="mt-1 text-sm text-gray-900">${log.name || ''}</p>
        </div>
        <div>
            <h4 class="text-sm font-medium text-gray-500">Email <button class='ml-2 text-xs text-blue-500 underline copy-btn' data-copy='${log.email}' title='Copy email'>Copy</button></h4>
            <p class="mt-1 text-sm text-gray-900">${log.email}</p>
        </div>
        <div>
            <h4 class="text-sm font-medium text-gray-500">IP Address <button class='ml-2 text-xs text-blue-500 underline copy-btn' data-copy='${log.ip_address || ''}' title='Copy IP'>Copy</button></h4>
            <p class="mt-1 text-sm text-gray-900">${log.ip_address || ''}</p>
        </div>
        <div>
            <h4 class="text-sm font-medium text-gray-500">Status</h4>
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold ${log.status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                ${log.status === 'success' ? `<svg class='h-4 w-4 mr-1 text-green-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 13l4 4L19 7'/></svg>` : `<svg class='h-4 w-4 mr-1 text-red-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'/></svg>`}
                ${log.status.charAt(0).toUpperCase() + log.status.slice(1)}
            </span>
        </div>
        <div>
            <h4 class="text-sm font-medium text-gray-500">Type</h4>
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold ${log.type === 'logout' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'}">
                ${log.type === 'logout' ? `<svg class='h-4 w-4 mr-1 text-yellow-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M17 16l4-4m0 0l-4-4m4 4H7'/></svg>` : `<svg class='h-4 w-4 mr-1 text-blue-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M7 8V6a4 4 0 118 0v2'/></svg>`}
                ${log.type ? log.type.charAt(0).toUpperCase() + log.type.slice(1) : 'Login'}
            </span>
        </div>
    `;
    modal.classList.remove('hidden');
    // Copy buttons
    content.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            navigator.clipboard.writeText(this.dataset.copy);
            showToast('Copied!');
        });
    });
}
document.getElementById('close-login-log-modal').addEventListener('click', function() {
    document.getElementById('login-log-modal').classList.add('hidden');
});
document.getElementById('login-log-modal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') document.getElementById('login-log-modal').classList.add('hidden');
});
window.addEventListener('DOMContentLoaded', fetchLoginLogs);
</script> 
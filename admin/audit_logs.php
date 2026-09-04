<?php
/**
 * AgriSync — Immutable Admin Audit Logs View (Issue: Admin Accountability)
 * Strictly read-only audit log tracking every administrative state change and user status modification.
 */

$page_title = 'System Audit Logs';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../auth/auth_check.php';
checkRole(['admin']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$csrf_token = generateCSRFToken();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid dashboard-wrapper">
    <div class="row">
        <!-- Sidebar Navigation -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

        <!-- Main Content Area -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <!-- Page Header Banner -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
                <div>
                    <h2 class="fw-bold text-dark mb-1">
                        <i class="bi bi-journal-text text-primary me-2"></i>Admin System Audit Logs
                    </h2>
                    <p class="text-muted small mb-0">Immutable, read-only ledger capturing all administrative state changes, account status toggles, and system actions.</p>
                </div>
                <div class="mt-3 mt-md-0 d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary shadow-sm" id="btnRefreshAuditLogs" style="min-height: 44px; border-radius: 8px;">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh Audit Logs
                    </button>
                </div>
            </div>

            <!-- Security Callout Banner -->
            <div class="card border-0 shadow-sm rounded-3 p-3 mb-4 bg-light border-start border-4 border-success">
                <div class="d-flex align-items-center">
                    <div class="p-2 rounded-circle bg-success-subtle text-success me-3">
                        <i class="bi bi-shield-lock-fill fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-1">Immutable Accountability Ledger</h6>
                        <p class="small text-muted mb-0">
                            This audit log is strictly <strong>read-only</strong>. No administrator has privileges to alter, clear, or delete log entries via the interface. Every entry records the admin ID, action type, target ID, details, and client IP address (<code class="bg-white px-1 border rounded">REMOTE_ADDR</code>).
                        </p>
                    </div>
                </div>
            </div>

            <!-- Filter & Table Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-list-check text-primary me-2"></i>Recorded Audit Events</h5>
                        <span class="badge bg-light text-dark border ms-2" id="auditCountBadge">0 Records</span>
                    </div>
                    
                    <div class="d-flex gap-2 flex-grow-1 flex-md-grow-0">
                        <div class="input-group input-group-sm" style="min-width: 240px;">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="auditSearchInput" class="form-control border-start-0" placeholder="Search admin, details, IP...">
                        </div>
                        <select id="actionFilter" class="form-select form-select-sm w-auto">
                            <option value="all">All Actions</option>
                            <option value="deactivate_user">Deactivate User</option>
                            <option value="activate_user">Activate User</option>
                        </select>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light extra-small">
                                <tr>
                                    <th class="ps-4">Log ID</th>
                                    <th>Admin User</th>
                                    <th>Action Type</th>
                                    <th>Target ID</th>
                                    <th>Action Details</th>
                                    <th>IP Address</th>
                                    <th class="pe-4 text-end">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody id="auditTbody">
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <span class="spinner-border spinner-border-sm me-2" role="status"></span> Fetching system audit logs...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const auditTbody = document.getElementById('auditTbody');
    const actionFilter = document.getElementById('actionFilter');
    const auditSearchInput = document.getElementById('auditSearchInput');
    const btnRefreshAuditLogs = document.getElementById('btnRefreshAuditLogs');
    const auditCountBadge = document.getElementById('auditCountBadge');

    async function loadAuditLogs() {
        const filterAction = actionFilter.value;
        const search = encodeURIComponent(auditSearchInput.value.trim());

        try {
            const res = await fetch(`<?= APP_URL ?>/api/admin.php?action=get_audit_logs&filter_action=${filterAction}&search=${search}`);
            const data = await res.json();

            if (data.success) {
                const logs = data.data.logs || [];
                renderAuditTable(logs);
            } else {
                auditTbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">${data.error || 'Failed to load audit logs.'}</td></tr>`;
            }
        } catch (err) {
            auditTbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Error connecting to server.</td></tr>`;
        }
    }

    function renderAuditTable(logs) {
        auditCountBadge.textContent = `${logs.length} Event${logs.length === 1 ? '' : 's'}`;

        if (logs.length === 0) {
            auditTbody.innerHTML = `<tr><td colspan="7" class="text-center py-5 text-muted"><i class="bi bi-journal-x fs-2 d-block mb-2 text-muted"></i>No admin audit log entries found.</td></tr>`;
            return;
        }

        auditTbody.innerHTML = logs.map(l => {
            const badgeClass = getActionBadgeClass(l.action);
            const formattedAction = l.action ? l.action.replace('_', ' ').toUpperCase() : 'UNKNOWN';
            return `
                <tr>
                    <td class="ps-4 text-muted fw-bold">#LOG-${l.id}</td>
                    <td>
                        <div class="fw-bold text-dark">${escapeHtml(l.admin_name || 'Admin #' + l.admin_id)}</div>
                        <div class="text-muted extra-small">${escapeHtml(l.admin_email || '')}</div>
                    </td>
                    <td><span class="badge ${badgeClass} extra-small">${formattedAction}</span></td>
                    <td><span class="badge bg-light text-dark border">#USR-${l.target_id || 'N/A'}</span></td>
                    <td class="small text-dark" style="max-width: 340px;">${escapeHtml(l.details || '')}</td>
                    <td><code class="bg-light text-secondary px-2 py-1 border rounded extra-small">${escapeHtml(l.ip_address || '127.0.0.1')}</code></td>
                    <td class="pe-4 text-end text-muted extra-small">${l.created_at || 'N/A'}</td>
                </tr>
            `;
        }).join('');
    }

    function getActionBadgeClass(action) {
        switch ((action || '').toLowerCase()) {
            case 'deactivate_user': return 'bg-danger-subtle text-danger border border-danger';
            case 'activate_user': return 'bg-success-subtle text-success border border-success';
            default: return 'bg-primary-subtle text-primary border border-primary';
        }
    }

    function escapeHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    actionFilter.addEventListener('change', loadAuditLogs);
    btnRefreshAuditLogs.addEventListener('click', loadAuditLogs);

    let searchTimer;
    auditSearchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(loadAuditLogs, 300);
    });

    loadAuditLogs();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
// AgriSync Business Pre-Order Requests Page (TASK-011)
$page_title = 'Pre-Order Requests';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../auth/auth_check.php';
checkRole(['business']);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-4 py-4">
    <!-- Header Banner -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="fw-bold text-dark mb-1">Pre-Order Procurement Requests</h2>
            <p class="text-muted small mb-0">Submit bulk crop demand requests for automated farmer yield matching</p>
        </div>
        <div class="mt-3 mt-md-0">
            <button type="button" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addRequestModal">
                <i class="bi bi-plus-lg"></i>
                <span>Submit New Pre-Order</span>
            </button>
        </div>
    </div>

    <!-- Filter Bar & Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-0 pt-4 px-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-bag-check text-primary me-2"></i>My Procurement Requests</h5>
            <div class="d-flex gap-2">
                <select id="statusFilter" class="form-select form-select-sm w-auto">
                    <option value="all">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="matching">Matching</option>
                    <option value="matched">Matched</option>
                    <option value="fulfilled">Fulfilled</option>
                </select>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Request ID</th>
                            <th>Crop Type</th>
                            <th>Required Yield (kg)</th>
                            <th>Max Budget / kg</th>
                            <th>Delivery Target Date</th>
                            <th>Urgency</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="requestsTbody">
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <span class="spinner-border spinner-border-sm me-2" role="status"></span> Loading pre-orders...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Submit Pre-Order Modal -->
<div class="modal fade" id="addRequestModal" tabindex="-1" aria-labelledby="addRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="addRequestModalLabel"><i class="bi bi-bag-plus text-primary me-2"></i>Submit Bulk Pre-Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addRequestForm" method="POST">
                <div class="modal-body py-4">
                    <div id="modalAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="mb-3">
                        <label for="crop_type" class="form-label fw-medium text-secondary">Required Crop Type</label>
                        <select class="form-select" id="crop_type" name="crop_type" required>
                            <option value="" disabled selected>Select Crop Type</option>
                            <?php foreach (AGRISYNC_CROPS as $c): ?>
                                <option value="<?= $c ?>"><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="quantity_kg" class="form-label fw-medium text-secondary">Quantity (kg)</label>
                            <input type="number" step="0.01" class="form-control" id="quantity_kg" name="quantity_kg" placeholder="e.g. 1000" required min="1">
                        </div>
                        <div class="col-md-6">
                            <label for="max_price" class="form-label fw-medium text-secondary">Max Price / kg (LKR)</label>
                            <input type="number" step="0.01" class="form-control" id="max_price" name="max_price" placeholder="e.g. 220.00" required min="1">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="delivery_date" class="form-label fw-medium text-secondary">Target Delivery Date</label>
                            <input type="date" class="form-control" id="delivery_date" name="delivery_date" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="urgency" class="form-label fw-medium text-secondary">Urgency Level</label>
                            <select class="form-select" id="urgency" name="urgency">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label fw-medium text-secondary">Delivery Notes / Specifications</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Grade A quality preferred, delivery to Colombo distribution center..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="saveRequestBtn" class="btn btn-primary px-4 fw-semibold">Submit Pre-Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const requestsTbody = document.getElementById('requestsTbody');
    const statusFilter = document.getElementById('statusFilter');
    const addRequestForm = document.getElementById('addRequestForm');
    const saveRequestBtn = document.getElementById('saveRequestBtn');
    const modalAlert = document.getElementById('modalAlert');

    async function loadRequests() {
        const status = statusFilter.value;
        try {
            const res = await fetch(`../api/business.php?action=get_requests&status=${status}`);
            const data = await res.json();

            if (data.success) {
                const requests = data.data.requests || [];
                if (requests.length === 0) {
                    requestsTbody.innerHTML = `<tr><td colspan="7" class="p-0">${renderEmptyStateHTML('No pre-order requests yet', 'Submit your bulk crop procurement needs for automated farmer yield matching.', 'bi-bag-plus', 'Submit Pre-Order', '#addRequestModal')}</td></tr>`;
                    return;
                }

                requestsTbody.innerHTML = requests.map(item => `
                    <tr>
                        <td class="ps-4 text-muted">#OR-${item.id}</td>
                        <td class="fw-bold text-dark">${item.crop_type}</td>
                        <td>${parseFloat(item.quantity_kg).toLocaleString()} kg</td>
                        <td>Rs. ${parseFloat(item.max_price).toFixed(2)}</td>
                        <td>${item.delivery_date}</td>
                        <td><span class="badge bg-light text-dark border text-capitalize extra-small">${item.urgency}</span></td>
                        <td><span class="badge ${getStatusBadgeClass(item.status)}">${item.status}</span></td>
                    </tr>
                `).join('');
            }
        } catch (err) {
            requestsTbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Failed to load requests.</td></tr>`;
        }
    }

    statusFilter.addEventListener('change', loadRequests);

    addRequestForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        modalAlert.classList.add('d-none');
        saveRequestBtn.disabled = true;

        try {
            const formData = new FormData(addRequestForm);
            const res = await fetch('../api/place_order.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': '<?= generateCSRFToken() ?>' }
            });

            const data = await res.json();
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('addRequestModal')).hide();
                addRequestForm.reset();
                if (window.AgriSync && window.AgriSync.showToast) {
                    const msg = data.data.auto_matched ? 'Pre-order submitted & matched with nearby farmer yield!' : 'Pre-order submitted! AI Broker searching for matches...';
                    window.AgriSync.showToast(msg, 'success');
                }
                loadRequests();
            } else {
                modalAlert.textContent = data.error || 'Failed to submit pre-order.';
                modalAlert.classList.remove('d-none');
            }
        } catch (err) {
            modalAlert.textContent = 'Network error submitting pre-order.';
            modalAlert.classList.remove('d-none');
        } finally {
            saveRequestBtn.disabled = false;
        }
    });

    loadRequests();
});

function getStatusBadgeClass(status) {
    switch (status.toLowerCase()) {
        case 'pending': return 'bg-secondary-subtle text-secondary';
        case 'matching': return 'bg-warning-subtle text-warning border border-warning';
        case 'matched': return 'bg-info-subtle text-info border border-info';
        case 'fulfilled': return 'bg-success-subtle text-success border border-success';
        case 'cancelled': return 'bg-danger-subtle text-danger';
        default: return 'bg-light text-dark';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

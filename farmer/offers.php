<?php
/**
 * AgriSync — Farmer Incoming Offers & AI Match Proposals Page (TASK-037 / TASK-073)
 * Allows farmers to review incoming match offers negotiated by the Gemini AI Broker,
 * view buyer details, price per kg, fulfillment terms, and Accept or Reject proposals.
 */

$page_title = 'Incoming Offers & AI Matches';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../auth/auth_check.php';
checkRole(['farmer']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$farmer_id = (int)$_SESSION['user_id'];
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
                    <h2 class="fw-bold text-dark mb-1">Incoming Offers & AI Broker Proposals</h2>
                    <p class="text-muted small mb-0">Review matched purchase offers from verified commercial buyers across Sri Lanka</p>
                </div>
                <div class="mt-3 mt-md-0 d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary shadow-sm" id="btnRefreshOffers">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh Offers
                    </button>
                    <a href="<?= APP_URL ?>/farmer/listings.php" class="btn btn-primary shadow-sm">
                        <i class="bi bi-plus-circle me-1"></i> New Harvest Listing
                    </a>
                </div>
            </div>

            <!-- Metric Cards -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="stat-card stat-card-warning">
                        <div class="stat-card-inner">
                            <div>
                                <span class="stat-card-label">Pending Action</span>
                                <h3 class="stat-card-value text-warning" id="countPendingOffers">0</h3>
                                <div class="stat-card-trend trend-up"><i class="bi bi-clock-history"></i> Awaiting Your Decision</div>
                            </div>
                            <div class="stat-card-icon-wrapper">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="stat-card stat-card-success">
                        <div class="stat-card-inner">
                            <div>
                                <span class="stat-card-label">Accepted Deals</span>
                                <h3 class="stat-card-value text-success" id="countAcceptedOffers">0</h3>
                                <div class="stat-card-trend trend-up"><i class="bi bi-check-circle"></i> Ready for Fulfillment</div>
                            </div>
                            <div class="stat-card-icon-wrapper">
                                <i class="bi bi-bag-check"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="stat-card stat-card-primary">
                        <div class="stat-card-inner">
                            <div>
                                <span class="stat-card-label">Potential Revenue</span>
                                <h3 class="stat-card-value" id="totalPotentialRevenue">Rs. 0</h3>
                                <div class="stat-card-trend trend-up"><i class="bi bi-cash-stack"></i> Negotiated Value</div>
                            </div>
                            <div class="stat-card-icon-wrapper">
                                <i class="bi bi-currency-exchange"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Offers List Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-tag-fill text-primary me-2"></i>Offers & Match Proposals</h5>
                        <span class="badge bg-light text-dark border ms-2" id="offersCountBadge">0 Total</span>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <select id="filterStatus" class="form-select form-select-sm w-auto">
                            <option value="all">All Offers</option>
                            <option value="proposed" selected>Pending Review (Proposed)</option>
                            <option value="accepted">Accepted</option>
                            <option value="rejected">Declined</option>
                        </select>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div id="offersContainer" class="row g-3">
                        <div class="col-12 text-center py-5 text-muted">
                            <span class="spinner-border spinner-border-sm me-2"></span> Loading your incoming offers...
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Offer Decision & Digital Contract Confirmation Modal -->
<div class="modal fade" id="decisionModal" tabindex="-1" aria-labelledby="decisionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header text-white border-0 py-3 rounded-top-4" style="background-color: #2D6A4F !important;">
                <h5 class="modal-title fw-bold" id="decisionModalLabel">
                    <i class="bi bi-file-earmark-check-fill me-2"></i>Digital Sales Order Contract
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p id="decisionModalMessage" class="text-secondary small mb-3"></p>

                <!-- Contract Summary Box -->
                <div class="card border-0 bg-light rounded-3 p-3 mb-3">
                    <div class="row g-2 text-dark small">
                        <div class="col-6 col-md-4">
                            <span class="text-muted d-block extra-small">Crop:</span>
                            <strong id="modalCrop">--</strong>
                        </div>
                        <div class="col-6 col-md-4">
                            <span class="text-muted d-block extra-small">Quantity:</span>
                            <strong id="modalQuantity">--</strong>
                        </div>
                        <div class="col-6 col-md-4">
                            <span class="text-muted d-block extra-small">Agreed Unit Price:</span>
                            <strong class="text-success" id="modalPrice">--</strong>
                        </div>
                    </div>
                </div>

                <div id="contractOtpSection">
                    <!-- Terms Checkbox -->
                    <div class="form-check mb-3 p-3 bg-white border rounded-3 ms-0">
                        <input class="form-check-input ms-1 me-2" type="checkbox" id="farmerAgreedCheck">
                        <label class="form-check-label text-dark fw-semibold small" for="farmerAgreedCheck">
                            I agree to the legally binding terms of this sales order, produce delivery timeline, and AgriSync fair trade terms.
                        </label>
                    </div>

                    <!-- OTP Section -->
                    <div class="p-3 bg-light rounded-3 border mb-2">
                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-shield-lock-fill text-success me-1"></i>One-Time Password (OTP) Verification</h6>
                        <p class="extra-small text-muted mb-3">Click "Send OTP" to receive a 6-digit verification code on your mobile phone.</p>

                        <div class="row g-2 align-items-center">
                            <div class="col-12 col-md-5">
                                <button type="button" class="btn btn-outline-success btn-sm w-100 py-2 fw-semibold" id="btnFarmerSendOtp">
                                    <i class="bi bi-send-fill me-1"></i> Send OTP Code
                                </button>
                            </div>
                            <div class="col-12 col-md-7">
                                <input type="text" class="form-control form-control-sm text-center fw-bold fs-5 tracking-wider" id="inputFarmerOtp" maxlength="6" placeholder="Enter 6-digit OTP" disabled>
                            </div>
                        </div>

                        <div id="farmerOtpAlertBox" class="mt-3 d-none"></div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0 pe-4 pb-4">
                <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success rounded-3 px-4 shadow-sm" id="btnConfirmDecision" style="background-color: #2D6A4F; border-color: #2D6A4F;">
                    <i class="bi bi-check-circle-fill me-1"></i> Confirm & Sign
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = '<?= $csrf_token ?>';
    const offersContainer = document.getElementById('offersContainer');
    const filterStatus = document.getElementById('filterStatus');
    const btnRefreshOffers = document.getElementById('btnRefreshOffers');
    const offersCountBadge = document.getElementById('offersCountBadge');

    const countPendingOffers = document.getElementById('countPendingOffers');
    const countAcceptedOffers = document.getElementById('countAcceptedOffers');
    const totalPotentialRevenue = document.getElementById('totalPotentialRevenue');

    const decisionModal = new bootstrap.Modal(document.getElementById('decisionModal'));
    const btnConfirmDecision = document.getElementById('btnConfirmDecision');

    let currentActionMatchId = null;
    let currentActionType = null; // 'accept' or 'reject'

    async function loadOffers() {
        offersContainer.innerHTML = `
            <div class="col-12 text-center py-5 text-muted">
                <span class="spinner-border spinner-border-sm me-2"></span> Loading your incoming offers...
            </div>
        `;

        try {
            const res = await fetch('../api/farmer.php?action=get_dashboard');
            const data = await res.json();

            if (data.success) {
                const matches = data.data.recent_matches || [];
                renderOffers(matches);
            } else {
                offersContainer.innerHTML = `<div class="col-12 text-center py-4 text-danger">${data.error || 'Failed to load offers.'}</div>`;
            }
        } catch (err) {
            offersContainer.innerHTML = `<div class="col-12 text-center py-4 text-danger">Error connecting to server.</div>`;
        }
    }

    function renderOffers(matches) {
        let pending = 0;
        let accepted = 0;
        let potentialRev = 0;

        matches.forEach(m => {
            const st = (m.status || '').toLowerCase();
            const totalVal = (parseFloat(m.matched_price || 0) * parseFloat(m.quantity_kg || 0));
            if (st === 'proposed' || st === 'matching' || st === 'pending') pending++;
            if (st === 'accepted' || st === 'delivered' || st === 'fulfilled') accepted++;
            potentialRev += totalVal;
        });

        countPendingOffers.textContent = pending;
        countAcceptedOffers.textContent = accepted;
        totalPotentialRevenue.textContent = `Rs. ${potentialRev.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

        const selectedFilter = filterStatus.value;
        const filtered = matches.filter(m => {
            if (selectedFilter === 'all') return true;
            return (m.status || '').toLowerCase() === selectedFilter;
        });

        offersCountBadge.textContent = `${filtered.length} Offer${filtered.length === 1 ? '' : 's'}`;

        if (filtered.length === 0) {
            offersContainer.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="avatar rounded-circle bg-light text-muted mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px; font-size: 1.8rem;">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <h6 class="fw-bold text-dark">No offers found</h6>
                    <p class="text-muted small mb-0">There are no offers matching the selected status filter.</p>
                </div>
            `;
            return;
        }

        offersContainer.innerHTML = filtered.map(m => {
            const st = (m.status || '').toLowerCase();
            const isProposed = (st === 'proposed' || st === 'matching' || st === 'pending');
            const totalVal = (parseFloat(m.matched_price || 0) * parseFloat(m.quantity_kg || 0));

            let statusBadge = `<span class="badge bg-secondary extra-small text-capitalize">${st}</span>`;
            if (st === 'proposed') statusBadge = `<span class="badge bg-warning-subtle text-warning border border-warning extra-small"><i class="bi bi-clock me-1"></i>Pending Review</span>`;
            if (st === 'accepted') statusBadge = `<span class="badge bg-success-subtle text-success border border-success extra-small"><i class="bi bi-check2-circle me-1"></i>Accepted</span>`;
            if (st === 'in_transit') statusBadge = `<span class="badge bg-info-subtle text-info border border-info extra-small"><i class="bi bi-truck me-1"></i>In Transit</span>`;
            if (st === 'delivered' || st === 'completed') statusBadge = `<span class="badge bg-success text-white extra-small"><i class="bi bi-patch-check-fill me-1"></i>Delivered (Escrow Released)</span>`;
            if (st === 'rejected') statusBadge = `<span class="badge bg-danger-subtle text-danger border border-danger extra-small"><i class="bi bi-x-circle me-1"></i>Declined</span>`;

            return `
                <div class="col-12">
                    <div class="card border shadow-sm rounded-3 hover-lift p-3 p-md-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 border-bottom pb-3 mb-3">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-success-subtle text-success fw-bold px-2 py-1">${m.crop_type}</span>
                                    <span class="text-muted extra-small">Match Ref: #M-${m.id}</span>
                                    ${statusBadge}
                                </div>
                                <h5 class="fw-bold text-dark mb-0">Buyer: ${m.business_name || 'Commercial Buyer'}</h5>
                                <div class="text-muted extra-small"><i class="bi bi-geo-alt me-1"></i>${m.business_district || 'Sri Lanka'} • Delivery by ${m.delivery_date || 'Flexible'}</div>
                            </div>
                            
                            <div class="text-md-end">
                                <div class="fw-bold text-success fs-5">Rs. ${parseFloat(m.matched_price || 0).toFixed(2)} / kg</div>
                                <div class="text-muted extra-small">Quantity: <span class="fw-semibold text-dark">${parseFloat(m.quantity_kg || 0).toLocaleString()} kg</span> (Total: <span class="fw-bold text-dark">Rs. ${totalVal.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>)</div>
                            </div>
                        </div>

                        ${m.agent_reasoning ? `
                            <div class="p-3 bg-light rounded-3 mb-3 border border-light-subtle extra-small">
                                <div class="fw-semibold text-primary mb-1"><i class="bi bi-robot text-warning me-1"></i> Gemini AI Broker Match Insight:</div>
                                <p class="text-secondary mb-0">${m.agent_reasoning}</p>
                            </div>
                        ` : ''}

                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted extra-small"><i class="bi bi-shield-check text-success me-1"></i>Fair Trade Floor Protected</span>
                            
                            ${isProposed ? `
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-danger px-3 btn-offer-action" 
                                            data-id="${m.id}" data-action="reject" 
                                            data-crop="${m.crop_type}" data-qty="${m.quantity_kg}" data-price="${m.matched_price}">
                                        <i class="bi bi-x-lg me-1"></i> Decline
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success px-4 btn-offer-action" 
                                            data-id="${m.id}" data-action="accept"
                                            data-crop="${m.crop_type}" data-qty="${m.quantity_kg}" data-price="${m.matched_price}">
                                        <i class="bi bi-check-lg me-1"></i> Accept Deal
                                    </button>
                                </div>
                            ` : (st === 'accepted' ? `
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-info text-white px-3 btn-dispatch-action" data-match-id="${m.id}">
                                        <i class="bi bi-truck me-1"></i> Mark as Dispatched
                                    </button>
                                </div>
                            ` : `
                                <div>
                                    <a href="<?= APP_URL ?>/farmer/orders.php" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye me-1"></i> View in Orders
                                    </a>
                                </div>
                            `)}
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Attach dispatch action handlers
        document.querySelectorAll('.btn-dispatch-action').forEach(btn => {
            btn.addEventListener('click', async () => {
                const matchId = btn.getAttribute('data-match-id');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Updating...';

                try {
                    const res = await fetch('../api/update_delivery_status.php', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken 
                        },
                        body: JSON.stringify({
                            match_id: parseInt(matchId),
                            status: 'in_transit',
                            csrf_token: csrfToken
                        })
                    });
                    const result = await res.json();
                    if (result.success) {
                        if (typeof showToast === 'function') showToast(result.data.message || 'Order marked as In Transit!', 'success');
                        loadOffers();
                    } else {
                        if (typeof showToast === 'function') showToast(result.error || 'Failed to update status.', 'error');
                        btn.disabled = false;
                    }
                } catch (err) {
                    if (typeof showToast === 'function') showToast('Server error updating delivery status.', 'error');
                    btn.disabled = false;
                }
            });
        });

        // Attach action handlers
        const contractOtpSection = document.getElementById('contractOtpSection');
        const farmerAgreedCheck = document.getElementById('farmerAgreedCheck');
        const btnFarmerSendOtp = document.getElementById('btnFarmerSendOtp');
        const inputFarmerOtp = document.getElementById('inputFarmerOtp');
        const farmerOtpAlertBox = document.getElementById('farmerOtpAlertBox');

        farmerAgreedCheck.addEventListener('change', () => {
            if (currentActionType === 'accept') {
                btnConfirmDecision.disabled = !(farmerAgreedCheck.checked && inputFarmerOtp.value.length === 6);
            }
        });

        inputFarmerOtp.addEventListener('input', () => {
            inputFarmerOtp.value = inputFarmerOtp.value.replace(/[^0-9]/g, '');
            if (currentActionType === 'accept') {
                btnConfirmDecision.disabled = !(farmerAgreedCheck.checked && inputFarmerOtp.value.length === 6);
            }
        });

        btnFarmerSendOtp.addEventListener('click', async () => {
            if (!currentActionMatchId) return;

            btnFarmerSendOtp.disabled = true;
            btnFarmerSendOtp.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending...';
            farmerOtpAlertBox.className = 'mt-3 d-none';

            try {
                const formData = new FormData();
                formData.append('action', 'send_otp');
                formData.append('match_id', currentActionMatchId);
                formData.append('csrf_token', csrfToken);

                const res = await fetch('../api/confirm_match_otp.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();

                if (result.success) {
                    inputFarmerOtp.disabled = false;
                    inputFarmerOtp.focus();

                    let msg = result.data.message || 'OTP sent.';
                    if (result.data.mock_otp) {
                        msg += ` [DEMO MOCK OTP: ${result.data.mock_otp}]`;
                        inputFarmerOtp.value = result.data.mock_otp;
                        if (farmerAgreedCheck.checked) btnConfirmDecision.disabled = false;
                    }

                    farmerOtpAlertBox.className = 'alert alert-success extra-small mt-3 mb-0';
                    farmerOtpAlertBox.textContent = msg;
                } else {
                    farmerOtpAlertBox.className = 'alert alert-danger extra-small mt-3 mb-0';
                    farmerOtpAlertBox.textContent = result.error || 'Failed to dispatch OTP.';
                }
            } catch (err) {
                farmerOtpAlertBox.className = 'alert alert-danger extra-small mt-3 mb-0';
                farmerOtpAlertBox.textContent = 'Server connection error.';
            } finally {
                btnFarmerSendOtp.disabled = false;
                btnFarmerSendOtp.innerHTML = '<i class="bi bi-send-fill me-1"></i> Resend OTP Code';
            }
        });

        document.querySelectorAll('.btn-offer-action').forEach(btn => {
            btn.addEventListener('click', () => {
                currentActionMatchId = btn.getAttribute('data-id');
                currentActionType = btn.getAttribute('data-action');
                
                const crop = btn.getAttribute('data-crop');
                const qty = parseFloat(btn.getAttribute('data-qty')).toLocaleString();
                const price = parseFloat(btn.getAttribute('data-price')).toFixed(2);

                document.getElementById('modalCrop').textContent = crop;
                document.getElementById('modalQuantity').textContent = `${qty} kg`;
                document.getElementById('modalPrice').textContent = `Rs. ${price} / kg`;

                farmerAgreedCheck.checked = false;
                inputFarmerOtp.value = '';
                inputFarmerOtp.disabled = true;
                farmerOtpAlertBox.className = 'mt-3 d-none';

                if (currentActionType === 'accept') {
                    document.getElementById('decisionModalLabel').innerHTML = '<i class="bi bi-file-earmark-check-fill me-2"></i>Sign Sales Order Contract (OTP)';
                    document.getElementById('decisionModalMessage').textContent = 'Review agreement details and verify OTP to confirm legally binding sale to the buyer.';
                    contractOtpSection.style.display = 'block';
                    btnConfirmDecision.className = 'btn btn-success';
                    btnConfirmDecision.textContent = 'Verify OTP & Confirm Contract';
                    btnConfirmDecision.disabled = true;
                } else {
                    document.getElementById('decisionModalLabel').innerHTML = '<i class="bi bi-x-circle me-2"></i>Decline Purchase Offer';
                    document.getElementById('decisionModalMessage').textContent = 'Are you sure you want to decline this offer? The AI broker will seek alternative buyer matches.';
                    contractOtpSection.style.display = 'none';
                    btnConfirmDecision.className = 'btn btn-danger';
                    btnConfirmDecision.textContent = 'Decline Offer';
                    btnConfirmDecision.disabled = false;
                }

                decisionModal.show();
            });
        });
    }

    btnConfirmDecision.addEventListener('click', async () => {
        if (!currentActionMatchId || !currentActionType) return;

        btnConfirmDecision.disabled = true;
        btnConfirmDecision.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

        try {
            let res, result;

            if (currentActionType === 'accept') {
                const formData = new FormData();
                formData.append('action', 'verify_otp');
                formData.append('match_id', currentActionMatchId);
                formData.append('otp_code', inputFarmerOtp.value);
                formData.append('contract_agreed', '1');
                formData.append('csrf_token', csrfToken);

                res = await fetch('../api/confirm_match_otp.php', {
                    method: 'POST',
                    body: formData
                });
                result = await res.json();
            } else {
                const formData = new FormData();
                formData.append('action', 'match_decision');
                formData.append('match_id', currentActionMatchId);
                formData.append('decision', currentActionType);
                formData.append('csrf_token', csrfToken);

                res = await fetch('../api/farmer.php?action=match_decision', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                });
                result = await res.json();
            }

            if (result.success) {
                showToast(result.error || `Offer ${currentActionType === 'accept' ? 'contract verified and signed' : 'declined'} successfully!`, 'success');
                decisionModal.hide();
                loadOffers();
            } else {
                showToast(result.error || 'Failed to submit match decision.', 'error');
                btnConfirmDecision.disabled = false;
            }
        } catch (err) {
            showToast('Server error while processing decision.', 'error');
            btnConfirmDecision.disabled = false;
        }
    });

    filterStatus.addEventListener('change', () => {
        loadOffers();
    });

    btnRefreshOffers.addEventListener('click', () => {
        loadOffers();
    });

    loadOffers();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

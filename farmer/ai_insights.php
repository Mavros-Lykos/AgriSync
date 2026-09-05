<?php
/**
 * AgriSync — Farmer AI Demand Insights & Crop Advisory (TASK-067)
 * Interactive AI-powered crop forecasting and market intelligence for farmers.
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

// Strict Farmer Access Control
requireRole('farmer');

$page_title = 'AI Demand Insights & Market Advisory';
$user_id = (int) ($_SESSION['user_id'] ?? 0);
$user_name = $_SESSION['user_name'] ?? 'Farmer';

$recent_predictions = [];
$default_district = 'Dambulla';

try {
    $db = getDbConnection();

    // Fetch farmer profile district if available
    $pStmt = $db->prepare("SELECT district FROM users WHERE id = :user_id LIMIT 1");
    $pStmt->execute([':user_id' => $user_id]);
    $farmerProfile = $pStmt->fetch(PDO::FETCH_ASSOC);
    if ($farmerProfile && !empty($farmerProfile['district'])) {
        $default_district = $farmerProfile['district'];
    }

    // Fetch recent demand predictions from agent_logs
    $logStmt = $db->query("
        SELECT id, action_step, log_data, created_at 
        FROM agent_logs 
        WHERE agent_type = 'demand_predictor' 
        ORDER BY id DESC 
        LIMIT 6
    ");
    $recent_predictions = $logStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log("Farmer AI Insights DB Error: " . $e->getMessage());
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex" style="min-height: 100vh;">
    <!-- Role-based Sidebar Navigation -->
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex-grow-1 bg-light p-4 overflow-auto">
        <div class="container-fluid max-w-7xl">
            
            <!-- Page Header -->
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 pb-2 border-bottom">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">
                        <i class="bi bi-graph-up-arrow text-success me-2"></i> AI Demand Insights & Advisory
                    </h1>
                    <p class="text-muted small mb-0">
                        Predict regional wholesale demand, forecast fair prices in LKR, and optimize your harvest planting schedule.
                    </p>
                </div>
                <div class="mt-3 mt-md-0">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                        <i class="bi bi-stars me-1"></i> Powered by Google Gemini 2.5 Flash
                    </span>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- Interactive Demand Query Generator -->
                <div class="col-12 col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <div class="d-flex align-items-center mb-3">
                            <div class="p-2 rounded-3 bg-success-subtle text-success me-3">
                                <i class="bi bi-magic fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">Forecast Crop Demand</h5>
                                <small class="text-muted">Select crop & district to query market AI</small>
                            </div>
                        </div>

                        <form id="demandForm" class="mt-3">
                            <div class="mb-3">
                                <label for="cropType" class="form-label small fw-semibold text-muted">Crop / Produce Type</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-tree text-success"></i></span>
                                    <select class="form-select border-start-0 rounded-end-3" id="cropType" name="crop_type" required>
                                        <option value="" disabled>Select target crop...</option>
                                        <?php foreach (AGRISYNC_CROPS as $c): ?>
                                            <option value="<?= $c ?>" <?= $c === 'Tomato' ? 'selected' : '' ?>><?= $c ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="districtSelect" class="form-label small fw-semibold text-muted">Farming District</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-geo-alt text-success"></i></span>
                                    <select class="form-select border-start-0 rounded-end-3" id="districtSelect" name="district" required>
                                        <?php
                                        $districts = ['Dambulla', 'Nuwara Eliya', 'Matale', 'Kandy', 'Colombo', 'Jaffna', 'Anuradhapura', 'Badulla', 'Kurunegala', 'Hambantota', 'Ratnapura', 'Gampaha'];
                                        foreach ($districts as $d):
                                        ?>
                                            <option value="<?= $d ?>" <?= ($d === $default_district) ? 'selected' : '' ?>><?= $d ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" id="btnPredict" class="btn btn-primary w-100 rounded-3 py-2 fw-semibold d-flex align-items-center justify-content-center shadow-sm" style="min-height: 46px;">
                                <i class="bi bi-lightning-charge-fill me-2"></i> Run AI Demand Forecast
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Real-time AI Forecast Output Panel -->
                <div class="col-12 col-lg-7">
                    <!-- Default Initial State -->
                    <div id="initialStateCard" class="card border-0 shadow-sm rounded-4 p-5 bg-white text-center h-100 d-flex flex-column justify-content-center align-items-center">
                        <div class="p-4 rounded-circle bg-success-subtle text-success mb-3" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-stars fs-1"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">Ready to Forecast Market Trends</h5>
                        <p class="text-muted small max-w-md mb-0">
                            Select your crop and farming district on the left and click <strong>"Run AI Demand Forecast"</strong> to generate predictive analytics, wholesale price projections, and harvest timing advisories.
                        </p>
                    </div>

                    <!-- Animated AI Processing State -->
                    <div id="loadingStateCard" class="card border-0 shadow-sm rounded-4 p-5 bg-white text-center h-100 d-none flex-column justify-content-center align-items-center">
                        <div class="spinner-border text-success mb-3" style="width: 3.5rem; height: 3.5rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">AgriSync AI Agent is Analyzing...</h5>
                        <p class="text-muted small mb-0" id="processingText">Synthesizing wholesale order demand, regional harvest pipelines, and seasonal weather patterns...</p>
                    </div>

                    <!-- AI Prediction Result Card -->
                    <div id="resultStateCard" class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-none">
                        <div class="d-flex flex-wrap align-items-center justify-content-between pb-3 mb-3 border-bottom">
                            <div>
                                <span class="badge bg-success text-white px-2 py-1 rounded-pill small mb-1" id="resSeasonBadge">Maha Season</span>
                                <h4 class="fw-bold text-dark mb-0" id="resCropDistrictTitle">Tomato in Dambulla</h4>
                            </div>
                            <div class="text-end mt-2 mt-sm-0">
                                <span class="text-muted small d-block">AI Confidence</span>
                                <span class="badge bg-success-subtle text-success fs-6 fw-bold px-3 py-1 rounded-pill" id="resConfidenceScore">92%</span>
                            </div>
                        </div>

                        <!-- Top Metric Indicators -->
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-sm-4">
                                <div class="p-3 rounded-3 bg-light border text-center">
                                    <small class="text-muted d-block mb-1">Demand Level</small>
                                    <span class="fw-bold fs-5 text-success" id="resDemandLevel">High</span>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="p-3 rounded-3 bg-light border text-center">
                                    <small class="text-muted d-block mb-1">Market Trend</small>
                                    <span class="fw-bold fs-5 text-primary" id="resMarketTrend">Rising</span>
                                </div>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="p-3 rounded-3 bg-light border text-center">
                                    <small class="text-muted d-block mb-1">Projected Price Range</small>
                                    <span class="fw-bold fs-5 text-dark" id="resPriceRange">Rs. 180 - 240 /kg</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actionable Farmer Advisory Banner -->
                        <div class="p-3 rounded-3 bg-success-subtle border border-success-subtle mb-3">
                            <div class="d-flex">
                                <i class="bi bi-lightbulb-fill text-success fs-4 me-3 mt-1"></i>
                                <div>
                                    <h6 class="fw-bold text-success mb-1">Strategic Farmer Advisory</h6>
                                    <p class="small text-dark mb-0" id="resActionableAdvice">
                                        Strong commercial demand projected. List harvests 7-10 days in advance to secure premium pre-orders.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Key Market Drivers -->
                        <div class="mb-3">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-check2-circle text-success me-1"></i> Key Market Drivers:</h6>
                            <ul class="list-unstyled mb-0 small" id="resKeyFactorsList">
                                <li class="text-muted mb-1">• Peak retail supermarket demand</li>
                            </ul>
                        </div>

                        <!-- Recommended Next Cycle Crops -->
                        <div class="pt-2 border-top">
                            <small class="text-muted fw-semibold me-2">Recommended Crops for Next Planting Cycle:</small>
                            <span id="resCropTags" class="d-inline-flex flex-wrap gap-1 mt-1">
                                <span class="badge bg-secondary-subtle text-dark">Big Onion</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent AI Query History -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-clock-history text-success me-2"></i> Recent Platform Demand Advisories
                    </h5>
                    <span class="text-muted small">Live Transparency Log</span>
                </div>

                <div class="row g-3">
                    <?php if (empty($recent_predictions)): ?>
                        <div class="col-12 text-center py-4 text-muted small">
                            No previous demand queries logged yet. Run your first forecast above!
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_predictions as $item): ?>
                            <?php 
                                $payload = !empty($item['log_data']) ? json_decode($item['log_data'], true) : [];
                                $cropName = $payload['crop'] ?? $payload['target_crop'] ?? 'Vegetable';
                                $districtName = $payload['district'] ?? $payload['target_district'] ?? 'Sri Lanka';
                                $demandLvl = $payload['demand_level'] ?? 'High';
                            ?>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="p-3 rounded-3 bg-light border h-100">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <strong class="text-dark"><?= htmlspecialchars($cropName, ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span class="badge bg-success-subtle text-success small"><?= htmlspecialchars($demandLvl, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="text-muted small mb-2">
                                        <i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($districtName, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        <i class="bi bi-calendar-event me-1"></i> <?= date('M d, Y H:i', strtotime($item['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const demandForm = document.getElementById('demandForm');
    const btnPredict = document.getElementById('btnPredict');
    const initialState = document.getElementById('initialStateCard');
    const loadingState = document.getElementById('loadingStateCard');
    const resultState = document.getElementById('resultStateCard');

    if (demandForm) {
        demandForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const cropType = document.getElementById('cropType').value;
            const district = document.getElementById('districtSelect').value;

            if (!cropType || !district) {
                alert('Please select both a crop and district.');
                return;
            }

            // Show loading animation
            initialState.classList.add('d-none');
            resultState.classList.add('d-none');
            loadingState.classList.remove('d-none');
            loadingState.classList.add('d-flex');
            btnPredict.disabled = true;

            try {
                const response = await fetch('../api/predict_demand.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        crop_type: cropType,
                        district: district
                    })
                });

                const rawText = await response.text();
                let res;
                try {
                    res = JSON.parse(rawText);
                } catch (parseErr) {
                    throw new Error(`Server returned unexpected response (HTTP ${response.status}).`);
                }

                if (res && res.success && res.data && res.data.forecast) {
                    const f = res.data.forecast;
                    const stats = res.data.market_stats || {};

                    // Update result card
                    document.getElementById('resSeasonBadge').textContent = `${res.data.season} • ${res.data.month}`;
                    document.getElementById('resCropDistrictTitle').textContent = `${cropType} in ${district}`;
                    document.getElementById('resConfidenceScore').textContent = `${f.confidence_score}%`;
                    document.getElementById('resDemandLevel').textContent = f.predicted_demand_level;
                    document.getElementById('resMarketTrend').textContent = f.market_trend;

                    if (f.predicted_price_range) {
                        document.getElementById('resPriceRange').textContent = `Rs. ${f.predicted_price_range.min} - ${f.predicted_price_range.max} /kg`;
                    }

                    document.getElementById('resActionableAdvice').textContent = f.actionable_advice;

                    // Update key factors list
                    const factorsList = document.getElementById('resKeyFactorsList');
                    factorsList.innerHTML = '';
                    if (f.key_factors && Array.isArray(f.key_factors)) {
                        f.key_factors.forEach(factor => {
                            const li = document.createElement('li');
                            li.className = 'text-dark mb-1 d-flex align-items-start';
                            li.innerHTML = `<i class="bi bi-dot text-success fs-5 me-1"></i> <span>${factor}</span>`;
                            factorsList.appendChild(li);
                        });
                    }

                    // Update crop tags
                    const cropTags = document.getElementById('resCropTags');
                    cropTags.innerHTML = '';
                    if (f.recommended_crops_next_cycle && Array.isArray(f.recommended_crops_next_cycle)) {
                        f.recommended_crops_next_cycle.forEach(c => {
                            const span = document.createElement('span');
                            span.className = 'badge bg-success-subtle text-success border border-success-subtle px-2 py-1';
                            span.textContent = c;
                            cropTags.appendChild(span);
                        });
                    }

                    // Display results
                    loadingState.classList.add('d-none');
                    loadingState.classList.remove('d-flex');
                    resultState.classList.remove('d-none');
                } else {
                    throw new Error(res?.error || 'Failed to fetch AI forecast.');
                }
            } catch (err) {
                alert('Error running AI demand prediction: ' + err.message);
                loadingState.classList.add('d-none');
                loadingState.classList.remove('d-flex');
                initialState.classList.remove('d-none');
            } finally {
                btnPredict.disabled = false;
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

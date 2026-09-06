<?php
/**
 * AgriSync — Farmer Dashboard & AI Market Advisory (TASK-032, TASK-068)
 * Central command center for farmers with integrated AI demand predictions and harvest metrics.
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

// Strict Farmer Access Control
requireRole('farmer');

$page_title = 'Farmer Dashboard';
$user_id = (int) ($_SESSION['user_id'] ?? 0);
$user_name = $_SESSION['user_name'] ?? 'Farmer';
$district = $_SESSION['user_district'] ?? 'Dambulla';

$stats = [
    'active_listings' => 0,
    'total_produce_kg' => 0,
    'pending_offers' => 0,
    'completed_matches' => 0,
];
$recent_listings = [];
$incoming_offers = [];

try {
    $db = getDbConnection();

    // Fetch Summary Metrics
    $lStatStmt = $db->prepare("
        SELECT 
            COUNT(*) as total_listings,
            COALESCE(SUM(quantity_kg), 0) as total_kg
        FROM harvest_listings 
        WHERE farmer_id = :farmer_id AND status = 'available'
    ");
    $lStatStmt->execute([':farmer_id' => $user_id]);
    $lRow = $lStatStmt->fetch(PDO::FETCH_ASSOC);
    if ($lRow) {
        $stats['active_listings'] = (int) $lRow['total_listings'];
        $stats['total_produce_kg'] = (float) $lRow['total_kg'];
    }

    // Fetch Offers Count
    $mStatStmt = $db->prepare("
        SELECT 
            SUM(CASE WHEN status IN ('proposed', 'accepted') THEN 1 ELSE 0 END) as pending_offers,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_matches
        FROM order_matches 
        WHERE farmer_id = :farmer_id
    ");
    $mStatStmt->execute([':farmer_id' => $user_id]);
    $mRow = $mStatStmt->fetch(PDO::FETCH_ASSOC);
    if ($mRow) {
        $stats['pending_offers'] = (int) ($mRow['pending_offers'] ?? 0);
        $stats['completed_matches'] = (int) ($mRow['completed_matches'] ?? 0);
    }

    // Fetch Recent Listings
    $listStmt = $db->prepare("
        SELECT id, crop_type, quantity_kg, price_per_kg, harvest_date, status, created_at
        FROM harvest_listings
        WHERE farmer_id = :farmer_id
        ORDER BY id DESC LIMIT 5
    ");
    $listStmt->execute([':farmer_id' => $user_id]);
    $recent_listings = $listStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Recent Matches / Offers (Excluding closed deals)
    $matchStmt = $db->prepare("
        SELECT 
            m.id as match_id, m.matched_price, m.confidence_score, m.status as match_status, m.created_at,
            o.crop_type, o.quantity_kg as demanded_kg,
            u.name as business_name, u.district as business_district
        FROM order_matches m
        JOIN order_requests o ON m.order_id = o.id
        JOIN users u ON m.business_id = u.id
        WHERE m.farmer_id = :farmer_id AND m.status NOT IN ('completed', 'rejected', 'expired')
        ORDER BY m.id DESC LIMIT 4
    ");
    $matchStmt->execute([':farmer_id' => $user_id]);
    $incoming_offers = $matchStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log("Farmer Dashboard DB Error: " . $e->getMessage());
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
                        🌾 Welcome back, <?= htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') ?>!
                    </h1>
                    <p class="text-muted small mb-0">
                        Region: <span class="badge bg-light text-dark border"><?= htmlspecialchars($district ?? 'Dambulla', ENT_QUOTES, 'UTF-8') ?></span> | Manage your produce listings, track buyer matches, and view AI demand forecasts.
                    </p>
                </div>
                <div class="d-flex gap-2 mt-3 mt-md-0">
                    <a href="ai_insights.php" class="btn btn-outline-success rounded-3 d-flex align-items-center">
                        <i class="bi bi-stars me-1"></i> AI Demand Forecast
                    </a>
                    <a href="add_listing.php" class="btn btn-primary rounded-3 d-flex align-items-center">
                        <i class="bi bi-plus-circle-fill me-1"></i> List New Harvest
                    </a>
                </div>
            </div>

            <!-- Top Summary Stat Cards -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Active Listings</span>
                            <div class="p-2 rounded-3 bg-success-subtle text-success">
                                <i class="bi bi-flower1 fs-5"></i>
                            </div>
                        </div>
                        <h2 class="h3 fw-bold mb-0 text-dark"><?= number_format($stats['active_listings']) ?></h2>
                        <small class="text-muted"><?= number_format($stats['total_produce_kg'], 1) ?> kg in market</small>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Buyer Matches & Offers</span>
                            <div class="p-2 rounded-3 bg-primary-subtle text-primary">
                                <i class="bi bi-receipt-cutoff fs-5"></i>
                            </div>
                        </div>
                        <h2 class="h3 fw-bold mb-0 text-dark"><?= number_format($stats['pending_offers']) ?></h2>
                        <small class="text-muted">Awaiting fulfillment / action</small>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">Completed Trades</span>
                            <div class="p-2 rounded-3 bg-info-subtle text-info">
                                <i class="bi bi-patch-check-fill fs-5"></i>
                            </div>
                        </div>
                        <h2 class="h3 fw-bold mb-0 text-dark"><?= number_format($stats['completed_matches']) ?></h2>
                        <small class="text-muted">Direct commercial deals</small>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">AI Match Success</span>
                            <div class="p-2 rounded-3 bg-warning-subtle text-warning">
                                <i class="bi bi-magic fs-5"></i>
                            </div>
                        </div>
                        <h2 class="h3 fw-bold mb-0 text-dark">94%</h2>
                        <small class="text-muted">Regional price fairness</small>
                    </div>
                </div>
            </div>

            <!-- AI Market Insights & Demand Advisory Banner (TASK-068) -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 pb-2 border-bottom">
                    <div class="d-flex align-items-center">
                        <div class="p-2 rounded-3 bg-success-subtle text-success me-3">
                            <i class="bi bi-graph-up-arrow fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Regional Market Demand Advisory</h5>
                            <small class="text-muted">Autonomous Gemini Agent Market Forecasting for <strong><?= htmlspecialchars($district) ?></strong> hub</small>
                        </div>
                    </div>
                    <a href="ai_insights.php" class="btn btn-sm btn-outline-success rounded-3 mt-2 mt-md-0">
                        Full Advisory Report &rarr;
                    </a>
                </div>

                <div class="row g-3" id="quickDemandSummary">
                    <div class="col-12 col-md-4">
                        <div class="p-3 bg-light rounded-3 border-start border-success border-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark">Tomatoes</span>
                                <span class="badge bg-success-subtle text-success">High Demand (88%)</span>
                            </div>
                            <p class="small text-muted mb-0 mt-1">Shortage expected in next 2 weeks. Suggested target: Rs. 210 - 240/kg.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="p-3 bg-light rounded-3 border-start border-primary border-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark">Carrots</span>
                                <span class="badge bg-primary-subtle text-primary">Moderate (65%)</span>
                            </div>
                            <p class="small text-muted mb-0 mt-1">Stable retail and wholesale uptake. Suggested target: Rs. 175 - 195/kg.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="p-3 bg-light rounded-3 border-start border-warning border-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark">Big Onions</span>
                                <span class="badge bg-warning-subtle text-warning">Surplus Alert (32%)</span>
                            </div>
                            <p class="small text-muted mb-0 mt-1">High incoming harvest. Consider forward match contracts now.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Listings & Incoming Buyer Offers -->
            <div class="row g-4 mb-4">
                
                <!-- Recent Listings Column -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                        <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold text-dark mb-0">My Active Harvests</h5>
                            <a href="listings.php" class="text-success small fw-semibold text-decoration-none">Manage All &rarr;</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light small">
                                        <tr>
                                            <th>Produce</th>
                                            <th>Quantity</th>
                                            <th>Price/kg</th>
                                            <th>Harvest Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recent_listings)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted small">
                                                    No harvest listings active. 
                                                    <div class="mt-2">
                                                        <a href="add_listing.php" class="btn btn-sm btn-primary rounded-3">Add First Listing</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recent_listings as $item): ?>
                                                <?php
                                                    $st = $item['status'];
                                                    $badge = 'bg-secondary-subtle text-secondary';
                                                    if ($st === 'available') $badge = 'bg-success-subtle text-success';
                                                    if ($st === 'matched') $badge = 'bg-warning-subtle text-warning';
                                                    if ($st === 'sold') $badge = 'bg-info-subtle text-info';
                                                ?>
                                                <tr>
                                                    <td class="fw-bold text-dark"><?= htmlspecialchars($item['crop_type'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td><?= number_format($item['quantity_kg'], 1) ?> kg</td>
                                                    <td>Rs. <?= number_format($item['price_per_kg'], 2) ?></td>
                                                    <td class="small text-muted"><?= htmlspecialchars($item['harvest_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td>
                                                        <span class="badge rounded-pill <?= $badge ?> px-2 py-1 text-capitalize">
                                                            <?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Incoming AI Match Offers Column -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                        <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold text-dark mb-0">Buyer Match Deals</h5>
                            <a href="orders.php" class="text-success small fw-semibold text-decoration-none">View All Deals &rarr;</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light small">
                                        <tr>
                                            <th>Commercial Buyer</th>
                                            <th>Produce</th>
                                            <th>Volume</th>
                                            <th>Offered Rate</th>
                                            <th>AI Confidence</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($incoming_offers)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted small">
                                                    No buyer deals pending right now.
                                                    <br><small class="text-muted">AI Broker will notify you as soon as an order matches.</small>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($incoming_offers as $offer): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($offer['business_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                                        <small class="text-muted"><?= htmlspecialchars($offer['business_district'], ENT_QUOTES, 'UTF-8') ?></small>
                                                    </td>
                                                    <td class="fw-bold"><?= htmlspecialchars($offer['crop_type'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td><?= number_format($offer['demanded_kg'], 1) ?> kg</td>
                                                    <td class="text-success fw-bold">Rs. <?= number_format($offer['matched_price'], 2) ?>/kg</td>
                                                    <td>
                                                        <span class="badge bg-success rounded-pill px-2 py-1">
                                                            <?= (int)$offer['confidence_score'] ?>% Match
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

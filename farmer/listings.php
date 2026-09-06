<?php
/**
 * AgriSync — Farmer Harvest Inventory Management (TASK-034)
 * View, filter, and manage listed crops, pricing, and availability states.
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

// Strict Farmer Access Control
requireRole('farmer');

$page_title = 'My Harvest Listings';
$user_id = (int) ($_SESSION['user_id'] ?? 0);
$status_filter = sanitize($_GET['status'] ?? 'all');

$listings = [];
$total_volume_kg = 0;
$active_count = 0;

try {
    $db = getDbConnection();

    $sql = "
        SELECT id, crop_type, quantity_kg, price_per_kg, harvest_date, status, created_at
        FROM harvest_listings
        WHERE farmer_id = :farmer_id
    ";
    $params = [':farmer_id' => $user_id];

    if ($status_filter !== 'all' && in_array($status_filter, ['available', 'matched', 'sold'])) {
        $sql .= " AND status = :status";
        $params[':status'] = $status_filter;
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate aggregated inventory volume
    foreach ($listings as $l) {
        if ($l['status'] === 'available') {
            $active_count++;
            $total_volume_kg += (float) $l['quantity_kg'];
        }
    }
} catch (Throwable $e) {
    error_log("Farmer Listings Query Error: " . $e->getMessage());
    $error_message = "Unable to load harvest listings.";
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
            
            <!-- Header -->
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 pb-2 border-bottom">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">
                        🌾 My Harvest Inventory
                    </h1>
                    <p class="text-muted small mb-0">
                        Monitor crop listings, update stock volumes, and track AI commercial matching status.
                    </p>
                </div>
                <div class="d-flex gap-2 mt-3 mt-md-0">
                    <a href="add_listing.php" class="btn btn-primary rounded-3 d-flex align-items-center shadow-sm">
                        <i class="bi bi-plus-circle-fill me-1"></i> Add Harvest Listing
                    </a>
                </div>
            </div>

            <!-- Summary KPI Strip -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-semibold">Active Listings</span>
                                <h3 class="fw-bold mb-0 text-dark mt-1"><?= $active_count ?></h3>
                            </div>
                            <div class="p-3 rounded-3 bg-success-subtle text-success">
                                <i class="bi bi-box-seam fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-semibold">Available Produce Volume</span>
                                <h3 class="fw-bold mb-0 text-dark mt-1"><?= number_format($total_volume_kg, 1) ?> <span class="fs-6 text-muted">kg</span></h3>
                            </div>
                            <div class="p-3 rounded-3 bg-primary-subtle text-primary">
                                <i class="bi bi-speedometer2 fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-semibold">AI Match Readines</span>
                                <h3 class="fw-bold mb-0 text-success mt-1">Live <span class="badge bg-success rounded-pill fs-6">100%</span></h3>
                            </div>
                            <div class="p-3 rounded-3 bg-info-subtle text-info">
                                <i class="bi bi-robot fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Filter Tabs -->
            <div class="card border-0 shadow-sm rounded-4 p-2 mb-4 bg-white">
                <ul class="nav nav-pills nav-fill small fw-semibold">
                    <li class="nav-item">
                        <a class="nav-link <?= $status_filter === 'all' ? 'active bg-primary' : 'text-dark' ?>" href="listings.php?status=all">All Listings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $status_filter === 'available' ? 'active bg-primary' : 'text-dark' ?>" href="listings.php?status=available">Available</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $status_filter === 'matched' ? 'active bg-primary' : 'text-dark' ?>" href="listings.php?status=matched">Matched with Buyer</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $status_filter === 'sold' ? 'active bg-primary' : 'text-dark' ?>" href="listings.php?status=sold">Sold / Closed</a>
                    </li>
                </ul>
            </div>

            <!-- Listings Data Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Listing ID</th>
                                <th>Produce / Crop</th>
                                <th>Quantity (kg)</th>
                                <th>Asking Price / kg</th>
                                <th>Estimated Value</th>
                                <th>Harvest Date</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($listings)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                        No harvest listings found for this filter.
                                        <div class="mt-3">
                                            <a href="add_listing.php" class="btn btn-sm btn-primary rounded-3">Add First Harvest</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($listings as $row): ?>
                                    <?php
                                        $st = $row['status'];
                                        $badge = 'bg-secondary-subtle text-secondary';
                                        if ($st === 'available') $badge = 'bg-success-subtle text-success';
                                        if ($st === 'matched') $badge = 'bg-warning-subtle text-warning';
                                        if ($st === 'sold') $badge = 'bg-info-subtle text-info';
                                        if ($st === 'expired') $badge = 'bg-danger-subtle text-danger';
                                        $est_val = (float)$row['quantity_kg'] * (float)$row['price_per_kg'];
                                    ?>
                                    <tr>
                                        <td class="fw-semibold text-muted">#LST-<?= (int)$row['id'] ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($row['crop_type'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><strong><?= number_format($row['quantity_kg'], 1) ?></strong> kg</td>
                                        <td>Rs. <?= number_format($row['price_per_kg'], 2) ?></td>
                                        <td class="fw-semibold text-success">Rs. <?= number_format($est_val, 2) ?></td>
                                        <td class="small text-muted">
                                            <i class="bi bi-calendar3 me-1"></i> <?= htmlspecialchars($row['harvest_date'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill <?= $badge ?> px-2 py-1 text-capitalize">
                                                <?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="edit_listing.php?id=<?= (int)$row['id'] ?>" class="btn btn-outline-secondary rounded-start-3" title="Edit Listing">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger rounded-end-3" onclick="deleteListing(<?= (int)$row['id'] ?>)" title="Delete Listing">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
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

<script>
async function deleteListing(id) {
    if (!confirm('Are you sure you want to remove this harvest listing?')) return;

    try {
        const res = await fetch('../api/delete_listing.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '<?= generateCSRFToken() ?>'
            },
            body: JSON.stringify({ listing_id: id })
        });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || 'Failed to delete listing.');
        }
    } catch (err) {
        alert('Network error while deleting listing.');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

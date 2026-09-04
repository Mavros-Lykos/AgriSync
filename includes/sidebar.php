<?php
/**
 * AgriSync — Unified Role-Based Sidebar Navigation Component (TASK-013 / TASK-026)
 * Supports Farmer, Commercial Buyer, and Admin portals with active states and dark styling.
 */

if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/session.php';
}
if (!function_exists('getUserRole')) {
    require_once __DIR__ . '/../includes/functions.php';
}

$user_role = getUserRole();
$user_name = $_SESSION['user_name'] ?? 'User';
$app_url = defined('APP_URL') ? APP_URL : '';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse shadow-sm">
    <div class="position-sticky pt-3">
        
        <!-- User Role Header -->
        <div class="px-3 mb-2 sidebar-header text-uppercase">
            <?= htmlspecialchars(ucfirst((string)$user_role), ENT_QUOTES, 'UTF-8') ?> Portal
        </div>
        
        <ul class="nav flex-column px-2">
            <?php if ($user_role === 'farmer'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>" href="<?= $app_url ?>/farmer/dashboard.php">
                        <i class="bi bi-grid-1x2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= in_array($current_page, ['listings.php', 'add_listing.php', 'edit_listing.php'], true) ? 'active' : '' ?>" href="<?= $app_url ?>/farmer/listings.php">
                        <i class="bi bi-box-seam"></i>
                        <span>My Harvests</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'ai_insights.php' ? 'active' : '' ?>" href="<?= $app_url ?>/farmer/ai_insights.php">
                        <i class="bi bi-magic text-warning"></i>
                        <span>AI Forecasts</span>
                        <span class="badge bg-success ms-auto extra-small">AI</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'offers.php' ? 'active' : '' ?>" href="<?= $app_url ?>/farmer/offers.php">
                        <i class="bi bi-tags"></i>
                        <span>Incoming Offers</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'orders.php' ? 'active' : '' ?>" href="<?= $app_url ?>/farmer/orders.php">
                        <i class="bi bi-cart-check"></i>
                        <span>Completed Orders</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'profile.php' ? 'active' : '' ?>" href="<?= $app_url ?>/farmer/profile.php">
                        <i class="bi bi-person-circle"></i>
                        <span>Profile</span>
                    </a>
                </li>

            <?php elseif ($user_role === 'business'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>" href="<?= $app_url ?>/business/dashboard.php">
                        <i class="bi bi-grid-1x2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= in_array($current_page, ['browse.php', 'place_order.php'], true) ? 'active' : '' ?>" href="<?= $app_url ?>/business/browse.php">
                        <i class="bi bi-shop"></i>
                        <span>Produce Market</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'matches.php' ? 'active' : '' ?>" href="<?= $app_url ?>/business/matches.php">
                        <i class="bi bi-cpu text-warning"></i>
                        <span>AI Broker Deals</span>
                        <span class="badge bg-primary ms-auto extra-small">AI</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= in_array($current_page, ['orders.php', 'tracking.php'], true) ? 'active' : '' ?>" href="<?= $app_url ?>/business/orders.php">
                        <i class="bi bi-bag-check"></i>
                        <span>Procurement Orders</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'profile.php' ? 'active' : '' ?>" href="<?= $app_url ?>/business/profile.php">
                        <i class="bi bi-building"></i>
                        <span>Company Profile</span>
                    </a>
                </li>

            <?php elseif ($user_role === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>" href="<?= $app_url ?>/admin/dashboard.php">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'users.php' ? 'active' : '' ?>" href="<?= $app_url ?>/admin/users.php">
                        <i class="bi bi-people"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'orders.php' ? 'active' : '' ?>" href="<?= $app_url ?>/admin/orders.php">
                        <i class="bi bi-receipt"></i>
                        <span>Orders</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'agent_logs.php' ? 'active' : '' ?>" href="<?= $app_url ?>/admin/agent_logs.php">
                        <i class="bi bi-robot text-warning"></i>
                        <span>AI Agent Monitor</span>
                        <span class="badge bg-warning text-dark ms-auto extra-small">Live</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'audit_logs.php' ? 'active' : '' ?>" href="<?= $app_url ?>/admin/audit_logs.php">
                        <i class="bi bi-journal-text text-info"></i>
                        <span>Audit Logs</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'sdg_impact.php' ? 'active' : '' ?>" href="<?= $app_url ?>/admin/sdg_impact.php">
                        <i class="bi bi-globe-americas text-success"></i>
                        <span>SDG Impact</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'settings.php' ? 'active' : '' ?>" href="<?= $app_url ?>/admin/settings.php">
                        <i class="bi bi-gear"></i>
                        <span>Settings</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <hr class="mx-3 my-3 text-white-50 opacity-25">

        <!-- Quick Branding Badge -->
        <div class="px-3 pb-3">
            <div class="p-3 bg-black bg-opacity-25 rounded-3 text-center border border-white-50 border-opacity-10">
                <div class="fw-bold text-white extra-small mb-1"><i class="bi bi-flower1 text-accent me-1"></i>AgriSync Sri Lanka</div>
                <div class="text-white-50 extra-small">Smart B2B Agritech</div>
            </div>
        </div>
    </div>
</nav>

<?php
/**
 * AgriSync — Public Landing Page (TASK-095)
 * Premium front door showcasing the AI agricultural supply chain matchmaking ecosystem.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'AI-Powered Agricultural Supply Chain Platform';
$app_url = defined('APP_URL') ? APP_URL : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?> | <?= APP_NAME ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom AgriSync Styles -->
    <link rel="stylesheet" href="<?= $app_url ?>/assets/css/style.css">
</head>
<body class="bg-light">

    <!-- Global Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm sticky-top py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center fw-bold text-primary fs-3" href="<?= $app_url ?>/">
                <i class="bi bi-tree-fill text-success me-2 fs-2"></i> <?= APP_NAME ?>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4 gap-2">
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="#features">AI Features</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="#sdg-impact">SDG Impact</a></li>
                </ul>

                <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                    <?php if (isLoggedIn()): ?>
                        <?php 
                            $user_role = getUserRole();
                            $dash_url = ($user_role === 'farmer') ? '/farmer/dashboard.php' : (($user_role === 'business') ? '/business/dashboard.php' : '/admin/dashboard.php'); 
                        ?>
                        <a href="<?= $app_url . $dash_url ?>" class="btn btn-primary rounded-3 px-3">
                            <i class="bi bi-speedometer2 me-1"></i> My Dashboard
                        </a>
                    <?php else: ?>
                        <a href="<?= $app_url ?>/auth/login.php" class="btn btn-outline-secondary rounded-3 px-3">Sign In</a>
                        <a href="<?= $app_url ?>/auth/register.php" class="btn btn-primary rounded-3 px-3 shadow-sm">Get Started</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="py-5 bg-white border-bottom position-relative overflow-hidden">
        <div class="container py-4">
            <div class="row align-items-center g-5">
                <div class="col-12 col-lg-7">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-semibold mb-3">
                        <i class="bi bi-stars me-1"></i> Autonomous AI Broker & Demand Forecasting
                    </span>
                    <h1 class="display-4 fw-extrabold text-dark tracking-tight mb-3">
                        Direct Agricultural Trade for <span class="text-success">Sri Lankan Farmers</span>.
                    </h1>
                    <p class="lead text-muted mb-4">
                        AgriSync connects smallholder farmers directly with commercial buyers, eliminating predatory middlemen through intelligent AI matchmaking, fair pricing algorithms, and predictive demand insights.
                    </p>
                    <div class="d-flex flex-wrap gap-3 mb-4">
                        <a href="<?= $app_url ?>/auth/register.php?role=farmer" class="btn btn-primary btn-lg rounded-3 px-4 shadow-sm">
                            <i class="bi bi-flower1 me-2"></i> Join as Farmer
                        </a>
                        <a href="<?= $app_url ?>/auth/register.php?role=business" class="btn btn-outline-success btn-lg rounded-3 px-4">
                            <i class="bi bi-building me-2"></i> Join as Commercial Buyer
                        </a>
                    </div>
                    <div class="d-flex align-items-center gap-4 text-muted small">
                        <div class="d-flex align-items-center"><i class="bi bi-shield-check text-success fs-5 me-1"></i> 100% Fair Trade</div>
                        <div class="d-flex align-items-center"><i class="bi bi-cpu text-success fs-5 me-1"></i> Explainable AI</div>
                        <div class="d-flex align-items-center"><i class="bi bi-geo-alt text-success fs-5 me-1"></i> 12 Agro Districts</div>
                    </div>
                </div>

                <div class="col-12 col-lg-5">
                    <div class="card border-0 shadow-lg rounded-4 p-4 bg-light border">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-success text-white rounded-pill px-2 py-1 me-2">Live AI</span>
                                <strong class="text-dark small">AI Matchmaking Telemetry</strong>
                            </div>
                            <i class="bi bi-activity text-success fs-5"></i>
                        </div>
                        <div class="p-3 bg-white rounded-3 shadow-sm mb-3">
                            <div class="d-flex justify-content-between text-muted small mb-1">
                                <span>Commercial Order: #ORD-104</span>
                                <span class="text-success fw-bold">Matched in 42ms</span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">FreshMart Supermarkets ➔ Sunil Bandara</h6>
                            <p class="text-muted small mb-0">500kg Tomato @ Rs. 160.00/kg (Dambulla ➔ Kandy)</p>
                        </div>
                        <div class="p-3 bg-white rounded-3 shadow-sm">
                            <div class="d-flex justify-content-between text-muted small mb-1">
                                <span>Demand Forecast: Carrot</span>
                                <span class="badge bg-success-subtle text-success">High Demand (92%)</span>
                            </div>
                            <p class="text-muted small mb-0">Nuwara Eliya wholesale price projected at Rs. 240 - 310/kg</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Core Features Grid -->
    <section id="features" class="py-5">
        <div class="container py-4">
            <div class="text-center max-w-2xl mx-auto mb-5">
                <span class="text-success fw-bold text-uppercase small tracking-wide">Intelligent Platform</span>
                <h2 class="h1 fw-bold text-dark mt-1">Transforming Agriculture With AI</h2>
                <p class="text-muted">Built from the ground up to empower producers and streamline wholesale procurement.</p>
            </div>

            <div class="row g-4">
                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                        <div class="p-3 rounded-3 bg-success-subtle text-success d-inline-block mb-3" style="width: 54px; height: 54px;">
                            <i class="bi bi-robot fs-3"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">AI Broker Matchmaker</h4>
                        <p class="text-muted small mb-0">
                            Our autonomous Gemini-driven broker evaluates harvest listings, delivery logistics, and price bounds to match buyers with farmers instantly.
                        </p>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                        <div class="p-3 rounded-3 bg-primary-subtle text-primary d-inline-block mb-3" style="width: 54px; height: 54px;">
                            <i class="bi bi-graph-up-arrow fs-3"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">Demand Forecasting</h4>
                        <p class="text-muted small mb-0">
                            Predicts seasonal demand surges across Maha and Yala seasons, helping farmers avoid harvest gluts and plan optimal crop cycles.
                        </p>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                        <div class="p-3 rounded-3 bg-info-subtle text-info d-inline-block mb-3" style="width: 54px; height: 54px;">
                            <i class="bi bi-shield-check fs-3"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">Explainable AI Audit</h4>
                        <p class="text-muted small mb-0">
                            Every single matchmaking decision and price recommendation is recorded with human-readable rationale for total trust and transparency.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- UN SDG Impact Section -->
    <section id="sdg-impact" class="py-5 bg-white border-top border-bottom">
        <div class="container py-4">
            <div class="text-center max-w-2xl mx-auto mb-5">
                <span class="text-success fw-bold text-uppercase small tracking-wide">Global Goals Alignment</span>
                <h2 class="h1 fw-bold text-dark mt-1">Driving United Nations SDGs</h2>
                <p class="text-muted">Targeting real-world economic resilience and zero food waste in Sri Lanka.</p>
            </div>

            <div class="row g-4 justify-content-center text-center">
                <div class="col-12 col-sm-6 col-md-4">
                    <div class="p-4 rounded-4 bg-light border h-100">
                        <h5 class="fw-bold text-danger mb-2">SDG 2: Zero Hunger</h5>
                        <p class="text-muted small mb-0">Eliminates harvest spoilage through advance buyer matching before produce leaves the farm.</p>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4">
                    <div class="p-4 rounded-4 bg-light border h-100">
                        <h5 class="fw-bold text-primary mb-2">SDG 8: Decent Work</h5>
                        <p class="text-muted small mb-0">Protects rural farmer incomes with fair-trade guaranteed margins and direct buyer access.</p>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4">
                    <div class="p-4 rounded-4 bg-light border h-100">
                        <h5 class="fw-bold text-warning mb-2">SDG 12: Responsible Trade</h5>
                        <p class="text-muted small mb-0">Shortens transportation food miles and establishes sustainable agro supply chains.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4 bg-light text-center border-top">
        <div class="container">
            <p class="text-muted small mb-0">
                &copy; <?= date('Y') ?> <strong><?= APP_NAME ?></strong> — Developed for AIESEC AgriTech Initiative. All rights reserved.
            </p>
        </div>
    </footer>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

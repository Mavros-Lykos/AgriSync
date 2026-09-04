<?php
/**
 * AgriSync — PayHere Escrow Checkout (TASK-PAYHERE)
 * Commercial buyer checkout page for locking AI broker matches via PayHere gateway escrow.
 * Generates PayHere MD5 signature server-side; merchant_secret is NEVER exposed in client HTML.
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

// Mandatory Role Check
requireRole('business');

$page_title = 'PayHere Escrow Checkout';
$user_id = (int)($_SESSION['user_id'] ?? 0);
$match_id = isset($_GET['match_id']) ? (int)$_GET['match_id'] : 0;

$match = null;
$payment = null;
$error_msg = '';

if ($match_id <= 0) {
    header('Location: matches.php');
    exit;
}

try {
    $db = getDbConnection();

    // Fetch order match details owned by this business buyer
    $stmt = $db->prepare("
        SELECT 
            m.id as match_id, m.order_id, m.listing_id, m.farmer_id, m.business_id, m.matched_price, 
            m.confidence_score, m.agent_reasoning, m.status as match_status, m.created_at as matched_date,
            o.crop_type, o.quantity_kg, o.delivery_date,
            u.name as farmer_name, u.district as farmer_district, u.phone as farmer_phone,
            b.name as buyer_name, b.email as buyer_email, b.phone as buyer_phone, b.district as buyer_district
        FROM order_matches m
        JOIN order_requests o ON m.order_id = o.id
        JOIN users u ON m.farmer_id = u.id
        JOIN users b ON m.business_id = b.id
        WHERE m.id = :match_id AND m.business_id = :business_id
    ");
    $stmt->execute([
        ':match_id' => $match_id,
        ':business_id' => $user_id
    ]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match) {
        $error_msg = 'The requested deal match was not found or access is restricted.';
    } else {
        // Fetch existing payment status if any
        $stmt_pay = $db->prepare("SELECT id, payhere_payment_id, amount, status FROM payments WHERE order_match_id = :match_id");
        $stmt_pay->execute([':match_id' => $match_id]);
        $payment = $stmt_pay->fetch(PDO::FETCH_ASSOC);

        // If no payment entry exists, create a pending payment record
        if (!$payment) {
            $total_amt = (float)$match['quantity_kg'] * (float)$match['matched_price'];
            $stmt_ins = $db->prepare("
                INSERT INTO payments (order_match_id, amount, status, created_at, updated_at)
                VALUES (:match_id, :amount, 'pending', NOW(), NOW())
            ");
            $stmt_ins->execute([
                ':match_id' => $match_id,
                ':amount' => $total_amt
            ]);
            
            $stmt_pay->execute([':match_id' => $match_id]);
            $payment = $stmt_pay->fetch(PDO::FETCH_ASSOC);
        }
    }
} catch (Throwable $e) {
    error_log("Checkout Fetch Error: " . $e->getMessage());
    $error_msg = "An error occurred while loading checkout information.";
}

// Calculate PayHere Form Fields & Signature
$merchant_id = defined('PAYHERE_MERCHANT_ID') ? PAYHERE_MERCHANT_ID : '1220000';
$merchant_secret = defined('PAYHERE_MERCHANT_SECRET') ? PAYHERE_MERCHANT_SECRET : '4Mx8365287415493218526541598452';
$currency = defined('PAYHERE_CURRENCY') ? PAYHERE_CURRENCY : 'LKR';
$mode = defined('PAYHERE_MODE') ? PAYHERE_MODE : 'sandbox';

$order_id = (string)$match_id;
$total_amount = $match ? ((float)$match['quantity_kg'] * (float)$match['matched_price']) : 0.00;
$amount_formatted = number_format($total_amount, 2, '.', '');

// PayHere Server MD5 Signature: strtoupper(md5($merchant_id . $order_id . $amount_formatted . $currency . strtoupper(md5($merchant_secret))))
$hash = strtoupper(
    md5(
        $merchant_id . 
        $order_id . 
        $amount_formatted . 
        $currency . 
        strtoupper(md5($merchant_secret))
    )
);

// PayHere Form Action Endpoint
$payhere_url = ($mode === 'sandbox') 
    ? 'https://sandbox.payhere.lk/pay/checkout' 
    : 'https://www.payhere.lk/pay/checkout';

// Return & Callback URLs
$return_url = rtrim(APP_URL, '/') . '/business/matches.php?status=success&match_id=' . $match_id;
$cancel_url = rtrim(APP_URL, '/') . '/business/matches.php?status=cancel&match_id=' . $match_id;
$notify_url = rtrim(APP_URL, '/') . '/api/payhere_notify.php';

// Prepare Buyer Name parts
$buyer_name = $match['buyer_name'] ?? 'Commercial Buyer';
$name_parts = explode(' ', trim($buyer_name), 2);
$first_name = $name_parts[0] ?? 'Valued';
$last_name = $name_parts[1] ?? 'Buyer';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex" style="min-height: 100vh;">
    <!-- Sidebar Navigation -->
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex-grow-1 bg-light p-4 overflow-auto">
        <div class="container-fluid max-w-5xl">
            
            <!-- Breadcrumb / Back Button -->
            <div class="mb-3">
                <a href="matches.php" class="text-decoration-none text-muted small">
                    <i class="bi bi-arrow-left me-1"></i> Back to AI Deals & Matches
                </a>
            </div>

            <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">
                        <i class="bi bi-shield-check text-success me-2"></i>PayHere Escrow Checkout
                    </h1>
                    <p class="text-muted small mb-0">
                        Secure commercial agricultural trade financing with Sri Lanka's leading payment gateway.
                    </p>
                </div>
                <span class="badge bg-success-subtle text-success border border-success px-3 py-2 rounded-pill">
                    <i class="bi bg-dot text-success"></i> PayHere Sandbox Mode
                </span>
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger rounded-3 p-4">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php else: ?>

                <div class="row g-4">
                    <!-- Deal Summary Card -->
                    <div class="col-12 col-lg-7">
                        <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-3">
                                <div>
                                    <span class="badge bg-secondary-subtle text-secondary border mb-1">
                                        Match #<?= (int)$match['match_id'] ?> &bull; Request #<?= (int)$match['order_id'] ?>
                                    </span>
                                    <h3 class="fw-bold text-dark mb-0"><?= htmlspecialchars($match['crop_type'], ENT_QUOTES, 'UTF-8') ?> Produce Pre-Order</h3>
                                </div>
                                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">
                                    AI Score: <?= (int)$match['confidence_score'] ?>%
                                </span>
                            </div>

                            <div class="table-responsive mb-4">
                                <table class="table table-borderless mb-0 align-middle">
                                    <tbody>
                                        <tr class="border-bottom">
                                            <td class="text-muted py-2">Farmer / Producer:</td>
                                            <td class="fw-bold text-end py-2">
                                                <i class="bi bi-person-fill text-success me-1"></i>
                                                <?= htmlspecialchars($match['farmer_name'], ENT_QUOTES, 'UTF-8') ?>
                                                <span class="badge bg-light text-dark border ms-1"><?= htmlspecialchars($match['farmer_district'], ENT_QUOTES, 'UTF-8') ?></span>
                                            </td>
                                        </tr>
                                        <tr class="border-bottom">
                                            <td class="text-muted py-2">Order Quantity:</td>
                                            <td class="fw-bold text-end py-2"><?= number_format($match['quantity_kg'], 1) ?> kg</td>
                                        </tr>
                                        <tr class="border-bottom">
                                            <td class="text-muted py-2">Matched Unit Price:</td>
                                            <td class="fw-bold text-end py-2 text-success">Rs. <?= number_format($match['matched_price'], 2) ?> / kg</td>
                                        </tr>
                                        <tr class="border-bottom">
                                            <td class="text-muted py-2">Target Delivery Date:</td>
                                            <td class="fw-bold text-end py-2"><?= htmlspecialchars($match['delivery_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                        <tr class="bg-light rounded-3">
                                            <td class="fw-bold text-dark py-3 ps-3">Total Escrow Amount:</td>
                                            <td class="fw-bold text-end py-3 pe-3 text-success fs-4">Rs. <?= number_format($total_amount, 2) ?> LKR</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="p-3 bg-light rounded-3 border-start border-4 border-success mb-3">
                                <h6 class="fw-bold text-dark mb-1">
                                    <i class="bi bi-lock-fill text-success me-1"></i> How AgriSync Escrow Protects You
                                </h6>
                                <p class="text-muted small mb-0">
                                    Your payment is held safely in escrow until the produce is inspected and verified upon warehouse delivery. Funds are automatically disbursed to <strong><?= htmlspecialchars($match['farmer_name'], ENT_QUOTES, 'UTF-8') ?></strong> once quality checks pass.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Action Card -->
                    <div class="col-12 col-lg-5">
                        <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100 d-flex flex-column justify-content-between">
                            <div>
                                <h4 class="fw-bold text-dark mb-3">Payment Authorization</h4>

                                <?php if ($payment && $payment['status'] === 'paid'): ?>
                                    <div class="alert alert-success rounded-3 p-4 text-center">
                                        <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-2"></i>
                                        <h5 class="fw-bold text-dark">Payment Held in Escrow</h5>
                                        <p class="small text-muted mb-2">
                                            PayHere Transaction Reference:<br>
                                            <strong class="text-dark"><?= htmlspecialchars($payment['payhere_payment_id'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        </p>
                                        <span class="badge bg-success px-3 py-2 rounded-pill">Status: Paid & Escrow Secured</span>
                                    </div>
                                    <a href="matches.php" class="btn btn-outline-secondary w-100 rounded-3 py-2">
                                        Return to Matches
                                    </a>
                                <?php else: ?>
                                    <div class="p-3 bg-white border rounded-3 mb-4">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bi bi-credit-card-2-front-fill text-primary fs-3 me-3"></i>
                                            <div>
                                                <strong class="d-block text-dark">PayHere Gateway</strong>
                                                <small class="text-muted">Visa, Mastercard, eZ Cash, mCash, Internet Banking</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- PayHere Form -->
                                    <form method="POST" action="<?= htmlspecialchars($payhere_url, ENT_QUOTES, 'UTF-8') ?>" id="payhere_form">
                                        <input type="hidden" name="merchant_id" value="<?= htmlspecialchars($merchant_id, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="return_url" value="<?= htmlspecialchars($return_url, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="cancel_url" value="<?= htmlspecialchars($cancel_url, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="notify_url" value="<?= htmlspecialchars($notify_url, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_id, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="items" value="<?= htmlspecialchars($match['crop_type'] . ' Produce Match #' . $match_id, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="currency" value="<?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="amount" value="<?= htmlspecialchars($amount_formatted, ENT_QUOTES, 'UTF-8') ?>">
                                        
                                        <!-- Customer Details -->
                                        <input type="hidden" name="first_name" value="<?= htmlspecialchars($first_name, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="last_name" value="<?= htmlspecialchars($last_name, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="email" value="<?= htmlspecialchars($match['buyer_email'], ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="phone" value="<?= htmlspecialchars($match['buyer_phone'] ?: '0771234567', ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="address" value="<?= htmlspecialchars($match['buyer_district'] ?: 'Colombo', ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="city" value="<?= htmlspecialchars($match['buyer_district'] ?: 'Colombo', ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="country" value="Sri Lanka">
                                        
                                        <!-- MD5 Hash generated strictly on server-side -->
                                        <input type="hidden" name="hash" value="<?= htmlspecialchars($hash, ENT_QUOTES, 'UTF-8') ?>">

                                        <button type="submit" class="btn btn-success btn-lg w-100 rounded-3 shadow-sm py-3 fw-bold mb-3" style="min-height: 52px; background-color: #2D6A4F; border-color: #2D6A4F;">
                                            <i class="bi bi-shield-lock-fill me-2"></i> Pay Rs. <?= number_format($total_amount, 2) ?> via PayHere
                                        </button>
                                    </form>

                                    <div class="text-center">
                                        <small class="text-muted extra-small">
                                            <i class="bi bi-info-circle me-1"></i> Sandbox Test Cards available at <a href="https://support.payhere.lk/payhere-checkout/sandbox-testing" target="_blank" class="text-decoration-none">PayHere Docs</a>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mt-4 pt-3 border-top text-center text-muted small">
                                <i class="bi bi-shield-fill-check text-success me-1"></i> 256-bit SSL Encrypted & Verified
                            </div>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

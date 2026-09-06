<?php
/**
 * AgriSync — Test Buy API Endpoint (DEV ONLY)
 * Simulates a successful PayHere payment without going through the payment gateway.
 * Performs the EXACT same database operations as a real PayHere IPN webhook.
 *
 * ⚠️  THIS ENDPOINT BYPASSES REAL PAYMENT — NEVER USE IN PRODUCTION.
 * It is protected by a session-based role check AND a local-environment guard.
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// ── Security Guard 1: Only business buyers or admins can use this ─────────────
checkRole(['business', 'admin']);

// ── Security Guard 2: Only allow on localhost / local dev ──────────────────────
$is_local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
$is_dev   = (defined('APP_ENV') && APP_ENV === 'development') || $is_local;

if (!$is_dev) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Test Buy is only available in local development environments.']);
    exit;
}

// ── CSRF Validation ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'POST method required.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$csrf  = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!validateCSRFToken($csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF token validation failed.']);
    exit;
}

// ── Parse Input ────────────────────────────────────────────────────────────────
$match_id = (int)($input['match_id'] ?? 0);
$user_id  = (int)$_SESSION['user_id'];

if ($match_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Valid match_id is required.']);
    exit;
}

try {
    $db = getDbConnection();

    // ── Fetch match with ownership verification ────────────────────────────────
    $stmt = $db->prepare("
        SELECT m.id, m.order_id, m.listing_id, m.farmer_id, m.business_id,
               m.matched_price, m.status,
               o.crop_type, o.quantity_kg
        FROM order_matches m
        JOIN order_requests o ON m.order_id = o.id
        WHERE m.id = :match_id AND m.business_id = :business_id
    ");
    $stmt->execute([':match_id' => $match_id, ':business_id' => $user_id]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Match not found or access denied.']);
        exit;
    }

    // ── Guard: Only allow if match is in a payable state ──────────────────────
    $payable_statuses = ['contract_signed', 'accepted', 'proposed'];
    if (!in_array($match['status'], $payable_statuses, true)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error'   => "Cannot process test payment for a match with status '{$match['status']}'. Match must be in contract_signed, accepted, or proposed state."
        ]);
        exit;
    }

    $total_amount     = (float)$match['quantity_kg'] * (float)$match['matched_price'];
    $test_payment_id  = 'TEST_' . strtoupper(bin2hex(random_bytes(8)));
    $formatted_amount = number_format($total_amount, 2);

    $db->beginTransaction();

    // ── Step 1: Upsert payments table (same as PayHere IPN status_code=2) ─────
    $stmt_check = $db->prepare("SELECT id FROM payments WHERE order_match_id = :mid");
    $stmt_check->execute([':mid' => $match_id]);

    if ($stmt_check->fetch()) {
        $db->prepare("
            UPDATE payments
            SET payhere_payment_id = :pid, amount = :amt, status = 'paid', updated_at = NOW()
            WHERE order_match_id = :mid
        ")->execute([':pid' => $test_payment_id, ':amt' => $total_amount, ':mid' => $match_id]);
    } else {
        $db->prepare("
            INSERT INTO payments (order_match_id, payhere_payment_id, amount, status, created_at, updated_at)
            VALUES (:mid, :pid, :amt, 'paid', NOW(), NOW())
        ")->execute([':mid' => $match_id, ':pid' => $test_payment_id, ':amt' => $total_amount]);
    }

    // ── Step 2: Update order_matches → accepted ────────────────────────────────
    $db->prepare("UPDATE order_matches SET status = 'accepted', updated_at = NOW() WHERE id = :mid")
       ->execute([':mid' => $match_id]);

    // ── Step 3: Update harvest_listings → matched ──────────────────────────────
    $db->prepare("UPDATE harvest_listings SET status = 'matched', updated_at = NOW() WHERE id = :lid")
       ->execute([':lid' => $match['listing_id']]);

    // ── Step 4: Update order_requests → matched ────────────────────────────────
    $db->prepare("UPDATE order_requests SET status = 'matched', updated_at = NOW() WHERE id = :oid")
       ->execute([':oid' => $match['order_id']]);

    // ── Step 5: Notify Farmer ──────────────────────────────────────────────────
    $db->prepare("
        INSERT INTO notifications (user_id, message, link, is_read, created_at, updated_at)
        VALUES (:uid, :msg, '/farmer/orders.php', 0, NOW(), NOW())
    ")->execute([
        ':uid' => $match['farmer_id'],
        ':msg' => "[TEST] Escrow Payment Received: Rs. {$formatted_amount} secured for {$match['crop_type']} order #{$match['order_id']}. Delivery authorized."
    ]);

    // ── Step 6: Notify Buyer ───────────────────────────────────────────────────
    $db->prepare("
        INSERT INTO notifications (user_id, message, link, is_read, created_at, updated_at)
        VALUES (:uid, :msg, '/business/matches.php', 0, NOW(), NOW())
    ")->execute([
        ':uid' => $match['business_id'],
        ':msg' => "[TEST] Payment Simulated: Rs. {$formatted_amount} held in escrow for order match #{$match_id}. Farmer notified for dispatch."
    ]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'data'    => [
            'match_id'         => $match_id,
            'test_payment_id'  => $test_payment_id,
            'amount'           => $total_amount,
            'new_match_status' => 'accepted',
            'message'          => "Test purchase successful! Rs. {$formatted_amount} simulated. All tables updated identically to a real PayHere payment."
        ],
        'error' => null
    ]);

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Test Buy Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error during test purchase: ' . $e->getMessage()]);
}

<?php
/**
 * AgriSync — PayHere IPN Webhook Endpoint (TASK-PAYHERE)
 * Handles Instant Payment Notifications (IPN) from PayHere payment gateway.
 * Strictly validates MD5 signature hash before updating escrow payment and match status.
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(455);
    echo json_encode([
        'success' => false,
        'error' => 'Only POST requests are supported.',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Extract PayHere POST parameters
$merchant_id      = trim($_POST['merchant_id'] ?? '');
$order_id         = trim($_POST['order_id'] ?? '');
$payhere_amount   = trim($_POST['payhere_amount'] ?? '');
$payhere_currency = trim($_POST['payhere_currency'] ?? '');
$status_code      = trim($_POST['status_code'] ?? '');
$md5sig           = trim($_POST['md5sig'] ?? '');
$payhere_payment_id = trim($_POST['payment_id'] ?? $_POST['payhere_payment_id'] ?? '');

$merchant_secret  = defined('PAYHERE_MERCHANT_SECRET') ? PAYHERE_MERCHANT_SECRET : '4Mx8365287415493218526541598452';

// Server-side MD5 signature verification
$local_md5sig = strtoupper(
    md5(
        $merchant_id . 
        $order_id . 
        $payhere_amount . 
        $payhere_currency . 
        $status_code . 
        strtoupper(md5($merchant_secret))
    )
);

// Strict Signature Validation
if (empty($md5sig) || strtoupper($md5sig) !== $local_md5sig) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid PayHere signature hash verification failed.',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Parse Order Match ID from order_id (supports raw integer or MATCH_XX format)
$order_match_id = (int) preg_replace('/[^0-9]/', '', $order_id);

if ($order_match_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid order match reference.',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = getDbConnection();

    // Verify order match existence
    $stmt_match = $db->prepare("
        SELECT m.id, m.order_id, m.listing_id, m.farmer_id, m.business_id, m.matched_price, m.status,
               o.crop_type, o.quantity_kg
        FROM order_matches m
        JOIN order_requests o ON m.order_id = o.id
        WHERE m.id = :match_id
    ");
    $stmt_match->execute([':match_id' => $order_match_id]);
    $match = $stmt_match->fetch(PDO::FETCH_ASSOC);

    if (!$match) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Order match record not found.',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($match['status'] !== 'contract_signed') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Payment cannot be processed. Match contract has not been signed via OTP yet.',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $db->beginTransaction();

    $payment_status = 'failed';
    $match_updated_status = $match['status'];

    if ((int)$status_code === 2) {
        // Code 2 = Payment Success / Paid into Escrow
        $payment_status = 'paid';
        $match_updated_status = 'accepted';

        // 1. Insert or update payments table
        $stmt_check_pay = $db->prepare("SELECT id FROM payments WHERE order_match_id = :match_id");
        $stmt_check_pay->execute([':match_id' => $order_match_id]);
        $existing_pay = $stmt_check_pay->fetch(PDO::FETCH_ASSOC);

        if ($existing_pay) {
            $stmt_upd_pay = $db->prepare("
                UPDATE payments 
                SET payhere_payment_id = :payment_id,
                    amount = :amount,
                    status = 'paid',
                    updated_at = NOW()
                WHERE order_match_id = :match_id
            ");
            $stmt_upd_pay->execute([
                ':payment_id' => $payhere_payment_id ?: 'PAYHERE_' . time(),
                ':amount' => (float)$payhere_amount,
                ':match_id' => $order_match_id
            ]);
        } else {
            $stmt_ins_pay = $db->prepare("
                INSERT INTO payments (order_match_id, payhere_payment_id, amount, status, created_at, updated_at)
                VALUES (:match_id, :payment_id, :amount, 'paid', NOW(), NOW())
            ");
            $stmt_ins_pay->execute([
                ':match_id' => $order_match_id,
                ':payment_id' => $payhere_payment_id ?: 'PAYHERE_' . time(),
                ':amount' => (float)$payhere_amount
            ]);
        }

        // 2. Update order_matches status to accepted
        $stmt_upd_match = $db->prepare("UPDATE order_matches SET status = 'accepted', updated_at = NOW() WHERE id = :match_id");
        $stmt_upd_match->execute([':match_id' => $order_match_id]);

        // 3. Update harvest listing status to matched
        $stmt_upd_listing = $db->prepare("UPDATE harvest_listings SET status = 'matched', updated_at = NOW() WHERE id = :listing_id");
        $stmt_upd_listing->execute([':listing_id' => $match['listing_id']]);

        // 4. Send notifications
        $formatted_amt = number_format((float)$payhere_amount, 2);
        
        // Farmer Notification
        $stmt_notif_farmer = $db->prepare("
            INSERT INTO notifications (user_id, message, link, is_read, created_at, updated_at)
            VALUES (:farmer_id, :msg, '/farmer/offers.php', 0, NOW(), NOW())
        ");
        $stmt_notif_farmer->execute([
            ':farmer_id' => $match['farmer_id'],
            ':msg' => "Escrow Payment Received: Rs. {$formatted_amt} secured for {$match['crop_type']} order match #{$order_match_id}. Delivery authorization granted."
        ]);

        // Commercial Buyer Notification
        $stmt_notif_buyer = $db->prepare("
            INSERT INTO notifications (user_id, message, link, is_read, created_at, updated_at)
            VALUES (:business_id, :msg, '/business/matches.php', 0, NOW(), NOW())
        ");
        $stmt_notif_buyer->execute([
            ':business_id' => $match['business_id'],
            ':msg' => "Payment Success: Rs. {$formatted_amt} held in escrow for order match #{$order_match_id}. Farmer notified for dispatch."
        ]);

    } else {
        // Payment failed or canceled
        $stmt_check_pay = $db->prepare("SELECT id FROM payments WHERE order_match_id = :match_id");
        $stmt_check_pay->execute([':match_id' => $order_match_id]);
        if ($stmt_check_pay->fetch()) {
            $stmt_fail = $db->prepare("UPDATE payments SET status = 'failed', updated_at = NOW() WHERE order_match_id = :match_id");
            $stmt_fail->execute([':match_id' => $order_match_id]);
        }
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'error' => null,
        'data' => [
            'order_match_id' => $order_match_id,
            'payment_status' => $payment_status,
            'match_status' => $match_updated_status,
            'payhere_payment_id' => $payhere_payment_id
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("PayHere IPN Webhook Error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An internal database error occurred while processing payment webhook.',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
}

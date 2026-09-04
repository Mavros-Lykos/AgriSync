<?php
/**
 * AgriSync — Digital Purchase Order OTP Generation & Verification API (TASK-OTP-CONTRACT)
 * Handles sending and verifying 6-digit OTP codes for legally binding match acceptance.
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../auth/auth_check.php';

header('Content-Type: application/json; charset=utf-8');

// Strict Auth & Role check: Farmers and Commercial Buyers only
checkRole(['farmer', 'business']);

$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'data' => [], 'error' => 'Method not allowed. Use POST.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validate CSRF token
$csrf_token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCSRFToken($csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'data' => [], 'error' => 'CSRF security token validation failed.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = sanitize($_POST['action'] ?? '');
$match_id = (int)($_POST['match_id'] ?? 0);

if ($match_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'data' => [], 'error' => 'Invalid or missing match ID parameter.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = getDbConnection();

// ---------------------------------------------------------
// Action 1: Send OTP
// ---------------------------------------------------------
if ($action === 'send_otp') {
    // Rate-limiting OTP generation (maximum 1 request per 15 seconds)
    $last_otp_key = 'last_otp_time_' . $match_id . '_' . $user_id;
    if (isset($_SESSION[$last_otp_key]) && (time() - $_SESSION[$last_otp_key]) < 15) {
        $remaining = 15 - (time() - $_SESSION[$last_otp_key]);
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'data' => [],
            'error' => "Please wait {$remaining} seconds before requesting another OTP."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        // Verify user is a party to this match
        $stmt = $db->prepare("
            SELECT id, farmer_id, business_id, status 
            FROM order_matches 
            WHERE id = :match_id AND (farmer_id = :user_id OR business_id = :user_id)
        ");
        $stmt->execute([':match_id' => $match_id, ':user_id' => $user_id]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$match) {
            http_response_code(403);
            echo json_encode(['success' => false, 'data' => [], 'error' => 'Match deal record not found or access denied.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Generate 6-digit random OTP code
        $otp_code = (string)random_int(100000, 999999);

        // Update database with OTP code
        $stmt_upd = $db->prepare("UPDATE order_matches SET otp_code = :otp, updated_at = NOW() WHERE id = :match_id");
        $stmt_upd->execute([':otp' => $otp_code, ':match_id' => $match_id]);

        // Record rate limit timestamp
        $_SESSION[$last_otp_key] = time();

        // Log Mock SMS as required by specification
        error_log("MOCK SMS to User #{$user_id}: Your AgriSync Digital Purchase Order OTP is {$otp_code}");

        echo json_encode([
            'success' => true,
            'data' => [
                'otp_sent' => true,
                'message' => 'A 6-digit OTP confirmation code has been dispatched to your mobile phone.',
                'mock_otp' => (defined('APP_ENV') && APP_ENV === 'development') ? $otp_code : null
            ],
            'error' => null
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        error_log("Send OTP Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'data' => [], 'error' => 'An internal database error occurred while generating OTP.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ---------------------------------------------------------
// Action 2: Verify OTP & Sign Digital Purchase Order
// ---------------------------------------------------------
if ($action === 'verify_otp') {
    $otp_input = trim((string)($_POST['otp_code'] ?? ''));
    $contract_agreed = isset($_POST['contract_agreed']) && (
        $_POST['contract_agreed'] === '1' || 
        $_POST['contract_agreed'] === 'true' || 
        $_POST['contract_agreed'] === true || 
        $_POST['contract_agreed'] === 1
    );

    if (!$contract_agreed) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'data' => [],
            'error' => 'You must check the agreement box to accept the legally binding terms.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!preg_match('/^[0-9]{6}$/', $otp_input)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'data' => [],
            'error' => 'OTP code must be exactly a 6-digit numeric sequence.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        // Fetch match record
        $stmt = $db->prepare("
            SELECT m.id, m.order_id, m.listing_id, m.farmer_id, m.business_id, m.matched_price, m.otp_code, m.status,
                   o.crop_type, o.quantity_kg
            FROM order_matches m
            JOIN order_requests o ON m.order_id = o.id
            WHERE m.id = :match_id AND (m.farmer_id = :user_id OR m.business_id = :user_id)
        ");
        $stmt->execute([':match_id' => $match_id, ':user_id' => $user_id]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$match) {
            http_response_code(403);
            echo json_encode(['success' => false, 'data' => [], 'error' => 'Order match record not found or access denied.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Strict OTP verification check
        if (empty($match['otp_code']) || $match['otp_code'] !== $otp_input) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'data' => [],
                'error' => 'Invalid OTP code entered. Please check your SMS notification and try again.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $db->beginTransaction();

        // 1. Update order_matches status to contract_signed, contract_agreed = 1, otp_verified = 1, clear OTP code
        $stmt_upd_match = $db->prepare("
            UPDATE order_matches 
            SET contract_agreed = 1,
                otp_verified = 1,
                otp_code = NULL,
                status = 'contract_signed',
                updated_at = NOW()
            WHERE id = :match_id
        ");
        $stmt_upd_match->execute([':match_id' => $match_id]);

        // 2. Update harvest listing status to matched
        $stmt_upd_listing = $db->prepare("UPDATE harvest_listings SET status = 'matched', updated_at = NOW() WHERE id = :listing_id");
        $stmt_upd_listing->execute([':listing_id' => $match['listing_id']]);

        // 3. Update order request status to matched
        $stmt_upd_order = $db->prepare("UPDATE order_requests SET status = 'matched', updated_at = NOW() WHERE id = :order_id");
        $stmt_upd_order->execute([':order_id' => $match['order_id']]);

        // 4. Send notification to counterparty
        $counterparty_id = ($user_id === (int)$match['farmer_id']) ? $match['business_id'] : $match['farmer_id'];
        $role_label = ($user_id === (int)$match['farmer_id']) ? 'Farmer' : 'Commercial Buyer';
        
        $stmt_notif = $db->prepare("
            INSERT INTO notifications (user_id, message, link, is_read, created_at, updated_at)
            VALUES (:user_id, :msg, :link, 0, NOW(), NOW())
        ");
        $stmt_notif->execute([
            ':user_id' => $counterparty_id,
            ':msg' => "Contract Signed: {$role_label} verified OTP and signed Digital Purchase Order for {$match['crop_type']} Match #{$match_id}.",
            ':link' => ($user_id === (int)$match['farmer_id']) ? '/business/matches.php' : '/farmer/offers.php'
        ]);

        $db->commit();

        echo json_encode([
            'success' => true,
            'data' => [
                'match_id' => $match_id,
                'status' => 'contract_signed',
                'contract_agreed' => 1,
                'otp_verified' => 1,
                'message' => 'Digital Purchase Order signed and contract verified successfully!'
            ],
            'error' => null
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Verify OTP Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'data' => [], 'error' => 'An internal database error occurred while verifying OTP.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'data' => [], 'error' => 'Invalid API action requested.'], JSON_UNESCAPED_UNICODE);

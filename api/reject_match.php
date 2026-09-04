<?php
/**
 * AgriSync — Dedicated Reject Match API Endpoint (TASK-050)
 * Handles farmer or admin match rejection, resets listing/order availability, and sends notifications.
 * Response: JSON {"success": bool, "data": array, "error": string|null}
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../auth/auth_check.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(false, [], 'Authentication required.', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'HTTP POST method required.', 405);
}

$user_id = (int)$_SESSION['user_id'];
$user_role = getUserRole();

if (!in_array($user_role, ['farmer', 'admin'], true)) {
    jsonResponse(false, [], 'Access denied: Only farmers and administrators can decline match proposals.', 403);
}

$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;
if (!validateCSRFToken($csrf_token)) {
    jsonResponse(false, [], 'Invalid or missing CSRF token.', 403);
}

$match_id = (int)($_POST['match_id'] ?? 0);
$rejection_reason = trim($_POST['rejection_reason'] ?? 'Declined by farmer');

if ($match_id <= 0) {
    jsonResponse(false, [], 'Valid Match ID is required.', 400);
}

$db = getDbConnection();

try {
    $db->beginTransaction();

    // Fetch match record
    $stmt = $db->prepare("
        SELECT m.id, m.order_id, m.listing_id, m.farmer_id, m.business_id, m.matched_price, m.status,
               u.name as business_name, o.crop_type, o.quantity_kg
        FROM order_matches m
        JOIN users u ON m.business_id = u.id
        JOIN order_requests o ON m.order_id = o.id
        WHERE m.id = ? LIMIT 1
    ");
    $stmt->execute([$match_id]);
    $match = $stmt->fetch();

    if (!$match) {
        $db->rollBack();
        jsonResponse(false, [], 'Order match record not found.', 404);
    }

    // Role check: Admin or the assigned Farmer
    if ($user_role !== 'admin' && (int)$match['farmer_id'] !== $user_id) {
        $db->rollBack();
        jsonResponse(false, [], 'Unauthorized: You are not the farmer associated with this match.', 403);
    }

    // Update match status to 'rejected'
    $stmt_m = $db->prepare("UPDATE order_matches SET status = 'rejected', updated_at = NOW() WHERE id = ?");
    $stmt_m->execute([$match_id]);

    // Reset harvest listing back to 'available'
    $stmt_l = $db->prepare("UPDATE harvest_listings SET status = 'available', updated_at = NOW() WHERE id = ?");
    $stmt_l->execute([$match['listing_id']]);

    // Reset order request status back to 'pending'
    $stmt_o = $db->prepare("UPDATE order_requests SET status = 'pending', updated_at = NOW() WHERE id = ?");
    $stmt_o->execute([$match['order_id']]);

    // Send in-app notification to business buyer
    $notif_msg = "ℹ️ Match proposal #M-{$match_id} for {$match['crop_type']} was declined by the farmer. The order request has been returned to the AI matching pool.";
    $notif_stmt = $db->prepare("
        INSERT INTO notifications (user_id, message, link, is_read, created_at, updated_at)
        VALUES (?, ?, ?, 0, NOW(), NOW())
    ");
    $notif_stmt->execute([$match['business_id'], $notif_msg, '/business/requests.php']);

    $db->commit();

    jsonResponse(true, [
        'match_id' => $match_id,
        'order_id' => $match['order_id'],
        'status'   => 'rejected'
    ], 'Match proposal declined successfully.');

} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(false, [], 'Failed to decline order match.', 500);
}

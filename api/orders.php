<?php
// AgriSync Generic AJAX Order Status Update API (M3 Task)
// Returns JSON: {"success": bool, "data": array, "error": string|null}

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../auth/auth_check.php';

header('Content-Type: application/json; charset=utf-8');

// Mandatory Auth Check - User must be logged in
if (!isLoggedIn()) {
    jsonResponse(false, [], 'Authentication required to access order API.', 401);
}

$user_id = (int)$_SESSION['user_id'];
$user_role = getUserRole();
$db = getDbConnection();

$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '');

// Allowed status transition map (State Machine Validation)
$allowed_transitions = [
    'pending'    => ['matching', 'matched', 'cancelled'],
    'matching'   => ['matched', 'cancelled'],
    'matched'    => ['accepted', 'rejected', 'cancelled'],
    'accepted'   => ['in_transit', 'cancelled'],
    'in_transit' => ['delivered', 'cancelled'],
    'delivered'  => [], // Terminal state
    'cancelled'  => [], // Terminal state
    'rejected'   => [], // Terminal state
];

if ($action === 'update_status') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, [], 'HTTP POST method required.', 405);
    }

    // CSRF Token Validation
    $csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;
    if (!validateCSRFToken($csrf_token)) {
        jsonResponse(false, [], 'Invalid or missing CSRF token.', 403);
    }

    $order_id = (int)($_POST['order_id'] ?? 0);
    $new_status = strtolower(trim(sanitize($_POST['status'] ?? '')));
    $target_type = sanitize($_POST['type'] ?? 'match'); // 'match' or 'request'

    if ($order_id <= 0 || empty($new_status)) {
        jsonResponse(false, [], 'Order ID and target status are required.', 400);
    }

    try {
        if ($target_type === 'match') {
            // Check order_matches table
            $stmt = $db->prepare("
                SELECT m.id, m.order_id, m.listing_id, m.status, m.agreed_price, m.quantity_kg,
                       o.business_id, h.farmer_id
                FROM order_matches m
                JOIN order_requests o ON m.order_id = o.id
                JOIN harvest_listings h ON m.listing_id = h.id
                WHERE m.id = ? LIMIT 1
            ");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();

            if (!$order) {
                jsonResponse(false, [], 'Matched order record not found.', 404);
            }

            // Auth Check: Must be Admin OR involved Farmer OR involved Business Buyer
            $is_admin = ($user_role === 'admin');
            $is_farmer = ((int)$order['farmer_id'] === $user_id);
            $is_business = ((int)$order['business_id'] === $user_id);

            if (!$is_admin && !$is_farmer && !$is_business) {
                jsonResponse(false, [], 'Access denied: You are not authorized to modify status for this order.', 403);
            }

            $current_status = strtolower(trim($order['status']));

            // State Machine Transition Validation
            $valid_targets = $allowed_transitions[$current_status] ?? [];
            if (!in_array($new_status, $valid_targets, true) && !$is_admin) {
                jsonResponse(false, [], "Invalid status transition from '{$current_status}' to '{$new_status}'.", 400);
            }

            // Perform Database Update with updated_at timestamp
            $upd = $db->prepare("UPDATE order_matches SET status = ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$new_status, $order_id]);

            // Sync harvest listing / order request status if delivered or cancelled
            if ($new_status === 'delivered') {
                $db->prepare("UPDATE harvest_listings SET status = 'sold', updated_at = NOW() WHERE id = ?")->execute([$order['listing_id']]);
                $db->prepare("UPDATE order_requests SET status = 'fulfilled', updated_at = NOW() WHERE id = ?")->execute([$order['order_id']]);
            }

            // Send Notifications to involved parties
            $notif_msg = "Order Match #{$order_id} status updated to '" . ucfirst($new_status) . "'.";
            
            // Notify Business if update was initiated by Farmer/Admin
            if ($user_id !== (int)$order['business_id']) {
                $notif_stmt = $db->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
                $notif_stmt->execute([$order['business_id'], $notif_msg]);
            }
            // Notify Farmer if update was initiated by Business/Admin
            if ($user_id !== (int)$order['farmer_id']) {
                $notif_stmt = $db->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
                $notif_stmt->execute([$order['farmer_id'], $notif_msg]);
            }

            jsonResponse(true, [
                'order_id'       => $order_id,
                'old_status'     => $current_status,
                'new_status'     => $new_status,
                'updated_at'     => date('Y-m-d H:i:s')
            ], "Order match #{$order_id} status updated to '{$new_status}'.");

        } else {
            // Check order_requests table directly
            $stmt = $db->prepare("SELECT id, business_id, status FROM order_requests WHERE id = ? LIMIT 1");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();

            if (!$order) {
                jsonResponse(false, [], 'Order request not found.', 404);
            }

            $is_admin = ($user_role === 'admin');
            $is_owner = ((int)$order['business_id'] === $user_id);

            if (!$is_admin && !$is_owner) {
                jsonResponse(false, [], 'Access denied: Unauthorized to update status for this order request.', 403);
            }

            $current_status = strtolower(trim($order['status']));
            $valid_targets = $allowed_transitions[$current_status] ?? [];

            if (!in_array($new_status, $valid_targets, true) && !$is_admin) {
                jsonResponse(false, [], "Invalid status transition from '{$current_status}' to '{$new_status}'.", 400);
            }

            $upd = $db->prepare("UPDATE order_requests SET status = ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$new_status, $order_id]);

            // Create notification for request owner
            $notif_stmt = $db->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
            $notif_stmt->execute([$order['business_id'], "Your Order Request #{$order_id} status is now '" . ucfirst($new_status) . "'."]);

            jsonResponse(true, [
                'order_id'   => $order_id,
                'old_status' => $current_status,
                'new_status' => $new_status,
                'updated_at' => date('Y-m-d H:i:s')
            ], "Order request #{$order_id} status updated to '{$new_status}'.");
        }

    } catch (PDOException $e) {
        jsonResponse(false, [], 'Database error during order status update.', 500);
    }

} else {
    jsonResponse(false, [], 'Invalid order API action.', 400);
}

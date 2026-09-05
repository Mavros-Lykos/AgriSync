<?php
/**
 * AgriSync — Update Order Price API
 * Allows businesses to increase their max budget for a pending order and re-trigger AI matching.
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../auth/auth_check.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn() || getUserRole() !== 'business') {
    jsonResponse(false, [], 'Unauthorized access.', 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'HTTP POST method required.', 405);
}

$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;
if (!validateCSRFToken($csrf_token)) {
    jsonResponse(false, [], 'Invalid or missing CSRF token.', 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$order_id = (int)($input['order_id'] ?? 0);
$new_price = (float)($input['new_price'] ?? 0);

if ($order_id <= 0 || $new_price <= 0) {
    jsonResponse(false, [], 'Valid Order ID and New Price are required.', 400);
}

$user_id = (int)$_SESSION['user_id'];

try {
    $db = getDbConnection();

    // Verify order belongs to user and is pending
    $stmt = $db->prepare("SELECT id, status, max_price FROM order_requests WHERE id = :id AND business_id = :bid LIMIT 1");
    $stmt->execute([':id' => $order_id, ':bid' => $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        jsonResponse(false, [], 'Order not found or access denied.', 404);
    }

    if ($order['status'] !== 'pending') {
        jsonResponse(false, [], 'You can only update the price of pending orders.', 400);
    }

    if ($new_price <= (float)$order['max_price']) {
        jsonResponse(false, [], 'New price must be higher than your current budget to find new matches.', 400);
    }

    // Update the max price
    $upd = $db->prepare("UPDATE order_requests SET max_price = :price, updated_at = NOW() WHERE id = :id");
    $upd->execute([':price' => $new_price, ':id' => $order_id]);

    // Trigger async broker worker
    $app_url = defined('APP_URL') && !empty(APP_URL) ? APP_URL : 'http://localhost:8000';
    $internal_token = defined('APP_NAME') ? md5(APP_NAME) : md5('AgriSync');
    $async_url = rtrim($app_url, '/') . '/api/run_broker_async.php?order_id=' . $order_id . '&internal_token=' . $internal_token;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $async_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 400); // Non-blocking
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    @curl_exec($ch);
    @curl_close($ch);

    jsonResponse(true, [
        'order_id' => $order_id,
        'new_price' => $new_price
    ], "Order budget updated to Rs. " . number_format($new_price, 2) . ". AI matching is running now.");

} catch (PDOException $e) {
    error_log("Update Price Error: " . $e->getMessage());
    jsonResponse(false, [], 'Database error while updating price.', 500);
}

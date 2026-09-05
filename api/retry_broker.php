<?php
/**
 * AgriSync — Retry AI Broker API
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

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$order_id = (int)($input['order_id'] ?? 0);

if ($order_id <= 0) {
    jsonResponse(false, [], 'Valid Order ID is required.', 400);
}

try {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT id FROM order_requests WHERE id = :id AND business_id = :bid AND status = 'pending' LIMIT 1");
    $stmt->execute([':id' => $order_id, ':bid' => (int)$_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        jsonResponse(false, [], 'Pending order not found or access denied.', 404);
    }

    $app_url = defined('APP_URL') && !empty(APP_URL) ? APP_URL : 'http://localhost:8000';
    $internal_token = defined('APP_NAME') ? md5(APP_NAME) : md5('AgriSync');
    $async_url = rtrim($app_url, '/') . '/api/run_broker_async.php?order_id=' . $order_id . '&internal_token=' . $internal_token;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $async_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 400); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    @curl_exec($ch);
    @curl_close($ch);

    jsonResponse(true, ['order_id' => $order_id], "AI Broker matching retry initiated.");
} catch (PDOException $e) {
    jsonResponse(false, [], 'Database error.', 500);
}

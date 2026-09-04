<?php
/**
 * AgriSync — Match Order API Endpoint
 * POST /api/match_order.php
 * Triggers the AI Broker Agent for a specified order_id
 */

header('Content-Type: application/json; charset=UTF-8');
@set_time_limit(60);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../agents/broker_agent.php';

// Accept only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, null, 'Method Not Allowed. Only POST is accepted.', 405);
}

// Extract input from JSON body or POST form data
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);

$orderId = null;
if (is_array($inputData) && isset($inputData['order_id'])) {
    $orderId = (int) $inputData['order_id'];
} elseif (isset($_POST['order_id'])) {
    $orderId = (int) $_POST['order_id'];
}

if (!$orderId || $orderId <= 0) {
    jsonResponse(false, null, 'Invalid or missing order_id parameter.', 400);
}

// CSRF validation (if session active and CSRF token passed)
$headerCsrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
$bodyCsrf = $inputData['csrf_token'] ?? ($_POST['csrf_token'] ?? null);
$submittedCsrf = $headerCsrf ?? $bodyCsrf;

if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['csrf_token'])) {
    if (!validateCSRFToken($submittedCsrf)) {
        jsonResponse(false, null, 'CSRF security token verification failed.', 403);
    }
}

try {
    $broker = new BrokerAgent();
    $result = $broker->matchOrder($orderId);

    if (!$result['success']) {
        jsonResponse(false, null, $result['error'] ?? 'Matching failed.', 400);
    }

    jsonResponse(true, $result, null, 200);

} catch (Throwable $e) {
    jsonResponse(false, null, 'Server error executing AI Broker Agent: ' . $e->getMessage(), 500);
}

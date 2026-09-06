<?php
/**
 * AgriSync — Delete Harvest Listing API (TASK-036)
 * Secure endpoint to remove or cancel a farmer's produce listing.
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? '';

$input = json_decode(file_get_contents('php://input'), true);
$listing_id = (int) ($input['listing_id'] ?? ($_POST['listing_id'] ?? 0));
$csrf = $input['csrf_token'] ?? ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

if (!validateCSRFToken($csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF security token validation failed.']);
    exit;
}

if ($listing_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid listing ID provided.']);
    exit;
}

try {
    $db = getDbConnection();

    // Verify ownership or admin privileges
    if ($user_role === 'admin') {
        $stmt = $db->prepare("DELETE FROM harvest_listings WHERE id = :id");
        $stmt->execute([':id' => $listing_id]);
    } else {
        $stmt = $db->prepare("DELETE FROM harvest_listings WHERE id = :id AND farmer_id = :farmer_id");
        $stmt->execute([':id' => $listing_id, ':farmer_id' => $user_id]);
    }

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Harvest listing deleted successfully.'
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Listing not found or access denied.'
        ]);
    }

} catch (Throwable $e) {
    error_log("Delete Listing Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to delete harvest listing.'
    ]);
}

<?php
/**
 * AgriSync — Expire Stale Listings API Endpoint (TASK-150)
 * Automatically marks listings as 'expired' if their harvest_date is more than 7 days in the past.
 * Intended to be run via a cron job daily, or manually by admin.
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDbConnection();
    
    // Find all 'available' listings where harvest_date is older than 7 days from today
    $stmt = $db->prepare("
        UPDATE harvest_listings 
        SET status = 'expired', updated_at = NOW() 
        WHERE status = 'available' AND harvest_date < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    
    $stmt->execute();
    $expiredCount = $stmt->rowCount();

    echo json_encode([
        'success' => true,
        'message' => "Successfully expired {$expiredCount} stale harvest listings."
    ]);

} catch (Throwable $e) {
    error_log("Expire Listings API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error processing expiry: ' . $e->getMessage()
    ]);
}

<?php
/**
 * AgriSync — Get Market Price API
 * Fetches the current average market price and lowest available price for a specific crop.
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$crop = sanitize($_GET['crop'] ?? '');

if (empty($crop)) {
    jsonResponse(false, [], 'Crop type is required.', 400);
}

try {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT 
            MIN(price_per_kg) as lowest_price,
            AVG(price_per_kg) as avg_price,
            COUNT(*) as available_listings,
            SUM(quantity_kg - COALESCE(quantity_reserved, 0)) as total_available_kg
        FROM harvest_listings 
        WHERE status = 'available' 
          AND LOWER(crop_type) = LOWER(:crop)
          AND (quantity_kg - COALESCE(quantity_reserved, 0)) > 0
    ");
    
    $stmt->execute([':crop' => $crop]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$stats || $stats['available_listings'] == 0) {
        jsonResponse(true, [
            'has_data' => false,
            'message' => 'No active market data available for this crop right now.'
        ]);
    }

    jsonResponse(true, [
        'has_data' => true,
        'lowest_price' => round((float)$stats['lowest_price'], 2),
        'avg_price' => round((float)$stats['avg_price'], 2),
        'available_listings' => (int)$stats['available_listings'],
        'total_available_kg' => round((float)$stats['total_available_kg'], 2)
    ]);

} catch (PDOException $e) {
    jsonResponse(false, [], 'Database error.', 500);
}

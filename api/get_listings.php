<?php
/**
 * AgriSync — Get Listings API (TASK-040)
 * Returns filtered active produce listings with farmer and regional metadata.
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    $db = getDbConnection();

    $crop_type = sanitize($_GET['crop_type'] ?? '');
    $district = sanitize($_GET['district'] ?? '');
    $min_qty = isset($_GET['min_qty']) ? (float)$_GET['min_qty'] : 0;
    $max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
    $status = sanitize($_GET['status'] ?? 'available');

    $sql = "
        SELECT 
            h.id, h.crop_type, h.quantity_kg, h.price_per_kg, h.harvest_date, h.status, h.created_at,
            u.id as farmer_id, u.name as farmer_name, u.district as farmer_district, u.phone as farmer_phone
        FROM harvest_listings h
        JOIN users u ON h.farmer_id = u.id
        WHERE 1=1
    ";
    $params = [];

    if (!empty($status) && $status !== 'all') {
        $sql .= " AND h.status = :status";
        $params[':status'] = $status;
    }

    if (!empty($crop_type)) {
        $sql .= " AND h.crop_type = :crop_type";
        $params[':crop_type'] = $crop_type;
    }

    if (!empty($district)) {
        $sql .= " AND u.district = :district";
        $params[':district'] = $district;
    }

    if ($min_qty > 0) {
        $sql .= " AND h.quantity_kg >= :min_qty";
        $params[':min_qty'] = $min_qty;
    }

    if ($max_price > 0) {
        $sql .= " AND h.price_per_kg <= :max_price";
        $params[':max_price'] = $max_price;
    }

    $sql .= " ORDER BY h.id DESC LIMIT 100";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count' => count($listings),
        'data' => $listings
    ], JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    error_log("Get Listings API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch produce listings.'
    ]);
}

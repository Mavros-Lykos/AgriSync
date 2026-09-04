<?php
// AgriSync Farmer API Endpoint (TASK-010)
// Returns JSON formatted response: {"success": bool, "data": array, "error": string|null}

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../auth/auth_check.php';

checkRole(['farmer']);

$farmer_id = (int)$_SESSION['user_id'];
$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '');
$db = getDbConnection();

if ($action === 'get_dashboard') {
    try {
        // Summary metrics
        $stmt_active = $db->prepare("SELECT COUNT(*) as count, COALESCE(SUM(quantity_kg), 0) as total_kg FROM harvest_listings WHERE farmer_id = ? AND status = ?");
        $stmt_active->execute([$farmer_id, 'available']);
        $active_data = $stmt_active->fetch();

        $stmt_earnings = $db->prepare("SELECT COALESCE(SUM(quantity_kg * price_per_kg), 0) as total_earnings FROM harvest_listings WHERE farmer_id = ? AND status = ?");
        $stmt_earnings->execute([$farmer_id, 'sold']);
        $earnings_data = $stmt_earnings->fetch();

        $stmt_matches = $db->prepare("SELECT COUNT(*) as count FROM order_matches WHERE farmer_id = ? AND status = ?");
        $stmt_matches->execute([$farmer_id, 'proposed']);
        $matches_count = (int)($stmt_matches->fetch()['count'] ?? 0);

        // Crop distribution for charts
        $stmt_crops = $db->prepare("SELECT crop_type, SUM(quantity_kg) as total_qty FROM harvest_listings WHERE farmer_id = ? GROUP BY crop_type ORDER BY total_qty DESC LIMIT 6");
        $stmt_crops->execute([$farmer_id]);
        $crop_distribution = $stmt_crops->fetchAll();

        // Recent listings
        $stmt_recent = $db->prepare("SELECT id, crop_type, quantity_kg, price_per_kg, harvest_date, status, created_at FROM harvest_listings WHERE farmer_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt_recent->execute([$farmer_id]);
        $recent_listings = $stmt_recent->fetchAll();

        jsonResponse(true, [
            'metrics' => [
                'active_listings_count' => (int)($active_data['count'] ?? 0),
                'active_volume_kg'      => (float)($active_data['total_kg'] ?? 0),
                'total_earnings'        => (float)($earnings_data['total_earnings'] ?? 0),
                'pending_matches_count' => $matches_count
            ],
            'crop_distribution' => $crop_distribution,
            'recent_listings'   => $recent_listings
        ]);
    } catch (PDOException $e) {
        jsonResponse(false, [], 'Failed to retrieve farmer dashboard metrics.', 500);
    }

} elseif ($action === 'get_listings') {
    $status_filter = sanitize($_GET['status'] ?? 'all');
    try {
        if ($status_filter !== 'all') {
            $stmt = $db->prepare("SELECT id, crop_type, quantity_kg, price_per_kg, harvest_date, status, created_at FROM harvest_listings WHERE farmer_id = ? AND status = ? ORDER BY harvest_date ASC");
            $stmt->execute([$farmer_id, $status_filter]);
        } else {
            $stmt = $db->prepare("SELECT id, crop_type, quantity_kg, price_per_kg, harvest_date, status, created_at FROM harvest_listings WHERE farmer_id = ? ORDER BY harvest_date ASC");
            $stmt->execute([$farmer_id]);
        }
        $listings = $stmt->fetchAll();
        jsonResponse(true, ['listings' => $listings]);
    } catch (PDOException $e) {
        jsonResponse(false, [], 'Failed to fetch harvest listings.', 500);
    }

} elseif ($action === 'create_listing') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, [], 'Method not allowed.', 405);
    }

    $crop_type          = sanitize($_POST['crop_type'] ?? '');
    $quantity_kg        = (float)($_POST['quantity_kg'] ?? 0);
    $min_order_quantity = (float)($_POST['min_order_quantity'] ?? 0);
    $price_per_kg       = (float)($_POST['price_per_kg'] ?? 0);
    $harvest_date       = sanitize($_POST['harvest_date'] ?? '');

    if (empty($crop_type) || $quantity_kg <= 0 || $price_per_kg <= 0 || empty($harvest_date)) {
        jsonResponse(false, [], 'Please enter a valid crop type, quantity (>0), price per kg (>0), and harvest date.', 400);
    }

    if ($min_order_quantity < 0 || $min_order_quantity > $quantity_kg) {
        jsonResponse(false, [], 'Minimum order quantity (MOQ) must be between 0 and total available quantity.', 400);
    }

    try {
        $stmt = $db->prepare("INSERT INTO harvest_listings (farmer_id, crop_type, quantity_kg, min_order_quantity, price_per_kg, harvest_date, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 'available', NOW(), NOW())");
        $stmt->execute([$farmer_id, $crop_type, $quantity_kg, $min_order_quantity, $price_per_kg, $harvest_date]);
        $listing_id = (int)$db->lastInsertId();

        jsonResponse(true, ['listing_id' => $listing_id], 'Harvest yield listed successfully!');
    } catch (PDOException $e) {
        jsonResponse(false, [], 'Failed to create harvest listing.', 500);
    }

} elseif ($action === 'update_status') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, [], 'Method not allowed.', 405);
    }

    $listing_id = (int)($_POST['listing_id'] ?? 0);
    $new_status = sanitize($_POST['status'] ?? '');

    $allowed_statuses = ['available', 'matched', 'sold'];
    if ($listing_id <= 0 || !in_array($new_status, $allowed_statuses, true)) {
        jsonResponse(false, [], 'Invalid listing or status transition requested.', 400);
    }

    try {
        $stmt = $db->prepare("UPDATE harvest_listings SET status = ?, updated_at = NOW() WHERE id = ? AND farmer_id = ?");
        $stmt->execute([$new_status, $listing_id, $farmer_id]);

        jsonResponse(true, [], 'Listing status updated successfully.');
    } catch (PDOException $e) {
        jsonResponse(false, [], 'Failed to update listing status.', 500);
    }

} elseif ($action === 'respond_match') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, [], 'Method not allowed.', 405);
    }

    $match_id = (int)($_POST['match_id'] ?? 0);
    $decision = sanitize($_POST['decision'] ?? ''); // 'accept' or 'reject'

    if ($match_id <= 0 || !in_array($decision, ['accept', 'reject'], true)) {
        jsonResponse(false, [], 'Invalid match decision parameters.', 400);
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT id, order_id, listing_id, business_id FROM order_matches WHERE id = ? AND farmer_id = ? LIMIT 1");
        $stmt->execute([$match_id, $farmer_id]);
        $match = $stmt->fetch();

        if (!$match) {
            $db->rollBack();
            jsonResponse(false, [], 'Order match record not found.', 44);
        }

        $new_match_status = ($decision === 'accept') ? 'accepted' : 'rejected';
        $update_match = $db->prepare("UPDATE order_matches SET status = ?, updated_at = NOW() WHERE id = ?");
        $update_match->execute([$new_match_status, $match_id]);

        if ($decision === 'accept') {
            // Update listing status to matched
            $update_listing = $db->prepare("UPDATE harvest_listings SET status = 'matched', updated_at = NOW() WHERE id = ?");
            $update_listing->execute([$match['listing_id']]);

            // Update order request status to matched
            $update_order = $db->prepare("UPDATE order_requests SET status = 'matched', updated_at = NOW() WHERE id = ?");
            $update_order->execute([$match['order_id']]);

            // Notify Business buyer
            $notif_stmt = $db->prepare("INSERT INTO notifications (user_id, message, link, is_read, created_at, updated_at) VALUES (?, ?, ?, 0, NOW(), NOW())");
            $notif_message = "Farmer has accepted your matched order #" . $match['order_id'] . "!";
            $notif_stmt->execute([$match['business_id'], $notif_message, '/business/matches.php']);
        }

        $db->commit();
        jsonResponse(true, [], 'Match proposal ' . $new_match_status . ' successfully!');
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        jsonResponse(false, [], 'Failed to record match decision.', 500);
    }

} elseif ($action === 'get_profile') {
    try {
        $stmt = $db->prepare("
            SELECT u.id, u.name, u.email, u.phone, u.district,
                   COALESCE(fp.farm_name, '') as farm_name,
                   COALESCE(fp.location, '') as location,
                   COALESCE(fp.primary_crops, '') as primary_crops
            FROM users u
            LEFT JOIN farmer_profiles fp ON u.id = fp.user_id
            WHERE u.id = ? LIMIT 1
        ");
        $stmt->execute([$farmer_id]);
        $profile = $stmt->fetch();
        jsonResponse(true, ['profile' => $profile]);
    } catch (PDOException $e) {
        jsonResponse(false, [], 'Failed to fetch farmer profile.', 500);
    }

} elseif ($action === 'update_profile') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, [], 'Method not allowed.', 405);
    }

    $name          = sanitize($_POST['name'] ?? '');
    $phone         = sanitize($_POST['phone'] ?? '');
    $district      = sanitize($_POST['district'] ?? '');
    $farm_name     = sanitize($_POST['farm_name'] ?? '');
    $location      = sanitize($_POST['location'] ?? '');
    $primary_crops = sanitize($_POST['primary_crops'] ?? '');

    if (empty($name) || empty($phone) || empty($district)) {
        jsonResponse(false, [], 'Full Name, Phone Number, and District are required.', 400);
    }

    try {
        $db->beginTransaction();

        // 1. Update users table
        $stmt_u = $db->prepare("UPDATE users SET name = ?, phone = ?, district = ?, updated_at = NOW() WHERE id = ?");
        $stmt_u->execute([$name, $phone, $district, $farmer_id]);

        // Update active session variables
        $_SESSION['user_name'] = $name;
        $_SESSION['user_district'] = $district;

        // 2. Upsert farmer_profiles table
        $stmt_check = $db->prepare("SELECT id FROM farmer_profiles WHERE user_id = ? LIMIT 1");
        $stmt_check->execute([$farmer_id]);
        $exists = $stmt_check->fetch();

        if ($exists) {
            $stmt_fp = $db->prepare("UPDATE farmer_profiles SET farm_name = ?, location = ?, primary_crops = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt_fp->execute([$farm_name, $location, $primary_crops, $farmer_id]);
        } else {
            $stmt_fp = $db->prepare("INSERT INTO farmer_profiles (user_id, farm_name, location, primary_crops, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
            $stmt_fp->execute([$farmer_id, $farm_name, $location, $primary_crops]);
        }

        $db->commit();
        jsonResponse(true, [], 'Farmer profile updated successfully!');
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        jsonResponse(false, [], 'Failed to update farmer profile details.', 500);
    }

} else {
    jsonResponse(false, [], 'Invalid farmer API action.', 400);
}

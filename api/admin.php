<?php
// AgriSync Admin API Endpoint (TASK-012)
// Returns JSON formatted response: {"success": bool, "data": array, "error": string|null}

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../auth/auth_check.php';

checkRole(['admin']);

$admin_id = (int)$_SESSION['user_id'];
$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '');
$db = getDbConnection();

if ($action === 'get_metrics') {
    try {
        $user_counts = $db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role")->fetchAll();
        $farmers_count = 0;
        $business_count = 0;
        foreach ($user_counts as $uc) {
            if ($uc['role'] === 'farmer') $farmers_count = (int)$uc['count'];
            if ($uc['role'] === 'business') $business_count = (int)$uc['count'];
        }

        $stmt_listings = $db->query("SELECT COUNT(*) as count, COALESCE(SUM(quantity_kg), 0) as total_kg FROM harvest_listings")->fetch();
        $stmt_orders_prep = $db->prepare("SELECT COUNT(*) as count, COALESCE(SUM(quantity_kg * max_price), 0) as total_value FROM order_requests WHERE status = ?");
        $stmt_orders_prep->execute(['fulfilled']);
        $stmt_orders = $stmt_orders_prep->fetch();
        $stmt_matches = $db->query("SELECT COUNT(*) as count FROM order_matches")->fetch();

        // System logs preview
        $logs_stmt = $db->query("SELECT id, agent_type, action_step, created_at FROM agent_logs ORDER BY created_at DESC LIMIT 10")->fetchAll();

        jsonResponse(true, [
            'metrics' => [
                'farmers_count'   => $farmers_count,
                'business_count'  => $business_count,
                'total_listings'  => (int)$stmt_listings['count'],
                'total_volume_kg' => (float)$stmt_listings['total_kg'],
                'fulfilled_value' => (float)$stmt_orders['total_value'],
                'total_matches'   => (int)$stmt_matches['count']
            ],
            'recent_logs' => $logs_stmt
        ]);
    } catch (PDOException $e) {
        jsonResponse(false, [], 'Failed to retrieve admin platform metrics.', 500);
    }

} elseif ($action === 'get_users') {
    $role_filter = sanitize($_GET['role'] ?? 'all');
    $search = sanitize($_GET['search'] ?? '');

    try {
        $sql = "SELECT id, name, email, role, phone, district, is_active, created_at FROM users WHERE 1=1";
        $params = [];

        if ($role_filter !== 'all') {
            $sql .= " AND role = ?";
            $params[] = $role_filter;
        }

        if (!empty($search)) {
            $sql .= " AND (name LIKE ? OR email LIKE ? OR district LIKE ?)";
            $term = "%{$search}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " ORDER BY created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        jsonResponse(true, ['users' => $users]);
    } catch (PDOException $e) {
        jsonResponse(false, [], 'Failed to fetch user directory.', 500);
    }

} elseif ($action === 'toggle_user_status') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, [], 'Method not allowed.', 405);
    }

    $target_user_id = (int)($_POST['user_id'] ?? 0);
    if ($target_user_id <= 0 || $target_user_id === $admin_id) {
        jsonResponse(false, [], 'Cannot modify active status for this user.', 400);
    }

    try {
        $stmt = $db->prepare("SELECT name, is_active FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$target_user_id]);
        $target = $stmt->fetch();

        if (!$target) {
            jsonResponse(false, [], 'User record not found.', 404);
        }

        $new_status = ((int)$target['is_active'] === 1) ? 0 : 1;
        $upd_stmt = $db->prepare("UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ?");
        $upd_stmt->execute([$new_status, $target_user_id]);

        // Insert immutable audit log record
        $audit_action = ($new_status === 0) ? 'deactivate_user' : 'activate_user';
        $target_name = $target['name'] ?? ("#USR-" . $target_user_id);
        $details = "Admin #{$admin_id} " . ($new_status === 0 ? 'deactivated' : 'activated') . " user account #{$target_user_id} ({$target_name}).";
        logAdminAudit($admin_id, $audit_action, $target_user_id, $details, $db);

        jsonResponse(true, ['is_active' => $new_status], 'User account status toggled successfully.');
    } catch (PDOException $e) {
        jsonResponse(false, [], 'Failed to update user account status.', 500);
    }

} elseif ($action === 'get_audit_logs') {
    $search = sanitize($_GET['search'] ?? '');
    $filter_action = sanitize($_GET['filter_action'] ?? 'all');
    try {
        $sql = "
            SELECT a.id, a.admin_id, a.action, a.target_id, a.details, a.ip_address, a.created_at,
                   u.name as admin_name, u.email as admin_email
            FROM admin_audit_logs a
            JOIN users u ON a.admin_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if ($filter_action !== 'all') {
            $sql .= " AND a.action = ?";
            $params[] = $filter_action;
        }

        if (!empty($search)) {
            $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR a.details LIKE ? OR a.ip_address LIKE ?)";
            $term = "%{$search}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " ORDER BY a.created_at DESC LIMIT 200";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse(true, ['logs' => $logs]);
    } catch (PDOException $e) {
        jsonResponse(false, [], 'Failed to fetch audit log history.', 500);
    }

} elseif ($action === 'get_all_listings') {
    try {
        $stmt = $db->query("
            SELECT h.id, h.crop_type, h.quantity_kg, h.price_per_kg, h.harvest_date, h.status, h.created_at, u.name as farmer_name, u.district as farmer_district
            FROM harvest_listings h
            JOIN users u ON h.farmer_id = u.id
            ORDER BY h.created_at DESC
        ");
        jsonResponse(true, ['listings' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        jsonResponse(false, [], 'Failed to fetch platform listings audit.', 500);
    }

} elseif ($action === 'get_all_orders') {
    $status_filter = sanitize($_GET['status'] ?? 'all');
    $crop_filter   = sanitize($_GET['crop'] ?? 'all');
    $search        = sanitize($_GET['search'] ?? '');
    $date_filter   = sanitize($_GET['date'] ?? '');

    try {
        $sql = "
            SELECT o.id, o.crop_type, o.quantity_kg, o.max_price, o.delivery_date, o.urgency, o.status, o.created_at,
                   u.name as business_name, u.district as business_district,
                   (SELECT COUNT(*) FROM order_matches m WHERE m.order_id = o.id) as matches_count
            FROM order_requests o
            JOIN users u ON o.business_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if ($status_filter !== 'all') {
            $sql .= " AND o.status = ?";
            $params[] = $status_filter;
        }

        if ($crop_filter !== 'all') {
            $sql .= " AND o.crop_type = ?";
            $params[] = $crop_filter;
        }

        if (!empty($date_filter)) {
            $sql .= " AND o.delivery_date = ?";
            $params[] = $date_filter;
        }

        if (!empty($search)) {
            $sql .= " AND (o.crop_type LIKE ? OR u.name LIKE ? OR u.district LIKE ? OR CAST(o.id AS CHAR) LIKE ?)";
            $term = "%{$search}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " ORDER BY o.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        jsonResponse(true, ['orders' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        jsonResponse(false, [], 'Failed to fetch platform orders audit.', 500);
    }

} else {
    jsonResponse(false, [], 'Invalid admin API action.', 400);
}

<?php
// AgriSync User Notifications API Endpoint (TASK-013)
// Returns JSON formatted response: {"success": bool, "data": array, "error": string|null}

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../auth/auth_check.php';

$user_id = (int)$_SESSION['user_id'];
$action = sanitize($_GET['action'] ?? $_POST['action'] ?? 'list');
$db = getDbConnection();

if ($action === 'list') {
    try {
        $stmt = $db->prepare("SELECT id, message, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 15");
        $stmt->execute([$user_id]);
        $rows = $stmt->fetchAll();

        $unread_stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $unread_stmt->execute([$user_id]);
        $unread_count = (int)($unread_stmt->fetch()['count'] ?? 0);

        $notifications = array_map(function($row) {
            return [
                'id'         => (int)$row['id'],
                'message'    => sanitize($row['message']),
                'link'       => sanitize($row['link']),
                'is_read'    => (bool)$row['is_read'],
                'created_at' => $row['created_at'],
                'time_ago'   => timeAgo($row['created_at'])
            ];
        }, $rows);

        jsonResponse(true, [
            'notifications' => $notifications,
            'unread_count'  => $unread_count
        ]);
    } catch (PDOException $e) {
        jsonResponse(false, [], 'Failed to fetch notifications.', 500);
    }

} elseif ($action === 'read_all') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, [], 'Method not allowed', 405);
    }
    
    try {
        $update_stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $update_stmt->execute([$user_id]);

        jsonResponse(true, [], 'All notifications marked as read.');
    } catch (PDOException $e) {
        jsonResponse(false, [], 'Failed to update notifications.', 500);
    }

} else {
    jsonResponse(false, [], 'Invalid notifications action.', 400);
}

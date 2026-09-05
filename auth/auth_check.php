<?php
/**
 * AgriSync Authentication & Role Authorization Middleware (TASK-019 / TASK-009)
 * Protects routes and ensures authenticated access with role verification.
 */

if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/session.php';
}
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/constants.php';
}
require_once __DIR__ . '/../includes/functions.php';

/**
 * Enforce that the user is logged in
 * 
 * @return void
 */
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (str_contains($request_uri, '/api/')) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized access. Please log in.']);
            exit;
        }
        $_SESSION['flash_error'] = 'Please log in to access this page.';
        $app_url = defined('APP_URL') ? APP_URL : '';
        redirect($app_url . '/auth/login.php');
    }
}

/**
 * Enforce specific user role(s)
 * 
 * @param string|array $allowed_roles
 * @return void
 */
function requireRole($allowed_roles): void {
    requireLogin();

    $current_role = $_SESSION['user_role'] ?? '';
    $allowed = is_array($allowed_roles) ? $allowed_roles : [$allowed_roles];

    if (!in_array($current_role, $allowed, true)) {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (str_contains($request_uri, '/api/')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden: Insufficient permissions.']);
            exit;
        }

        $app_url = defined('APP_URL') ? APP_URL : '';
        // Redirect to their respective dashboard
        if ($current_role === 'farmer') {
            redirect($app_url . '/farmer/dashboard.php');
        } elseif ($current_role === 'business') {
            redirect($app_url . '/business/dashboard.php');
        } elseif ($current_role === 'admin') {
            redirect($app_url . '/admin/dashboard.php');
        } else {
            redirect($app_url . '/auth/login.php');
        }
    }
}

/**
 * Compatibility alias for checkRole
 */
function checkRole(array $allowed_roles): void {
    requireRole($allowed_roles);
}

/**
 * Enforce admin role
 * 
 * @return void
 */
function requireAdmin(): void {
    requireRole('admin');
}

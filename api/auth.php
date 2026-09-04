<?php
// AgriSync User Authentication API Endpoint (TASK-009)
// Returns JSON formatted response: {"success": bool, "data": array, "error": string|null}

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, [], 'Invalid request method. Only POST is allowed.', 405);
}

$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '');
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';

if (!validateCSRFToken($csrf_token)) {
    jsonResponse(false, [], 'CSRF security token validation failed. Please refresh and try again.', 403);
}

$db = getDbConnection();

if ($action === 'login') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = trim($_POST['password'] ?? '');

    if (!$email || empty($password)) {
        jsonResponse(false, [], 'Please provide a valid email address and password.', 400);
    }

    try {
        $stmt = $db->prepare("SELECT id, name, email, password_hash, role, phone, district, is_active FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            jsonResponse(false, [], 'Invalid email or password credentials.', 401);
        }

        if ((int)$user['is_active'] !== 1) {
            jsonResponse(false, [], 'Your account has been deactivated. Please contact support.', 403);
        }

        // Initialize session state
        $_SESSION['user_id']       = (int)$user['id'];
        $_SESSION['user_name']     = sanitize($user['name']);
        $_SESSION['user_email']    = sanitize($user['email']);
        $_SESSION['user_role']     = sanitize($user['role']);
        $_SESSION['user_district'] = sanitize($user['district']);

        $app_url = defined('APP_URL') ? APP_URL : '';
        $redirect_target = match ($user['role']) {
            'farmer'   => $app_url . '/farmer/dashboard.php',
            'business' => $app_url . '/business/dashboard.php',
            'admin'    => $app_url . '/admin/dashboard.php',
            default    => $app_url . '/index.php',
        };

        jsonResponse(true, [
            'redirect' => $redirect_target,
            'user' => [
                'id'       => (int)$user['id'],
                'name'     => sanitize($user['name']),
                'role'     => sanitize($user['role']),
                'district' => sanitize($user['district'])
            ]
        ], 'Login successful!');

    } catch (PDOException $e) {
        jsonResponse(false, [], 'A database error occurred during login. Please try again.', 500);
    }

} elseif ($action === 'register') {
    $name     = sanitize($_POST['name'] ?? '');
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = trim($_POST['password'] ?? '');
    $role            = sanitize($_POST['role'] ?? '');
    $phone           = sanitize($_POST['phone'] ?? '');
    $district        = sanitize($_POST['district'] ?? '');
    $nic_number      = sanitize($_POST['nic_number'] ?? '');
    $business_reg_no = sanitize($_POST['business_reg_no'] ?? '');

    if (empty($name) || !$email || empty($password) || empty($role) || empty($district)) {
        jsonResponse(false, [], 'All required fields (Name, Email, Password, Role, District) must be completed.', 400);
    }

    $valid_roles = ['farmer', 'business', 'admin'];
    if (!in_array($role, $valid_roles, true)) {
        jsonResponse(false, [], 'Invalid user role selected.', 400);
    }

    if (strlen($password) < 6) {
        jsonResponse(false, [], 'Password must be at least 6 characters in length.', 400);
    }

    if ($role === 'farmer') {
        if (empty($nic_number)) {
            jsonResponse(false, [], 'National Identity Card (NIC) number is required for farmer registration.', 400);
        }
        if (!preg_match('/^([0-9]{9}[xXvV]|[0-9]{12})$/', $nic_number)) {
            jsonResponse(false, [], 'Invalid Sri Lankan NIC number format. Please provide a valid 9-digit (with V/X) or 12-digit NIC.', 400);
        }
        $business_reg_no = null;
    } elseif ($role === 'business') {
        if (empty($business_reg_no)) {
            jsonResponse(false, [], 'Business Registration Number (BRN) is required for commercial buyer registration.', 400);
        }
        $nic_number = null;
    }

    try {
        // Check if email already registered
        $check_stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check_stmt->execute([$email]);
        if ($check_stmt->fetch()) {
            jsonResponse(false, [], 'An account with this email address already exists.', 409);
        }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        $insert_stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role, phone, district, nic_number, business_reg_no, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
        $insert_stmt->execute([$name, $email, $password_hash, $role, $phone, $district, $nic_number, $business_reg_no]);

        $new_user_id = (int)$db->lastInsertId();

        // Auto login newly registered user
        $_SESSION['user_id']       = $new_user_id;
        $_SESSION['user_name']     = $name;
        $_SESSION['user_email']    = $email;
        $_SESSION['user_role']     = $role;
        $_SESSION['user_district'] = $district;

        $app_url = defined('APP_URL') ? APP_URL : '';
        $redirect_target = match ($role) {
            'farmer'   => $app_url . '/farmer/dashboard.php',
            'business' => $app_url . '/business/dashboard.php',
            'admin'    => $app_url . '/admin/dashboard.php',
            default    => $app_url . '/index.php',
        };

        jsonResponse(true, [
            'redirect' => $redirect_target,
            'user' => [
                'id'       => $new_user_id,
                'name'     => $name,
                'role'     => $role,
                'district' => $district
            ]
        ], 'Registration successful!');

    } catch (PDOException $e) {
        jsonResponse(false, [], 'A database error occurred during registration. Please try again.', 500);
    }

} else {
    jsonResponse(false, [], 'Invalid action requested.', 400);
}

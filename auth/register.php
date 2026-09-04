<?php
/**
 * AgriSync — User Registration (TASK-017)
 * Onboarding for Farmers and Commercial Buyers.
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (!empty($_SESSION['user_id']) && !empty($_SESSION['user_role'])) {
    $role = $_SESSION['user_role'];
    $app_url = defined('APP_URL') ? APP_URL : '';
    $dest = match($role) {
        'farmer' => $app_url . '/farmer/dashboard.php',
        'business' => $app_url . '/business/dashboard.php',
        'admin' => $app_url . '/admin/dashboard.php',
        default => $app_url . '/index.php'
    };
    redirect($dest);
}

$page_title = 'Register';
$error = '';
$name_val = '';
$email_val = '';
$phone_val = '';
$role_val = 'farmer';
$district_val = 'Dambulla';

$districts = ['Dambulla', 'Nuwara Eliya', 'Matale', 'Kandy', 'Colombo', 'Jaffna', 'Anuradhapura', 'Badulla', 'Kurunegala', 'Hambantota', 'Ratnapura', 'Gampaha'];

$nic_val = sanitize($_POST['nic_number'] ?? '');
$brn_val = sanitize($_POST['business_reg_no'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name_val = sanitize($_POST['name'] ?? '');
    $email_val = sanitize($_POST['email'] ?? '');
    $phone_val = sanitize($_POST['phone'] ?? '');
    $role_val = sanitize($_POST['role'] ?? 'farmer');
    $district_val = sanitize($_POST['district'] ?? 'Dambulla');
    $nic_val = sanitize($_POST['nic_number'] ?? '');
    $brn_val = sanitize($_POST['business_reg_no'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!validateCSRFToken($csrf)) {
        $error = 'Security validation failed. Please refresh and try again.';
    } elseif (empty($name_val) || empty($email_val) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email_val, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $password_confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role_val, ['farmer', 'business'], true)) {
        $error = 'Invalid account type selected.';
    } elseif ($role_val === 'farmer' && empty($nic_val)) {
        $error = 'National Identity Card (NIC) number is required for farmer registration.';
    } elseif ($role_val === 'farmer' && !preg_match('/^([0-9]{9}[xXvV]|[0-9]{12})$/', $nic_val)) {
        $error = 'Invalid Sri Lankan NIC number format. Please provide a valid 9-digit (with V/X) or 12-digit NIC.';
    } elseif ($role_val === 'business' && empty($brn_val)) {
        $error = 'Business Registration Number (BRN) is required for commercial buyer registration.';
    } else {
        if ($role_val === 'farmer') {
            $brn_val = null;
        } else {
            $nic_val = null;
        }

        try {
            $db = getDbConnection();
            
            // Check if email exists
            $check = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $check->execute([':email' => $email_val]);
            
            // Check if NIC exists
            $check_nic = false;
            if (!empty($nic_val)) {
                $stmt_nic = $db->prepare("SELECT id FROM users WHERE nic_number = :nic LIMIT 1");
                $stmt_nic->execute([':nic' => $nic_val]);
                $check_nic = $stmt_nic->fetch();
            }
            
            // Check if BRN exists
            $check_brn = false;
            if (!empty($brn_val)) {
                $stmt_brn = $db->prepare("SELECT id FROM users WHERE business_reg_no = :brn LIMIT 1");
                $stmt_brn->execute([':brn' => $brn_val]);
                $check_brn = $stmt_brn->fetch();
            }

            if ($check->fetch()) {
                $error = 'An account with this email address already exists.';
            } elseif ($check_nic) {
                $error = 'An account with this NIC number already exists.';
            } elseif ($check_brn) {
                $error = 'An account with this Business Registration Number already exists.';
            } else {
                $db->beginTransaction();

                $hash = password_hash($password, PASSWORD_BCRYPT);
                $ins = $db->prepare("
                    INSERT INTO users (name, email, password_hash, role, phone, district, nic_number, business_reg_no, is_active, created_at, updated_at)
                    VALUES (:name, :email, :hash, :role, :phone, :district, :nic_number, :business_reg_no, 1, NOW(), NOW())
                ");
                $ins->execute([
                    ':name' => $name_val,
                    ':email' => $email_val,
                    ':hash' => $hash,
                    ':role' => $role_val,
                    ':phone' => $phone_val,
                    ':district' => $district_val,
                    ':nic_number' => $nic_val,
                    ':business_reg_no' => $brn_val
                ]);

                $user_id = (int) $db->lastInsertId();

                // Create profile
                if ($role_val === 'farmer') {
                    $pIns = $db->prepare("INSERT INTO farmers (user_id, full_name, district, phone, farm_size_acres, created_at, updated_at) VALUES (:uid, :name, :dist, :phone, 2.0, NOW(), NOW())");
                    $pIns->execute([':uid' => $user_id, ':name' => $name_val, ':dist' => $district_val, ':phone' => $phone_val]);
                } else {
                    $pIns = $db->prepare("INSERT INTO businesses (user_id, company_name, district, phone, business_type, created_at, updated_at) VALUES (:uid, :name, :dist, :phone, 'Wholesaler', NOW(), NOW())");
                    $pIns->execute([':uid' => $user_id, ':name' => $name_val, ':dist' => $district_val, ':phone' => $phone_val]);
                }

                $db->commit();

                $app_url = defined('APP_URL') ? APP_URL : '';
                redirect($app_url . '/auth/login.php?registered=1');
            }
        } catch (Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Registration Error: " . $e->getMessage());
            $error = 'Registration temporarily unavailable. Please try again.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-9 col-lg-6">
            
            <div class="text-center mb-4">
                <a href="../index.php" class="d-inline-flex align-items-center text-decoration-none mb-2">
                    <span class="fs-2">🌾</span>
                    <span class="fs-3 fw-bold text-success ms-2"><?= APP_NAME ?></span>
                </a>
                <h4 class="fw-bold text-dark mb-1">Create your AgriSync account</h4>
                <p class="text-muted small">Join Sri Lanka's AI-Powered agricultural network</p>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger rounded-3 py-2 px-3 small d-flex align-items-center mb-4">
                        <i class="bi bi-exclamation-circle-fill me-2 fs-5"></i>
                        <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                    <!-- Role Selector -->
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted d-block">I want to register as:</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="role" id="roleFarmer" value="farmer" <?= $role_val === 'farmer' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-success w-100 py-2 rounded-3 text-start" for="roleFarmer">
                                    🌱 <strong class="d-block">Farmer</strong>
                                    <small class="text-muted extra-small">List & sell harvest</small>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="role" id="roleBusiness" value="business" <?= $role_val === 'business' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-primary w-100 py-2 rounded-3 text-start" for="roleBusiness">
                                    🛒 <strong class="d-block">Commercial Buyer</strong>
                                    <small class="text-muted extra-small">Supermarket / Hotel</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="nameInput" class="form-label small fw-semibold text-muted">Full Name / Business Name</label>
                            <input type="text" name="name" id="nameInput" class="form-control rounded-3" placeholder="e.g. Sunil Perera" value="<?= htmlspecialchars($name_val, ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="emailInput" class="form-label small fw-semibold text-muted">Email Address</label>
                            <input type="email" name="email" id="emailInput" class="form-control rounded-3" placeholder="name@domain.lk" value="<?= htmlspecialchars($email_val, ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="phoneInput" class="form-label small fw-semibold text-muted">Contact Phone</label>
                            <input type="text" name="phone" id="phoneInput" class="form-control rounded-3" placeholder="07X XXX XXXX" value="<?= htmlspecialchars($phone_val, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="districtSelect" class="form-label small fw-semibold text-muted">District</label>
                            <select name="district" id="districtSelect" class="form-select rounded-3">
                                <?php foreach ($districts as $d): ?>
                                    <option value="<?= $d ?>" <?= $district_val === $d ? 'selected' : '' ?>><?= $d ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- KYC Verification Fields -->
                    <div class="row g-3 mb-3">
                        <div class="col-12" id="nicContainer">
                            <label for="nicInput" class="form-label small fw-semibold text-muted">Sri Lankan National Identity Card (NIC) <span class="text-danger">*</span></label>
                            <input type="text" name="nic_number" id="nicInput" class="form-control rounded-3" placeholder="e.g. 901234567V or 199012345678" value="<?= htmlspecialchars($nic_val, ENT_QUOTES, 'UTF-8') ?>">
                            <small class="text-muted extra-small">Required for verification of farmer accounts in Sri Lanka.</small>
                        </div>

                        <div class="col-12 d-none" id="brnContainer">
                            <label for="brnInput" class="form-label small fw-semibold text-muted">Business Registration Number (BRN) <span class="text-danger">*</span></label>
                            <input type="text" name="business_reg_no" id="brnInput" class="form-control rounded-3" placeholder="e.g. PV12345" value="<?= htmlspecialchars($brn_val, ENT_QUOTES, 'UTF-8') ?>">
                            <small class="text-muted extra-small">Required for registered commercial supermarket & wholesale buyers.</small>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label for="passwordInput" class="form-label small fw-semibold text-muted">Password</label>
                            <input type="password" name="password" id="passwordInput" class="form-control rounded-3" placeholder="Min. 6 characters" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="passwordConfirmInput" class="form-label small fw-semibold text-muted">Confirm Password</label>
                            <input type="password" name="password_confirm" id="passwordConfirmInput" class="form-control rounded-3" placeholder="Repeat password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold rounded-3 mb-3 shadow-sm">
                        <i class="bi bi-person-plus me-1"></i> Complete Registration
                    </button>
                </form>

            </div>

            <div class="text-center mt-3 text-muted small">
                Already have an account? <a href="login.php" class="text-success fw-semibold text-decoration-none">Sign in here</a>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const roleFarmer = document.getElementById('roleFarmer');
    const roleBusiness = document.getElementById('roleBusiness');
    const nicContainer = document.getElementById('nicContainer');
    const brnContainer = document.getElementById('brnContainer');

    function updateKycVisibility() {
        if (roleFarmer.checked) {
            nicContainer.classList.remove('d-none');
            brnContainer.classList.add('d-none');
        } else if (roleBusiness.checked) {
            brnContainer.classList.remove('d-none');
            nicContainer.classList.add('d-none');
        }
    }

    roleFarmer.addEventListener('change', updateKycVisibility);
    roleBusiness.addEventListener('change', updateKycVisibility);
    updateKycVisibility();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

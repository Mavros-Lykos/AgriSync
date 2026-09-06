<?php
// AgriSync Farmer Profile Management Page (M3 Task)
$page_title = 'Farmer Profile Management';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../auth/auth_check.php';
checkRole(['farmer']);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$farmer_id = (int)$_SESSION['user_id'];
$db = getDbConnection();

// Fetch initial farmer profile data
try {
    $stmt = $db->prepare("
        SELECT u.id, u.name, u.email, u.phone, u.district, u.created_at,
               COALESCE(fp.farm_name, '') as farm_name,
               COALESCE(fp.location, '') as location,
               COALESCE(fp.primary_crops, '') as primary_crops
        FROM users u
        LEFT JOIN farmer_profiles fp ON u.id = fp.user_id
        WHERE u.id = ? LIMIT 1
    ");
    $stmt->execute([$farmer_id]);
    $user_profile = $stmt->fetch();
} catch (PDOException $e) {
    $user_profile = [];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid dashboard-wrapper">
    <div class="row">
        <!-- Sidebar Navigation -->
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

        <!-- Main Content Area -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <!-- Header Banner -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
                <div>
                    <h2 class="fw-bold text-dark mb-1">My Farmer Profile</h2>
                    <p class="text-muted small mb-0">View and manage your farm identity, contact details, and primary crop offerings.</p>
                </div>
                <div class="mt-3 mt-md-0">
                    <button type="button" id="btnToggleEdit" class="btn btn-outline-primary shadow-sm">
                        <i class="bi bi-pencil-square"></i>
                        <span id="btnToggleText">Edit Profile</span>
                    </button>
                </div>
            </div>

            <div class="row g-4">
                <!-- Profile Avatar & Quick Info Card -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm text-center p-4">
                        <div class="mx-auto bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 90px; height: 90px; font-size: 2.5rem;">
                            <i class="bi bi-person-badge"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-1" id="dispHeaderName"><?= htmlspecialchars($user_profile['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h4>
                        <span class="badge bg-success-subtle text-success border border-success rounded-pill px-3 py-1.5 align-self-center mb-3">
                            <i class="bi bi-patch-check-fill me-1"></i> Verified Farmer
                        </span>
                        
                        <div class="text-start border-top pt-3 mt-2 extra-small">
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted"><i class="bi bi-envelope me-1"></i> Account Email:</span>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($user_profile['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted"><i class="bi bi-geo-alt me-1"></i> District:</span>
                                <span class="fw-semibold text-dark" id="dispDistrict"><?= htmlspecialchars($user_profile['district'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted"><i class="bi bi-telephone me-1"></i> Phone:</span>
                                <span class="fw-semibold text-dark" id="dispPhone"><?= htmlspecialchars($user_profile['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-muted"><i class="bi bi-calendar3 me-1"></i> Registered Since:</span>
                                <span class="fw-semibold text-dark"><?= date('M Y', strtotime($user_profile['created_at'] ?? 'now')) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Profile Details / Edit Form -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm p-4">
                        <!-- VIEW MODE CONTAINER -->
                        <div id="viewModeContainer">
                            <h5 class="fw-bold text-dark mb-3 border-bottom pb-2">Farm & Crop Details</h5>
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <span class="text-muted extra-small text-uppercase fw-semibold d-block">Full Name</span>
                                    <p class="fw-bold text-dark fs-6 mb-3" id="dispName"><?= htmlspecialchars($user_profile['name'] ?? 'Not Specified', ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted extra-small text-uppercase fw-semibold d-block">Phone Number</span>
                                    <p class="fw-bold text-dark fs-6 mb-3" id="dispPhoneBody"><?= htmlspecialchars($user_profile['phone'] ?? 'Not Specified', ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted extra-small text-uppercase fw-semibold d-block">Farm Name</span>
                                    <p class="fw-bold text-dark fs-6 mb-3" id="dispFarmName"><?= htmlspecialchars($user_profile['farm_name'] ?: 'Not Specified', ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted extra-small text-uppercase fw-semibold d-block">Farm Location Address</span>
                                    <p class="fw-bold text-dark fs-6 mb-3" id="dispLocation"><?= htmlspecialchars($user_profile['location'] ?: 'Not Specified', ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted extra-small text-uppercase fw-semibold d-block">District</span>
                                    <p class="fw-bold text-dark fs-6 mb-3" id="dispDistrictBody"><?= htmlspecialchars($user_profile['district'] ?? 'Not Specified', ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted extra-small text-uppercase fw-semibold d-block">Primary Crops Harvested</span>
                                    <p class="fw-bold text-primary fs-6 mb-3" id="dispPrimaryCrops"><?= htmlspecialchars($user_profile['primary_crops'] ?: 'e.g. Carrots, Potatoes, Leeks', ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- EDIT MODE FORM CONTAINER (Hidden by default) -->
                        <div id="editModeContainer" class="d-none">
                            <h5 class="fw-bold text-dark mb-3 border-bottom pb-2">Edit Profile Information</h5>
                            
                            <form id="profileEditForm" novalidate>
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="inputName" class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="inputName" name="name" value="<?= htmlspecialchars($user_profile['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="inputPhone" class="form-label fw-semibold small">Phone Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="inputPhone" name="phone" value="<?= htmlspecialchars($user_profile['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="inputFarmName" class="form-label fw-semibold small">Farm Name</label>
                                        <input type="text" class="form-control" id="inputFarmName" name="farm_name" value="<?= htmlspecialchars($user_profile['farm_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. Nuwara Eliya Green Acres">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="inputDistrict" class="form-label fw-semibold small">District <span class="text-danger">*</span></label>
                                        <select class="form-select" id="inputDistrict" name="district" required>
                                            <?php 
                                            $districts = ['Nuwara Eliya', 'Badulla', 'Kandy', 'Matale', 'Gampaha', 'Colombo', 'Kalutara', 'Kurunegala', 'Anuradhapura', 'Polonnaruwa', 'Jaffna'];
                                            foreach ($districts as $d):
                                                $selected = ($user_profile['district'] === $d) ? 'selected' : '';
                                                echo "<option value=\"$d\" $selected>$d</option>";
                                            endforeach;
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="inputLocation" class="form-label fw-semibold small">Farm Location Address</label>
                                    <input type="text" class="form-control" id="inputLocation" name="location" value="<?= htmlspecialchars($user_profile['location'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. No. 45, Farm Road, Nuwara Eliya">
                                </div>

                                <div class="mb-4">
                                    <label for="inputPrimaryCrops" class="form-label fw-semibold small">Primary Crops Offered</label>
                                    <input type="text" class="form-control" id="inputPrimaryCrops" name="primary_crops" value="<?= htmlspecialchars($user_profile['primary_crops'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. Carrot, Potato, Leek, Cabbage">
                                    <div class="form-text extra-small">Separate crop varieties with commas.</div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                                    <button type="button" id="btnCancelEdit" class="btn btn-outline-secondary">Cancel</button>
                                    <button type="submit" id="btnSaveProfile" class="btn btn-primary shadow-sm">
                                        <i class="bi bi-check-lg me-1"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const btnToggleEdit = document.getElementById('btnToggleEdit');
    const btnToggleText = document.getElementById('btnToggleText');
    const btnCancelEdit = document.getElementById('btnCancelEdit');
    const viewModeContainer = document.getElementById('viewModeContainer');
    const editModeContainer = document.getElementById('editModeContainer');
    const profileEditForm = document.getElementById('profileEditForm');
    const btnSaveProfile = document.getElementById('btnSaveProfile');

    let isEditMode = false;

    function toggleEditState(showEdit) {
        isEditMode = showEdit;
        if (isEditMode) {
            viewModeContainer.classList.add('d-none');
            editModeContainer.classList.remove('d-none');
            btnToggleText.textContent = 'View Mode';
        } else {
            editModeContainer.classList.add('d-none');
            viewModeContainer.classList.remove('d-none');
            btnToggleText.textContent = 'Edit Profile';
        }
    }

    btnToggleEdit.addEventListener('click', () => toggleEditState(!isEditMode));
    btnCancelEdit.addEventListener('click', () => toggleEditState(false));

    profileEditForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const name = document.getElementById('inputName').value.trim();
        const phone = document.getElementById('inputPhone').value.trim();
        const district = document.getElementById('inputDistrict').value.trim();

        if (!name || !phone || !district) {
            showToast('Please complete all required fields (Name, Phone, District).', 'error');
            return;
        }

        btnSaveProfile.disabled = true;
        btnSaveProfile.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...';

        try {
            const formData = new FormData(profileEditForm);
            const res = await fetch('../api/farmer.php?action=update_profile', {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': formData.get('csrf_token') }
            });
            const result = await res.json();

            if (result.success) {
                showToast(result.error || 'Profile updated successfully!', 'success');

                // Update Display Text Elements
                document.getElementById('dispHeaderName').textContent = name;
                document.getElementById('dispName').textContent = name;
                document.getElementById('dispPhone').textContent = phone;
                document.getElementById('dispPhoneBody').textContent = phone;
                document.getElementById('dispDistrict').textContent = district;
                document.getElementById('dispDistrictBody').textContent = district;

                const farmName = document.getElementById('inputFarmName').value.trim();
                const location = document.getElementById('inputLocation').value.trim();
                const primaryCrops = document.getElementById('inputPrimaryCrops').value.trim();

                document.getElementById('dispFarmName').textContent = farmName || 'Not Specified';
                document.getElementById('dispLocation').textContent = location || 'Not Specified';
                document.getElementById('dispPrimaryCrops').textContent = primaryCrops || 'Not Specified';

                toggleEditState(false);
            } else {
                showToast(result.error || 'Failed to save profile.', 'error');
            }
        } catch (err) {
            showToast('An unexpected server error occurred.', 'error');
        } finally {
            btnSaveProfile.disabled = false;
            btnSaveProfile.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Changes';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

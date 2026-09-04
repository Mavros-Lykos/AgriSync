<?php
/**
 * AgriSync — Automated KYC Registration Validation Test Suite
 * Tests NIC (Farmer) and BRN (Business) validation rules according to strict SRS guidelines.
 */

echo "=======================================================\n";
echo "    AgriSync KYC Registration Validation Test Suite    \n";
echo "=======================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertKycTest(bool $condition, string $testName) {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] {$testName}\n";
        $passCount++;
    } else {
        echo "  [FAIL] {$testName}\n";
        $failCount++;
    }
}

// Validation function matching auth/register.php & api/auth.php logic
function validateKycRegistration(string $role, ?string $nic, ?string $brn): array {
    $nic = trim((string)$nic);
    $brn = trim((string)$brn);

    if ($role === 'farmer') {
        if (empty($nic)) {
            return ['valid' => false, 'error' => 'National Identity Card (NIC) number is required for farmer registration.'];
        }
        if (!preg_match('/^([0-9]{9}[xXvV]|[0-9]{12})$/', $nic)) {
            return ['valid' => false, 'error' => 'Invalid Sri Lankan NIC number format. Please provide a valid 9-digit (with V/X) or 12-digit NIC.'];
        }
        return ['valid' => true, 'nic_number' => $nic, 'business_reg_no' => null];
    } elseif ($role === 'business') {
        if (empty($brn)) {
            return ['valid' => false, 'error' => 'Business Registration Number (BRN) is required for commercial buyer registration.'];
        }
        return ['valid' => true, 'nic_number' => null, 'business_reg_no' => $brn];
    }

    return ['valid' => false, 'error' => 'Invalid account type selected.'];
}

// 1. Farmer Registration Tests
echo "1. Testing Farmer NIC Registration Validation...\n";

// Case 1.1: Missing NIC
$res1 = validateKycRegistration('farmer', '', null);
assertKycTest($res1['valid'] === false, "Missing NIC is rejected");

// Case 1.2: Invalid NIC format (short digit count)
$res2 = validateKycRegistration('farmer', '12345', null);
assertKycTest($res2['valid'] === false, "Malformed short NIC ('12345') is rejected");

// Case 1.3: Invalid NIC format (letters inside)
$res3 = validateKycRegistration('farmer', '90123ABCDV', null);
assertKycTest($res3['valid'] === false, "Invalid NIC with non-digits ('90123ABCDV') is rejected");

// Case 1.4: Valid 9-digit + V NIC (Old format)
$res4 = validateKycRegistration('farmer', '901234567V', null);
assertKycTest($res4['valid'] === true && $res4['nic_number'] === '901234567V', "Valid 9-digit+V NIC ('901234567V') is accepted");

// Case 1.5: Valid 9-digit + X NIC (Old format)
$res5 = validateKycRegistration('farmer', '851234567X', null);
assertKycTest($res5['valid'] === true && $res5['nic_number'] === '851234567X', "Valid 9-digit+X NIC ('851234567X') is accepted");

// Case 1.6: Valid 12-digit NIC (New format)
$res6 = validateKycRegistration('farmer', '199012345678', null);
assertKycTest($res6['valid'] === true && $res6['nic_number'] === '199012345678', "Valid 12-digit new format NIC ('199012345678') is accepted");

// 2. Business Registration Tests
echo "\n2. Testing Commercial Buyer BRN Registration Validation...\n";

// Case 2.1: Missing BRN
$res7 = validateKycRegistration('business', null, '');
assertKycTest($res7['valid'] === false, "Missing BRN is rejected");

// Case 2.2: Valid BRN
$res8 = validateKycRegistration('business', null, 'PV12345');
assertKycTest($res8['valid'] === true && $res8['business_reg_no'] === 'PV12345', "Valid BRN ('PV12345') is accepted");

echo "\n=======================================================\n";
echo "Tests Passed: {$passCount} | Tests Failed: {$failCount}\n";
echo "=======================================================\n";

if ($failCount === 0) {
    echo "KYC REGISTRATION VALIDATION VERIFICATION SUCCESSFUL!\n";
    exit(0);
} else {
    echo "KYC REGISTRATION VALIDATION VERIFICATION FAILED!\n";
    exit(1);
}

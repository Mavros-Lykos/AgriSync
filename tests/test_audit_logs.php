<?php
/**
 * AgriSync — Automated Admin Audit Logs Test Suite
 * Verifies that mutating admin actions (deactivating/activating users) generate immutable audit log entries.
 */

$_SERVER['REMOTE_ADDR'] = '192.168.1.100';

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=======================================================\n";
echo "       AgriSync Admin Audit Log Test Suite             \n";
echo "=======================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertAuditTest(bool $condition, string $testName) {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] {$testName}\n";
        $passCount++;
    } else {
        echo "  [FAIL] {$testName}\n";
        $failCount++;
    }
}

// Mock PDO for audit log testing
class TestAuditMockDb extends PDO {
    public array $insertedLogs = [];
    public function __construct() {}
    public function prepare($statement, $options = []): TestAuditMockStatement {
        return new TestAuditMockStatement($statement, $this);
    }
}

class TestAuditMockStatement extends PDOStatement {
    private string $sql;
    private TestAuditMockDb $dbRef;
    private array $params = [];
    public function __construct(string $sql, TestAuditMockDb $dbRef) {
        $this->sql = $sql;
        $this->dbRef = $dbRef;
    }
    public function execute(?array $params = null): bool {
        $this->params = $params ?? [];
        if (stripos($this->sql, 'INSERT INTO admin_audit_logs') !== false) {
            $this->dbRef->insertedLogs[] = [
                'admin_id'   => $this->params[0] ?? null,
                'action'     => $this->params[1] ?? null,
                'target_id'  => $this->params[2] ?? null,
                'details'    => $this->params[3] ?? null,
                'ip_address' => $this->params[4] ?? null,
            ];
        }
        return true;
    }
    public function rowCount(): int {
        return 1;
    }
    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed {
        if (stripos($this->sql, 'SELECT') !== false && stripos($this->sql, 'users') !== false) {
            return [
                'name' => 'Demo Target Farmer',
                'is_active' => 1
            ];
        }
        return false;
    }
}

// Test 1: Function logAdminAudit helper
echo "1. Testing logAdminAudit() helper function...\n";
$mockDb = new TestAuditMockDb();

$adminId = 8;
$targetId = 4;
$action = 'deactivate_user';
$details = "Admin #8 deactivated user account #4 (Demo Target Farmer).";

$success = logAdminAudit($adminId, $action, $targetId, $details, $mockDb);

assertAuditTest($success === true, "logAdminAudit() returns true on successful insert");
assertAuditTest(count($mockDb->insertedLogs) === 1, "Exactly 1 audit log entry was inserted");

$lastLog = $mockDb->insertedLogs[0] ?? [];
assertAuditTest($lastLog['admin_id'] === 8, "Captured correct admin_id (#8)");
assertAuditTest($lastLog['action'] === 'deactivate_user', "Captured correct action ('deactivate_user')");
assertAuditTest($lastLog['target_id'] === 4, "Captured correct target_id (#4)");
assertAuditTest($lastLog['ip_address'] === '192.168.1.100', "Captured client IP address ('192.168.1.100')");
assertAuditTest(str_contains($lastLog['details'], 'deactivated user account #4'), "Captured action details");

// Test 2: Activation logging
echo "\n2. Testing activation log audit recording...\n";
$successActivate = logAdminAudit(8, 'activate_user', 4, "Admin #8 activated user account #4.", $mockDb);
assertAuditTest($successActivate === true, "logAdminAudit() recorded activation action");
assertAuditTest(count($mockDb->insertedLogs) === 2, "Total audit log count is 2");

$latestLog = $mockDb->insertedLogs[1] ?? [];
assertAuditTest($latestLog['action'] === 'activate_user', "Captured activation action");

echo "\n=======================================================\n";
echo "Tests Passed: {$passCount} | Tests Failed: {$failCount}\n";
echo "=======================================================\n";

if ($failCount === 0) {
    echo "ADMIN AUDIT LOGGING VERIFICATION SUCCESSFUL!\n";
    exit(0);
} else {
    echo "ADMIN AUDIT LOGGING VERIFICATION FAILED!\n";
    exit(1);
}

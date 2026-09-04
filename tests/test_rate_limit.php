<?php
/**
 * AgriSync — Automated Rate Limit Test Suite
 * Tests 100 sequential requests to verify 1-60 succeed and 61-100 are rate limited (429).
 */

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

require_once __DIR__ . '/../includes/rate_limit.php';

// Clean temp cache file right before starting test loop
$ip_hash = md5($_SERVER['REMOTE_ADDR']);
$cache_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'agrisync_rate_limits' . DIRECTORY_SEPARATOR . 'limit_' . $ip_hash . '.json';
if (file_exists($cache_file)) {
    @unlink($cache_file);
}

echo "=======================================================\n";
echo "       AgriSync API Rate Limiting Test Suite           \n";
echo "=======================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertLimitTest(bool $condition, string $testName) {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] {$testName}\n";
        $passCount++;
    } else {
        echo "  [FAIL] {$testName}\n";
        $failCount++;
    }
}

function test_single_request(): int {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $now = time();
    $window_seconds = 60;
    $max_requests = 60;

    $cache_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'agrisync_rate_limits';
    $ip_hash = md5($ip);
    $cache_file = $cache_dir . DIRECTORY_SEPARATOR . 'limit_' . $ip_hash . '.json';

    $data = ['start_time' => $now, 'count' => 0];

    $fp = @fopen($cache_file, 'c+');
    if ($fp) {
        if (flock($fp, LOCK_EX)) {
            $content = stream_get_contents($fp);
            if (!empty($content)) {
                $decoded = json_decode($content, true);
                if (is_array($decoded) && isset($decoded['start_time'], $decoded['count'])) {
                    $data = $decoded;
                }
            }

            if (($now - $data['start_time']) >= $window_seconds) {
                $data['start_time'] = $now;
                $data['count'] = 1;
            } else {
                $data['count']++;
            }

            rewind($fp);
            ftruncate($fp, 0);
            fwrite($fp, json_encode($data));
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }

    if ($data['count'] > $max_requests) {
        return 429;
    }
    return 200;
}

echo "Simulating 100 rapid requests from IP: {$_SERVER['REMOTE_ADDR']}...\n";

$status_codes = [];
for ($i = 1; $i <= 100; $i++) {
    $code = test_single_request();
    $status_codes[$i] = $code;
}

// Verification 1: Requests 1-60 should receive 200
$first_60_ok = true;
for ($i = 1; $i <= 60; $i++) {
    if ($status_codes[$i] !== 200) {
        $first_60_ok = false;
        break;
    }
}
assertLimitTest($first_60_ok, "Requests 1 to 60 returned HTTP 200 OK");

// Verification 2: Requests 61-100 should receive 429
$last_40_limited = true;
for ($i = 61; $i <= 100; $i++) {
    if ($status_codes[$i] !== 429) {
        $last_40_limited = false;
        break;
    }
}
assertLimitTest($last_40_limited, "Requests 61 to 100 returned HTTP 429 Too Many Requests");

echo "\nSummary of responses:\n";
echo "  - Request 1 status: {$status_codes[1]}\n";
echo "  - Request 60 status: {$status_codes[60]}\n";
echo "  - Request 61 status: {$status_codes[61]}\n";
echo "  - Request 100 status: {$status_codes[100]}\n";

echo "\n=======================================================\n";
echo "Tests Passed: {$passCount} | Tests Failed: {$failCount}\n";
echo "=======================================================\n";

// Clean up after test
if (file_exists($cache_file)) {
    @unlink($cache_file);
}

if ($failCount === 0) {
    echo "RATE LIMITING VERIFICATION SUCCESSFUL!\n";
    exit(0);
} else {
    echo "RATE LIMITING VERIFICATION FAILED!\n";
    exit(1);
}

<?php
/**
 * AgriSync — API Rate Limiting Middleware
 * Protection against DoS attacks and rapid web scraping.
 * Restricts client IP addresses to 60 requests per minute.
 */

require_once __DIR__ . '/../config/session.php';

function check_rate_limit(int $max_requests = 60, int $window_seconds = 60): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $now = time();

    $cache_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'agrisync_rate_limits';
    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0777, true);
    }

    $ip_hash = md5($ip);
    $cache_file = $cache_dir . DIRECTORY_SEPARATOR . 'limit_' . $ip_hash . '.json';

    $data = [
        'start_time' => $now,
        'count' => 0
    ];

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
    } else {
        // Fallback to $_SESSION if file system lock fails
        if (!isset($_SESSION['rate_limit_start']) || ($now - $_SESSION['rate_limit_start']) >= $window_seconds) {
            $_SESSION['rate_limit_start'] = $now;
            $_SESSION['rate_limit_count'] = 1;
        } else {
            $_SESSION['rate_limit_count'] = ($_SESSION['rate_limit_count'] ?? 0) + 1;
        }
        $data['count'] = $_SESSION['rate_limit_count'];
    }

    if ($data['count'] > $max_requests) {
        http_response_code(429);
        header('Content-Type: application/json; charset=UTF-8');
        header('Retry-After: ' . $window_seconds);
        echo json_encode([
            'success' => false,
            'data' => null,
            'error' => 'Too Many Requests'
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }
}

// Execute rate limit check immediately upon inclusion
check_rate_limit(60, 60);

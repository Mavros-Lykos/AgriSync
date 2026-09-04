<?php
/**
 * AgriSync — Demand Prediction API Endpoint (TASK-066 / Issue #41)
 * GET/POST /api/predict_demand.php
 * Triggers the AI Demand Prediction Agent for a given crop_type & district
 */

header('Content-Type: application/json; charset=UTF-8');
@set_time_limit(60);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../agents/demand_agent.php';

// Accept GET query parameters or POST JSON/form data
$cropType = $_GET['crop_type'] ?? null;
$district = $_GET['district'] ?? null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $rawInput = file_get_contents('php://input');
    $inputData = json_decode($rawInput, true);

    if (is_array($inputData)) {
        $cropType = $inputData['crop_type'] ?? $cropType;
        $district = $inputData['district'] ?? $district;
    } else {
        $cropType = $_POST['crop_type'] ?? $cropType;
        $district = $_POST['district'] ?? $district;
    }
}

$cropType = trim((string) $cropType);
$district = trim((string) $district);

if (empty($cropType)) {
    jsonResponse(false, null, 'Parameter "crop_type" is required.', 400);
}

if (empty($district)) {
    $district = 'Dambulla'; // Default agricultural hub
}

try {
    $agent = new DemandAgent();
    $result = $agent->predict($cropType, $district);

    if (!$result['success']) {
        jsonResponse(false, null, $result['error'] ?? 'Prediction failed.', 400);
    }

    jsonResponse(true, $result, null, 200);

} catch (Throwable $e) {
    jsonResponse(false, null, 'Server error executing Demand Prediction Agent: ' . $e->getMessage(), 500);
}

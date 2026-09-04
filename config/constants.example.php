<?php
// AgriSync Application Constants Configuration Template
// Copy this file to config/constants.php and configure your environment

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'agrisync');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Google Gemini AI Configuration
define('GEMINI_API_KEY', 'your-gemini-api-key-here');
define('GEMINI_MODEL', 'gemini-2.5-flash');
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/');

// Dynamic Application URL Resolution
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
$appDir = str_replace('\\', '/', dirname(__DIR__));
$subDir = '';
if (!empty($docRoot) && str_starts_with($appDir, $docRoot)) {
    $subDir = substr($appDir, strlen($docRoot));
}
define('APP_URL', rtrim($protocol . $host . $subDir, '/'));

// Application Settings
define('APP_NAME', 'AgriSync');
define('FAIR_TRADE_MIN_MULTIPLIER', 1.2); // Minimum 20% margin above base cost
define('APP_ENV', 'development'); // 'development' or 'production'

// PayHere Payment Gateway Settings
define('PAYHERE_MERCHANT_ID', '1220000'); // Merchant Sandbox ID
define('PAYHERE_MERCHANT_SECRET', '4Mx8365287415493218526541598452'); // Merchant Secret Key
define('PAYHERE_MODE', 'sandbox'); // 'sandbox' or 'live'
define('PAYHERE_CURRENCY', 'LKR');

// Standardized Crop Catalog
define('AGRISYNC_CROPS', [
    'Tomato', 'Carrot', 'Big Onion', 'Bell Pepper', 'Potato', 
    'Cabbage', 'Leeks', 'Green Beans', 'Green Chili', 'Banana', 
    'Papaya', 'Pumpkin', 'Brinjal'
]);

<?php
/**
 * AgriSync - Local Database Reset Utility
 * Quickly drops, recreates, and seeds the local database for testing.
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

// Security check: Only allow this script to run on localhost
$is_local = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) || php_sapi_name() === 'cli';
if (!$is_local) {
    http_response_code(403);
    die("<h1>403 Forbidden</h1><p>This utility can only be executed on a local environment.</p>");
}

echo "<h2>AgriSync - Local Database Reset Utility</h2>";

try {
    $db = getDbConnection();
    
    echo "<ul>";
    
    // 1. Run Schema
    $schema = file_get_contents(__DIR__ . '/sql/schema.sql');
    if ($schema) {
        $db->exec($schema);
        echo "<li style='color:green'>Schema executed successfully (tables dropped and recreated).</li>";
    } else {
        throw new Exception("Could not read sql/schema.sql");
    }
    
    // 2. Run Seed
    $seed = file_get_contents(__DIR__ . '/sql/seed.sql');
    if ($seed) {
        $db->exec($seed);
        echo "<li style='color:green'>Seed data executed successfully (dummy users, matches, listings injected).</li>";
    } else {
        throw new Exception("Could not read sql/seed.sql");
    }
    
    echo "</ul>";
    echo "<h3>✅ Database fully reset and ready for testing!</h3>";
    echo "<a href='index.php'>Go to Homepage</a>";

} catch (PDOException $e) {
    echo "<p style='color:red'><strong>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

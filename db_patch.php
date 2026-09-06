<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

echo "<h2>AgriSync DB Migration Patch</h2>";

try {
    $db = getDbConnection();
    
    $queries = [
        "ALTER TABLE harvest_listings ADD COLUMN min_order_quantity decimal(10,2) NOT NULL DEFAULT 0.00 AFTER quantity_kg",
        "ALTER TABLE harvest_listings ADD COLUMN quantity_reserved decimal(10,2) NOT NULL DEFAULT 0.00 AFTER min_order_quantity",
        "ALTER TABLE order_requests ADD COLUMN min_delivery_qty decimal(10,2) NOT NULL DEFAULT 0.00 AFTER quantity_kg"
    ];
    
    foreach ($queries as $q) {
        try {
            $db->exec($q);
            echo "<p style='color:green'>Success: $q</p>";
        } catch (PDOException $e) {
            // 1060 = Duplicate column name
            if ($e->getCode() == '42S21' || strpos($e->getMessage(), '1060') !== false) {
                echo "<p style='color:orange'>Skipped (Already exists): $q</p>";
            } else {
                echo "<p style='color:red'>Error: " . $e->getMessage() . " on query: $q</p>";
            }
        }
    }
    
    echo "<h3>Done! You can now safely delete this file.</h3>";
} catch (Exception $e) {
    echo "<p style='color:red'>Connection Error: " . $e->getMessage() . "</p>";
}

<?php
// Check current database state
require_once 'db.php';

echo "=== CURRENT DATABASE STATE ===\n\n";

// Get all tables
$result = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

echo "Total tables: " . count($tables) . "\n\n";
echo "Tables:\n";
foreach ($tables as $table) {
    // Get row count
    $countResult = $conn->query("SELECT COUNT(*) as cnt FROM `$table`");
    $count = $countResult->fetch_assoc()['cnt'];
    echo "  - $table ($count rows)\n";
}

echo "\n=== DATABASE CHECK COMPLETE ===\n";
?>

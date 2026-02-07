<?php
require_once 'db.php';

echo "=== Checking consultations table structure ===\n\n";

$result = $conn->query("DESCRIBE consultations");
$columns = [];

echo "Existing columns:\n";
while($row = $result->fetch_assoc()) {
    echo "  - {$row['Field']} ({$row['Type']})\n";
    $columns[] = $row['Field'];
}

echo "\n";

// Check if updated_at exists
if (!in_array('updated_at', $columns)) {
    echo "❌ 'updated_at' column does NOT exist\n";
    echo "\nAdding 'updated_at' column...\n";
    
    $alterResult = $conn->query("
        ALTER TABLE consultations 
        ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ");
    
    if ($alterResult) {
        echo "✅ Successfully added 'updated_at' column\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
} else {
    echo "✅ 'updated_at' column exists\n";
}

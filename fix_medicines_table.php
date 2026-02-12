<?php
require_once 'db.php';

echo "Checking medicines table structure...\n\n";

// Get current structure
$result = $conn->query("DESCRIBE medicines");
$existing_columns = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
        echo "{$row['Field']} | {$row['Type']}\n";
    }
} else {
    echo "Table doesn't exist or error: " . $conn->error . "\n";
}

// Define required columns
$required_columns = ['generic_name', 'category', 'manufacturer', 'unit', 'requires_prescription'];

echo "\n\nAdding missing columns...\n";

foreach ($required_columns as $col) {
    if (!in_array($col, $existing_columns)) {
        if ($col === 'generic_name' || $col === 'category' || $col === 'manufacturer') {
            $sql = "ALTER TABLE medicines ADD COLUMN `$col` VARCHAR(255) NULL";
        } elseif ($col === 'unit') {
            $sql = "ALTER TABLE medicines ADD COLUMN `unit` VARCHAR(50) DEFAULT 'tablets'";
        } elseif ($col === 'requires_prescription') {
            $sql = "ALTER TABLE medicines ADD COLUMN `requires_prescription` BOOLEAN DEFAULT TRUE";
        }
        
        if ($conn->query($sql)) {
            echo "✓ Added column: $col\n";
        } else {
            echo "✗ Failed to add $col: " . $conn->error . "\n";
        }
    } else {
        echo "✓ Column $col exists\n";
    }
}

echo "\n✓ Medicines table structure updated!\n";
?>

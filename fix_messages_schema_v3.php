<?php
// Add missing columns to messages table
require_once 'db.php';

echo "=== Updating messages table schema v3 ===\n\n";

$columns_to_add = [
    'read_at' => "TIMESTAMP NULL DEFAULT NULL AFTER is_read"
];

foreach ($columns_to_add as $col => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM messages LIKE '$col'");
    if ($check->num_rows > 0) {
        echo "✓ Column '$col' already exists\n";
    } else {
        echo "Adding column '$col'...\n";
        $sql = "ALTER TABLE messages ADD COLUMN $col $definition";
        if ($conn->query($sql)) {
            echo "✓ Column '$col' added successfully\n";
        } else {
            echo "✗ Failed to add column '$col': " . $conn->error . "\n";
        }
    }
}

echo "\nDone!\n";

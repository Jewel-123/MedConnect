<?php
// Add consultation_id column to messages table
require_once 'db.php';

echo "=== Adding consultation_id to messages table ===\n\n";

// Check if column already exists
$check = $conn->query("SHOW COLUMNS FROM messages LIKE 'consultation_id'");
if ($check->num_rows > 0) {
    echo "✓ consultation_id column already exists\n";
} else {
    echo "Adding consultation_id column...\n";
    $conn->query("
        ALTER TABLE messages 
        ADD COLUMN consultation_id INT NULL AFTER id,
        ADD INDEX idx_consultation (consultation_id)
    ");
    echo "✓ consultation_id column added successfully\n";
}

echo "\nDone!\n";

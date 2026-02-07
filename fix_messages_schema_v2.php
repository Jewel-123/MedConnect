<?php
// Add missing columns to messages table
require_once 'db.php';

echo "=== Updating messages table schema ===\n\n";

$columns_to_add = [
    'consultation_id' => "INT NULL AFTER id",
    'message_type' => "VARCHAR(20) DEFAULT 'text' AFTER message_content"
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
            if ($col === 'consultation_id') {
                $conn->query("ALTER TABLE messages ADD INDEX idx_consultation (consultation_id)");
                echo "✓ Index added for consultation_id\n";
            }
        } else {
            echo "✗ Failed to add column '$col': " . $conn->error . "\n";
        }
    }
}

echo "\nDone!\n";

<?php
// Add 'accepted' status to consultations enum
require_once 'db.php';

echo "=== Adding 'accepted' Status to Consultations ===\n\n";

// Add 'accepted' to the status enum
$result = $conn->query("
    ALTER TABLE consultations 
    MODIFY COLUMN status ENUM('pending','accepted','in_progress','paused','completed','cancelled','declined') 
    DEFAULT 'pending'
");

if ($result) {
    echo "✅ Successfully added 'accepted' status to consultations table\n";
    
    // Verify
    $check = $conn->query("SHOW COLUMNS FROM consultations WHERE Field='status'");
    $row = $check->fetch_assoc();
    echo "\nNew enum values:\n";
    echo $row['Type'] . "\n";
} else {
    echo "❌ Error: " . $conn->error . "\n";
}

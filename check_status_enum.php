<?php
require_once 'db.php';

echo "=== Checking consultations table status enum ===\n\n";

$result = $conn->query("SHOW COLUMNS FROM consultations WHERE Field='status'");
$row = $result->fetch_assoc();

echo "Current status enum:\n";
echo $row['Type'] . "\n\n";

// Check if 'accepted' status exists
if (strpos($row['Type'], 'accepted') !== false) {
    echo "✅ 'accepted' status already exists\n";
} else {
    echo "❌ 'accepted' status does NOT exist\n";
    echo "Need to add it to the enum\n";
}

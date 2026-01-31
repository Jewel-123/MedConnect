<?php
/**
 * Quick database connectivity test for chat
 */
require_once 'db.php';

// Check messages table
$result = $conn->query("SELECT COUNT(*) as count FROM messages");
if ($result) {
    $row = $result->fetch_assoc();
    echo "✓ Messages table exists ({$row['count']} messages)";
} else {
    echo "✗ Messages table NOT found: " . $conn->error;
}
?>

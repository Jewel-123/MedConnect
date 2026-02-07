<?php
// Test chat message insertion
require_once 'db.php';

echo "=== Testing chat message insertion ===\n\n";

$consultation_id = 18; // Known consultation ID
$sender_id = 29;       // Dr. Sophia Martinez
$receiver_id = 21;     // JEWEL BIJU
$content = "Test message for verification";
$type = "text";

$stmt = $conn->prepare("
    INSERT INTO messages (consultation_id, sender_id, receiver_id, message_content, message_type)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param("iiiss", $consultation_id, $sender_id, $receiver_id, $content, $type);

if ($stmt->execute()) {
    echo "✓ Test message sent successfully (ID: " . $stmt->insert_id . ")\n";
    // Check if it's searchable
    $check = $conn->query("SELECT * FROM messages WHERE id = " . $stmt->insert_id)->fetch_assoc();
    echo "  Retrieved content: " . $check['message_content'] . "\n";
    echo "  Retrieved type: " . $check['message_type'] . "\n";
} else {
    echo "✗ Failed to send test message: " . $conn->error . "\n";
}

echo "\nDone!\n";

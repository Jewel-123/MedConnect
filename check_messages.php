<?php
// Check if the message from Dr. Emily Smith exists for this consultation
require_once 'db.php';

$consultationId = 18; // The consultation we've been working with

echo "=== Checking Messages for Consultation #$consultationId ===\n\n";

$messages = $conn->query("
    SELECT m.*, 
           u_sender.full_name as sender_name,
           u_receiver.full_name as receiver_name
    FROM messages m
    LEFT JOIN users u_sender ON m.sender_id = u_sender.id
    LEFT JOIN users u_receiver ON m.receiver_id = u_receiver.id
    WHERE m.consultation_id = $consultationId
    ORDER BY m.created_at ASC
")->fetch_all(MYSQLI_ASSOC);

if (count($messages) > 0) {
    echo "Found " . count($messages) . " message(s):\n\n";
    foreach ($messages as $msg) {
        echo "Message #{$msg['id']}:\n";
        echo "  From: {$msg['sender_name']} (ID: {$msg['sender_id']})\n";
        echo "  To: {$msg['receiver_name']} (ID: {$msg['receiver_id']})\n";
        echo "  Message: {$msg['message_text']}\n";
        echo "  Time: {$msg['created_at']}\n\n";
    }
    echo "✅ Messages exist and should display in the chat!\n";
} else {
    echo "❌ No messages found for this consultation.\n";
    echo "The chat will be empty until messages are sent.\n";
}

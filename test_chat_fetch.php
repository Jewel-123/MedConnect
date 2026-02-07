<?php
/**
 * Test if message fetching is working correctly
 */

session_start();
require_once 'db.php';

echo "===== CHAT FETCH TEST =====\n\n";

// Get latest messages
$result = $conn->query("
    SELECT id, consultation_id, sender_id, receiver_id, message_content, message_type, created_at 
    FROM messages 
    ORDER BY id DESC 
    LIMIT 10
");

echo "Last 10 messages in database:\n\n";
while ($row = $result->fetch_assoc()) {
    echo sprintf(
        "[%d] Consult:%d | %d→%d | Type:%s | %s | %s\n",
        $row['id'],
        $row['consultation_id'],
        $row['sender_id'],
        $row['receiver_id'],
        $row['message_type'],
        substr($row['message_content'], 0, 30),
        $row['created_at']
    );
}

echo "\n===== TESTING FETCH API =====\n\n";

// Simulate being logged in as user 2 (doctor)
$_SESSION['user_id'] = 2;

// Test fetching for a specific consultation
$test_consultation_id = 7; // From the console screenshot

echo "Fetching messages for consultation $test_consultation_id as user 2...\n";

$_GET['action'] = 'fetch';
$_GET['consultation_id'] = $test_consultation_id;
$_GET['last_id'] = 0; // Get all messages

// Capture the API response
ob_start();
include 'chat_api.php';
$response = ob_get_clean();

echo "API Response:\n";
echo $response . "\n\n";

$data = json_decode($response, true);
if ($data && $data['success']) {
    echo "✓ Fetch successful! Message count: " . count($data['messages']) . "\n";
    
    if (count($data['messages']) > 0) {
        echo "\nMessages returned:\n";
        foreach ($data['messages'] as $msg) {
            echo sprintf(
                "  [%d] From:%d | %s | %s\n",
                $msg['id'],
                $msg['sender_id'],
                substr($msg['message_content'], 0, 30),
                $msg['created_at']
            );
        }
    } else {
        echo "\n⚠ No messages returned (but query was successful)\n";
    }
} else {
    echo "✗ Fetch failed: " . ($data['error'] ?? 'Unknown error') . "\n";
}

echo "\n===== TEST COMPLETE =====\n";
<?php
/**
 * Test Chat API
 * Quick test to verify chat sending and receiving messages
 */

session_start();
require_once 'db.php';

// Fake login as patient (ID 5, Jewel Biju from the screenshot)
$_SESSION['user_id'] = 5;
$_SESSION['role'] = 'patient';

echo "===== CHAT API TEST =====\n\n";

// Test 1: Check if messages table exists
echo "1. Checking messages table...\n";
$result = $conn->query("DESCRIBE messages");
if ($result) {
    echo "✓ Messages table exists\n";
    echo "  Columns: ";
    $cols = [];
    while ($row = $result->fetch_assoc()) {
        $cols[] = $row['Field'];
    }
    echo implode(', ', $cols) . "\n\n";
} else {
    die("✗ Messages table NOT found!\n");
}

// Test 2: Find an active consultation for user 5
echo "2. Finding active consultation...\n";
$stmt = $conn->prepare("SELECT id, patient_id, doctor_id, status FROM consultations WHERE patient_id = 5 OR doctor_id = 5 LIMIT 1");
$stmt->execute();
$consultation = $stmt->get_result()->fetch_assoc();

if ($consultation) {
    echo "✓ Found consultation ID: {$consultation['id']}\n";
    echo "  Patient ID: {$consultation['patient_id']}, Doctor ID: {$consultation['doctor_id']}, Status: {$consultation['status']}\n\n";
    $consultation_id = $consultation['id'];
    $receiver_id = ($consultation['patient_id'] == 5) ? $consultation['doctor_id'] : $consultation['patient_id'];
} else {
    die("✗ No consultation found for user 5\n");
}

// Test 3: Send a test message via API
echo "3. Sending test message via chat_api.php...\n";
$_POST['action'] = 'send';
$_POST['consultation_id'] = $consultation_id;
$_POST['content'] = 'Test message from automated test at ' . date('H:i:s');
$_POST['receiver_id'] = $receiver_id;
$_POST['type'] = 'text';

ob_start();
include 'chat_api.php';
$response = ob_get_clean();

echo "  Response: $response\n";
$data = json_decode($response, true);

if ($data && $data['success']) {
    echo "✓ Message sent successfully! Message ID: {$data['message_id']}\n\n";
    $message_id = $data['message_id'];
} else {
    echo "✗ Failed to send message\n";
    echo "  Error: " . ($data['error'] ?? 'Unknown') . "\n\n";
    exit;
}

// Test 4: Fetch messages
echo "4. Fetching messages via chat_api.php...\n";
unset($_POST);
$_GET['action'] = 'fetch';
$_GET['consultation_id'] = $consultation_id;
$_GET['last_id'] = 0;

ob_start();
include 'chat_api.php';
$response = ob_get_clean();

echo "  Response: $response\n";
$data = json_decode($response, true);

if ($data && $data['success']) {
    echo "✓ Messages fetched successfully! Count: " . count($data['messages']) . "\n\n";
    
    if (count($data['messages']) > 0) {
        echo "Recent messages:\n";
        foreach (array_slice($data['messages'], -3) as $msg) {
            echo "  - [{$msg['id']}] {$msg['message_content']} (from user {$msg['sender_id']})\n";
        }
    }
} else {
    echo "✗ Failed to fetch messages\n";
    echo "  Error: " . ($data['error'] ?? 'Unknown') . "\n";
}

echo "\n===== TEST COMPLETE =====\n";
?>

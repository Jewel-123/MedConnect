<?php
// Simple direct database query test
require_once 'db.php';

echo "Testing message retrieval directly from database...\n\n";

$consultation_id = 7;
$last_id = 0;

$stmt = $conn->prepare("
    SELECT id, sender_id, receiver_id, message_content, message_type, created_at  
    FROM messages
    WHERE consultation_id = ? AND id > ?
    ORDER BY created_at ASC
");

$stmt->bind_param("ii", $consultation_id, $last_id);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo "Messages found: " . count($messages) . "\n\n";

foreach ($messages as $msg) {
    echo sprintf(
        "[%d] %d→%d | %s | %s\n",
        $msg['id'],
        $msg['sender_id'],
        $msg['receiver_id'],
        substr($msg['message_content'], 0, 40),
        $msg['created_at']
    );
}

// Test JSON encoding
$json = json_encode(['success' => true, 'messages' => $messages]);
echo "\n\nJSON encoded successfully: " . (strlen($json) > 0 ? "✓" : "✗") . "\n";
echo "JSON length: " . strlen($json) . " bytes\n";

if (strlen($json) < 500) {
    echo "\nJSON output:\n$json\n";
}
?>

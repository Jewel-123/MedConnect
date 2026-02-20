<?php
require_once 'db.php';
$res = $conn->query("SELECT * FROM messages WHERE consultation_id = 87");
echo "Found " . $res->num_rows . " messages for ID 87\n";
while($row = $res->fetch_assoc()) {
    echo "Msg #{$row['id']}: Sender {$row['sender_id']} -> {$row['message_content']}\n";
}
?>

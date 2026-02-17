<?php
require_once 'db.php';
$result = $conn->query("SHOW COLUMNS FROM prescriptions_v2 LIKE 'auto_sent_to_pharmacy'");
if ($result->num_rows > 0) {
    echo "Column exists!";
} else {
    echo "Column missing!";
}
$conn->close();
?>

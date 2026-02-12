<?php
require_once 'db.php';
$sql = "ALTER TABLE prescriptions_v2 MODIFY COLUMN status ENUM('draft','issued','sent_to_pharmacy','filled','cancelled','finalized') NOT NULL DEFAULT 'draft'";
if ($conn->query($sql) === TRUE) {
    echo "Table altered successfully\n";
} else {
    echo "Error altering table: " . $conn->error . "\n";
}
?>

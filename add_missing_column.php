<?php
require_once 'db.php';

// Add sent_to_pharmacy_at column
$sql = "ALTER TABLE prescriptions_v2 ADD COLUMN sent_to_pharmacy_at DATETIME NULL AFTER pharmacy_id";

if ($conn->query($sql) === TRUE) {
    echo "Column 'sent_to_pharmacy_at' added successfully\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
}

// Just in case, check existing columns again
$res = $conn->query("SHOW COLUMNS FROM prescriptions_v2");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>

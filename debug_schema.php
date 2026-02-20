<?php
include 'db.php';
$stmt = $conn->query("SHOW CREATE TABLE doctor_reviews");
if ($stmt) {
    $row = $stmt->fetch_row();
    echo $row[1];
} else {
    echo "Error: " . $conn->error;
}
?>

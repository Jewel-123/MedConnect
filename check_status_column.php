<?php
include 'db.php';
$res = $conn->query("SHOW COLUMNS FROM doctor_reviews LIKE 'status'");
if ($res && $row = $res->fetch_assoc()) {
    print_r($row);
} else {
    echo "Column not found or error: " . $conn->error;
}
?>

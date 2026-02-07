<?php
include 'db.php';
$result = $conn->query("DESCRIBE doctor_profiles");
if ($result) {
    echo "Columns in doctor_profiles:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Error describing doctor_profiles: " . $conn->error . "\n";
}
$conn->close();
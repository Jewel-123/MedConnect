<?php
error_reporting(E_ALL);
require_once 'db.php';

$sql = "SHOW TRIGGERS LIKE 'appointments'";
$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "Trigger: " . $row['Trigger'] . "\n";
            echo "Event: " . $row['Event'] . "\n";
            echo "Statement: " . $row['Statement'] . "\n\n";
        }
    } else {
        echo "No triggers found on appointments table.\n";
    }
} else {
    echo "Error showing triggers: " . $conn->error . "\n";
}
?>

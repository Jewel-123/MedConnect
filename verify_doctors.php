<?php
require_once 'db.php';

$sql = "SELECT id, full_name, email, role, status FROM users WHERE role='doctor' AND id BETWEEN 14 AND 25 ORDER BY id ASC";
$result = $conn->query($sql);

echo "--- Doctor Accounts Verification ---\n";
echo str_pad("ID", 5) . " | " . str_pad("Name", 20) . " | " . str_pad("Email", 30) . "\n";
echo str_repeat("-", 60) . "\n";

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo str_pad($row["id"], 5) . " | " . str_pad(substr($row["full_name"], 0, 18), 20) . " | " . str_pad($row["email"], 30) . "\n";
    }
} else {
    echo "0 results found\n";
}
echo str_repeat("-", 60) . "\n";
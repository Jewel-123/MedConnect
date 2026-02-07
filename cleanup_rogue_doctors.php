<?php
require_once 'db.php';

// First, show who we are deleting just for logs
$res = $conn->query("SELECT id, full_name, role FROM users WHERE id BETWEEN 3 AND 13 AND role='doctor'");
while($row = $res->fetch_assoc()) {
    echo "Deleting: " . $row['full_name'] . " (ID: " . $row['id'] . ")\n";
}

// Delete from doctor_profiles first (foreign key)
$conn->query("DELETE FROM doctor_profiles WHERE user_id IN (SELECT id FROM users WHERE id BETWEEN 3 AND 13 AND role='doctor')");

// Delete from users
$conn->query("DELETE FROM users WHERE id BETWEEN 3 AND 13 AND role='doctor'");

echo "Cleanup complete.\n";
<?php
require_once 'db.php';

echo "Searching for 'Michael Chen'...\n";
$stmt = $conn->prepare("SELECT id, full_name, email, role FROM users WHERE full_name LIKE ?");
$term = '%Michael Chen%';
$stmt->bind_param("s", $term);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        echo "Found: ID: " . $row['id'] . " | Name: " . $row['full_name'] . " | Role: " . $row['role'] . "\n";
        
        // Check profile
        $prof = $conn->query("SELECT * FROM doctor_profiles WHERE user_id = " . $row['id']);
        if ($profRow = $prof->fetch_assoc()) {
            echo "  - Specialty: " . $profRow['specialization'] . "\n";
        }
    }
} else {
    echo "No user found matching 'Michael Chen'.\n";
}

echo "\nChecking for other General Physicians:\n";
$gp = $conn->query("SELECT u.full_name, u.id FROM users u JOIN doctor_profiles dp ON u.id = dp.user_id WHERE dp.specialization = 'General Physician'");
while($row = $gp->fetch_assoc()) {
    echo "- " . $row['full_name'] . " (ID: " . $row['id'] . ")\n";
}
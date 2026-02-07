<?php
require_once 'db.php';

echo "=== CHECKING DATABASE FOR EXISTING DATA ===\n\n";

// Check users
$result = $conn->query("SELECT COUNT(*) as cnt FROM users");
if ($result) {
    $count = $result->fetch_assoc()['cnt'];
    echo "Users table: $count users\n";
    
    if ($count > 0) {
        echo "\nExisting users:\n";
        $users = $conn->query("SELECT id, name, email, role, status FROM users LIMIT 20");
        while ($row = $users->fetch_assoc()) {
            echo "  - {$row['name']} ({$row['email']}) - Role: {$row['role']}, Status: {$row['status']}\n";
        }
    }
} else {
    echo "Error checking users: " . $conn->error . "\n";
}

// Check doctor profiles
$result = $conn->query("SELECT COUNT(*) as cnt FROM doctor_profiles");
if ($result) {
    $count = $result->fetch_assoc()['cnt'];
    echo "\nDoctor profiles: $count doctors\n";
} else {
    echo "\nDoctor profiles table may not exist yet.\n";
}

// Check patient profiles
$result = $conn->query("SELECT COUNT(*) as cnt FROM patient_profiles");
if ($result) {
    $count = $result->fetch_assoc()['cnt'];
    echo "Patient profiles: $count patients\n";
} else {
    echo "Patient profiles table may not exist yet.\n";
}

// Check consultations
$result = $conn->query("SELECT COUNT(*) as cnt FROM consultations");
if ($result) {
    $count = $result->fetch_assoc()['cnt'];
    echo "Consultations: $count consultations\n";
} else {
    echo "Consultations table may not exist yet.\n";
}

echo "\n=== CHECK COMPLETE ===\n";
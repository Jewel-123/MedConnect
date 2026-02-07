<?php
include 'db.php';

// Add google_id column if it doesn't exist
$checkCol = $conn->query("SHOW COLUMNS FROM users LIKE 'google_id'");
if ($checkCol->num_rows == 0) {
    $sql = "ALTER TABLE users ADD COLUMN google_id VARCHAR(255) DEFAULT NULL AFTER role";
    if ($conn->query($sql) === TRUE) {
        echo "Successfully added google_id column to users table.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "google_id column already exists.\n";
}

$conn->close();
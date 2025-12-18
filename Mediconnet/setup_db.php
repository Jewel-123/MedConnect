<?php
// setup_db.php

$servername = "127.0.0.1";
$username = "root";
$password = "";

try {
    // Create connection without selecting DB first
    $conn = new mysqli($servername, $username, $password);
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Read SQL file
$sqlFile = 'medconnect.sql';
if (!file_exists($sqlFile)) {
    die("Error: SQL file not found.");
}

$sqlContent = file_get_contents($sqlFile);

// Execute multi_query
if ($conn->multi_query($sqlContent)) {
    echo "Database setup started...\n";
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
        // Check for errors
        if ($conn->errno) {
            echo "Error executing statement: " . $conn->error . "\n";
        }
    } while ($conn->more_results() && $conn->next_result());
    
    echo "Database setup completed successfully (check for any error messages above).\n";
} else {
    echo "Error executing SQL: " . $conn->error . "\n";
}

$conn->close();
?>

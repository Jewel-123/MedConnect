<?php
/**
 * Comprehensive Database Setup for MedConnect
 * Unifies all schemas and ensures structural integrity of core tables.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(600);

echo "=== MedConnect Comprehensive Database Setup ===\n\n";

require_once 'db.php';

// Function to execute SQL file using multi_query
function executeSqlFile($conn, $filename) {
    echo "Processing $filename...\n";
    if (!file_exists($filename)) {
        echo "  ! Skipping: File not found.\n";
        return false;
    }
    
    $sql = file_get_contents($filename);
    
    // Remove SQL comments to avoid multi_query issues
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    $sql = trim($sql);
    
    if (empty($sql)) {
        echo "  - File is empty or contains only comments.\n";
        return true;
    }

    try {
        if ($conn->multi_query($sql)) {
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());
            
            if ($conn->errno) {
                // Check if it's a "Duplicate" or "Already exists" error - ignore those
                if (stripos($conn->error, 'already exists') === false && stripos($conn->error, 'Duplicate entry') === false) {
                    echo "  ! Error in $filename: " . $conn->error . "\n";
                    return false;
                }
            }
            echo "  ✓ Success.\n";
            return true;
        } else {
            echo "  ! Failed to start multi_query for $filename: " . $conn->error . "\n";
            return false;
        }
    } catch (Exception $e) {
        if (stripos($e->getMessage(), 'already exists') === false && stripos($e->getMessage(), 'Duplicate entry') === false) {
            echo "  ! Exception in $filename: " . $e->getMessage() . "\n";
            return false;
        }
        echo "  ✓ Success (with ignored duplicates).\n";
        return true;
    }
}

// 1. Ensure core columns exist in 'users' table (required by auth.php and signup.php)
echo "Aligning 'users' table structure...\n";
$missing_cols = [
    'phone' => "ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER password",
    'status' => "ALTER TABLE users ADD COLUMN status ENUM('pending_onboarding', 'pending', 'approved', 'rejected') DEFAULT 'pending_onboarding' AFTER phone",
    'is_verified' => "ALTER TABLE users ADD COLUMN is_verified BOOLEAN DEFAULT 0 AFTER status"
];

foreach ($missing_cols as $col => $sql) {
    $check = $conn->query("SHOW COLUMNS FROM users LIKE '$col'");
    if ($check && $check->num_rows == 0) {
        if ($conn->query($sql)) {
            echo "  ✓ Added column: $col\n";
        } else {
            echo "  ! Error adding $col: " . $conn->error . "\n";
        }
    } else {
        echo "  - Column $col already exists.\n";
    }
}

// 2. Re-connect with multi_query enabled (some environments need a fresh connection for multi_query)
$conn->close();
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

// 3. Execute all relevant schema files in order
$schemas = [
    'medconnect.sql',
    'consolidated_database_setup.sql',
    'medical_ai_schema.sql',
    'consultation_chat_schema.sql'
];

foreach ($schemas as $schema) {
    executeSqlFile($conn, $schema);
}

echo "\n=== Setup Complete ===\n";
$conn->close();
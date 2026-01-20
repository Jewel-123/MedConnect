<?php
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = "";     // Default XAMPP password (empty)
$dbname = "medconnect";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Check connection
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database Connection Failed: " . $conn->connect_error]));
}

// Set timezone for MySQL session
$conn->query("SET time_zone = '+05:30'");

// --- AUTO-MIGRATION / SELF-HEALING (TEMPORARILY DISABLED) ---
/*
// 1. Ensure google_id column exists in users
$checkCol = $conn->query("SHOW COLUMNS FROM users LIKE 'google_id'");
...
// 17. Add rating and language to doctor_profiles if missing handled in step 11, but let's ensure experience is there
$checkExp = $conn->query("SHOW COLUMNS FROM doctor_profiles LIKE 'years_experience'");
if ($checkExp && $checkExp->num_rows == 0) {
    $conn->query("ALTER TABLE doctor_profiles ADD COLUMN years_experience INT DEFAULT 0");
}
*/
// -------------------------------------
// -------------------------------------

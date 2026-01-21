<?php
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = "";     // Default XAMPP password (empty)
$dbname = "medconnect";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database Connection Failed: " . $conn->connect_error]));
}

// Set timezone for MySQL session
$conn->query("SET time_zone = '+05:30'");

// --- AUTO-MIGRATION / SELF-HEALING ---
// 1. Ensure google_id column exists in users
$checkCol = $conn->query("SHOW COLUMNS FROM users LIKE 'google_id'");
if ($checkCol && $checkCol->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) DEFAULT NULL AFTER role");
}

// 2. Ensure password_resets table exists
$checkTable = $conn->query("SHOW TABLES LIKE 'password_resets'");
if ($checkTable && $checkTable->num_rows == 0) {
    $conn->query("CREATE TABLE `password_resets` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `email` VARCHAR(100) NOT NULL,
        `token` VARCHAR(255) DEFAULT NULL,
        `otp` VARCHAR(6) DEFAULT NULL,
        `is_verified` BOOLEAN DEFAULT FALSE,
        `expires_at` DATETIME NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

// 3. Add OTP columns if they don't exist (for existing tables)
$checkOtpCol = $conn->query("SHOW COLUMNS FROM password_resets LIKE 'otp'");
if ($checkOtpCol && $checkOtpCol->num_rows == 0) {
    $conn->query("ALTER TABLE password_resets ADD COLUMN otp VARCHAR(6) DEFAULT NULL AFTER token");
}

$checkVerifiedCol = $conn->query("SHOW COLUMNS FROM password_resets LIKE 'is_verified'");
if ($checkVerifiedCol && $checkVerifiedCol->num_rows == 0) {
    $conn->query("ALTER TABLE password_resets ADD COLUMN is_verified BOOLEAN DEFAULT FALSE AFTER otp");
}

$checkTypeCol = $conn->query("SHOW COLUMNS FROM password_resets LIKE 'type'");
if ($checkTypeCol && $checkTypeCol->num_rows == 0) {
    $conn->query("ALTER TABLE password_resets ADD COLUMN type ENUM('reset', 'verify') DEFAULT 'reset' AFTER is_verified");
}

// 4. Make token column nullable if it isn't already
$conn->query("ALTER TABLE password_resets MODIFY COLUMN token VARCHAR(255) DEFAULT NULL");

// 5. Add status column to users table if it doesn't exist
$conn->query("ALTER TABLE users MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'pending_onboarding') DEFAULT 'pending'");

// 6. Add designation column to users table if it doesn't exist
$checkDesignationCol = $conn->query("SHOW COLUMNS FROM users LIKE 'designation'");
if ($checkDesignationCol && $checkDesignationCol->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN designation VARCHAR(50) DEFAULT NULL AFTER status");
}

// 7. Update role enum to include 'staff', 'hospital', and 'clinic' and make it nullable
$conn->query("ALTER TABLE users MODIFY COLUMN role ENUM('patient', 'doctor', 'admin', 'pharmacy', 'staff', 'hospital', 'clinic') NULL");

// 7a. Add is_verified column to users
$checkVerified = $conn->query("SHOW COLUMNS FROM users LIKE 'is_verified'");
if ($checkVerified && $checkVerified->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN is_verified BOOLEAN DEFAULT FALSE AFTER google_id");
}

// 7b. Add phone column to users (if not exists)
$checkPhone = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
if ($checkPhone && $checkPhone->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER email");
}

// 8. Create staff_profiles table if it doesn't exist
$checkStaffTable = $conn->query("SHOW TABLES LIKE 'staff_profiles'");
if ($checkStaffTable && $checkStaffTable->num_rows == 0) {
    $conn->query("CREATE TABLE `staff_profiles` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL UNIQUE,
        `designation` ENUM('receptionist', 'nurse', 'lab_staff', 'canteen_staff', 'pharmacist') NOT NULL,
        `phone` VARCHAR(20),
        `license_number` VARCHAR(50) DEFAULT NULL,
        `documents` JSON DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    )");
}

// 9. Create hospital_profiles table if it doesn't exist
$checkHospitalTable = $conn->query("SHOW TABLES LIKE 'hospital_profiles'");
if ($checkHospitalTable && $checkHospitalTable->num_rows == 0) {
    $conn->query("CREATE TABLE `hospital_profiles` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL UNIQUE,
        `hospital_name` VARCHAR(200) NOT NULL,
        `address` TEXT NOT NULL,
        `phone` VARCHAR(20),
        `license_number` VARCHAR(50) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    )");
}

// 10. Create default admin account if it doesn't exist
$checkAdmin = $conn->query("SELECT id FROM users WHERE email = 'admin@medconnect.com'");
if ($checkAdmin && $checkAdmin->num_rows == 0) {
    // Create default admin account
    // Email: admin@medconnect.com
    // Password: admin123
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (full_name, email, password, role, status) 
                  VALUES ('System Admin', 'admin@medconnect.com', '$adminPassword', 'admin', 'approved')");
}
// 11. Extend doctor_profiles
$checkYears = $conn->query("SHOW COLUMNS FROM doctor_profiles LIKE 'years_experience'");
if ($checkYears && $checkYears->num_rows == 0) {
    $conn->query("ALTER TABLE doctor_profiles ADD COLUMN years_experience INT DEFAULT NULL");
}
$checkLangs = $conn->query("SHOW COLUMNS FROM doctor_profiles LIKE 'languages_spoken'");
if ($checkLangs && $checkLangs->num_rows == 0) {
    $conn->query("ALTER TABLE doctor_profiles ADD COLUMN languages_spoken VARCHAR(255) DEFAULT NULL");
}

// 12. Extend pharmacy_profiles
$checkHours = $conn->query("SHOW COLUMNS FROM pharmacy_profiles LIKE 'operating_hours'");
if ($checkHours && $checkHours->num_rows == 0) {
    $conn->query("ALTER TABLE pharmacy_profiles ADD COLUMN operating_hours VARCHAR(255) DEFAULT NULL");
}
$checkDelivery = $conn->query("SHOW COLUMNS FROM pharmacy_profiles LIKE 'delivery_options'");
if ($checkDelivery && $checkDelivery->num_rows == 0) {
    $conn->query("ALTER TABLE pharmacy_profiles ADD COLUMN delivery_options VARCHAR(255) DEFAULT NULL");
}

// 13. Create verification_documents table
$checkDocsTable = $conn->query("SHOW TABLES LIKE 'verification_documents'");
if ($checkDocsTable && $checkDocsTable->num_rows == 0) {
    $conn->query("CREATE TABLE `verification_documents` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `document_type` VARCHAR(50) NOT NULL,
        `file_path` VARCHAR(255) NOT NULL,
        `status` ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
        `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    )");
}

// 14. Create clinic_profiles table (similar to hospital)
$checkClinicTable = $conn->query("SHOW TABLES LIKE 'clinic_profiles'");
if ($checkClinicTable && $checkClinicTable->num_rows == 0) {
    $conn->query("CREATE TABLE `clinic_profiles` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL UNIQUE,
        `clinic_name` VARCHAR(200) NOT NULL,
        `registration_number` VARCHAR(100) NOT NULL,
        `departments` TEXT,
        `address` TEXT NOT NULL,
        `phone` VARCHAR(20),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    )");
}
// -------------------------------------
?>

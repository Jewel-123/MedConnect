<?php
include 'db.php';

echo "--- MedConnect Fix Script ---\n";

// 1. Fix doctor_profiles
echo "Checking doctor_profiles...\n";
$cols = [
    "years_experience" => "INT DEFAULT 0",
    "languages_spoken" => "VARCHAR(255) DEFAULT NULL"
];

foreach ($cols as $col => $def) {
    $check = $conn->query("SHOW COLUMNS FROM doctor_profiles LIKE '$col'");
    if ($check && $check->num_rows == 0) {
        if ($conn->query("ALTER TABLE doctor_profiles ADD COLUMN $col $def")) {
            echo "  ✓ Added $col to doctor_profiles\n";
        } else {
            echo "  ! Error adding $col: " . $conn->error . "\n";
        }
    } else {
        echo "  - Column $col already exists in doctor_profiles\n";
    }
}

// 2. Fix pharmacy_profiles
echo "Checking pharmacy_profiles...\n";
$pharmacy_cols = [
    "operating_hours" => "VARCHAR(150) DEFAULT NULL",
    "delivery_options" => "ENUM('pickup', 'delivery', 'both') DEFAULT 'pickup'"
];

foreach ($pharmacy_cols as $col => $def) {
    $check = $conn->query("SHOW COLUMNS FROM pharmacy_profiles LIKE '$col'");
    if ($check && $check->num_rows == 0) {
        if ($conn->query("ALTER TABLE pharmacy_profiles ADD COLUMN $col $def")) {
            echo "  ✓ Added $col to pharmacy_profiles\n";
        } else {
            echo "  ! Error adding $col: " . $conn->error . "\n";
        }
    } else {
        echo "  - Column $col already exists in pharmacy_profiles\n";
    }
}

// 3. Create clinic_profiles if missing
echo "Checking clinic_profiles...\n";
$create_clinic = "CREATE TABLE IF NOT EXISTS `clinic_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `clinic_name` VARCHAR(150) NOT NULL,
    `registration_number` VARCHAR(100) NOT NULL,
    `departments` TEXT,
    `address` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($create_clinic)) {
    echo "  ✓ clinic_profiles table ready\n";
} else {
    echo "  ! Error creating clinic_profiles: " . $conn->error . "\n";
}

// 4. Setup Admin Account
echo "Setting up admin account (admin@medconnect.com)...\n";
$conn->query("DELETE FROM users WHERE email = 'admin@medconnect.com'");
$adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, status, is_verified) VALUES (?, ?, ?, ?, ?, ?)");
$fullName = 'System Admin';
$email = 'admin@medconnect.com';
$role = 'admin';
$status = 'approved';
$is_verified = 1;

$stmt->bind_param("sssssi", $fullName, $email, $adminPassword, $role, $status, $is_verified);

if ($stmt->execute()) {
    echo "  ✓ Admin Account Setup Successful (admin@medconnect.com / admin123)\n";
} else {
    echo "  ! Error setting up admin: " . $stmt->error . "\n";
}

echo "--- Fix Script Completed ---\n";
$conn->close();
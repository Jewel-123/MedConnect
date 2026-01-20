<?php
require_once 'db.php';
$tables = [
    "CREATE TABLE IF NOT EXISTS `patient_profiles` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL UNIQUE,
        `date_of_birth` DATE,
        `gender` ENUM('male', 'female', 'other'),
        `phone` VARCHAR(20),
        `address` TEXT,
        `medical_history_summary` TEXT,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    "CREATE TABLE IF NOT EXISTS `doctor_profiles` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL UNIQUE,
        `specialization` VARCHAR(100) NOT NULL,
        `license_number` VARCHAR(50) NOT NULL,
        `bio` TEXT,
        `consultation_fee` DECIMAL(10, 2) DEFAULT 0.00,
        `availability_schedule` JSON,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

foreach ($tables as $sql) {
    if ($conn->query($sql)) {
        echo "✓ Table created successfully\n";
    } else {
        echo "✗ Error creating table: " . $conn->error . "\n";
    }
}
?>

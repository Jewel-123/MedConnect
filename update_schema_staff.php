<?php
/**
 * Database Schema Update Script
 * Adds support for staff roles and approval statuses
 */

require_once 'db.php';

try {
    // Add new columns to users table
    $sql1 = "ALTER TABLE `users` 
             MODIFY `role` ENUM('patient', 'doctor', 'admin', 'pharmacy', 'staff') NOT NULL";
    $conn->exec($sql1);
    echo "✓ Updated role enum in users table<br>";

    $sql2 = "ALTER TABLE `users` 
             ADD COLUMN `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'approved' AFTER `role`";
    $conn->exec($sql2);
    echo "✓ Added status column to users table<br>";

    $sql3 = "ALTER TABLE `users` 
             ADD COLUMN `designation` VARCHAR(50) DEFAULT NULL AFTER `status`";
    $conn->exec($sql3);
    echo "✓ Added designation column to users table<br>";

    // Create staff_profiles table
    $sql4 = "CREATE TABLE IF NOT EXISTS `staff_profiles` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL UNIQUE,
        `designation` ENUM('receptionist', 'nurse', 'lab_staff', 'canteen_staff', 'pharmacist') NOT NULL,
        `phone` VARCHAR(20),
        `license_number` VARCHAR(50) DEFAULT NULL,
        `documents` JSON DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    )";
    $conn->exec($sql4);
    echo "✓ Created staff_profiles table<br>";

    // Update existing users to have 'approved' status
    $sql5 = "UPDATE `users` SET `status` = 'approved' WHERE `status` IS NULL";
    $conn->exec($sql5);
    echo "✓ Updated existing users to approved status<br>";

    echo "<br><strong>Database schema updated successfully!</strong>";

} catch (PDOException $e) {
    // Check if error is because column/table already exists
    if (strpos($e->getMessage(), 'Duplicate column name') !== false || 
        strpos($e->getMessage(), 'already exists') !== false) {
        echo "⚠ Schema already up to date: " . $e->getMessage() . "<br>";
    } else {
        echo "❌ Error updating schema: " . $e->getMessage() . "<br>";
    }
}
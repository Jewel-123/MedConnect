-- Database Schema Update Script for MedConnect
-- This script adds support for staff roles and approval statuses

USE `medconnect`;

-- Add new columns to users table
ALTER TABLE `users` 
    MODIFY `role` ENUM('patient', 'doctor', 'admin', 'pharmacy', 'staff') NOT NULL,
    ADD COLUMN `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'approved' AFTER `role`,
    ADD COLUMN `designation` VARCHAR(50) DEFAULT NULL AFTER `status`;

-- Create staff_profiles table
CREATE TABLE IF NOT EXISTS `staff_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `designation` ENUM('receptionist', 'nurse', 'lab_staff', 'canteen_staff', 'pharmacist') NOT NULL,
    `phone` VARCHAR(20),
    `license_number` VARCHAR(50) DEFAULT NULL,
    `documents` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Update existing users to have 'approved' status (for backward compatibility)
UPDATE `users` SET `status` = 'approved' WHERE `status` IS NULL;

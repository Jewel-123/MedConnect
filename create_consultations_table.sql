-- --------------------------------------------------------
-- Create Consultations Table
-- --------------------------------------------------------
-- This table stores patient consultation requests with symptom information

USE `medconnect`;

CREATE TABLE IF NOT EXISTS `consultations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `symptoms` TEXT NOT NULL,
    `duration` VARCHAR(100) NOT NULL,
    `severity` ENUM('low', 'medium', 'high') NOT NULL,
    `age` INT DEFAULT NULL,
    `gender` ENUM('male', 'female', 'other') DEFAULT NULL,
    `existing_conditions` TEXT DEFAULT NULL,
    `input_method` ENUM('text', 'voice') DEFAULT 'text',
    `status` ENUM('pending', 'assigned', 'completed', 'cancelled') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_patient_id` (`patient_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

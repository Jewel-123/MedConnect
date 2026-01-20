-- Database updates for Telemedicine Redesign

USE `medconnect`;

-- 1. Add private notes for doctors in consultations
SET @dbname = DATABASE();
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'private_notes');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN private_notes TEXT DEFAULT NULL AFTER matched_specialty', 
    'SELECT "Column private_notes already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Add read receipts for messages
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'messages' AND COLUMN_NAME = 'read_at');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE messages ADD COLUMN read_at TIMESTAMP NULL DEFAULT NULL AFTER is_read', 
    'SELECT "Column read_at already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Create Patient Vitals table
CREATE TABLE IF NOT EXISTS `patient_vitals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `consultation_id` INT DEFAULT NULL,
    `weight` DECIMAL(5, 2) DEFAULT NULL,
    `height` DECIMAL(5, 2) DEFAULT NULL,
    `blood_pressure` VARCHAR(20) DEFAULT NULL,
    `temperature` DECIMAL(4, 1) DEFAULT NULL,
    `heart_rate` INT DEFAULT NULL,
    `oxygen_level` INT DEFAULT NULL,
    `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create Medical Reports table
CREATE TABLE IF NOT EXISTS `medical_reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `report_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(50) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

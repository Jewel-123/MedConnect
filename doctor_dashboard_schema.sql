-- ========================================
-- MedConnect Doctor Dashboard Schema
-- ========================================
-- This script creates all necessary tables for the Doctor Dashboard functionality
-- Run this after the main medconnect.sql schema

USE `medconnect`;

-- --------------------------------------------------------
-- 1. Extend Consultations Table for Doctor Assignment
-- --------------------------------------------------------
-- Check and add columns one by one
SET @dbname = DATABASE();

-- Add doctor_id column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'doctor_id');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN doctor_id INT DEFAULT NULL AFTER patient_id', 
    'SELECT "Column doctor_id already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add consultation_mode column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'consultation_mode');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN consultation_mode ENUM(\'video\', \'audio\', \'chat\') DEFAULT \'chat\' AFTER status', 
    'SELECT "Column consultation_mode already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add language_preference column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'language_preference');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN language_preference VARCHAR(50) DEFAULT \'English\' AFTER consultation_mode', 
    'SELECT "Column language_preference already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add urgency_score column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'urgency_score');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN urgency_score INT DEFAULT 50 AFTER severity', 
    'SELECT "Column urgency_score already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add assigned_at column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'assigned_at');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN assigned_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at', 
    'SELECT "Column assigned_at already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add completed_at column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'completed_at');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN completed_at TIMESTAMP NULL DEFAULT NULL AFTER assigned_at', 
    'SELECT "Column completed_at already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key if it doesn't exist
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'consultations' 
    AND COLUMN_NAME = 'doctor_id' AND REFERENCED_TABLE_NAME = 'users');
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE consultations ADD CONSTRAINT fk_consultations_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT "Foreign key already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update status enum to include more states
ALTER TABLE `consultations` 
MODIFY COLUMN `status` ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled', 'declined') DEFAULT 'pending';

-- --------------------------------------------------------
-- 2. Consultation Sessions Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `consultation_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `consultation_id` INT NOT NULL,
    `session_token` VARCHAR(255) NOT NULL,
    `session_type` ENUM('video', 'audio', 'chat') NOT NULL,
    `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `ended_at` TIMESTAMP NULL DEFAULT NULL,
    `duration_minutes` INT DEFAULT 0,
    `transcription` TEXT DEFAULT NULL,
    `ai_highlights` JSON DEFAULT NULL,
    `encryption_key_hash` VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE CASCADE,
    INDEX `idx_consultation_id` (`consultation_id`),
    INDEX `idx_session_token` (`session_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Enhanced Prescriptions Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prescriptions_v2` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `consultation_id` INT NOT NULL,
    `patient_id` INT NOT NULL,
    `doctor_id` INT NOT NULL,
    `pharmacy_id` INT DEFAULT NULL,
    `icd_code` VARCHAR(20) DEFAULT NULL,
    `diagnosis` TEXT NOT NULL,
    `follow_up_date` DATE DEFAULT NULL,
    `notes_for_patient` TEXT DEFAULT NULL,
    `notes_for_pharmacy` TEXT DEFAULT NULL,
    `status` ENUM('draft', 'issued', 'sent_to_pharmacy', 'filled', 'cancelled') DEFAULT 'draft',
    `sent_at` TIMESTAMP NULL DEFAULT NULL,
    `filled_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`pharmacy_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_consultation_id` (`consultation_id`),
    INDEX `idx_patient_id` (`patient_id`),
    INDEX `idx_doctor_id` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Prescription Items Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prescription_items_v2` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `prescription_id` INT NOT NULL,
    `medicine_name` VARCHAR(200) NOT NULL,
    `dosage` VARCHAR(100) NOT NULL,
    `frequency` VARCHAR(100) NOT NULL,
    `duration` VARCHAR(50) NOT NULL,
    `instructions` TEXT DEFAULT NULL,
    `quantity` INT DEFAULT 1,
    FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions_v2`(`id`) ON DELETE CASCADE,
    INDEX `idx_prescription_id` (`prescription_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Lab Tests and Referrals Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prescription_tests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `prescription_id` INT NOT NULL,
    `test_type` ENUM('lab_test', 'imaging', 'referral') NOT NULL,
    `test_name` VARCHAR(200) NOT NULL,
    `instructions` TEXT DEFAULT NULL,
    `urgency` ENUM('routine', 'urgent', 'stat') DEFAULT 'routine',
    FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions_v2`(`id`) ON DELETE CASCADE,
    INDEX `idx_prescription_id` (`prescription_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Doctor Reviews and Ratings Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `doctor_reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `doctor_id` INT NOT NULL,
    `patient_id` INT NOT NULL,
    `consultation_id` INT DEFAULT NULL,
    `rating` INT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `review_text` TEXT DEFAULT NULL,
    `doctor_response` TEXT DEFAULT NULL,
    `quality_flag` ENUM('none', 'pending_review', 'flagged') DEFAULT 'none',
    `admin_notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `responded_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE SET NULL,
    INDEX `idx_doctor_id` (`doctor_id`),
    INDEX `idx_patient_id` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. Doctor Availability and Schedule Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `doctor_availability` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `doctor_id` INT NOT NULL,
    `day_of_week` ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `is_available` BOOLEAN DEFAULT TRUE,
    `auto_booking_enabled` BOOLEAN DEFAULT TRUE,
    `max_consultations_per_slot` INT DEFAULT 1,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_doctor_id` (`doctor_id`),
    INDEX `idx_day_of_week` (`day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8. Doctor Availability Overrides Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `doctor_availability_overrides` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `doctor_id` INT NOT NULL,
    `override_date` DATE NOT NULL,
    `start_time` TIME DEFAULT NULL,
    `end_time` TIME DEFAULT NULL,
    `is_available` BOOLEAN NOT NULL,
    `reason` VARCHAR(200) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_doctor_id` (`doctor_id`),
    INDEX `idx_override_date` (`override_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 9. Doctor Earnings Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `doctor_earnings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `doctor_id` INT NOT NULL,
    `consultation_id` INT NOT NULL,
    `gross_amount` DECIMAL(10, 2) NOT NULL,
    `platform_commission_percent` DECIMAL(5, 2) DEFAULT 10.00,
    `platform_commission_amount` DECIMAL(10, 2) NOT NULL,
    `net_amount` DECIMAL(10, 2) NOT NULL,
    `payment_status` ENUM('pending', 'processed', 'paid', 'on_hold') DEFAULT 'pending',
    `payment_date` DATE DEFAULT NULL,
    `invoice_number` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE CASCADE,
    INDEX `idx_doctor_id` (`doctor_id`),
    INDEX `idx_consultation_id` (`consultation_id`),
    INDEX `idx_payment_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 10. Consultation Audit Log Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `consultation_audit_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `consultation_id` INT NOT NULL,
    `doctor_id` INT NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `action_details` JSON DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_consultation_id` (`consultation_id`),
    INDEX `idx_doctor_id` (`doctor_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 11. Patient Consent Logs Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `consent_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `doctor_id` INT NOT NULL,
    `consultation_id` INT DEFAULT NULL,
    `consent_type` ENUM('data_access', 'video_recording', 'data_sharing', 'prescription') NOT NULL,
    `consent_given` BOOLEAN NOT NULL,
    `consent_text` TEXT NOT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE SET NULL,
    INDEX `idx_patient_id` (`patient_id`),
    INDEX `idx_doctor_id` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 12. Doctor Notifications Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `doctor_notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `doctor_id` INT NOT NULL,
    `notification_type` ENUM('new_consultation', 'follow_up_due', 'pharmacy_query', 'review_received', 'system') NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `related_id` INT DEFAULT NULL,
    `is_read` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_doctor_id` (`doctor_id`),
    INDEX `idx_is_read` (`is_read`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 13. Patient Medical History Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `patient_medical_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `consultation_id` INT DEFAULT NULL,
    `doctor_id` INT DEFAULT NULL,
    `record_type` ENUM('diagnosis', 'procedure', 'allergy', 'medication', 'condition') NOT NULL,
    `record_title` VARCHAR(200) NOT NULL,
    `record_details` TEXT DEFAULT NULL,
    `record_date` DATE NOT NULL,
    `is_chronic` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_patient_id` (`patient_id`),
    INDEX `idx_record_type` (`record_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ========================================
-- Schema Creation Complete
-- ========================================
-- Note: Sample doctor availability data can be added manually after installation


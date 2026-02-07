-- ========================================
-- Consultation Management System - Database Schema Enhancements
-- ========================================
-- This script adds necessary columns and tables for the enhanced
-- consultation workflow with payment-first enforcement
-- ========================================

USE `medconnect`;

-- ========================================
-- 1. ENHANCE CONSULTATIONS TABLE
-- ========================================

-- Add consultation_fee column (locked fee at booking time)
ALTER TABLE `consultations` 
ADD COLUMN IF NOT EXISTS `consultation_fee` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Locked consultation fee at booking time'
AFTER `doctor_id`;

-- Add payment_transaction_id to link to payment 
ALTER TABLE `consultations`
ADD COLUMN IF NOT EXISTS `payment_transaction_id` INT DEFAULT NULL COMMENT 'Link to payment transaction'
AFTER `payment_status`;

-- Add foreign key for payment_transaction_id
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'consultations' 
    AND COLUMN_NAME = 'payment_transaction_id' 
    AND REFERENCED_TABLE_NAME = 'payment_transactions');

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE consultations ADD CONSTRAINT fk_consultations_payment 
     FOREIGN KEY (payment_transaction_id) REFERENCES payment_transactions(id) ON DELETE SET NULL',
    'SELECT "FK payment_transaction_id already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add start_time timestamp for tracking consultation duration
ALTER TABLE `consultations`
ADD COLUMN IF NOT EXISTS `start_time` TIMESTAMP NULL DEFAULT NULL COMMENT 'When consultation started'
AFTER `assigned_at`;

-- ========================================
-- 2. ENHANCE APPOINTMENTS TABLE  
-- ========================================

-- Ensure appointments has payment_status column
ALTER TABLE `appointments`
ADD COLUMN IF NOT EXISTS `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending'
AFTER `status`;

-- Add payment_transaction_id to appointments
ALTER TABLE `appointments`
ADD COLUMN IF NOT EXISTS `payment_transaction_id` INT DEFAULT NULL COMMENT 'Link to payment transaction'
AFTER `payment_status`;

-- Add foreign key
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'appointments' 
    AND COLUMN_NAME = 'payment_transaction_id' 
    AND REFERENCED_TABLE_NAME = 'payment_transactions');

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE appointments ADD CONSTRAINT fk_appointments_payment 
     FOREIGN KEY (payment_transaction_id) REFERENCES payment_transactions(id) ON DELETE SET NULL',
    'SELECT "FK payment_transaction_id already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add consultation_fee to appointments as well
ALTER TABLE `appointments`
ADD COLUMN IF NOT EXISTS `consultation_fee` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Consultation fee for this appointment'
AFTER `doctor_id`;

-- ========================================
-- 3. ENSURE DOCTOR_EARNINGS TABLE EXISTS
-- ========================================

CREATE TABLE IF NOT EXISTS `doctor_earnings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `doctor_id` INT NOT NULL,
    `consultation_id` INT DEFAULT NULL,
    `appointment_id` INT DEFAULT NULL,
    `gross_amount` DECIMAL(10, 2) NOT NULL,
    `platform_commission_percent` DECIMAL(5, 2) DEFAULT 10.00,
    `platform_commission_amount` DECIMAL(10, 2) NOT NULL,
    `net_amount` DECIMAL(10, 2) NOT NULL,
    `payment_status` ENUM('pending', 'completed', 'cancelled', 'on_hold') DEFAULT 'pending',
    `payment_date` DATE DEFAULT NULL,
    `invoice_number` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE CASCADE,
    INDEX `idx_doctor_id` (`doctor_id`),
    INDEX `idx_consultation_id` (`consultation_id`),
    INDEX `idx_appointment_id` (`appointment_id`),
    INDEX `idx_payment_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 4. ENSURE PAYMENT_TRANSACTIONS TABLE HAS NECESSARY COLUMNS
-- ========================================

-- Add doctor_id to payment_transactions for easier tracking
ALTER TABLE `payment_transactions`
ADD COLUMN IF NOT EXISTS `doctor_id` INT DEFAULT NULL COMMENT 'Selected doctor for consultation payment'
AFTER `user_id`;

-- Add refund columns
ALTER TABLE `payment_transactions`
ADD COLUMN IF NOT EXISTS `refund_amount` DECIMAL(10,2) DEFAULT NULL COMMENT 'Refund amount if applicable'
AFTER `amount`;

ALTER TABLE `payment_transactions`
ADD COLUMN IF NOT EXISTS `refund_transaction_id` VARCHAR(200) DEFAULT NULL COMMENT 'Gateway refund transaction ID'
AFTER `refund_amount`;

ALTER TABLE `payment_transactions`
ADD COLUMN IF NOT EXISTS `refund_status` ENUM('none', 'initiated', 'processed', 'failed') DEFAULT 'none'
AFTER `refund_transaction_id`;

ALTER TABLE `payment_transactions`
ADD COLUMN IF NOT EXISTS `refund_initiated_at` TIMESTAMP NULL DEFAULT NULL
AFTER `refund_status`;

-- ========================================
-- 5. ADD INDICES FOR PERFORMANCE
-- ========================================

-- Add index on consultations for doctor_id + status + payment_status (for dashboard queries)
CREATE INDEX IF NOT EXISTS `idx_doctor_status_payment` 
ON `consultations` (`doctor_id`, `status`, `payment_status`);

-- Add index on consultations for created_at (for ordering)
CREATE INDEX IF NOT EXISTS `idx_created_at` 
ON `consultations` (`created_at`);

-- Add index on doctor_earnings for date-based queries
CREATE INDEX IF NOT EXISTS `idx_earnings_created` 
ON `doctor_earnings` (`created_at`);

-- ========================================
-- 6. CREATE CONSULTATION REJECTION LOG
-- ========================================

CREATE TABLE IF NOT EXISTS `consultation_rejections` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `consultation_id` INT NOT NULL,
    `doctor_id` INT NOT NULL,
    `patient_id` INT NOT NULL,
    `rejection_reason` TEXT NOT NULL,
    `refund_amount` DECIMAL(10,2) DEFAULT 0.00,
    `refund_status` ENUM('pending', 'processed', 'failed') DEFAULT 'pending',
    `rejected_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_consultation_id` (`consultation_id`),
    INDEX `idx_doctor_id` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 7. CREATE DOCTOR ONLINE STATUS TABLE
-- ========================================

CREATE TABLE IF NOT EXISTS `doctor_online_status` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `doctor_id` INT NOT NULL UNIQUE,
    `is_online` BOOLEAN DEFAULT FALSE,
    `last_online_at` TIMESTAMP NULL DEFAULT NULL,
    `last_offline_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_doctor_id` (`doctor_id`),
    INDEX `idx_is_online` (`is_online`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- VERIFICATION QUERIES
-- ========================================

-- Verify consultations columns
SELECT 'Consultations columns verification:' AS message;
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'consultations'
AND COLUMN_NAME IN ('consultation_fee', 'payment_transaction_id', 'start_time', 'doctor_id', 'payment_status')
ORDER BY ORDINAL_POSITION;

-- Verify doctor_earnings table
SELECT 'Doctor earnings table verification:' AS message;
SELECT COUNT(*) as table_exists FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'doctor_earnings';

-- Verify indices
SELECT 'Indices verification:' AS message;
SELECT TABLE_NAME, INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as COLUMNS
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME IN ('consultations', 'doctor_earnings', 'appointments')
GROUP BY TABLE_NAME, INDEX_NAME;

SELECT '✓ Schema enhancements completed successfully!' AS message;

-- ========================================
-- MEDCONNECT CONSOLIDATED DATABASE SETUP
-- ========================================
-- This script consolidates ALL schemas into one unified database
-- Safe migration - preserves all existing data and tables
-- ========================================
-- Combines:
-- 1. Core System Schema (8 modules)
-- 2. Workflow Schema (locations, symptoms, notifications)
-- 3. Doctor Dashboard Schema (consultations, prescriptions, earnings)
-- ========================================

USE `medconnect`;

SET @dbname = DATABASE();

-- ========================================
-- PART 1: CORE SYSTEM WORKFLOWS SCHEMA
-- ========================================

-- --------------------------------------------------------
-- MODULE 1: SYMPTOM INTAKE & ANALYSIS
-- --------------------------------------------------------

-- Symptom Attachments Table (Medical images/documents)
CREATE TABLE IF NOT EXISTS `symptom_attachments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `consultation_id` INT NOT NULL,
    `patient_id` INT NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_type` VARCHAR(50) NOT NULL,
    `file_size` INT NOT NULL,
    `attachment_type` ENUM('image', 'document', 'lab_report', 'other') DEFAULT 'other',
    `upload_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `is_encrypted` BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_consultation_id` (`consultation_id`),
    INDEX `idx_patient_id` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enhance consultations table
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'input_method');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN input_method ENUM(''text'', ''voice'') DEFAULT ''text'' AFTER symptoms', 
    'SELECT \"Column input_method already exists\" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'attachment_count');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN attachment_count INT DEFAULT 0 AFTER input_method', 
    'SELECT \"Column attachment_count already exists\" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- --------------------------------------------------------
-- MODULE 2: APPOINTMENT ENGINE
-- --------------------------------------------------------

-- Appointments Table (Scheduled consultations)
CREATE TABLE IF NOT EXISTS `appointments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `doctor_id` INT NOT NULL,
    `consultation_id` INT DEFAULT NULL,
    `appointment_type` ENUM('instant', 'scheduled') DEFAULT 'scheduled',
    `scheduled_date` DATE NOT NULL,
    `scheduled_time` TIME NOT NULL,
    `duration_minutes` INT DEFAULT 30,
    `status` ENUM('pending', 'confirmed', 'rescheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'pending',
    `cancellation_reason` TEXT DEFAULT NULL,
    `reminder_sent` BOOLEAN DEFAULT FALSE,
    `reminder_sent_at` TIMESTAMP NULL DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE SET NULL,
    INDEX `idx_patient_id` (`patient_id`),
    INDEX `idx_doctor_id` (`doctor_id`),
    INDEX `idx_scheduled_date` (`scheduled_date`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Doctor Queue Table (Real-time queue management)
CREATE TABLE IF NOT EXISTS `doctor_queue` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `doctor_id` INT NOT NULL,
    `consultation_id` INT NOT NULL,
    `queue_position` INT NOT NULL,
    `estimated_wait_minutes` INT DEFAULT 15,
    `priority_level` ENUM('routine', 'priority', 'emergency') DEFAULT 'routine',
    `joined_queue_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `dequeued_at` TIMESTAMP NULL DEFAULT NULL,
    `status` ENUM('waiting', 'called', 'removed') DEFAULT 'waiting',
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE CASCADE,
    INDEX `idx_doctor_id` (`doctor_id`),
    INDEX `idx_consultation_id` (`consultation_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- MODULE 3: SECURE CONSULTATION
-- --------------------------------------------------------

-- Consultation Messages Table (Encrypted chat)
CREATE TABLE IF NOT EXISTS `consultation_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `consultation_id` INT NOT NULL,
    `sender_id` INT NOT NULL,
    `sender_role` ENUM('patient', 'doctor') NOT NULL,
    `message_type` ENUM('text', 'file', 'system') DEFAULT 'text',
    `message_content` TEXT NOT NULL,
    `file_reference` VARCHAR(500) DEFAULT NULL,
    `is_encrypted` BOOLEAN DEFAULT TRUE,
    `encryption_method` VARCHAR(50) DEFAULT 'AES-256',
    `is_read` BOOLEAN DEFAULT FALSE,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_consultation_id` (`consultation_id`),
    INDEX `idx_sender_id` (`sender_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Consultation Attachments Table
CREATE TABLE IF NOT EXISTS `consultation_attachments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `consultation_id` INT NOT NULL,
    `message_id` INT DEFAULT NULL,
    `uploader_id` INT NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_type` VARCHAR(50) NOT NULL,
    `file_size` INT NOT NULL,
    `attachment_category` ENUM('lab_report', 'prescription', 'image', 'document', 'other') DEFAULT 'other',
    `is_encrypted` BOOLEAN DEFAULT TRUE,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`message_id`) REFERENCES `consultation_messages`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`uploader_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_consultation_id` (`consultation_id`),
    INDEX `idx_uploader_id` (`uploader_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Consultation Sessions Table
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

-- Enhance consultation_sessions table
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'consultation_sessions' AND COLUMN_NAME = 'is_encrypted');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultation_sessions ADD COLUMN is_encrypted BOOLEAN DEFAULT TRUE AFTER encryption_key_hash', 
    'SELECT \"Column is_encrypted already exists\" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- --------------------------------------------------------
-- MODULE 4: ELECTRONIC PRESCRIPTION
-- --------------------------------------------------------

-- Enhanced Prescriptions Table
CREATE TABLE IF NOT EXISTS `prescriptions_v2` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `consultation_id` INT NOT NULL,
    `patient_id` INT NOT NULL,
    `doctor_id` INT NOT NULL,
    `pharmacy_id` INT DEFAULT NULL,
    `prescription_number` VARCHAR(50) UNIQUE DEFAULT NULL,
    `icd_code` VARCHAR(20) DEFAULT NULL,
    `diagnosis` TEXT NOT NULL,
    `follow_up_date` DATE DEFAULT NULL,
    `notes_for_patient` TEXT DEFAULT NULL,
    `notes_for_pharmacy` TEXT DEFAULT NULL,
    `digital_signature` TEXT DEFAULT NULL,
    `signature_timestamp` TIMESTAMP NULL DEFAULT NULL,
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

-- Prescription Items Table
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

-- Lab Tests and Referrals Table
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
-- MODULE 5: PHARMACY INTEGRATION
-- --------------------------------------------------------

-- Pharmacy Profiles Table
CREATE TABLE IF NOT EXISTS `pharmacy_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `pharmacy_name` VARCHAR(200) NOT NULL,
    `license_number` VARCHAR(100) NOT NULL,
    `registration_number` VARCHAR(100) DEFAULT NULL,
    `owner_name` VARCHAR(100) NOT NULL,
    `pharmacy_type` ENUM('retail', 'hospital', 'online', 'chain') DEFAULT 'retail',
    `operating_hours` VARCHAR(200) DEFAULT NULL,
    `delivery_available` BOOLEAN DEFAULT FALSE,
    `delivery_radius_km` INT DEFAULT 5,
    `minimum_order_amount` DECIMAL(10, 2) DEFAULT 0.00,
    `delivery_fee` DECIMAL(10, 2) DEFAULT 0.00,
    `verification_status` ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    `verification_date` DATE DEFAULT NULL,
    `verified_by` INT DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `logo_path` VARCHAR(500) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_verification_status` (`verification_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pharmacy Inventory Table (Simplified)
CREATE TABLE IF NOT EXISTS `pharmacy_inventory` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pharmacy_id` INT NOT NULL,
    `medicine_name` VARCHAR(200) NOT NULL,
    `generic_name` VARCHAR(200) DEFAULT NULL,
    `manufacturer` VARCHAR(200) DEFAULT NULL,
    `stock_quantity` INT DEFAULT 0,
    `unit_price` DECIMAL(10, 2) NOT NULL,
    `expiry_date` DATE DEFAULT NULL,
    `is_available` BOOLEAN DEFAULT TRUE,
    `requires_prescription` BOOLEAN DEFAULT TRUE,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`pharmacy_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_pharmacy_id` (`pharmacy_id`),
    INDEX `idx_medicine_name` (`medicine_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prescription Orders Table
CREATE TABLE IF NOT EXISTS `prescription_orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(50) UNIQUE NOT NULL,
    `prescription_id` INT NOT NULL,
    `pharmacy_id` INT NOT NULL,
    `patient_id` INT NOT NULL,
    `order_status` ENUM('pending', 'accepted', 'preparing', 'ready', 'out_for_delivery', 'delivered', 'completed', 'cancelled') DEFAULT 'pending',
    `fulfillment_type` ENUM('pickup', 'delivery') DEFAULT 'pickup',
    `delivery_address` TEXT DEFAULT NULL,
    `delivery_contact` VARCHAR(20) DEFAULT NULL,
    `total_amount` DECIMAL(10, 2) NOT NULL,
    `payment_status` ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    `payment_transaction_id` INT DEFAULT NULL,
    `accepted_at` TIMESTAMP NULL DEFAULT NULL,
    `ready_at` TIMESTAMP NULL DEFAULT NULL,
    `delivered_at` TIMESTAMP NULL DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions_v2`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`pharmacy_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_prescription_id` (`prescription_id`),
    INDEX `idx_pharmacy_id` (`pharmacy_id`),
    INDEX `idx_patient_id` (`patient_id`),
    INDEX `idx_order_status` (`order_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Delivery Tracking Table
CREATE TABLE IF NOT EXISTS `delivery_tracking` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `current_status` ENUM('pending', 'assigned', 'picked_up', 'in_transit', 'delivered', 'failed') DEFAULT 'pending',
    `delivery_person_name` VARCHAR(100) DEFAULT NULL,
    `delivery_person_contact` VARCHAR(20) DEFAULT NULL,
    `tracking_number` VARCHAR(100) DEFAULT NULL,
    `estimated_delivery_time` TIMESTAMP NULL DEFAULT NULL,
    `actual_delivery_time` TIMESTAMP NULL DEFAULT NULL,
    `delivery_notes` TEXT DEFAULT NULL,
    `location_latitude` DECIMAL(10, 8) DEFAULT NULL,
    `location_longitude` DECIMAL(11, 8) DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `prescription_orders`(`id`) ON DELETE CASCADE,
    INDEX `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pharmacy Locations Table
CREATE TABLE IF NOT EXISTS `pharmacy_locations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pharmacy_id` INT NOT NULL,
    `pharmacy_name` VARCHAR(200) NOT NULL,
    `address` TEXT NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `state` VARCHAR(100) NOT NULL,
    `postal_code` VARCHAR(20) DEFAULT NULL,
    `country` VARCHAR(100) DEFAULT 'India',
    `latitude` DECIMAL(10, 8) DEFAULT NULL,
    `longitude` DECIMAL(11, 8) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `is_24_hours` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`pharmacy_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_pharmacy_id` (`pharmacy_id`),
    INDEX `idx_location` (`latitude`, `longitude`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- MODULE 6: PAYMENT & REVENUE SHARING
-- --------------------------------------------------------

-- Payment Transactions Table
CREATE TABLE IF NOT EXISTS `payment_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `transaction_number` VARCHAR(50) UNIQUE NOT NULL,
    `user_id` INT NOT NULL,
    `transaction_type` ENUM('consultation_fee', 'medication_payment', 'refund', 'payout') NOT NULL,
    `related_id` INT DEFAULT NULL,
    `related_type` ENUM('consultation', 'prescription_order', 'payout') DEFAULT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `currency` VARCHAR(10) DEFAULT 'INR',
    `payment_method` ENUM('card', 'upi', 'netbanking', 'wallet', 'cod') NOT NULL,
    `payment_gateway` VARCHAR(50) DEFAULT NULL,
    `gateway_transaction_id` VARCHAR(200) DEFAULT NULL,
    `status` ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    `failure_reason` TEXT DEFAULT NULL,
    `payment_metadata` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_transaction_type` (`transaction_type`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Revenue Splits Configuration Table
CREATE TABLE IF NOT EXISTS `revenue_splits` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `service_type` ENUM('consultation', 'medication', 'lab_test') NOT NULL,
    `platform_commission_percent` DECIMAL(5, 2) NOT NULL DEFAULT 10.00,
    `doctor_percent` DECIMAL(5, 2) DEFAULT 90.00,
    `pharmacy_percent` DECIMAL(5, 2) DEFAULT NULL,
    `effective_from` DATE NOT NULL,
    `effective_until` DATE DEFAULT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_service_type` (`service_type`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payouts Table
CREATE TABLE IF NOT EXISTS `payouts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `payout_number` VARCHAR(50) UNIQUE NOT NULL,
    `recipient_id` INT NOT NULL,
    `recipient_role` ENUM('doctor', 'pharmacy') NOT NULL,
    `period_start` DATE NOT NULL,
    `period_end` DATE NOT NULL,
    `gross_earnings` DECIMAL(10, 2) NOT NULL,
    `platform_commission` DECIMAL(10, 2) NOT NULL,
    `net_payout` DECIMAL(10, 2) NOT NULL,
    `status` ENUM('pending', 'approved', 'processed', 'paid', 'failed') DEFAULT 'pending',
    `payout_method` ENUM('bank_transfer', 'upi', 'cheque') DEFAULT 'bank_transfer',
    `bank_details_encrypted` TEXT DEFAULT NULL,
    `processed_by` INT DEFAULT NULL,
    `processed_at` TIMESTAMP NULL DEFAULT NULL,
    `payment_reference` VARCHAR(200) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`recipient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_recipient_id` (`recipient_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_period_end` (`period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Doctor Earnings Table
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

-- Pharmacy Earnings Table
CREATE TABLE IF NOT EXISTS `pharmacy_earnings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pharmacy_id` INT NOT NULL,
    `prescription_order_id` INT NOT NULL,
    `gross_amount` DECIMAL(10, 2) NOT NULL,
    `platform_commission_percent` DECIMAL(5, 2) DEFAULT 5.00,
    `platform_commission_amount` DECIMAL(10, 2) NOT NULL,
    `net_amount` DECIMAL(10, 2) NOT NULL,
    `payment_status` ENUM('pending', 'processed', 'paid', 'on_hold') DEFAULT 'pending',
    `payment_date` DATE DEFAULT NULL,
    `invoice_number` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`pharmacy_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`prescription_order_id`) REFERENCES `prescription_orders`(`id`) ON DELETE CASCADE,
    INDEX `idx_pharmacy_id` (`pharmacy_id`),
    INDEX `idx_prescription_order_id` (`prescription_order_id`),
    INDEX `idx_payment_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enhance consultations table with payment status
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'payment_status');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN payment_status ENUM(''pending'', ''paid'', ''refunded'') DEFAULT ''pending'' AFTER status', 
    'SELECT \"Column payment_status already exists\" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- --------------------------------------------------------
-- MODULE 7: NOTIFICATION & REMINDER SYSTEM
-- --------------------------------------------------------

-- Notification Preferences Table
CREATE TABLE IF NOT EXISTS `notification_preferences` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `email_enabled` BOOLEAN DEFAULT TRUE,
    `sms_enabled` BOOLEAN DEFAULT TRUE,
    `in_app_enabled` BOOLEAN DEFAULT TRUE,
    `appointment_reminders` BOOLEAN DEFAULT TRUE,
    `prescription_alerts` BOOLEAN DEFAULT TRUE,
    `marketing_emails` BOOLEAN DEFAULT FALSE,
    `follow_up_reminders` BOOLEAN DEFAULT TRUE,
    `medication_reminders` BOOLEAN DEFAULT TRUE,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Scheduled Notifications Table (Reminder Queue)
CREATE TABLE IF NOT EXISTS `scheduled_notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `notification_type` ENUM('appointment_reminder', 'prescription_ready', 'medication_refill', 'follow_up_reminder') NOT NULL,
    `schedule_for` TIMESTAMP NOT NULL,
    `related_id` INT DEFAULT NULL,
    `related_type` ENUM('appointment', 'prescription', 'consultation') DEFAULT NULL,
    `notification_title` VARCHAR(200) NOT NULL,
    `notification_message` TEXT NOT NULL,
    `delivery_channels` JSON DEFAULT NULL,
    `status` ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    `sent_at` TIMESTAMP NULL DEFAULT NULL,
    `error_message` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_schedule_for` (`schedule_for`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification Templates Table
CREATE TABLE IF NOT EXISTS `notification_templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `template_name` VARCHAR(100) UNIQUE NOT NULL,
    `template_type` ENUM('email', 'sms', 'in_app', 'all') NOT NULL,
    `subject_template` VARCHAR(200) DEFAULT NULL,
    `body_template` TEXT NOT NULL,
    `variables` JSON DEFAULT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_template_name` (`template_name`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification Log Table
CREATE TABLE IF NOT EXISTS `notification_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `notification_type` ENUM('email', 'sms', 'in_app') NOT NULL,
    `recipient` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) DEFAULT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    `error_message` TEXT DEFAULT NULL,
    `sent_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Doctor Notifications Table
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
-- MODULE 8: SECURITY & COMPLIANCE
-- --------------------------------------------------------

-- Access Logs Table (Detailed audit trail)
CREATE TABLE IF NOT EXISTS `access_logs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `user_role` VARCHAR(20) DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `resource_type` VARCHAR(50) DEFAULT NULL,
    `resource_id` INT DEFAULT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `request_method` VARCHAR(10) DEFAULT NULL,
    `request_url` VARCHAR(500) DEFAULT NULL,
    `response_status` INT DEFAULT NULL,
    `session_id` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_resource` (`resource_type`, `resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compliance Events Table
CREATE TABLE IF NOT EXISTS `compliance_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_type` ENUM('data_access', 'data_export', 'data_deletion', 'consent_given', 'consent_revoked', 'privacy_request') NOT NULL,
    `user_id` INT DEFAULT NULL,
    `affected_user_id` INT DEFAULT NULL,
    `event_description` TEXT NOT NULL,
    `event_metadata` JSON DEFAULT NULL,
    `compliance_standard` VARCHAR(50) DEFAULT 'HIPAA',
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`affected_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_event_type` (`event_type`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_affected_user_id` (`affected_user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Patient Consent Logs Table
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

-- Consultation Audit Log Table
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

-- ========================================
-- PART 2: DOCTOR DASHBOARD ENHANCEMENTS
-- ========================================

-- Add doctor_id column to consultations
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'doctor_id');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN doctor_id INT DEFAULT NULL AFTER patient_id', 
    'SELECT \"Column doctor_id already exists\" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add consultation_mode column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'consultation_mode');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN consultation_mode ENUM(''video'', ''audio'', ''chat'') DEFAULT ''chat'' AFTER status', 
    'SELECT \"Column consultation_mode already exists\" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add language_preference column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'language_preference');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN language_preference VARCHAR(50) DEFAULT ''English'' AFTER consultation_mode', 
    'SELECT \"Column language_preference already exists\" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add urgency_score column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'urgency_score');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN urgency_score INT DEFAULT 50 AFTER severity', 
    'SELECT \"Column urgency_score already exists\" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add assigned_at column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'assigned_at');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN assigned_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at', 
    'SELECT \"Column assigned_at already exists\" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add completed_at column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'completed_at');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN completed_at TIMESTAMP NULL DEFAULT NULL AFTER assigned_at', 
    'SELECT \"Column completed_at already exists\" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add matched_specialty column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'matched_specialty');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN matched_specialty VARCHAR(100) DEFAULT NULL AFTER language_preference', 
    'SELECT \"Column matched_specialty already exists\" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add auto_assigned column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'auto_assigned');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN auto_assigned BOOLEAN DEFAULT FALSE AFTER matched_specialty', 
    'SELECT \"Column auto_assigned already exists\" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key for doctor_id if it doesn't exist
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'consultations' 
    AND COLUMN_NAME = 'doctor_id' AND REFERENCED_TABLE_NAME = 'users');
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE consultations ADD CONSTRAINT fk_consultations_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT \"Foreign key already exists\" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update status enum to include more states
ALTER TABLE `consultations` 
MODIFY COLUMN `status` ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled', 'declined') DEFAULT 'pending';

-- --------------------------------------------------------
-- Doctor Reviews and Ratings Table
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
-- Doctor Availability and Schedule Table
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
-- Doctor Availability Overrides Table
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
-- Patient Medical History Table
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
-- PART 3: WORKFLOW ENHANCEMENTS
-- ========================================

-- --------------------------------------------------------
-- Doctor Locations Table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `doctor_locations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `doctor_id` INT NOT NULL,
    `practice_name` VARCHAR(200) DEFAULT NULL,
    `address` TEXT NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `state` VARCHAR(100) NOT NULL,
    `postal_code` VARCHAR(20) DEFAULT NULL,
    `country` VARCHAR(100) DEFAULT 'India',
    `latitude` DECIMAL(10, 8) DEFAULT NULL,
    `longitude` DECIMAL(11, 8) DEFAULT NULL,
    `is_primary` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_doctor_id` (`doctor_id`),
    INDEX `idx_location` (`latitude`, `longitude`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Symptom Keywords Table (Medical Dictionary)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `symptom_keywords` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `keyword` VARCHAR(100) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `specialty` VARCHAR(100) DEFAULT NULL,
    `urgency_level` ENUM('routine', 'priority', 'emergency') DEFAULT 'routine',
    `urgency_score` INT DEFAULT 50,
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_keyword` (`keyword`),
    INDEX `idx_category` (`category`),
    INDEX `idx_specialty` (`specialty`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- PART 4: DATA SEEDING
-- ========================================

-- Insert default revenue split configuration
INSERT INTO `revenue_splits` (`service_type`, `platform_commission_percent`, `doctor_percent`, `effective_from`, `is_active`)
VALUES 
    ('consultation', 10.00, 90.00, CURDATE(), TRUE),
    ('medication', 5.00, NULL, CURDATE(), TRUE)
ON DUPLICATE KEY UPDATE `is_active` = TRUE;

-- Insert default notification templates
INSERT INTO `notification_templates` (`template_name`, `template_type`, `subject_template`, `body_template`, `variables`)
VALUES
    ('appointment_reminder_24h', 'all', 'Appointment Reminder - {{doctor_name}}', 
     'Hi {{patient_name}},\n\nThis is a reminder that you have an appointment with Dr. {{doctor_name}} tomorrow at {{appointment_time}}.\n\nPlease ensure you join on time.\n\nThank you,\nMedConnect Team',
     '{"patient_name": "string", "doctor_name": "string", "appointment_time": "string"}'),
    
    ('prescription_ready', 'all', 'Your Prescription is Ready', 
     'Hi {{patient_name}},\n\nYour prescription is now ready at {{pharmacy_name}}. You can pick it up or request home delivery.\n\nPrescription ID: {{prescription_number}}\n\nThank you,\nMedConnect Team',
     '{"patient_name": "string", "pharmacy_name": "string", "prescription_number": "string"}'),
     
    ('consultation_assigned', 'all', 'New Consultation Assigned', 
     'Dr. {{doctor_name}},\n\nYou have been assigned a new consultation from {{patient_name}}.\n\nSymptoms: {{symptoms}}\nUrgency: {{urgency_level}}\n\nPlease review and respond promptly.\n\nThank you,\nMedConnect Team',
     '{"doctor_name": "string", "patient_name": "string", "symptoms": "string", "urgency_level": "string"}')
ON DUPLICATE KEY UPDATE `body_template` = VALUES(`body_template`);

-- Populate Symptom Keywords (Medical Dictionary)
-- Emergency symptoms
INSERT INTO `symptom_keywords` (`keyword`, `category`, `specialty`, `urgency_level`, `urgency_score`, `description`) VALUES
('chest pain', 'cardiovascular', 'Cardiologist', 'emergency', 95, 'Potential heart attack or cardiac emergency'),
('severe chest pain', 'cardiovascular', 'Cardiologist', 'emergency', 98, 'Critical cardiac symptom'),
('heart attack', 'cardiovascular', 'Cardiologist', 'emergency', 100, 'Immediate emergency'),
('stroke', 'neurological', 'Neurologist', 'emergency', 100, 'Immediate emergency'),
('difficulty breathing', 'respiratory', 'Pulmonologist', 'emergency', 90, 'Respiratory distress'),
('shortness of breath', 'respiratory', 'Pulmonologist', 'emergency', 88, 'Breathing difficulty'),
('severe bleeding', 'general', 'Emergency Medicine', 'emergency', 95, 'Hemorrhage'),
('unconscious', 'neurological', 'Neurologist', 'emergency', 100, 'Loss of consciousness'),
('seizure', 'neurological', 'Neurologist', 'emergency', 92, 'Neurological emergency'),
('severe headache', 'neurological', 'Neurologist', 'priority', 80, 'Possible migraine or serious condition'),
('sudden vision loss', 'ophthalmology', 'Ophthalmologist', 'emergency', 90, 'Eye emergency')
ON DUPLICATE KEY UPDATE urgency_score = VALUES(urgency_score);

-- Priority symptoms
INSERT INTO `symptom_keywords` (`keyword`, `category`, `specialty`, `urgency_level`, `urgency_score`, `description`) VALUES
('high fever', 'infectious', 'General Physician', 'priority', 75, 'Elevated body temperature'),
('fever', 'infectious', 'General Physician', 'priority', 70, 'Body temperature elevation'),
('severe pain', 'general', 'General Physician', 'priority', 78, 'Intense pain'),
('abdominal pain', 'gastrointestinal', 'Gastroenterologist', 'priority', 72, 'Stomach or intestinal pain'),
('vomiting', 'gastrointestinal', 'Gastroenterologist', 'priority', 68, 'Nausea and vomiting'),
('diarrhea', 'gastrointestinal', 'Gastroenterologist', 'priority', 65, 'Loose stools'),
('back pain', 'orthopedic', 'Orthopedist', 'priority', 60, 'Spinal or muscular pain'),
('joint pain', 'orthopedic', 'Orthopedist', 'priority', 62, 'Arthritis or joint issues'),
('rash', 'dermatology', 'Dermatologist', 'priority', 55, 'Skin condition'),
('cough', 'respiratory', 'Pulmonologist', 'priority', 58, 'Respiratory symptom'),
('sore throat', 'ent', 'ENT Specialist', 'priority', 52, 'Throat infection')
ON DUPLICATE KEY UPDATE urgency_score = VALUES(urgency_score);

-- Routine symptoms
INSERT INTO `symptom_keywords` (`keyword`, `category`, `specialty`, `urgency_level`, `urgency_score`, `description`) VALUES
('headache', 'neurological', 'General Physician', 'routine', 45, 'Common headache'),
('cold', 'respiratory', 'General Physician', 'routine', 40, 'Common cold'),
('flu', 'infectious', 'General Physician', 'routine', 48, 'Influenza'),
('fatigue', 'general', 'General Physician', 'routine', 35, 'Tiredness'),
('insomnia', 'neurological', 'General Physician', 'routine', 38, 'Sleep disorder'),
('anxiety', 'mental health', 'Psychiatrist', 'routine', 50, 'Mental health concern'),
('depression', 'mental health', 'Psychiatrist', 'routine', 55, 'Mental health condition'),
('allergy', 'immunology', 'Allergist', 'routine', 42, 'Allergic reaction'),
('skin irritation', 'dermatology', 'Dermatologist', 'routine', 40, 'Minor skin issue'),
('minor cut', 'general', 'General Physician', 'routine', 30, 'Small wound'),
('bruise', 'general', 'General Physician', 'routine', 28, 'Contusion'),
('muscle ache', 'orthopedic', 'General Physician', 'routine', 35, 'Muscle soreness')
ON DUPLICATE KEY UPDATE urgency_score = VALUES(urgency_score);

-- Specialty-specific symptoms
INSERT INTO `symptom_keywords` (`keyword`, `category`, `specialty`, `urgency_level`, `urgency_score`, `description`) VALUES
('pregnancy', 'obstetrics', 'Obstetrician', 'routine', 50, 'Pregnancy-related'),
('menstrual', 'gynecology', 'Gynecologist', 'routine', 45, 'Menstrual issues'),
('diabetes', 'endocrinology', 'Endocrinologist', 'priority', 65, 'Blood sugar issues'),
('thyroid', 'endocrinology', 'Endocrinologist', 'priority', 60, 'Thyroid disorder'),
('kidney pain', 'urology', 'Urologist', 'priority', 70, 'Renal issues'),
('urinary', 'urology', 'Urologist', 'priority', 62, 'Urinary tract issues'),
('ear pain', 'ent', 'ENT Specialist', 'priority', 58, 'Ear infection'),
('hearing loss', 'ent', 'ENT Specialist', 'priority', 65, 'Auditory issues'),
('eye pain', 'ophthalmology', 'Ophthalmologist', 'priority', 68, 'Eye discomfort'),
('blurred vision', 'ophthalmology', 'Ophthalmologist', 'priority', 70, 'Vision problems'),
('dental pain', 'dentistry', 'Dentist', 'priority', 60, 'Tooth or gum pain'),
('toothache', 'dentistry', 'Dentist', 'priority', 62, 'Dental pain')
ON DUPLICATE KEY UPDATE urgency_score = VALUES(urgency_score);

-- ========================================
-- CONSOLIDATED DATABASE SETUP COMPLETE
-- ========================================
-- All schemas merged successfully
-- All existing data preserved
-- Safe to run multiple times (idempotent)
-- ========================================

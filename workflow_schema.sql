-- ========================================
-- Workflow Enhancement Schema
-- ========================================
-- Additional tables for complete workflow implementation

USE `medconnect`;

-- --------------------------------------------------------
-- 1. Doctor Locations Table
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
-- 2. Pharmacy Locations Table
-- --------------------------------------------------------
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
-- 3. Symptom Keywords Table (Medical Dictionary)
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

-- --------------------------------------------------------
-- 4. Extend Consultations Table
-- --------------------------------------------------------
-- Add matched_specialty column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'medconnect' AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'matched_specialty');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN matched_specialty VARCHAR(100) DEFAULT NULL AFTER language_preference', 
    'SELECT "Column matched_specialty already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add auto_assigned column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'medconnect' AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'auto_assigned');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN auto_assigned BOOLEAN DEFAULT FALSE AFTER matched_specialty', 
    'SELECT "Column auto_assigned already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- --------------------------------------------------------
-- 5. Notification Log Table
-- --------------------------------------------------------
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

-- --------------------------------------------------------
-- 6. Populate Symptom Keywords (Medical Dictionary)
-- --------------------------------------------------------

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
-- Schema Enhancement Complete
-- ========================================

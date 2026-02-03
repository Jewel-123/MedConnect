-- ========================================================
-- Medical AI Assistant - Database Schema
-- ========================================================
-- This schema supports the advanced symptom-matching AI assistant
-- with differential analysis, red-flag detection, and safety controls
-- ========================================================

USE `medconnect`;

-- --------------------------------------------------------
-- 1. Medical Conditions Table
-- --------------------------------------------------------
-- Stores medical conditions with descriptions and metadata
CREATE TABLE IF NOT EXISTS `medical_conditions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `condition_name` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `specialty` VARCHAR(100) NOT NULL,
    `severity_level` ENUM('mild', 'moderate', 'severe', 'critical') DEFAULT 'moderate',
    `requires_immediate_care` BOOLEAN DEFAULT FALSE,
    `common_age_range` VARCHAR(50) DEFAULT NULL,
    `gender_specific` ENUM('male', 'female', 'any') DEFAULT 'any',
    `prevalence` ENUM('very_common', 'common', 'uncommon', 'rare') DEFAULT 'common',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_specialty (`specialty`),
    INDEX idx_severity (`severity_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 2. Condition Symptoms Mapping Table
-- --------------------------------------------------------
-- Maps symptoms to conditions with likelihood scores
CREATE TABLE IF NOT EXISTS `condition_symptoms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `condition_id` INT NOT NULL,
    `symptom_name` VARCHAR(200) NOT NULL,
    `symptom_category` VARCHAR(100) DEFAULT NULL,
    `likelihood_score` INT DEFAULT 50 COMMENT 'Percentage 0-100',
    `is_primary_symptom` BOOLEAN DEFAULT FALSE COMMENT 'Key diagnostic symptom',
    `is_required` BOOLEAN DEFAULT FALSE COMMENT 'Must be present for diagnosis',
    `typical_onset` VARCHAR(100) DEFAULT NULL COMMENT 'e.g., sudden, gradual',
    `typical_duration` VARCHAR(100) DEFAULT NULL COMMENT 'e.g., hours, days, weeks',
    `notes` TEXT,
    FOREIGN KEY (`condition_id`) REFERENCES `medical_conditions`(`id`) ON DELETE CASCADE,
    INDEX idx_symptom (`symptom_name`),
    INDEX idx_condition (`condition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 3. Red Flag Symptoms Table
-- --------------------------------------------------------
-- Critical symptoms requiring immediate medical attention
CREATE TABLE IF NOT EXISTS `red_flag_symptoms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `symptom_keyword` VARCHAR(200) NOT NULL,
    `urgency_level` ENUM('urgent', 'emergency', 'critical') DEFAULT 'urgent',
    `warning_message` TEXT NOT NULL,
    `recommended_action` TEXT NOT NULL,
    `associated_conditions` TEXT COMMENT 'Comma-separated list of serious conditions',
    `context_required` JSON DEFAULT NULL COMMENT 'Age, gender, or other context that triggers this flag',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_keyword (`symptom_keyword`),
    INDEX idx_urgency (`urgency_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 4. Symptom Normalizations Table
-- --------------------------------------------------------
-- Maps informal language to medical terminology
CREATE TABLE IF NOT EXISTS `symptom_normalizations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `informal_term` VARCHAR(200) NOT NULL,
    `medical_term` VARCHAR(200) NOT NULL,
    `category` VARCHAR(100) DEFAULT NULL,
    `synonyms` TEXT COMMENT 'Comma-separated alternative informal terms',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_informal (`informal_term`),
    INDEX idx_medical (`medical_term`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 5. AI Analysis Logs Table
-- --------------------------------------------------------
-- Stores AI analysis results for auditing and improvement
CREATE TABLE IF NOT EXISTS `ai_analysis_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `consultation_id` INT DEFAULT NULL,
    `patient_id` INT DEFAULT NULL,
    `raw_symptoms` TEXT NOT NULL,
    `normalized_symptoms` JSON DEFAULT NULL,
    `extracted_context` JSON DEFAULT NULL COMMENT 'Age, gender, existing conditions',
    `matched_conditions` JSON DEFAULT NULL COMMENT 'Array of conditions with confidence scores',
    `red_flags_detected` JSON DEFAULT NULL,
    `clarifying_questions` JSON DEFAULT NULL,
    `confidence_summary` JSON DEFAULT NULL,
    `analysis_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX idx_consultation (`consultation_id`),
    INDEX idx_patient (`patient_id`),
    INDEX idx_timestamp (`analysis_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 6. Clarifying Questions Bank
-- --------------------------------------------------------
-- Pre-defined clarifying questions for different symptoms
CREATE TABLE IF NOT EXISTS `clarifying_questions_bank` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `symptom_keyword` VARCHAR(200) NOT NULL,
    `question_text` TEXT NOT NULL,
    `question_type` ENUM('yes_no', 'multiple_choice', 'scale', 'text') DEFAULT 'yes_no',
    `options` JSON DEFAULT NULL COMMENT 'For multiple choice questions',
    `helps_differentiate` TEXT COMMENT 'Which conditions this question helps distinguish',
    `priority` INT DEFAULT 5 COMMENT '1-10, higher = more important',
    INDEX idx_symptom (`symptom_keyword`),
    INDEX idx_priority (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Success Message
-- --------------------------------------------------------
SELECT 'Medical AI Schema created successfully!' AS Status;

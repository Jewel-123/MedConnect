-- ========================================
-- MedConnect Consultation Chat Enhancement
-- Real-Time Messaging with Workflow Guidance
-- ========================================

USE `medconnect`;

-- --------------------------------------------------------
-- 1. MESSAGES TABLE (Enhanced)
-- --------------------------------------------------------
-- Check if messages table exists, create if not
CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `consultation_id` INT NOT NULL,
    `sender_id` INT NOT NULL,
    `receiver_id` INT NOT NULL,
    `message_content` TEXT NOT NULL,
    `message_type` ENUM('text', 'file', 'signal', 'system') DEFAULT 'text',
    `is_read` BOOLEAN DEFAULT FALSE,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_consultation_id` (`consultation_id`),
    INDEX `idx_sender_id` (`sender_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. CONSULTATION CLINICAL NOTES TABLE
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `consultation_clinical_notes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `consultation_id` INT NOT NULL UNIQUE,
    `doctor_id` INT NOT NULL,
    `soap_notes` JSON DEFAULT NULL COMMENT 'Structured SOAP format: {subjective, objective, assessment, plan}',
    `private_notes` TEXT DEFAULT NULL,
    `consultation_summary` TEXT DEFAULT NULL,
    `last_autosaved_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_consultation_id` (`consultation_id`),
    INDEX `idx_doctor_id` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. MESSAGE CLASSIFICATION LOG TABLE
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `message_classification_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `message_id` INT NOT NULL,
    `consultation_id` INT NOT NULL,
    `classification_type` ENUM('non_clinical', 'partial_symptom', 'detailed_symptom', 'follow_up', 'general') NOT NULL,
    `confidence_score` DECIMAL(3, 2) DEFAULT NULL COMMENT '0.00 to 1.00',
    `detected_keywords` JSON DEFAULT NULL,
    `suggested_response` TEXT DEFAULT NULL,
    `workflow_stage_detected` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`message_id`) REFERENCES `messages`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE CASCADE,
    INDEX `idx_message_id` (`message_id`),
    INDEX `idx_consultation_id` (`consultation_id`),
    INDEX `idx_classification_type` (`classification_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. WORKFLOW GUIDANCE TEMPLATES TABLE
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `workflow_guidance_templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `workflow_stage` VARCHAR(50) NOT NULL UNIQUE,
    `stage_description` VARCHAR(200) NOT NULL,
    `suggested_questions` JSON DEFAULT NULL COMMENT 'Array of suggested follow-up questions',
    `guidance_text` TEXT DEFAULT NULL,
    `example_response` TEXT DEFAULT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_workflow_stage` (`workflow_stage`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default workflow guidance templates
INSERT IGNORE INTO `workflow_guidance_templates` 
(`workflow_stage`, `stage_description`, `suggested_questions`, `guidance_text`, `example_response`) 
VALUES
('greeting', 'Non-clinical greeting', 
 '["What symptoms are you experiencing today?", "How can I help you?", "What brings you here today?"]',
 'Patient sent a non-clinical greeting. Acknowledge briefly and immediately redirect to clinical information.',
 'Hi! To get started, please tell me what symptoms you''re experiencing today.'),

('chief_complaint', 'Initial symptom identification', 
 '["Where is the pain located?", "When did this start?", "How severe is it on a scale of 1-10?"]',
 'Patient mentioned a symptom but lacks detail. Ask structured follow-up questions about location, onset, and severity.',
 'Can you tell me where exactly the pain is located and when it started?'),

('hpi', 'History of present illness', 
 '["Does anything make it better or worse?", "Have you tried any treatments?", "Any associated symptoms?"]',
 'Gather detailed history: onset, progression, triggers, relief factors, previous treatments.',
 'Has anything made it better or worse? Have you taken any medication for this?'),

('medical_history', 'Past medical history', 
 '["Do you have any chronic conditions?", "Are you currently taking any medications?", "Any known allergies?"]',
 'Only ask if relevant to current complaint. Focus on conditions, medications, and allergies.',
 'Do you have any chronic health conditions I should know about?'),

('assessment', 'Clinical assessment', 
 '["Based on your symptoms, this could be related to..."]',
 'Summarize findings and state preliminary impression.',
 'Based on what you''ve shared, this may be related to...'),

('plan', 'Treatment planning', 
 '["I recommend...", "You should monitor for...", "Follow up if..."]',
 'Provide advice, recommended tests, prescriptions, red flags, and follow-up instructions.',
 'I recommend you take... and monitor for any worsening symptoms.'),

('closing', 'Consultation closure', 
 '["Do you have any other questions?", "Is there anything else I can help with?"]',
 'Confirm patient understanding and close professionally.',
 'Do you have any questions about the treatment plan?');

-- ========================================
-- SCHEMA COMPLETE
-- ========================================

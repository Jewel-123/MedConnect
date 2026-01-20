-- ========================================
-- RESTORE SAMPLE DATA FOR MEDCONNECT
-- ========================================
-- This script recreates admin, doctors, patients, and sample data
-- Safe to run multiple times
-- ========================================

USE `medconnect`;

-- ========================================
-- 1. CREATE ADMIN USER
-- ========================================
-- Password: admin123 (hashed with password_hash)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`, `created_at`)
VALUES ('Admin User', 'admin@medconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'approved', NOW())
ON DUPLICATE KEY UPDATE `status` = 'approved';

-- ========================================
-- 2. CREATE SAMPLE DOCTORS
-- ========================================
-- Password for all: doctor123

-- Dr. Sarah Johnson - Cardiologist
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`, `status`, `created_at`)
VALUES ('Dr. Sarah Johnson', 'sarah.johnson@medconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91-9876543210', 'doctor', 'approved', NOW())
ON DUPLICATE KEY UPDATE `status` = 'approved';

SET @sarah_id = (SELECT id FROM users WHERE email = 'sarah.johnson@medconnect.com');

INSERT INTO `doctor_profiles` (`user_id`, `specialization`, `qualification`, `license_number`, `years_experience`, `consultation_fee`, `rating`, `languages`, `bio`)
VALUES (@sarah_id, 'Cardiologist', 'MBBS, MD (Cardiology)', 'MCI-12345', 15, 800.00, 4.8, 'English, Hindi', 'Experienced cardiologist specializing in heart diseases and preventive cardiology.')
ON DUPLICATE KEY UPDATE `specialization` = 'Cardiologist';

-- Dr. Michael Chen - General Physician
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`, `status`, `created_at`)
VALUES ('Dr. Michael Chen', 'michael.chen@medconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91-9876543211', 'doctor', 'approved', NOW())
ON DUPLICATE KEY UPDATE `status` = 'approved';

SET @michael_id = (SELECT id FROM users WHERE email = 'michael.chen@medconnect.com');

INSERT INTO `doctor_profiles` (`user_id`, `specialization`, `qualification`, `license_number`, `years_experience`, `consultation_fee`, `rating`, `languages`, `bio`)
VALUES (@michael_id, 'General Physician', 'MBBS, MD (Internal Medicine)', 'MCI-12346', 10, 500.00, 4.7, 'English, Hindi, Tamil', 'General physician with expertise in treating common ailments and preventive healthcare.')
ON DUPLICATE KEY UPDATE `specialization` = 'General Physician';

-- Dr. Priya Sharma - Dermatologist
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`, `status`, `created_at`)
VALUES ('Dr. Priya Sharma', 'priya.sharma@medconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91-9876543212', 'doctor', 'approved', NOW())
ON DUPLICATE KEY UPDATE `status` = 'approved';

SET @priya_id = (SELECT id FROM users WHERE email = 'priya.sharma@medconnect.com');

INSERT INTO `doctor_profiles` (`user_id`, `specialization`, `qualification`, `license_number`, `years_experience`, `consultation_fee`, `rating`, `languages`, `bio`)
VALUES (@priya_id, 'Dermatologist', 'MBBS, MD (Dermatology)', 'MCI-12347', 8, 600.00, 4.9, 'English, Hindi', 'Dermatologist specializing in skin conditions, cosmetic procedures, and hair treatments.')
ON DUPLICATE KEY UPDATE `specialization` = 'Dermatologist';

-- Dr. Rajesh Kumar - Orthopedist
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`, `status`, `created_at`)
VALUES ('Dr. Rajesh Kumar', 'rajesh.kumar@medconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91-9876543213', 'doctor', 'approved', NOW())
ON DUPLICATE KEY UPDATE `status` = 'approved';

SET @rajesh_id = (SELECT id FROM users WHERE email = 'rajesh.kumar@medconnect.com');

INSERT INTO `doctor_profiles` (`user_id`, `specialization`, `qualification`, `license_number`, `years_experience`, `consultation_fee`, `rating`, `languages`, `bio`)
VALUES (@rajesh_id, 'Orthopedist', 'MBBS, MS (Orthopedics)', 'MCI-12348', 12, 700.00, 4.6, 'English, Hindi, Punjabi', 'Orthopedic surgeon specializing in joint replacements and sports injuries.')
ON DUPLICATE KEY UPDATE `specialization` = 'Orthopedist';

-- Dr. Emily White - Pediatrician
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`, `status`, `created_at`)
VALUES ('Dr. Emily White', 'emily.white@medconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91-9876543214', 'doctor', 'approved', NOW())
ON DUPLICATE KEY UPDATE `status` = 'approved';

SET @emily_id = (SELECT id FROM users WHERE email = 'emily.white@medconnect.com');

INSERT INTO `doctor_profiles` (`user_id`, `specialization`, `qualification`, `license_number`, `years_experience`, `consultation_fee`, `rating`, `languages`, `bio`)
VALUES (@emily_id, 'Pediatrician', 'MBBS, MD (Pediatrics)', 'MCI-12349', 9, 550.00, 4.8, 'English, Hindi', 'Pediatrician with expertise in child healthcare, vaccinations, and developmental disorders.')
ON DUPLICATE KEY UPDATE `specialization` = 'Pediatrician';

-- Dr. Amit Patel - Neurologist
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`, `status`, `created_at`)
VALUES ('Dr. Amit Patel', 'amit.patel@medconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91-9876543215', 'doctor', 'approved', NOW())
ON DUPLICATE KEY UPDATE `status` = 'approved';

SET @amit_id = (SELECT id FROM users WHERE email = 'amit.patel@medconnect.com');

INSERT INTO `doctor_profiles` (`user_id`, `specialization`, `qualification`, `license_number`, `years_experience`, `consultation_fee`, `rating`, `languages`, `bio`)
VALUES (@amit_id, 'Neurologist', 'MBBS, DM (Neurology)', 'MCI-12350', 14, 900.00, 4.7, 'English, Hindi, Gujarati', 'Neurologist specializing in brain and nervous system disorders.')
ON DUPLICATE KEY UPDATE `specialization` = 'Neurologist';

-- ========================================
-- 3. CREATE SAMPLE PATIENTS
-- ========================================
-- Password for all: patient123

-- John Doe
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`, `status`, `created_at`)
VALUES ('John Doe', 'john.doe@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91-9123456780', 'patient', 'approved', NOW())
ON DUPLICATE KEY UPDATE `status` = 'approved';

SET @john_id = (SELECT id FROM users WHERE email = 'john.doe@example.com');

INSERT INTO `patient_profiles` (`user_id`, `date_of_birth`, `gender`, `blood_group`, `address`, `emergency_contact`)
VALUES (@john_id, '1985-05-15', 'male', 'O+', '123 Main St, Mumbai, Maharashtra', '+91-9123456781')
ON DUPLICATE KEY UPDATE `gender` = 'male';

-- Jane Smith
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`, `status`, `created_at`)
VALUES ('Jane Smith', 'jane.smith@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91-9123456782', 'patient', 'approved', NOW())
ON DUPLICATE KEY UPDATE `status` = 'approved';

SET @jane_id = (SELECT id FROM users WHERE email = 'jane.smith@example.com');

INSERT INTO `patient_profiles` (`user_id`, `date_of_birth`, `gender`, `blood_group`, `address`, `emergency_contact`)
VALUES (@jane_id, '1990-08-22', 'female', 'A+', '456 Park Ave, Delhi, Delhi', '+91-9123456783')
ON DUPLICATE KEY UPDATE `gender` = 'female';

-- Rahul Verma
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`, `status`, `created_at`)
VALUES ('Rahul Verma', 'rahul.verma@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91-9123456784', 'patient', 'approved', NOW())
ON DUPLICATE KEY UPDATE `status` = 'approved';

SET @rahul_id = (SELECT id FROM users WHERE email = 'rahul.verma@example.com');

INSERT INTO `patient_profiles` (`user_id`, `date_of_birth`, `gender`, `blood_group`, `address`, `emergency_contact`)
VALUES (@rahul_id, '1988-03-10', 'male', 'B+', '789 Lake Road, Bangalore, Karnataka', '+91-9123456785')
ON DUPLICATE KEY UPDATE `gender` = 'male';

-- ========================================
-- 4. CREATE SAMPLE CONSULTATIONS
-- ========================================

-- Consultation 1: John Doe with Dr. Sarah Johnson (Completed)
INSERT INTO `consultations` (`patient_id`, `doctor_id`, `symptoms`, `severity`, `urgency_score`, `status`, `consultation_mode`, `created_at`, `assigned_at`, `completed_at`)
VALUES (@john_id, @sarah_id, 'Chest pain, shortness of breath', 'emergency', 95, 'completed', 'video', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY))
ON DUPLICATE KEY UPDATE `status` = 'completed';

-- Consultation 2: Jane Smith with Dr. Priya Sharma (In Progress)
INSERT INTO `consultations` (`patient_id`, `doctor_id`, `symptoms`, `severity`, `urgency_score`, `status`, `consultation_mode`, `created_at`, `assigned_at`)
VALUES (@jane_id, @priya_id, 'Skin rash, itching', 'moderate', 55, 'in_progress', 'chat', DATE_SUB(NOW(), INTERVAL 1 HOUR), DATE_SUB(NOW(), INTERVAL 30 MINUTE))
ON DUPLICATE KEY UPDATE `status` = 'in_progress';

-- Consultation 3: Rahul Verma with Dr. Michael Chen (Pending)
INSERT INTO `consultations` (`patient_id`, `symptoms`, `severity`, `urgency_score`, `status`, `consultation_mode`, `created_at`)
VALUES (@rahul_id, 'Fever, headache, body ache', 'moderate', 70, 'pending', 'video', DATE_SUB(NOW(), INTERVAL 10 MINUTE))
ON DUPLICATE KEY UPDATE `status` = 'pending';

-- ========================================
-- 5. CREATE SAMPLE PHARMACY
-- ========================================

INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`, `status`, `created_at`)
VALUES ('Apollo Pharmacy', 'apollo@pharmacy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91-9876500001', 'pharmacy', 'approved', NOW())
ON DUPLICATE KEY UPDATE `status` = 'approved';

SET @pharmacy_id = (SELECT id FROM users WHERE email = 'apollo@pharmacy.com');

INSERT INTO `pharmacy_profiles` (`user_id`, `pharmacy_name`, `license_number`, `owner_name`, `pharmacy_type`, `delivery_available`, `verification_status`)
VALUES (@pharmacy_id, 'Apollo Pharmacy - Main Branch', 'PHARM-2024-001', 'Apollo Healthcare Ltd', 'chain', TRUE, 'verified')
ON DUPLICATE KEY UPDATE `verification_status` = 'verified';

-- ========================================
-- SUMMARY
-- ========================================
SELECT 'Sample data restored successfully!' AS message;
SELECT COUNT(*) AS total_users FROM users;
SELECT COUNT(*) AS total_doctors FROM doctor_profiles;
SELECT COUNT(*) AS total_patients FROM patient_profiles;
SELECT COUNT(*) AS total_consultations FROM consultations;

-- ========================================
-- LOGIN CREDENTIALS
-- ========================================
-- Admin: admin@medconnect.com / admin123
-- Doctors: (email) / doctor123
--   - sarah.johnson@medconnect.com (Cardiologist)
--   - michael.chen@medconnect.com (General Physician)
--   - priya.sharma@medconnect.com (Dermatologist)
--   - rajesh.kumar@medconnect.com (Orthopedist)
--   - emily.white@medconnect.com (Pediatrician)
--   - amit.patel@medconnect.com (Neurologist)
-- Patients: (email) / patient123
--   - john.doe@example.com
--   - jane.smith@example.com
--   - rahul.verma@example.com
-- Pharmacy: apollo@pharmacy.com / doctor123
-- ========================================

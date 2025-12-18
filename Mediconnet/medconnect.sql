-- Database: `medconnect`

CREATE DATABASE IF NOT EXISTS `medconnect`;
USE `medconnect`;

-- --------------------------------------------------------
-- 1. Users Table (Core Auth)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('patient', 'doctor', 'admin', 'pharmacy') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- --------------------------------------------------------
-- 2. Patient Profiles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `patient_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `date_of_birth` DATE,
    `gender` ENUM('male', 'female', 'other'),
    `phone` VARCHAR(20),
    `address` TEXT,
    `medical_history_summary` TEXT,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- 3. Doctor Profiles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `doctor_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `specialization` VARCHAR(100) NOT NULL,
    `license_number` VARCHAR(50) NOT NULL,
    `bio` TEXT,
    `consultation_fee` DECIMAL(10, 2) DEFAULT 0.00,
    `availability_schedule` JSON, -- Stores availability like {"Monday": "09:00-17:00"}
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- 4. Pharmacy Profiles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pharmacy_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `pharmacy_name` VARCHAR(150) NOT NULL,
    `address` TEXT NOT NULL,
    `license_number` VARCHAR(50) NOT NULL,
    `contact_number` VARCHAR(20),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- 5. Appointments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `doctor_id` INT NOT NULL,
    `appointment_date` DATETIME NOT NULL,
    `status` ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    `symptom_description` TEXT,
    `meeting_link` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- 6. Medical Records (Diagnosis & Notes)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `medical_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `doctor_id` INT NOT NULL,
    `appointment_id` INT,
    `diagnosis` TEXT NOT NULL,
    `treatment_plan` TEXT,
    `visit_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- 7. Prescriptions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prescriptions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `appointment_id` INT,
    `patient_id` INT NOT NULL,
    `doctor_id` INT NOT NULL,
    `pharmacy_id` INT DEFAULT NULL,
    `status` ENUM('issued', 'forwarded_to_pharmacy', 'filled', 'cancelled') DEFAULT 'issued',
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`pharmacy_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- 8. Prescription Items (Medicines)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prescription_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `prescription_id` INT NOT NULL,
    `medicine_name` VARCHAR(150) NOT NULL,
    `dosage` VARCHAR(100) NOT NULL,
    `frequency` VARCHAR(100),
    `duration` VARCHAR(50),
    FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- 9. Messages (Chat)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sender_id` INT NOT NULL,
    `receiver_id` INT NOT NULL,
    `message_content` TEXT NOT NULL,
    `is_read` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- 10. Symptom Checks (AI Log)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `symptom_checks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL, -- Can be null for guest checks
    `symptoms_input` JSON,
    `ai_prediction` TEXT,
    `checked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- SAMPLE DATA
-- --------------------------------------------------------
INSERT INTO `users` (`full_name`, `email`, `password`, `role`) VALUES
('Admin User', 'admin@medconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Dr. Smith', 'doctor@medconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor'),
('Jane Doe', 'patient@medconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient'),
('City Pharmacy', 'pharmacy@medconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pharmacy');
-- Note: Password for all is 'password'

-- Add profiles for sample users (assuming IDs 1, 2, 3, 4 respectively)
-- We need to fetch IDs dynamically in a real app, but for this SQL script we assume auto-increment starts at 1 if fresh.
-- If not fresh, these inserts might fail on duplicate keys or wrong FKs if IDs differ.
-- For a robust setup script, we often rely on application logic or subqueries, but subqueries in VALUES are limited.
-- We will proceed with simple inserts, user can clear DB if needed.

INSERT INTO `doctor_profiles` (`user_id`, `specialization`, `license_number`, `bio`, `consultation_fee`) 
SELECT id, 'General Practitioner', 'LIC-12345', 'Experienced GP with 10 years practice.', 50.00 FROM `users` WHERE email='doctor@medconnect.com';

INSERT INTO `patient_profiles` (`user_id`, `date_of_birth`, `gender`, `address`) 
SELECT id, '1990-05-15', 'female', '123 Main St, New York' FROM `users` WHERE email='patient@medconnect.com';

INSERT INTO `pharmacy_profiles` (`user_id`, `pharmacy_name`, `address`, `license_number`) 
SELECT id, 'City Pharmacy', '456 High St, New York', 'PH-98765' FROM `users` WHERE email='pharmacy@medconnect.com';

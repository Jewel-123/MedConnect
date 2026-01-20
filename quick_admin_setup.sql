DROP DATABASE IF EXISTS `medconnect`;
CREATE DATABASE `medconnect` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `medconnect`;

-- Create users table
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `role` ENUM('patient', 'doctor', 'admin', 'pharmacy', 'clinic') NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `google_id` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create admin user
-- Email: admin@medconnect.com
-- Password: admin123
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`) 
VALUES ('Admin User', 'admin@medconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'approved');

SELECT 'Admin created successfully!' AS message;
SELECT * FROM users WHERE email = 'admin@medconnect.com';

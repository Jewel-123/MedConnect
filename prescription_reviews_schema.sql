-- ========================================
-- Prescription Reviews Schema
-- ========================================
-- Table for storing patient reviews and ratings
-- for completed prescription orders
-- ========================================

USE `medconnect`;

-- Create prescription reviews table
CREATE TABLE IF NOT EXISTS `prescription_reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `prescription_id` INT NOT NULL,
    `prescription_order_id` INT NOT NULL,
    `patient_id` INT NOT NULL,
    `pharmacy_id` INT NOT NULL,
    `rating` TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    `review_text` TEXT DEFAULT NULL,
    `service_quality` TINYINT DEFAULT NULL CHECK (service_quality BETWEEN 1 AND 5),
    `delivery_speed` TINYINT DEFAULT NULL CHECK (delivery_speed BETWEEN 1 AND 5),
    `medicine_quality` TINYINT DEFAULT NULL CHECK (medicine_quality BETWEEN 1 AND 5),
    `would_recommend` BOOLEAN DEFAULT TRUE,
    `is_verified_purchase` BOOLEAN DEFAULT TRUE,
    `pharmacy_response` TEXT DEFAULT NULL,
    `pharmacy_response_at` TIMESTAMP NULL DEFAULT NULL,
    `is_published` BOOLEAN DEFAULT TRUE,
    `helpful_count` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions_v2`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`prescription_order_id`) REFERENCES `prescription_orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`pharmacy_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_prescription_review` (`prescription_order_id`, `patient_id`),
    INDEX `idx_prescription_id` (`prescription_id`),
    INDEX `idx_patient_id` (`patient_id`),
    INDEX `idx_pharmacy_id` (`pharmacy_id`),
    INDEX `idx_rating` (`rating`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add review_submitted flag to prescription_orders
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prescription_orders' AND COLUMN_NAME = 'review_submitted');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE prescription_orders ADD COLUMN review_submitted BOOLEAN DEFAULT FALSE AFTER payment_transaction_id', 
    'SELECT "Column review_submitted already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add completed_at timestamp to prescription_orders if not exists
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prescription_orders' AND COLUMN_NAME = 'completed_at');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE prescription_orders ADD COLUMN completed_at TIMESTAMP NULL DEFAULT NULL AFTER delivered_at', 
    'SELECT "Column completed_at already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Success message
SELECT 'Prescription Reviews Schema Created Successfully!' AS Status;

-- ========================================
-- PHARMACY DASHBOARD ENHANCEMENT SCHEMA
-- ========================================
-- Enhances existing pharmacy tables with new features
-- Adds inventory alerts, notifications, and analytics

USE `medconnect`;

-- --------------------------------------------------------
-- 1. Enhance pharmacy_profiles table
-- --------------------------------------------------------

-- Add notification preferences
ALTER TABLE `pharmacy_profiles` 
ADD COLUMN IF NOT EXISTS `auto_accept_prescriptions` BOOLEAN DEFAULT FALSE AFTER `delivery_fee`,
ADD COLUMN IF NOT EXISTS `notification_email` VARCHAR(255) DEFAULT NULL AFTER `auto_accept_prescriptions`,
ADD COLUMN IF NOT EXISTS `notification_phone` VARCHAR(20) DEFAULT NULL AFTER `notification_email`,
ADD COLUMN IF NOT EXISTS `sms_notifications_enabled` BOOLEAN DEFAULT TRUE AFTER `notification_phone`,
ADD COLUMN IF NOT EXISTS `email_notifications_enabled` BOOLEAN DEFAULT TRUE AFTER `sms_notifications_enabled`,
ADD COLUMN IF NOT EXISTS `in_app_notifications_enabled` BOOLEAN DEFAULT TRUE AFTER `email_notifications_enabled`;

-- --------------------------------------------------------
-- 2. Enhance pharmacy_inventory table
-- --------------------------------------------------------

-- Add inventory management fields
ALTER TABLE `pharmacy_inventory`
ADD COLUMN IF NOT EXISTS `low_stock_threshold` INT DEFAULT 10 AFTER `stock_quantity`,
ADD COLUMN IF NOT EXISTS `reorder_quantity` INT DEFAULT 50 AFTER `low_stock_threshold`,
ADD COLUMN IF NOT EXISTS `last_restocked_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`;

-- --------------------------------------------------------
-- 3. Enhance prescription_orders table
-- --------------------------------------------------------

-- Add order tracking fields
ALTER TABLE `prescription_orders`
ADD COLUMN IF NOT EXISTS `payment_confirmed_at` TIMESTAMP NULL DEFAULT NULL AFTER `payment_transaction_id`,
ADD COLUMN IF NOT EXISTS `packaging_completed_at` TIMESTAMP NULL DEFAULT NULL AFTER `payment_confirmed_at`,
ADD COLUMN IF NOT EXISTS `rejection_reason` TEXT DEFAULT NULL AFTER `notes`;

-- --------------------------------------------------------
-- 4. Create pharmacy_notifications table
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `pharmacy_notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pharmacy_id` INT NOT NULL,
    `notification_type` ENUM('new_prescription', 'payment_received', 'low_stock', 'order_update', 'system') NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `related_id` INT DEFAULT NULL,
    `related_type` ENUM('prescription', 'order', 'inventory') DEFAULT NULL,
    `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    `is_read` BOOLEAN DEFAULT FALSE,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`pharmacy_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_pharmacy_id` (`pharmacy_id`),
    INDEX `idx_is_read` (`is_read`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_notification_type` (`notification_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Create pharmacy_inventory_alerts table
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `pharmacy_inventory_alerts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pharmacy_id` INT NOT NULL,
    `inventory_id` INT NOT NULL,
    `alert_type` ENUM('low_stock', 'out_of_stock', 'expiring_soon', 'expired') NOT NULL,
    `medicine_name` VARCHAR(200) NOT NULL,
    `current_stock` INT DEFAULT 0,
    `threshold` INT DEFAULT 0,
    `expiry_date` DATE DEFAULT NULL,
    `is_resolved` BOOLEAN DEFAULT FALSE,
    `resolved_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`pharmacy_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`inventory_id`) REFERENCES `pharmacy_inventory`(`id`) ON DELETE CASCADE,
    INDEX `idx_pharmacy_id` (`pharmacy_id`),
    INDEX `idx_alert_type` (`alert_type`),
    INDEX `idx_is_resolved` (`is_resolved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Create pharmacy_analytics table
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `pharmacy_analytics` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pharmacy_id` INT NOT NULL,
    `date` DATE NOT NULL,
    `total_prescriptions_received` INT DEFAULT 0,
    `prescriptions_accepted` INT DEFAULT 0,
    `prescriptions_rejected` INT DEFAULT 0,
    `orders_completed` INT DEFAULT 0,
    `orders_cancelled` INT DEFAULT 0,
    `total_revenue` DECIMAL(10, 2) DEFAULT 0.00,
    `platform_commission` DECIMAL(10, 2) DEFAULT 0.00,
    `net_earnings` DECIMAL(10, 2) DEFAULT 0.00,
    `fulfillment_rate` DECIMAL(5, 2) DEFAULT 0.00,
    `average_order_value` DECIMAL(10, 2) DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`pharmacy_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_pharmacy_date` (`pharmacy_id`, `date`),
    INDEX `idx_pharmacy_id` (`pharmacy_id`),
    INDEX `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. Create pharmacy_settings table
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `pharmacy_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pharmacy_id` INT NOT NULL UNIQUE,
    `auto_accept_prescriptions` BOOLEAN DEFAULT FALSE,
    `require_payment_before_preparation` BOOLEAN DEFAULT TRUE,
    `enable_delivery_tracking` BOOLEAN DEFAULT TRUE,
    `enable_sms_notifications` BOOLEAN DEFAULT TRUE,
    `enable_email_notifications` BOOLEAN DEFAULT TRUE,
    `enable_push_notifications` BOOLEAN DEFAULT TRUE,
    `working_hours_start` TIME DEFAULT '09:00:00',
    `working_hours_end` TIME DEFAULT '21:00:00',
    `max_daily_orders` INT DEFAULT 100,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`pharmacy_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_pharmacy_id` (`pharmacy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8. Insert default settings for existing pharmacy
-- --------------------------------------------------------

INSERT INTO `pharmacy_settings` (`pharmacy_id`, `auto_accept_prescriptions`, `enable_sms_notifications`, `enable_email_notifications`)
SELECT u.id, FALSE, TRUE, TRUE
FROM users u
WHERE u.role = 'pharmacy' AND u.email = 'pharmacy@medconnect.com'
ON DUPLICATE KEY UPDATE pharmacy_id = pharmacy_id;

-- --------------------------------------------------------
-- 9. Update pharmacy profile with notification preferences
-- --------------------------------------------------------

UPDATE `pharmacy_profiles` pp
JOIN `users` u ON pp.user_id = u.id
SET 
    pp.notification_email = u.email,
    pp.notification_phone = pp.phone_number,
    pp.sms_notifications_enabled = TRUE,
    pp.email_notifications_enabled = TRUE,
    pp.in_app_notifications_enabled = TRUE
WHERE u.role = 'pharmacy' AND u.email = 'pharmacy@medconnect.com';

-- ========================================
-- PHARMACY SCHEMA ENHANCEMENT COMPLETE
-- ========================================

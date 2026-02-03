-- ========================================
-- Prescription Workflow Schema Update
-- ========================================
-- Updates prescription status lifecycle and adds tracking timestamps
-- Safe migration - preserves all existing data
-- ========================================

USE `medconnect`;

-- ========================================
-- 1. Update prescriptions_v2 status enum
-- ========================================

-- Update status enum to include new workflow statuses
ALTER TABLE `prescriptions_v2` 
MODIFY COLUMN `status` ENUM(
    'draft', 
    'finalized', 
    'sent_to_pharmacy', 
    'in_progress', 
    'ready', 
    'completed', 
    'cancelled'
) DEFAULT 'draft';

-- Migrate existing 'issued' status to 'finalized'
UPDATE `prescriptions_v2` 
SET `status` = 'finalized' 
WHERE `status` = 'issued';

-- Migrate existing 'filled' status to 'completed'
UPDATE `prescriptions_v2` 
SET `status` = 'completed' 
WHERE `status` = 'filled';

-- ========================================
-- 2. Add tracking timestamp columns
-- ========================================

-- Add finalized_at timestamp
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prescriptions_v2' AND COLUMN_NAME = 'finalized_at');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE prescriptions_v2 ADD COLUMN finalized_at TIMESTAMP NULL DEFAULT NULL AFTER created_at', 
    'SELECT "Column finalized_at already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add sent_to_pharmacy_at timestamp
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prescriptions_v2' AND COLUMN_NAME = 'sent_to_pharmacy_at');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE prescriptions_v2 ADD COLUMN sent_to_pharmacy_at TIMESTAMP NULL DEFAULT NULL AFTER finalized_at', 
    'SELECT "Column sent_to_pharmacy_at already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add in_progress_at timestamp
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prescriptions_v2' AND COLUMN_NAME = 'in_progress_at');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE prescriptions_v2 ADD COLUMN in_progress_at TIMESTAMP NULL DEFAULT NULL AFTER sent_to_pharmacy_at', 
    'SELECT "Column in_progress_at already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add ready_at timestamp
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prescriptions_v2' AND COLUMN_NAME = 'ready_at');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE prescriptions_v2 ADD COLUMN ready_at TIMESTAMP NULL DEFAULT NULL AFTER in_progress_at', 
    'SELECT "Column ready_at already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add completed_at timestamp
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prescriptions_v2' AND COLUMN_NAME = 'completed_at');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE prescriptions_v2 ADD COLUMN completed_at TIMESTAMP NULL DEFAULT NULL AFTER ready_at', 
    'SELECT "Column completed_at already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add cancelled_at timestamp
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prescriptions_v2' AND COLUMN_NAME = 'cancelled_at');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE prescriptions_v2 ADD COLUMN cancelled_at TIMESTAMP NULL DEFAULT NULL AFTER completed_at', 
    'SELECT "Column cancelled_at already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add cancellation_reason column
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prescriptions_v2' AND COLUMN_NAME = 'cancellation_reason');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE prescriptions_v2 ADD COLUMN cancellation_reason TEXT DEFAULT NULL AFTER cancelled_at', 
    'SELECT "Column cancellation_reason already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ========================================
-- 3. Create Central Pharmacy if not exists
-- ========================================

-- Check if pharmacy role user exists
INSERT INTO `users` (
    `email`, 
    `password`, 
    `full_name`, 
    `phone`, 
    `role`, 
    `is_verified`, 
    `created_at`
)
SELECT 
    'central.pharmacy@medconnect.com',
    '$2y$10$YourHashedPasswordHere', -- Change this to actual hashed password
    'Central Pharmacy',
    '1800-MEDCONNECT',
    'pharmacy',
    1,
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `users` WHERE `email` = 'central.pharmacy@medconnect.com'
);

-- Get Central Pharmacy ID
SET @central_pharmacy_id = (SELECT id FROM users WHERE email = 'central.pharmacy@medconnect.com' LIMIT 1);

-- Create pharmacy profile if not exists
INSERT INTO `pharmacy_profiles` (
    `user_id`,
    `pharmacy_name`,
    `license_number`,
    `owner_name`,
    `pharmacy_type`,
    `operating_hours`,
    `delivery_available`,
    `verification_status`,
    `verification_date`,
    `created_at`
)
SELECT
    @central_pharmacy_id,
    'Central Pharmacy',
    'PH-CENTRAL-2026',
    'MedConnect Admin',
    'hospital',
    '24/7',
    TRUE,
    'verified',
    CURDATE(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `pharmacy_profiles` WHERE `user_id` = @central_pharmacy_id
);

-- ========================================
-- 4. Update prescription_orders status enum
-- ========================================

-- Update order_status enum to match prescription workflow
ALTER TABLE `prescription_orders` 
MODIFY COLUMN `order_status` ENUM(
    'pending',
    'accepted', 
    'preparing',
    'in_progress',
    'ready', 
    'out_for_delivery', 
    'delivered',
    'completed',
    'cancelled'
) DEFAULT 'pending';

-- ========================================
-- 5. Add indexes for performance
-- ========================================

-- Add index on status for faster queries
CREATE INDEX IF NOT EXISTS `idx_status` ON `prescriptions_v2` (`status`);

-- Add index on pharmacy_id and status combination
CREATE INDEX IF NOT EXISTS `idx_pharmacy_status` ON `prescriptions_v2` (`pharmacy_id`, `status`);

-- Add index on patient_id and status combination
CREATE INDEX IF NOT EXISTS `idx_patient_status` ON `prescriptions_v2` (`patient_id`, `status`);

-- ========================================
-- Success Message
-- ========================================
SELECT 'Prescription Workflow Schema Updated Successfully!' AS Status;
SELECT CONCAT('Central Pharmacy ID: ', @central_pharmacy_id) AS Info;

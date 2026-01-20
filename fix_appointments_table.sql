-- Fix appointments table - Add missing columns if they don't exist

USE `medconnect`;

-- Check and add missing columns to appointments table

-- Add appointment_type if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'appointments' AND COLUMN_NAME = 'appointment_type');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE appointments ADD COLUMN appointment_type ENUM(''instant'', ''scheduled'') DEFAULT ''scheduled'' AFTER doctor_id', 
    'SELECT "Column appointment_type already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add scheduled_date if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'appointments' AND COLUMN_NAME = 'scheduled_date');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE appointments ADD COLUMN scheduled_date DATE NOT NULL AFTER appointment_type', 
    'SELECT "Column scheduled_date already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add scheduled_time if missing  
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'appointments' AND COLUMN_NAME = 'scheduled_time');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE appointments ADD COLUMN scheduled_time TIME NOT NULL AFTER scheduled_date', 
    'SELECT "Column scheduled_time already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Appointments table columns fixed!' AS result;

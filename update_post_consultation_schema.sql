-- ========================================
-- POST-CONSULTATION WORKFLOW SCHEMA UPDATES
-- ========================================
-- Adds required columns for post-consultation workflow
-- Safe to run multiple times (uses IF NOT EXISTS checks)
-- ========================================

USE `medconnect`;

-- Add diagnosis and medical advice columns to consultations table
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'diagnosis');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN diagnosis TEXT DEFAULT NULL AFTER symptoms', 
    'SELECT "Column diagnosis already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'medical_advice');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN medical_advice TEXT DEFAULT NULL AFTER diagnosis', 
    'SELECT "Column medical_advice already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add follow-up scheduling columns
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'follow_up_scheduled');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN follow_up_scheduled DATE DEFAULT NULL AFTER completed_at', 
    'SELECT "Column follow_up_scheduled already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'consultations' AND COLUMN_NAME = 'follow_up_notes');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE consultations ADD COLUMN follow_up_notes TEXT DEFAULT NULL AFTER follow_up_scheduled', 
    'SELECT "Column follow_up_notes already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add auto-send pharmacy flag to prescriptions
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prescriptions_v2' AND COLUMN_NAME = 'auto_sent_to_pharmacy');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE prescriptions_v2 ADD COLUMN auto_sent_to_pharmacy BOOLEAN DEFAULT FALSE AFTER sent_at', 
    'SELECT "Column auto_sent_to_pharmacy already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify all columns were added successfully
SELECT 
    'consultations' as table_name,
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'consultations'
    AND COLUMN_NAME IN ('diagnosis', 'medical_advice', 'follow_up_scheduled', 'follow_up_notes')
UNION ALL
SELECT 
    'prescriptions_v2' as table_name,
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'prescriptions_v2'
    AND COLUMN_NAME = 'auto_sent_to_pharmacy';

SELECT 'Schema update completed successfully!' AS status;

-- Fix Pharmacy Account
USE medconnect;

-- First, check if account exists and delete if corrupted
DELETE FROM users WHERE email = 'pharmacy@medconnect.com' AND (password IS NULL OR password = '');

-- Create or update pharmacy account with correct password
INSERT INTO users (email, password, full_name, role, status, created_at)
VALUES (
    'pharmacy@medconnect.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- This is bcrypt hash of 'pharmacy123'
    'MedConnect Pharmacy',
    'pharmacy',
    'approved',
    NOW()
)
ON DUPLICATE KEY UPDATE
    password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    status = 'approved',
    role = 'pharmacy';

-- Get the user ID
SET @pharmacy_user_id = (SELECT id FROM users WHERE email = 'pharmacy@medconnect.com');

-- Create or update pharmacy profile
INSERT INTO pharmacy_profiles (
    user_id, pharmacy_name, license_number, owner_name, address, 
    phone_number, operating_hours, delivery_available, verification_status
)
VALUES (
    @pharmacy_user_id,
    'MedConnect Central Pharmacy',
    CONCAT('PH', FLOOR(RAND() * 90000 + 10000)),
    'MedConnect Pharmacy',
    '123 Healthcare Avenue, Medical District',
    '1234567890',
    '24/7',
    TRUE,
    'verified'
)
ON DUPLICATE KEY UPDATE
    pharmacy_name = 'MedConnect Central Pharmacy',
    verification_status = 'verified';

-- Verify the account
SELECT 
    u.id,
    u.email,
    u.role,
    u.status,
    pp.pharmacy_name,
    pp.verification_status
FROM users u
LEFT JOIN pharmacy_profiles pp ON u.id = pp.user_id
WHERE u.email = 'pharmacy@medconnect.com';

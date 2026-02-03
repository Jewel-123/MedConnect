-- ========================================
-- Pharmacy Medicine Inventory Seed Data
-- ========================================
-- Adds sample medicines to pharmacy inventory
-- for testing prescription payment workflow
-- ========================================

USE `medconnect`;

-- Get Central Pharmacy ID
SET @central_pharmacy_id = (SELECT id FROM users WHERE email = 'central.pharmacy@medconnect.com' LIMIT 1);

-- Insert sample medicines
INSERT INTO `pharmacy_inventory` (
    `pharmacy_id`,
    `medicine_name`,
    `generic_name`,
    `manufacturer`,
    `stock_quantity`,
    `unit_price`,
    `expiry_date`,
    `is_available`,
    `requires_prescription`
) VALUES
    -- Common Pain Relief
    (@central_pharmacy_id, 'Paracetamol 500mg', 'Paracetamol', 'Cipla', 500, 5.00, DATE_ADD(CURDATE(), INTERVAL 2 YEAR), TRUE, FALSE),
    (@central_pharmacy_id, 'Ibuprofen 400mg', 'Ibuprofen', 'Sun Pharma', 300, 8.50, DATE_ADD(CURDATE(), INTERVAL 18 MONTH), TRUE, FALSE),
    (@central_pharmacy_id, 'Aspirin 75mg', 'Acetylsalicylic Acid', 'Bayer', 400, 12.00, DATE_ADD(CURDATE(), INTERVAL 2 YEAR), TRUE, FALSE),
    (@central_pharmacy_id, 'Diclofenac 50mg', 'Diclofenac Sodium', 'Dr. Reddy''s', 250, 15.00, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), TRUE, TRUE),
    
    -- Antibiotics
    (@central_pharmacy_id, 'Amoxicillin 500mg', 'Amoxicillin', 'Cipla', 200, 25.00, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), TRUE, TRUE),
    (@central_pharmacy_id, 'Azithromycin 500mg', 'Azithromycin', 'Sun Pharma', 150, 45.00, DATE_ADD(CURDATE(), INTERVAL 18 MONTH), TRUE, TRUE),
    (@central_pharmacy_id, 'Ciprofloxacin 500mg', 'Ciprofloxacin', 'Lupin', 180, 35.00, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), TRUE, TRUE),
    (@central_pharmacy_id, 'Doxycycline 100mg', 'Doxycycline', 'Alkem', 120, 28.00, DATE_ADD(CURDATE(), INTERVAL 2 YEAR), TRUE, TRUE),
    
    -- Antihistamines & Allergy
    (@central_pharmacy_id, 'Cetirizine 10mg', 'Cetirizine', 'Cipla', 400, 6.00, DATE_ADD(CURDATE(), INTERVAL 2 YEAR), TRUE, FALSE),
    (@central_pharmacy_id, 'Loratadine 10mg', 'Loratadine', 'Sun Pharma', 300, 8.00, DATE_ADD(CURDATE(), INTERVAL 18 MONTH), TRUE, FALSE),
    (@central_pharmacy_id, 'Fexofenadine 120mg', 'Fexofenadine', 'Sanofi', 200, 18.00, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), TRUE, FALSE),
    
    -- Gastrointestinal
    (@central_pharmacy_id, 'Omeprazole 20mg', 'Omeprazole', 'Dr. Reddy''s', 350, 12.00, DATE_ADD(CURDATE(), INTERVAL 2 YEAR), TRUE, FALSE),
    (@central_pharmacy_id, 'Pantoprazole 40mg', 'Pantoprazole', 'Cipla', 300, 15.00, DATE_ADD(CURDATE(), INTERVAL 18 MONTH), TRUE, TRUE),
    (@central_pharmacy_id, 'Ranitidine 150mg', 'Ranitidine', 'Sun Pharma', 250, 10.00, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), TRUE, FALSE),
    (@central_pharmacy_id, 'Domperidone 10mg', 'Domperidone', 'Cipla', 200, 8.00, DATE_ADD(CURDATE(), INTERVAL 2 YEAR), TRUE, FALSE),
    
    -- Diabetes
    (@central_pharmacy_id, 'Metformin 500mg', 'Metformin', 'USV', 400, 5.50, DATE_ADD(CURDATE(), INTERVAL 2 YEAR), TRUE, TRUE),
    (@central_pharmacy_id, 'Glimepiride 2mg', 'Glimepiride', 'Cipla', 300, 18.00, DATE_ADD(CURDATE(), INTERVAL 18 MONTH), TRUE, TRUE),
    (@central_pharmacy_id, 'Insulin Glargine', 'Insulin Glargine', 'Sanofi', 50, 450.00, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), TRUE, TRUE),
    
    -- Cardiovascular
    (@central_pharmacy_id, 'Atorvastatin 10mg', 'Atorvastatin', 'Cipla', 350, 12.00, DATE_ADD(CURDATE(), INTERVAL 2 YEAR), TRUE, TRUE),
    (@central_pharmacy_id, 'Amlodipine 5mg', 'Amlodipine', 'Dr. Reddy''s', 400, 8.00, DATE_ADD(CURDATE(), INTERVAL 18 MONTH), TRUE, TRUE),
    (@central_pharmacy_id, 'Losartan 50mg', 'Losartan', 'Sun Pharma', 300, 15.00, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), TRUE, TRUE),
    (@central_pharmacy_id, 'Clopidogrel 75mg', 'Clopidogrel', 'Cipla', 250, 25.00, DATE_ADD(CURDATE(), INTERVAL 2 YEAR), TRUE, TRUE),
    
    -- Respiratory
    (@central_pharmacy_id, 'Salbutamol Inhaler', 'Salbutamol', 'Cipla', 100, 85.00, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), TRUE, TRUE),
    (@central_pharmacy_id, 'Montelukast 10mg', 'Montelukast', 'Sun Pharma', 200, 22.00, DATE_ADD(CURDATE(), INTERVAL 18 MONTH), TRUE, TRUE),
    (@central_pharmacy_id, 'Prednisolone 5mg', 'Prednisolone', 'Cipla', 300, 6.50, DATE_ADD(CURDATE(), INTERVAL 2 YEAR), TRUE, TRUE),
    
    -- Vitamins & Supplements
    (@central_pharmacy_id, 'Vitamin D3 60000 IU', 'Cholecalciferol', 'Cipla', 500, 35.00, DATE_ADD(CURDATE(), INTERVAL 2 YEAR), TRUE, FALSE),
    (@central_pharmacy_id, 'Vitamin B12 1500mcg', 'Methylcobalamin', 'Sun Pharma', 400, 28.00, DATE_ADD(CURDATE(), INTERVAL 18 MONTH), TRUE, FALSE),
    (@central_pharmacy_id, 'Calcium + Vitamin D', 'Calcium Carbonate', 'Cipla', 350, 45.00, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), TRUE, FALSE),
    (@central_pharmacy_id, 'Multivitamin Tablets', 'Multivitamin', 'HealthKart', 300, 120.00, DATE_ADD(CURDATE(), INTERVAL 2 YEAR), TRUE, FALSE),
    
    -- Antacids & Digestive
    (@central_pharmacy_id, 'Digene Gel', 'Antacid', 'Abbott', 200, 55.00, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), TRUE, FALSE),
    (@central_pharmacy_id, 'ENO Powder', 'Antacid', 'GSK', 300, 25.00, DATE_ADD(CURDATE(), INTERVAL 2 YEAR), TRUE, FALSE),
    
    -- Cough & Cold
    (@central_pharmacy_id, 'Cough Syrup', 'Dextromethorphan', 'Cipla', 150, 65.00, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), TRUE, FALSE),
    (@central_pharmacy_id, 'Vicks Vaporub', 'Camphor + Menthol', 'P&G', 250, 85.00, DATE_ADD(CURDATE(), INTERVAL 2 YEAR), TRUE, FALSE),
    
    -- Antiseptics & First Aid
    (@central_pharmacy_id, 'Dettol Liquid', 'Chloroxylenol', 'Reckitt', 200, 95.00, DATE_ADD(CURDATE(), INTERVAL 2 YEAR), TRUE, FALSE),
    (@central_pharmacy_id, 'Betadine Solution', 'Povidone Iodine', 'Win Medicare', 180, 75.00, DATE_ADD(CURDATE(), INTERVAL 18 MONTH), TRUE, FALSE),
    (@central_pharmacy_id, 'Band-Aid Pack', 'Adhesive Bandage', 'Johnson & Johnson', 300, 45.00, DATE_ADD(CURDATE(), INTERVAL 3 YEAR), TRUE, FALSE)

ON DUPLICATE KEY UPDATE 
    stock_quantity = VALUES(stock_quantity),
    unit_price = VALUES(unit_price),
    is_available = VALUES(is_available);

-- Success message
SELECT 
    COUNT(*) as total_medicines,
    SUM(stock_quantity) as total_stock,
    CONCAT('₹', FORMAT(AVG(unit_price), 2)) as avg_price
FROM pharmacy_inventory 
WHERE pharmacy_id = @central_pharmacy_id;

SELECT 'Pharmacy Medicine Inventory Seeded Successfully!' AS Status;

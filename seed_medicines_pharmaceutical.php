<?php
/**
 * Seed Medicines Database with Pharmaceutical Terminology
 * Populates medicines table with common medications using proper medical names
 */

require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Seeding Medicines Database</h1>";
echo "<pre>";

try {
    $conn->begin_transaction();
    
    echo "Inserting medicines with pharmaceutical terminology...\n\n";
    
    $medicines = [
        // ANALGESICS & ANTIPYRETICS (Pain Relief & Fever Reduction)
        ['Paracetamol 500mg', 'Acetaminophen', 'Analgesic', 'Cipla', 5.00, 500, 50, 'tablets', 0],
        ['Paracetamol 650mg', 'Acetaminophen', 'Analgesic', 'Sun Pharma', 7.00, 400, 50, 'tablets', 0],
        ['Ibuprofen 400mg', 'Ibuprofen', 'NSAID', 'Dr. Reddy\'s', 10.00, 300, 30, 'tablets', 0],
        ['Diclofenac Sodium 50mg', 'Diclofenac', 'NSAID', 'Lupin', 15.00, 250, 25, 'tablets', 1],
        ['Aspirin 75mg', 'Acetylsalicylic Acid', 'Antiplatelet', 'Bayer', 12.00, 400, 40, 'tablets', 1],
        
        // ANTIBIOTICS
        ['Amoxicillin 500mg', 'Amoxicillin', 'Antibiotic', 'Cipla', 25.00, 200, 20, 'capsules', 1],
        ['Azithromycin 500mg', 'Azithromycin', 'Macrolide', 'Sun Pharma', 45.00, 150, 15, 'tablets', 1],
        ['Ciprofloxacin 500mg', 'Ciprofloxacin', 'Fluoroquinolone', 'Lupin', 35.00, 180, 20, 'tablets', 1],
        ['Amoxicillin-Clavulanate 625mg', 'Amoxicillin + Clavulanic Acid', 'Antibiotic Combination', 'GSK', 55.00, 120, 15, 'tablets', 1],
        ['Cefixime 200mg', 'Cefixime', 'Cephalosporin', 'Alkem', 40.00, 100, 10, 'tablets', 1],
        
        // ANTIHISTAMINES & ANTI-ALLERGICS
        ['Cetirizine 10mg', 'Cetirizine Hydrochloride', 'Antihistamine', 'Cipla', 6.00, 400, 40, 'tablets', 0],
        ['Loratadine 10mg', 'Loratadine', 'Antihistamine', 'Sun Pharma', 8.00, 300, 30, 'tablets', 0],
        ['Fexofenadine 120mg', 'Fexofenadine', 'Antihistamine', 'Sanofi', 18.00, 200, 20, 'tablets', 0],
        ['Levocetirizine 5mg', 'Levocetirizine', 'Antihistamine', 'Dr. Reddy\'s', 10.00, 250, 25, 'tablets', 0],
        ['Montelukast 10mg', 'Montelukast Sodium', 'Leukotriene Inhibitor', 'Sun Pharma', 22.00, 200, 20, 'tablets', 1],
        
        // GASTROINTESTINAL MEDICATIONS
        ['Omeprazole 20mg', 'Omeprazole', 'Proton Pump Inhibitor', 'Dr. Reddy\'s', 12.00, 350, 35, 'capsules', 0],
        ['Pantoprazole 40mg', 'Pantoprazole Sodium', 'Proton Pump Inhibitor', 'Cipla', 15.00, 300, 30, 'tablets', 1],
        ['Ranitidine 150mg', 'Ranitidine', 'H2 Blocker', 'Sun Pharma', 10.00, 250, 25, 'tablets', 0],
        ['Domperidone 10mg', 'Domperidone', 'Antiemetic', 'Cipla', 8.00, 200, 20, 'tablets', 0],
        ['Ondansetron 4mg', 'Ondansetron', 'Antiemetic', 'Sun Pharma', 20.00, 150, 15, 'tablets', 1],
        ['Metoclopramide 10mg', 'Metoclopramide', 'Prokinetic', 'Alkem', 6.00, 200, 20, 'tablets', 1],
        
        // ANTIDIABETIC MEDICATIONS
        ['Metformin 500mg', 'Metformin Hydrochloride', 'Biguanide', 'USV', 5.50, 400, 40, 'tablets', 1],
        ['Metformin 850mg', 'Metformin Hydrochloride', 'Biguanide', 'Sun Pharma', 8.00, 300, 30, 'tablets', 1],
        ['Glimepiride 2mg', 'Glimepiride', 'Sulfonylurea', 'Cipla', 18.00, 250, 25, 'tablets', 1],
        ['Glibenclamide 5mg', 'Glibenclamide', 'Sulfonylurea', 'Dr. Reddy\'s', 12.00, 200, 20, 'tablets', 1],
        ['Sitagliptin 100mg', 'Sitagliptin', 'DPP-4 Inhibitor', 'MSD', 85.00, 100, 10, 'tablets', 1],
        
        // CARDIOVASCULAR MEDICATIONS
        ['Atorvastatin 10mg', 'Atorvastatin Calcium', 'Statin', 'Cipla', 12.00, 350, 35, 'tablets', 1],
        ['Atorvastatin 20mg', 'Atorvastatin Calcium', 'Statin', 'Dr. Reddy\'s', 18.00, 300, 30, 'tablets', 1],
        ['Amlodipine 5mg', 'Amlodipine Besylate', 'Calcium Channel Blocker', 'Sun Pharma', 8.00, 400, 40, 'tablets', 1],
        ['Losartan 50mg', 'Losartan Potassium', 'ARB', 'Cipla', 15.00, 300, 30, 'tablets', 1],
        ['Telmisartan 40mg', 'Telmisartan', 'ARB', 'Lupin', 20.00, 250, 25, 'tablets', 1],
        ['Metoprolol 50mg', 'Metoprolol Succinate', 'Beta Blocker', 'AstraZeneca', 16.00, 200, 20, 'tablets', 1],
        ['Clopidogrel 75mg', 'Clopidogrel Bisulfate', 'Antiplatelet', 'Cipla', 25.00, 250, 25, 'tablets', 1],
        
        // RESPIRATORY MEDICATIONS
        ['Salbutamol Inhaler', 'Salbutamol', 'Bronchodilator', 'Cipla', 85.00, 100, 10, 'inhaler', 1],
        ['Budesonide Inhaler 200mcg', 'Budesonide', 'Corticosteroid', 'AstraZeneca', 350.00, 50, 5, 'inhaler', 1],
        ['Prednisolone 5mg', 'Prednisolone', 'Corticosteroid', 'Cipla', 6.50, 300, 30, 'tablets', 1],
        ['Ambroxol 30mg', 'Ambroxol Hydrochloride', 'Mucolytic', 'Sun Pharma', 8.00, 200, 20, 'tablets', 0],
        
        // VITAMINS & SUPPLEMENTS
        ['Vitamin D3 60000 IU', 'Cholecalciferol', 'Vitamin', 'Cipla', 35.00, 500, 50, 'capsules', 0],
        ['Vitamin B12 1500mcg', 'Methylcobalamin', 'Vitamin', 'Sun Pharma', 28.00, 400, 40, 'tablets', 0],
        ['Calcium Carbonate 500mg + Vitamin D3', 'Calcium + Cholecalciferol', 'Supplement', 'Cipla', 45.00, 350, 35, 'tablets', 0],
        ['Folic Acid 5mg', 'Folic Acid', 'Vitamin', 'Dr. Reddy\'s', 12.00, 300, 30, 'tablets', 0],
        ['Iron + Folic Acid', 'Ferrous Sulfate + Folic Acid', 'Supplement', 'Sun Pharma', 18.00, 250, 25, 'tablets', 0],
        ['Multivitamin Tablets', 'Multivitamin & Minerals', 'Supplement', 'HealthKart', 120.00, 200, 20, 'tablets', 0],
        
        // ANTACIDS & DIGESTIVE ENZYMES
        ['Magaldrate 540mg', 'Magaldrate', 'Antacid', 'Abbott', 8.00, 200, 20, 'tablets', 0],
        ['Sucralfate 1g', 'Sucralfate', 'Mucosal Protectant', 'Sun Pharma', 15.00, 150, 15, 'tablets', 1],
        ['Pancreatin Enzymes', 'Pancreatin', 'Digestive Enzyme', 'Abbott', 25.00, 100, 10, 'capsules', 0],
        
        // COUGH & COLD PREPARATIONS
        ['Dextromethorphan 15mg', 'Dextromethorphan HBr', 'Antitussive', 'Cipla', 12.00, 150, 15, 'tablets', 0],
        ['Chlorpheniramine 4mg', 'Chlorpheniramine Maleate', 'Antihistamine', 'Sun Pharma', 5.00, 300, 30, 'tablets', 0],
        ['Phenylephrine 10mg', 'Phenylephrine', 'Decongestant', 'Dr. Reddy\'s', 8.00, 200, 20, 'tablets', 0],
        
        // ANTIFUNGAL & TOPICAL
        ['Fluconazole 150mg', 'Fluconazole', 'Antifungal', 'Cipla', 35.00, 100, 10, 'tablets', 1],
        ['Clotrimazole Cream 1%', 'Clotrimazole', 'Antifungal', 'Bayer', 45.00, 80, 10, 'tube', 0],
        ['Betamethasone Cream', 'Betamethasone', 'Corticosteroid', 'GSK', 55.00, 60, 10, 'tube', 1],
        
        // ANTIPARASITIC
        ['Albendazole 400mg', 'Albendazole', 'Anthelmintic', 'Cipla', 12.00, 200, 20, 'tablets', 1],
        ['Mebendazole 100mg', 'Mebendazole', 'Anthelmintic', 'Sun Pharma', 10.00, 150, 15, 'tablets', 1],
        
        // OTHERS
        ['Levothyroxine 50mcg', 'Levothyroxine Sodium', 'Thyroid Hormone', 'Abbott', 25.00, 200, 20, 'tablets', 1],
        ['Allopurinol 100mg', 'Allopurinol', 'Antigout', 'Cipla', 15.00, 150, 15, 'tablets', 1],
        ['Gabapentin 300mg', 'Gabapentin', 'Anticonvulsant', 'Sun Pharma', 45.00, 100, 10, 'capsules', 1]
    ];
    
    $stmt = $conn->prepare("
        INSERT INTO medicines (name, generic_name, category, manufacturer, price, stock, low_stock_threshold, unit, requires_prescription)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            price = VALUES(price),
            stock = stock + VALUES(stock),
            generic_name = VALUES(generic_name),
            category = VALUES(category),
            manufacturer = VALUES(manufacturer)
    ");
    
    $inserted = 0;
    $updated = 0;
    
    foreach ($medicines as $med) {
        $stmt->bind_param(
            "ssssdiiis",
            $med[0], // name
            $med[1], // generic_name
            $med[2], // category
            $med[3], // manufacturer
            $med[4], // price
            $med[5], // stock
            $med[6], // low_stock_threshold
            $med[7], // unit
            $med[8]  // requires_prescription
        );
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows == 1) {
                $inserted++;
                echo "✓ Inserted: {$med[0]} - ₹{$med[4]}\n";
            } else {
                $updated++;
                echo "↻ Updated: {$med[0]}\n";
            }
        } else {
            echo "✗ Failed: {$med[0]} - " . $stmt->error . "\n";
        }
    }
    
    $conn->commit();
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "SEEDING COMPLETE!\n";
    echo str_repeat("=", 60) . "\n";
    echo "Inserted: $inserted medicines\n";
    echo "Updated: $updated medicines\n";
    echo "Total in database: " . ($inserted + $updated) . "\n\n";
    
    // Display summary
    $result = $conn->query("
        SELECT category, COUNT(*) as count, 
               CONCAT('₹', FORMAT(AVG(price), 2)) as avg_price,
               SUM(stock) as total_stock
        FROM medicines 
        GROUP BY category 
        ORDER BY count DESC
    ");
    
    echo "\nSummary by Category:\n";
    echo str_repeat("-", 60) . "\n";
    printf("%-30s %8s %12s %12s\n", "Category", "Count", "Avg Price", "Stock");
    echo str_repeat("-", 60) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        printf("%-30s %8d %12s %12d\n", 
            $row['category'], 
            $row['count'], 
            $row['avg_price'], 
            $row['total_stock']
        );
    }
    
    echo "\n✓ Medicines database successfully populated with pharmaceutical terminology\n";
    echo "✓ Ready for prescription billing integration\n";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>

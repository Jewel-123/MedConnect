<?php
/**
 * Link Prescription Items to Medicines
 * Updates medicine_id in prescription_items_v2 by matching medicine names
 */

require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Linking Prescription Items to Medicines</h1>";
echo "<pre>";

try {
    // Get all prescription items without medicine_id
    $result = $conn->query("
        SELECT pi.id, pi.medicine_name
        FROM prescription_items_v2 pi
        WHERE pi.medicine_id IS NULL
    ");
    
    if (!$result) {
        throw new Exception("Failed to fetch prescription items: " . $conn->error);
    }
    
    $total = $result->num_rows;
    $linked = 0;
    $notFound = [];
    
    echo "Found $total prescription items to link...\n\n";
    
    while ($row = $result->fetch_assoc()) {
        $itemId = $row['id'];
        $medName = trim($row['medicine_name']);
        
        // Try to find matching medicine
        $stmt = $conn->prepare("SELECT id, name FROM medicines WHERE name LIKE ?");
        $searchName = "%$medName%";
        $stmt->bind_param("s", $searchName);
        $stmt->execute();
        $medicine = $stmt->get_result()->fetch_assoc();
        
        if ($medicine) {
            // Update prescription item with medicine_id
            $updateStmt = $conn->prepare("UPDATE prescription_items_v2 SET medicine_id = ? WHERE id = ?");
            $updateStmt->bind_param("ii", $medicine['id'], $itemId);
            
            if ($updateStmt->execute()) {
                $linked++;
                echo "✓ Linked '{$medName}' -> '{$medicine['name']}' (ID: {$medicine['id']})\n";
            } else {
                echo "✗ Failed to update item $itemId: " . $conn->error . "\n";
            }
        } else {
            $notFound[] = $medName;
            echo "⚠ No match found for: '$medName'\n";
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "LINKING COMPLETE!\n";
    echo str_repeat("=", 60) . "\n";
    echo "Total items processed: $total\n";
    echo "Successfully linked: $linked\n";
    echo "Not found: " . count($notFound) . "\n";
    
    if (!empty($notFound)) {
        echo "\nMedicines not found in inventory:\n";
        foreach (array_unique($notFound) as $med) {
            echo "  - $med\n";
        }
        echo "\nThese medicines may need to be added to the medicines table manually.\n";
    }
    
    // Display summary
    echo "\n\nCurrent Status:\n";
    $stats = $conn->query("
        SELECT 
            COUNT(*) as total_items,
            SUM(CASE WHEN medicine_id IS NOT NULL THEN 1 ELSE 0 END) as linked_items,
            SUM(CASE WHEN medicine_id IS NULL THEN 1 ELSE 0 END) as unlinked_items
        FROM prescription_items_v2
    ")->fetch_assoc();
    
    echo "Total prescription items: {$stats['total_items']}\n";
    echo "Linked to medicines: {$stats['linked_items']}\n";
    echo "Still unlinked: {$stats['unlinked_items']}\n";
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>

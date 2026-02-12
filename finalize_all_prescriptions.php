<?php
/**
 * Finalize All Prescriptions Script
 * Automatically finalizes all non-finalized prescriptions
 */

ob_start();

require_once 'db.php';

echo "=== Prescription Finalization Script ===\n\n";

try {
    // ... (rest of the script logic) ...
    // Get Central Pharmacy ID - REMOVED (Not needed for manual assignment)
    /*
    $centralPharmacyResult = $conn->query("
        SELECT id FROM users 
        WHERE email = 'central.pharmacy@medconnect.com' 
        AND role = 'pharmacy' 
        LIMIT 1
    ");
    
    if ($centralPharmacyResult->num_rows === 0) {
        die("ERROR: Central Pharmacy not configured in the system.\n");
    }
    
    $CENTRAL_PHARMACY_ID = $centralPharmacyResult->fetch_assoc()['id'];
    echo "Central Pharmacy ID: $CENTRAL_PHARMACY_ID\n\n";
    */
    $CENTRAL_PHARMACY_ID = 0; // Placeholder
    
    // Get all prescriptions that need to be finalized
    $prescriptionsResult = $conn->query("
        SELECT p.id, p.patient_id, p.doctor_id, p.consultation_id, p.status,
               (SELECT COUNT(*) FROM prescription_items_v2 WHERE prescription_id = p.id) as item_count
        FROM prescriptions_v2 p
        WHERE p.status NOT IN ('finalized', 'sent_to_pharmacy', 'order_placed', 'delivered')
        ORDER BY p.created_at ASC
    ");
    
    $totalPrescriptions = $prescriptionsResult->num_rows;
    echo "Found $totalPrescriptions prescriptions to finalize.\n\n";
    
    if ($totalPrescriptions === 0) {
        echo "All prescriptions are already finalized!\n";
    } else {
    
        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;
        
        while ($prescription = $prescriptionsResult->fetch_assoc()) {
            $prescriptionId = $prescription['id'];
            $currentStatus = $prescription['status'];
            $itemCount = $prescription['item_count'];
            
            echo "Processing Prescription ID: $prescriptionId (Current Status: $currentStatus, Items: $itemCount)\n";
            
            // Skip if no items
            if ($itemCount == 0) {
                echo "  ⚠ SKIPPED: No prescription items found\n\n";
                $skippedCount++;
                continue;
            }
            
            // Start transaction - REMOVED
            // $conn->begin_transaction();
            
            // try {
                // Step 1: Update to FINALIZED
                $stmt = $conn->prepare("
                    UPDATE prescriptions_v2 
                    SET status = 'finalized'
                    WHERE id = ?
                ");
                $stmt->bind_param("i", $prescriptionId);
                
                if ($stmt->execute()) {
                     // Step 3: Generate prescription number if not exists
                    $prescriptionNumber = "RX-" . date('Y') . "-" . str_pad($prescriptionId, 6, '0', STR_PAD_LEFT);
                    $stmt = $conn->prepare("
                        UPDATE prescriptions_v2 
                        SET prescription_number = ?
                        WHERE id = ? AND prescription_number IS NULL
                    ");
                    $stmt->bind_param("si", $prescriptionNumber, $prescriptionId);
                    $stmt->execute();
                    
                    echo "  ✓ SUCCESS: Finalized (Ready for patient order)\n\n";
                    $successCount++;
                } else {
                    echo "  ✗ ERROR: Update failed: " . $stmt->error . "\n\n";
                    $errorCount++;
                }
                
                // Commit transaction - REMOVED
                // $conn->commit();
                
                
            /*
            } catch (Exception $e) {
                $conn->rollback();
                echo "  ✗ ERROR: " . $e->getMessage() . "\n\n";
                $errorCount++;
            }
            */
        }
        
        // Summary
        echo "\n=== FINALIZATION SUMMARY ===\n";
        echo "Total Prescriptions Processed: $totalPrescriptions\n";
        echo "Successfully Finalized: $successCount\n";
        echo "Skipped (No Items): $skippedCount\n";
        echo "Errors: $errorCount\n";
    }
    
    // Show final status counts
    echo "\n=== FINAL STATUS DISTRIBUTION ===\n";
    $statusResult = $conn->query("
        SELECT status, COUNT(*) as count 
        FROM prescriptions_v2 
        GROUP BY status
        ORDER BY count DESC
    ");
    
    while ($row = $statusResult->fetch_assoc()) {
        echo $row['status'] . ": " . $row['count'] . "\n";
    }
    
    echo "\n✓ Script completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n✗ FATAL ERROR: " . $e->getMessage() . "\n";
}

$output = ob_get_clean();
file_put_contents('debug_finalize.log', $output);
echo $output;

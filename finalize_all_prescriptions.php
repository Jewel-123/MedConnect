<?php
/**
 * Finalize All Prescriptions Script
 * Automatically finalizes all non-finalized prescriptions
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';

echo "=== Prescription Finalization Script ===\n\n";

try {
    // Get Central Pharmacy ID
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
        exit(0);
    }
    
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
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Step 1: Update to FINALIZED
            $stmt = $conn->prepare("
                UPDATE prescriptions_v2 
                SET status = 'finalized',
                    finalized_at = COALESCE(finalized_at, NOW())
                WHERE id = ?
            ");
            $stmt->bind_param("i", $prescriptionId);
            $stmt->execute();
            
            // Step 2: Auto-assign to Central Pharmacy and update to SENT_TO_PHARMACY
            $stmt = $conn->prepare("
                UPDATE prescriptions_v2 
                SET pharmacy_id = ?,
                    status = 'sent_to_pharmacy',
                    sent_to_pharmacy_at = COALESCE(sent_to_pharmacy_at, NOW())
                WHERE id = ?
            ");
            $stmt->bind_param("ii", $CENTRAL_PHARMACY_ID, $prescriptionId);
            $stmt->execute();
            
            // Step 3: Generate prescription number if not exists
            $prescriptionNumber = "RX-" . date('Y') . "-" . str_pad($prescriptionId, 6, '0', STR_PAD_LEFT);
            $stmt = $conn->prepare("
                UPDATE prescriptions_v2 
                SET prescription_number = ?
                WHERE id = ? AND prescription_number IS NULL
            ");
            $stmt->bind_param("si", $prescriptionNumber, $prescriptionId);
            $stmt->execute();
            
            // Step 4: Create prescription order if not exists
            $orderNumber = "ORD-" . date('Y') . "-" . str_pad($prescriptionId, 6, '0', STR_PAD_LEFT);
            
            // Check if order already exists
            $checkOrder = $conn->query("
                SELECT id FROM prescription_orders 
                WHERE prescription_id = $prescriptionId
            ");
            
            if ($checkOrder->num_rows === 0) {
                // Create new order
                $totalAmount = 0.00; // Will be calculated by pharmacy
                
                $stmt = $conn->prepare("
                    INSERT INTO prescription_orders (
                        order_number,
                        prescription_id,
                        pharmacy_id,
                        patient_id,
                        order_status,
                        total_amount,
                        created_at
                    ) VALUES (?, ?, ?, ?, 'pending', ?, NOW())
                ");
                $stmt->bind_param("siiid", 
                    $orderNumber, 
                    $prescriptionId, 
                    $CENTRAL_PHARMACY_ID, 
                    $prescription['patient_id'],
                    $totalAmount
                );
                $stmt->execute();
                echo "  ✓ Created prescription order: $orderNumber\n";
            } else {
                echo "  ℹ Prescription order already exists\n";
            }
            
            // Commit transaction
            $conn->commit();
            
            echo "  ✓ SUCCESS: Finalized and sent to pharmacy\n\n";
            $successCount++;
            
        } catch (Exception $e) {
            $conn->rollback();
            echo "  ✗ ERROR: " . $e->getMessage() . "\n\n";
            $errorCount++;
        }
    }
    
    // Summary
    echo "\n=== FINALIZATION SUMMARY ===\n";
    echo "Total Prescriptions Processed: $totalPrescriptions\n";
    echo "Successfully Finalized: $successCount\n";
    echo "Skipped (No Items): $skippedCount\n";
    echo "Errors: $errorCount\n";
    
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
    exit(1);
}
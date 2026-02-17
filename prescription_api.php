<?php
/**
 * Prescription API (Patient-facing)
 * View and manage prescriptions
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'db.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please login first']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

try {
    switch ($action) {
        
        // ==================================================
        // Get patient prescriptions
        // ==================================================
        case 'get_my_prescriptions':
           $status = $_GET['status'] ?? 'all';
            $limit = intval($_GET['limit'] ?? 20);
            $offset = intval($_GET['offset'] ?? 0);
            
            $query = "
                SELECT p.*, 
                       c.symptoms,
                       u.full_name as doctor_name,
                       dp.specialization,
                       po.order_number,
                       po.id as order_id,
                       po.order_status,
                       po.payment_status,
                       po.review_submitted,
                       po.total_amount as order_amount,
                       pharm.full_name as pharmacy_name
                FROM prescriptions_v2 p
                JOIN consultations c ON p.consultation_id = c.id
                JOIN users u ON p.doctor_id = u.id
                LEFT JOIN doctor_profiles dp ON u.id = dp.user_id
                LEFT JOIN prescription_orders po ON p.id = po.prescription_id
                LEFT JOIN users pharm ON po.pharmacy_id = pharm.id
                WHERE p.patient_id = ?
                AND p.status != 'draft'
            ";
            
            if ($status !== 'all') {
                $query .= " AND p.status = '$status'";
            }
            
            $query .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iii", $userId, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $prescriptions = [];
            while ($row = $result->fetch_assoc()) {
                // Get prescription items
                $items = $conn->query("
                    SELECT * FROM prescription_items_v2
                    WHERE prescription_id = {$row['id']}
                ")->fetch_all(MYSQLI_ASSOC);
                
                $row['items'] = $items;
                $prescriptions[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'prescriptions' => $prescriptions
            ]);
            break;
        
        // ==================================================
        // Get prescription details
        // ==================================================
        case 'get_prescription_details':
            $prescriptionId = $_GET['prescription_id'] ?? 0;
            
            // Verify prescription belongs to user
            $stmt = $conn->prepare("
                SELECT p.*, 
                       c.symptoms, c.duration, c.severity,
                       u.full_name as doctor_name,
                       dp.specialization, dp.license_number,
                       po.order_number, po.order_status, po.total_amount
                FROM prescriptions_v2 p
                JOIN consultations c ON p.consultation_id = c.id
                JOIN users u ON p.doctor_id = u.id
                LEFT JOIN doctor_profiles dp ON u.id = dp.user_id
                LEFT JOIN prescription_orders po ON p.id = po.prescription_id
                WHERE p.id = ? AND p.patient_id = ?
            ");
            
            $stmt->bind_param("ii", $prescriptionId, $userId);
            $stmt->execute();
            $prescription = $stmt->get_result()->fetch_assoc();
            
            if (!$prescription) {
                throw new Exception('Prescription not found');
            }
            
            // Get items
            $items = $conn->query("
                SELECT * FROM prescription_items_v2
                WHERE prescription_id = $prescriptionId
            ")->fetch_all(MYSQLI_ASSOC);
            
            // Get tests/referrals if any
            $tests = $conn->query("
                SELECT * FROM prescription_tests
                WHERE prescription_id = $prescriptionId
            ")->fetch_all(MYSQLI_ASSOC);
            
            $prescription['items'] = $items;
            $prescription['tests'] = $tests;
            
            echo json_encode([
                'success' => true,
                'prescription' => $prescription
            ]);
            break;
        
        // ==================================================
        // Download prescription PDF
        // ==================================================
        case 'download_prescription':
            $prescriptionId = $_GET['prescription_id'] ?? 0;
            
            // Verify prescription belongs to user
            $stmt = $conn->prepare("
                SELECT id FROM prescriptions_v2
                WHERE id = ? AND patient_id = ?
            ");
            $stmt->bind_param("ii", $prescriptionId, $userId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception('Prescription not found');
            }
            
            // In production, generate actual PDF
            // For now, return a simple HTML version
            header('Content-Type: text/html');
            
            echo generatePrescriptionHTML($conn, $prescriptionId);
            exit;
        
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Generate prescription HTML for viewing/printing
 */
function generatePrescriptionHTML($conn, $prescriptionId) {
    // Get prescription details
    $result = $conn->query("
        SELECT p.*, 
               c.symptoms, c.duration,
               pat.full_name as patient_name, pat.email as patient_email,
               doc.full_name as doctor_name, doc.email as doctor_email,
               dp.specialization, dp.license_number
        FROM prescriptions_v2 p
        JOIN consultations c ON p.consultation_id = c.id
        JOIN users pat ON p.patient_id = pat.id
        JOIN users doc ON p.doctor_id = doc.id
        LEFT JOIN doctor_profiles dp ON doc.id = dp.user_id
        WHERE p.id = $prescriptionId
    ");
    
    $prescription = $result->fetch_assoc();
    
    // Get items
    $items = $conn->query("
        SELECT * FROM prescription_items_v2
        WHERE prescription_id = $prescriptionId
    ")->fetch_all(MYSQLI_ASSOC);
    
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Prescription - {$prescription['prescription_number']}</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 40px; }
            .header { text-align: center; border-bottom: 3px solid #0ea5e9; padding-bottom: 20px; margin-bottom: 30px; }
            .doctor-info { margin-bottom: 20px; }
            .patient-info { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
           table, th, td { border: 1px solid #ddd; }
            th, td { padding: 12px; text-align: left; }
            th { background: #0ea5e9; color: white; }
            .footer { margin-top: 40px; padding-top: 20px; border-top: 2px solid #eee; }
            .signature { margin-top: 40px; text-align: right; }
            @media print {
                body { padding: 20px; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>🏥 MedConnect</h1>
            <p>Electronic Prescription</p>
            <p><strong>Rx #: {$prescription['prescription_number']}</strong></p>
        </div>
        
        <div class='doctor-info'>
            <h3>Dr. {$prescription['doctor_name']}</h3>
            <p>{$prescription['specialization']}</p>
            <p>License: {$prescription['license_number']}</p>
        </div>
        
        <div class='patient-info'>
            <strong>Patient:</strong> {$prescription['patient_name']}<br>
            <strong>Date:</strong> " . date('d-M-Y', strtotime($prescription['created_at'])) . "<br>
            <strong>Symptoms:</strong> {$prescription['symptoms']}
        </div>
        
        <h3>Diagnosis</h3>
        <p>{$prescription['diagnosis']}</p>
        
        <h3>Prescription</h3>
        <table>
            <thead>
                <tr>
                    <th>Medicine</th>
                    <th>Dosage</th>
                    <th>Frequency</th>
                    <th>Duration</th>
                    <th>Instructions</th>
                </tr>
            </thead>
            <tbody>";
    
    foreach ($items as $item) {
        $html .= "
                <tr>
                    <td>{$item['medicine_name']}</td>
                    <td>{$item['dosage']}</td>
                    <td>{$item['frequency']}</td>
                    <td>{$item['duration']}</td>
                    <td>{$item['instructions']}</td>
                </tr>";
    }
    
    $html .= "
            </tbody>
        </table>
        
        <div class='signature'>
            <p><strong>Digital Signature:</strong> {$prescription['digital_signature']}</p>
            <p><small>Signed on: " . date('d-M-Y H:i:s', strtotime($prescription['signature_timestamp'])) . "</small></p>
        </div>
        
        <div class='footer'>
            <p><small>This is a computer-generated prescription and is valid without a physical signature.</small></p>
        </div>
        
        <div class='no-print'>
            <button onclick='window.print()' style='padding: 10px 20px; background: #0ea5e9; color: white; border: none; cursor: pointer; border-radius: 6px;'>Print Prescription</button>
        </div>
    </body>
    </html>
    ";
    
    return $html;
}

<?php
/**
 * Doctor API - Earnings and Consultation Lifecycle Endpoints
 * These endpoints handle earnings tracking and consultation end workflow
 */

session_start();
require_once 'db.php';
require_once 'notification_service.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$doctor_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        
        // ========================================
        // END CONSULTATION - Mark as completed and finalize earnings
        // ========================================
        case 'end_consultation':
            $consultation_id = $_POST['consultation_id'] ?? 0;
            
            if (!$consultation_id) {
                throw new Exception('Consultation ID is required');
            }
            
            // Get consultation details
            $consultation = $conn->query("
                SELECT patient_id, status FROM consultations 
                WHERE id = $consultation_id AND doctor_id = $doctor_id
            ")->fetch_assoc();
            
            if (!$consultation) {
                throw new Exception('Consultation not found');
            }
            
            if (!in_array($consultation['status'], ['in_progress', 'waiting', 'paused', 'scheduled'])) {
                throw new Exception('Consultation cannot be ended in current status: ' . $consultation['status']);
            }
            
            $conn->begin_transaction();
            
            try {
                // Update consultation to completed
                $conn->query("
                    UPDATE consultations 
                    SET status = 'completed', completed_at = NOW(), updated_at = NOW()
                    WHERE id = $consultation_id
                ");
                
                // Update earnings to 'completed' status (fee release)
                $conn->query("
                    UPDATE doctor_earnings 
                    SET payment_status = 'completed', payment_date = CURDATE()
                    WHERE consultation_id = $consultation_id AND doctor_id = $doctor_id
                ");
                
                // Get updated earnings for response
                $earnings = $conn->query("
                    SELECT net_amount FROM doctor_earnings 
                    WHERE consultation_id = $consultation_id AND doctor_id = $doctor_id
                ")->fetch_assoc();
                
                // Notify patient
                $notifService = getNotificationService();
                $notifService->send(
                    $consultation['patient_id'],
                    'all',
                    'Consultation Completed',
                    'Your consultation has been completed. You can now view your consultation summary and prescription.',
                    ['notification_type' => 'consultation_completed', 'related_id' => $consultation_id]
                );
                
                $conn->commit();
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Consultation ended successfully',
                    'earnings_released' => number_format($earnings['net_amount'] ?? 0, 2)
                ]);
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;
            
        // ========================================
        // GET EARNINGS STATISTICS
        // ========================================
        case 'get_earnings_stats':
            $today = date('Y-m-d');
            $thisWeekStart = date('Y-m-d', strtotime('monday this week'));
            $thisMonthStart = date('Y-m-01');
            
            // Today's earnings (completed only)
            $todayData = $conn->query("
                SELECT SUM(net_amount) as total 
                FROM doctor_earnings 
                WHERE doctor_id = $doctor_id 
                  AND payment_status = 'completed'
                  AND DATE(created_at) = '$today'
            ")->fetch_assoc();
            
            // Weekly earnings
            $weeklyData = $conn->query("
                SELECT SUM(net_amount) as total 
                FROM doctor_earnings 
                WHERE doctor_id = $doctor_id 
                  AND payment_status = 'completed'
                  AND DATE(created_at) >= '$thisWeekStart'
            ")->fetch_assoc();
            
            // Monthly earnings
            $monthlyData = $conn->query("
                SELECT SUM(net_amount) as total 
                FROM doctor_earnings 
                WHERE doctor_id = $doctor_id 
                  AND payment_status = 'completed'
                  AND DATE(created_at) >= '$thisMonthStart'
            ")->fetch_assoc();
            
            // Total lifetime earnings
            $totalData = $conn->query("
                SELECT SUM(net_amount) as total 
                FROM doctor_earnings 
                WHERE doctor_id = $doctor_id 
                  AND payment_status = 'completed'
            ")->fetch_assoc();
            
            // Pending earnings
            $pendingData = $conn->query("
                SELECT SUM(net_amount) as total 
                FROM doctor_earnings 
                WHERE doctor_id = $doctor_id 
                  AND payment_status = 'pending'
            ")->fetch_assoc();
            
            // Breakdown by status
            $breakdown = $conn->query("
                SELECT payment_status, COUNT(*) as count, SUM(net_amount) as total
                FROM doctor_earnings 
                WHERE doctor_id = $doctor_id
                GROUP BY payment_status
            ");
            
            $statusBreakdown = [];
            while ($row = $breakdown->fetch_assoc()) {
                $statusBreakdown[$row['payment_status']] = [
                    'count' => $row['count'],
                    'total' => number_format($row['total'], 2)
                ];
            }
            
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'today' => number_format($todayData['total'] ?? 0, 2),
                    'weekly' => number_format($weeklyData['total'] ?? 0, 2),
                    'monthly' => number_format($monthlyData['total'] ?? 0, 2),
                    'total' => number_format($totalData['total'] ?? 0, 2),
                    'pending' => number_format($pendingData['total'] ?? 0, 2),
                    'breakdown' => $statusBreakdown
                ]
            ]);
            break;
            
        // ========================================
        // TOGGLE ONLINE STATUS
        // ========================================
        case 'toggle_online_status':
            $is_online = $_POST['is_online'] ?? false;
            $is_online_bool = filter_var($is_online, FILTER_VALIDATE_BOOLEAN);
            
            // Insert or update online status
            $conn->query("
                INSERT INTO doctor_online_status (doctor_id, is_online, last_online_at, last_offline_at)
                VALUES ($doctor_id, " . ($is_online_bool ? 'TRUE' : 'FALSE') . ", 
                        " . ($is_online_bool ? 'NOW()' : 'NULL') . ", 
                        " . ($is_online_bool ? 'NULL' : 'NOW()') . ")
                ON DUPLICATE KEY UPDATE 
                    is_online = VALUES(is_online),
                    last_online_at = VALUES(last_online_at),
                    last_offline_at = VALUES(last_offline_at),
                    updated_at = NOW()
            ");
            
            echo json_encode([
                'status' => 'success',
                'is_online' => $is_online_bool,
                'message' => $is_online_bool ? 'You are now online' : 'You are now offline'
            ]);
            break;
            
        // ========================================
        // GET ONLINE STATUS
        // ========================================
        case 'get_online_status':
            $status = $conn->query("
                SELECT is_online, last_online_at, last_offline_at 
                FROM doctor_online_status 
                WHERE doctor_id = $doctor_id
            ")->fetch_assoc();
            
            if (!$status) {
                // Create default offline status
                $conn->query("
                    INSERT INTO doctor_online_status (doctor_id, is_online) 
                    VALUES ($doctor_id, FALSE)
                ");
                $status = ['is_online' => false, 'last_online_at' => null, 'last_offline_at' => null];
            }
            
            echo json_encode([
                'status' => 'success',
                'data' => $status
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
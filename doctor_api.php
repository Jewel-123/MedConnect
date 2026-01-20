<?php
/**
 * Doctor API Handler
 * Unified API for all doctor dashboard operations
 */

session_start();
require_once 'db.php';

// Enable error logging to local file for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/doctor_api_errors.log');
error_reporting(E_ALL);

// CRITICAL: Disable display_errors to prevent HTML output before JSON
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Doctor access required.']);
    exit;
}

$doctor_id = $_SESSION['user_id'];

// Support both standard POST and JSON POST
$json_input = json_decode(file_get_contents('php://input'), true);
$action = $_POST['action'] ?? $_GET['action'] ?? $json_input['action'] ?? '';
$input_data = array_merge($_POST, $_GET, $json_input ?? []);

try {
    switch ($action) {
        
        // ========================================
        // DASHBOARD STATS
        // ========================================
        case 'get_dashboard_stats':
            $today = date('Y-m-d');
            
            // Today's consultations
            $todayConsultations = $conn->query("
                SELECT COUNT(*) as count FROM consultations 
                WHERE doctor_id = $doctor_id AND DATE(created_at) = '$today'
            ")->fetch_assoc()['count'];
            
            // Pending requests
            $pendingRequests = $conn->query("
                SELECT COUNT(*) as count FROM consultations 
                WHERE doctor_id IS NULL AND status = 'pending'
            ")->fetch_assoc()['count'];
            
            // Follow-ups due
            $followUpsDue = $conn->query("
                SELECT COUNT(*) as count FROM prescriptions_v2 
                WHERE doctor_id = $doctor_id AND follow_up_date = '$today'
            ")->fetch_assoc()['count'] ?? 0;
            
            // Average rating
            $ratingData = $conn->query("
                SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                FROM doctor_reviews 
                WHERE doctor_id = $doctor_id
            ")->fetch_assoc();
            
            $avgRating = $ratingData['avg_rating'] ? round($ratingData['avg_rating'], 1) : 0;
            $totalReviews = $ratingData['total_reviews'];
            
            // Monthly earnings
            $currentMonth = date('Y-m');
            $earningsData = $conn->query("
                SELECT SUM(net_amount) as total FROM doctor_earnings 
                WHERE doctor_id = $doctor_id AND DATE_FORMAT(created_at, '%Y-%m') = '$currentMonth'
            ")->fetch_assoc();
            
            $monthlyEarnings = $earningsData['total'] ?? 0;
            
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'today_consultations' => $todayConsultations,
                    'pending_requests' => $pendingRequests,
                    'followups_due' => $followUpsDue,
                    'average_rating' => $avgRating,
                    'total_reviews' => $totalReviews,
                    'monthly_earnings' => number_format($monthlyEarnings, 2)
                ]
            ]);
            break;
            
        case 'get_consultation_requests':
            // Show requests that are unassigned OR assigned to this doctor but not yet accepted
            $requests = $conn->query("
                SELECT c.*, u.full_name as patient_name, u.email as patient_email,
                       TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as patient_age,
                       (CASE 
                           WHEN c.severity = 'high' OR c.urgency_score >= 75 THEN 'emergency'
                           WHEN c.severity = 'medium' OR c.urgency_score >= 50 THEN 'priority'
                           ELSE 'routine' 
                       END) as urgency_level
                FROM consultations c
                JOIN users u ON c.patient_id = u.id
                LEFT JOIN patient_profiles p ON u.id = p.user_id
                WHERE (c.status = 'pending' AND c.doctor_id IS NULL) 
                   OR (c.status = 'assigned' AND c.doctor_id = $doctor_id)
                ORDER BY 
                    (CASE 
                        WHEN c.severity = 'high' OR c.urgency_score >= 75 THEN 1
                        WHEN c.severity = 'medium' OR c.urgency_score >= 50 THEN 2
                        ELSE 3 
                    END) ASC, 
                    c.created_at ASC
                LIMIT 50
            ");
            
            $data = [];
            while ($row = $requests->fetch_assoc()) {
                // AI-summarize symptoms (simplified - in production use actual NLP)
                $symptomsSummary = strlen($row['symptoms']) > 100 
                    ? substr($row['symptoms'], 0, 100) . '...' 
                    : $row['symptoms'];
                
                // Determine urgency badge
                $urgencyBadge = 'routine';
                if ($row['severity'] === 'high' || $row['urgency_score'] >= 75) {
                    $urgencyBadge = 'emergency';
                } elseif ($row['severity'] === 'medium' || $row['urgency_score'] >= 50) {
                    $urgencyBadge = 'priority';
                }
                
                $data[] = [
                    'id' => $row['id'],
                    'patient_name' => $row['patient_name'],
                    'patient_age' => $row['patient_age'] ?? 'N/A',
                    'symptoms' => $row['symptoms'],
                    'symptoms_summary' => $symptomsSummary,
                    'severity' => $row['severity'],
                    'urgency_badge' => $urgencyBadge,
                    'urgency_score' => $row['urgency_score'],
                    'consultation_mode' => $row['consultation_mode'],
                    'language_preference' => $row['language_preference'],
                    'duration' => $row['duration'],
                    'created_at' => $row['created_at']
                ];
            }
            
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;
            
        // ========================================
        // ACCEPT CONSULTATION
        // ========================================
        case 'accept_consultation':
            $consultation_id = $_POST['consultation_id'] ?? 0;
            
            if (!$consultation_id) {
                throw new Exception('Consultation ID is required');
            }
            
            // Update consultation status to 'accepted'
            $stmt = $conn->prepare("
                UPDATE consultations 
                SET doctor_id = ?, status = 'accepted', assigned_at = NOW(), updated_at = NOW() 
                WHERE id = ? AND status IN ('pending', 'assigned') AND (doctor_id IS NULL OR doctor_id = ?)
            ");
            $stmt->bind_param("iii", $doctor_id, $consultation_id, $doctor_id);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                // Log audit
                $conn->query("
                    INSERT INTO consultation_audit_log (consultation_id, doctor_id, action, ip_address) 
                    VALUES ($consultation_id, $doctor_id, 'accepted_consultation', '{$_SERVER['REMOTE_ADDR']}')
                ");
                
                // Create notification for patient (if notifications table exists)
                $conn->query("
                    INSERT INTO doctor_notifications (doctor_id, notification_type, title, message, related_id) 
                    VALUES ($doctor_id, 'new_consultation', 'Consultation Accepted', 'You have accepted a new consultation request', $consultation_id)
                ");
                
                echo json_encode(['status' => 'success', 'message' => 'Consultation accepted successfully']);
            } else {
                throw new Exception('Failed to accept consultation. It may have been already assigned.');
            }
            break;
            
        // ========================================
        // DECLINE CONSULTATION
        // ========================================
        case 'decline_consultation':
            $consultation_id = $_POST['consultation_id'] ?? 0;
            $reason = $_POST['reason'] ?? '';
            
            if (!$consultation_id) {
                throw new Exception('Consultation ID is required');
            }
            
            $stmt = $conn->prepare("
                UPDATE consultations 
                SET status = 'declined', updated_at = NOW() 
                WHERE id = ? AND (status = 'pending' OR doctor_id = ?)
            ");
            $stmt->bind_param("ii", $consultation_id, $doctor_id);
            
            if ($stmt->execute()) {
                // Log audit
                $details = json_encode(['reason' => $reason]);
                $conn->query("
                    INSERT INTO consultation_audit_log (consultation_id, doctor_id, action, action_details, ip_address) 
                    VALUES ($consultation_id, $doctor_id, 'declined_consultation', '$details', '{$_SERVER['REMOTE_ADDR']}')
                ");
                
                echo json_encode(['status' => 'success', 'message' => 'Consultation declined']);
            } else {
                throw new Exception('Failed to decline consultation');
            }
            break;
            
        // ========================================
        // GET ACTIVE CONSULTATIONS
        // ========================================
        case 'get_active_consultations':
            // ONLY show consultations that have been manually accepted or are in progress
            $active = $conn->query("
                SELECT c.*, u.full_name as patient_name, u.email as patient_email,
                       (CASE 
                           WHEN c.severity = 'high' OR c.urgency_score >= 75 THEN 'emergency'
                           WHEN c.severity = 'medium' OR c.urgency_score >= 50 THEN 'priority'
                           ELSE 'routine' 
                       END) as urgency_level
                FROM consultations c
                JOIN users u ON c.patient_id = u.id
                WHERE c.doctor_id = $doctor_id AND c.status IN ('accepted', 'in_progress')
                ORDER BY 
                    (CASE 
                        WHEN c.severity = 'high' OR c.urgency_score >= 75 THEN 1
                        WHEN c.severity = 'medium' OR c.urgency_score >= 50 THEN 2
                        ELSE 3 
                    END) ASC, 
                    c.updated_at DESC
            ");
            
            $data = [];
            while ($row = $active->fetch_assoc()) {
                $data[] = $row;
            }
            
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;
            
        // ========================================
        // START CONSULTATION SESSION
        // ========================================
        case 'start_session':
            $consultation_id = $_POST['consultation_id'] ?? 0;
            
            if (!$consultation_id) {
                throw new Exception('Consultation ID is required');
            }
            
            // Update consultation status
            $conn->query("
                UPDATE consultations 
                SET status = 'in_progress' 
                WHERE id = $consultation_id AND doctor_id = $doctor_id
            ");
            
            // Create session token
            $session_token = bin2hex(random_bytes(32));
            
            // Get consultation mode
            $mode = $conn->query("SELECT consultation_mode FROM consultations WHERE id = $consultation_id")->fetch_assoc()['consultation_mode'];
            
            // Create session record
            $stmt = $conn->prepare("
                INSERT INTO consultation_sessions (consultation_id, session_token, session_type) 
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("iss", $consultation_id, $session_token, $mode);
            $stmt->execute();
            
            echo json_encode([
                'status' => 'success',
                'session_token' => $session_token,
                'consultation_mode' => $mode
            ]);
            break;
            
        // ========================================
        // GET PATIENT HISTORY
        // ========================================
        case 'get_patient_history':
            $patient_id = $_GET['patient_id'] ?? 0;
            
            if (!$patient_id) {
                throw new Exception('Patient ID is required');
            }
            
            // Get patient info
            $patient = $conn->query("
                SELECT u.*, p.* 
                FROM users u 
                LEFT JOIN patient_profiles p ON u.id = p.user_id 
                WHERE u.id = $patient_id
            ")->fetch_assoc();
            
            // Get past consultations
            $consultations = $conn->query("
                SELECT c.*, u.full_name as doctor_name 
                FROM consultations c 
                LEFT JOIN users u ON c.doctor_id = u.id 
                WHERE c.patient_id = $patient_id AND c.status = 'completed' 
                ORDER BY c.completed_at DESC 
                LIMIT 10
            ");
            
            $consultationHistory = [];
            while ($row = $consultations->fetch_assoc()) {
                $consultationHistory[] = $row;
            }
            
            // Get past prescriptions
            $prescriptions = $conn->query("
                SELECT p.*, u.full_name as doctor_name 
                FROM prescriptions_v2 p 
                LEFT JOIN users u ON p.doctor_id = u.id 
                WHERE p.patient_id = $patient_id 
                ORDER BY p.created_at DESC 
                LIMIT 10
            ");
            
            $prescriptionHistory = [];
            while ($row = $prescriptions->fetch_assoc()) {
                $prescriptionHistory[] = $row;
            }
            
            // Get medical history
            $medicalHistory = $conn->query("
                SELECT * FROM patient_medical_history 
                WHERE patient_id = $patient_id 
                ORDER BY record_date DESC
            ");
            
            $medHistory = [];
            while ($row = $medicalHistory->fetch_assoc()) {
                $medHistory[] = $row;
            }
            
            echo json_encode([
                'status' => 'success',
                'patient' => $patient,
                'consultation_history' => $consultationHistory,
                'prescription_history' => $prescriptionHistory,
                'medical_history' => $medHistory
            ]);
            break;
            
        // ========================================
        // SAVE PRESCRIPTION
        // ========================================
        case 'save_prescription':
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Log received data for debugging
            error_log("Prescription save input: " . json_encode($input));
            
            $consultation_id = $input['consultation_id'] ?? 0;
            $patient_id = $input['patient_id'] ?? 0;
            $icd_code = $input['icd_code'] ?? null;
            $diagnosis = $input['diagnosis'] ?? '';
            $medicines = $input['medicines'] ?? [];
            $tests = $input['tests'] ?? [];
            $follow_up_date = $input['follow_up_date'] ?? null;
            $notes_patient = $input['notes_for_patient'] ?? '';
            $notes_pharmacy = $input['notes_for_pharmacy'] ?? '';
            
            if (!$consultation_id || !$patient_id || !$diagnosis) {
                $error_msg = 'Missing required fields';
                error_log("Prescription save error: $error_msg - consultation_id: $consultation_id, patient_id: $patient_id, diagnosis: $diagnosis");
                throw new Exception($error_msg);
            }
            
            // Create prescription
            $stmt = $conn->prepare("
                INSERT INTO prescriptions_v2 
                (consultation_id, patient_id, doctor_id, icd_code, diagnosis, follow_up_date, notes_for_patient, notes_for_pharmacy, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft')
            ");
            $stmt->bind_param("iiisssss", $consultation_id, $patient_id, $doctor_id, $icd_code, $diagnosis, $follow_up_date, $notes_patient, $notes_pharmacy);
            
            if (!$stmt->execute()) {
                error_log("Prescription insert error: " . $stmt->error);
                throw new Exception("Failed to create prescription: " . $stmt->error);
            }
            
            $prescription_id = $stmt->insert_id;
            error_log("Prescription created with ID: $prescription_id");
            
            // Add medicines
            foreach ($medicines as $med) {
                // Store values in variables (required for bind_param references)
                $med_name = $med['name'];
                $med_dosage = $med['dosage'];
                $med_frequency = $med['frequency'];
                $med_duration = $med['duration'];
                $med_instructions = $med['instructions'] ?? '';
                $med_quantity = $med['quantity'] ?? 1;
                
                $stmt = $conn->prepare("
                    INSERT INTO prescription_items_v2 
                    (prescription_id, medicine_name, dosage, frequency, duration, instructions, quantity) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("isssssi", 
                    $prescription_id, 
                    $med_name,
                    $med_dosage,
                    $med_frequency,
                    $med_duration,
                    $med_instructions,
                    $med_quantity
                );
                
                if (!$stmt->execute()) {
                    error_log("Medicine insert error: " . $stmt->error);
                    throw new Exception("Failed to add medicine: " . $stmt->error);
                }
            }
            
            // Add tests/referrals
            foreach ($tests as $test) {
                $stmt = $conn->prepare("
                    INSERT INTO prescription_tests 
                    (prescription_id, test_type, test_name, instructions, urgency) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("issss", 
                    $prescription_id, 
                    $test['type'], 
                    $test['name'], 
                    $test['instructions'] ?? '', 
                    $test['urgency'] ?? 'routine'
                );
                $stmt->execute();
            }
            
            error_log("Prescription saved successfully: ID $prescription_id");
            
            // Send response
            echo json_encode([
                'status' => 'success',
                'prescription_id' => $prescription_id,
                'message' => 'Prescription saved successfully'
            ]);
            exit;
            break;
            
        // ========================================
        // SEND PRESCRIPTION
        // ========================================
        case 'send_prescription':
            $prescription_id = $_POST['prescription_id'] ?? 0;
            $pharmacy_id = $_POST['pharmacy_id'] ?? null;
            
            if (!$prescription_id) {
                throw new Exception('Prescription ID is required');
            }
            
            // Get prescription details
            $prescriptionData = $conn->query("
                SELECT p.*, u.full_name as patient_name, u.email as patient_email,
                       d.full_name as doctor_name
                FROM prescriptions_v2 p
                JOIN users u ON p.patient_id = u.id
                JOIN users d ON p.doctor_id = d.id
                WHERE p.id = $prescription_id AND p.doctor_id = $doctor_id
            ")->fetch_assoc();
            
            if (!$prescriptionData) {
                throw new Exception('Prescription not found');
            }
            
            // ========================================
            // AUTOMATIC PHARMACY SELECTION
            // ========================================
            if (!$pharmacy_id) {
                require_once 'location_service.php';
                
                // Try to get patient location (for now, use default or first available pharmacy)
                // In production, you'd get actual patient coordinates
                $nearestPharmacies = $conn->query("
                    SELECT u.id, u.full_name, pl.phone
                    FROM users u
                    INNER JOIN pharmacy_locations pl ON u.id = pl.pharmacy_id
                    WHERE u.role = 'pharmacy' AND u.status = 'approved'
                    LIMIT 1
                ");
                
                if ($nearestPharmacies->num_rows > 0) {
                    $pharmacy = $nearestPharmacies->fetch_assoc();
                    $pharmacy_id = $pharmacy['id'];
                    $pharmacyPhone = $pharmacy['phone'];
                    $pharmacyName = $pharmacy['full_name'];
                } else {
                    // Fallback: get any approved pharmacy
                    $anyPharmacy = $conn->query("
                        SELECT id, full_name FROM users 
                        WHERE role = 'pharmacy' AND status = 'approved' 
                        LIMIT 1
                    ")->fetch_assoc();
                    
                    if ($anyPharmacy) {
                        $pharmacy_id = $anyPharmacy['id'];
                        $pharmacyName = $anyPharmacy['full_name'];
                        $pharmacyPhone = null;
                    }
                }
            } else {
                // Get pharmacy details
                $pharmacyData = $conn->query("
                    SELECT u.full_name, pl.phone
                    FROM users u
                    LEFT JOIN pharmacy_locations pl ON u.id = pl.pharmacy_id
                    WHERE u.id = $pharmacy_id
                ")->fetch_assoc();
                
                $pharmacyName = $pharmacyData['full_name'] ?? 'Pharmacy';
                $pharmacyPhone = $pharmacyData['phone'] ?? null;
            }
            
            // Update prescription status
            $stmt = $conn->prepare("
                UPDATE prescriptions_v2 
                SET status = 'sent_to_pharmacy', pharmacy_id = ?, sent_at = NOW() 
                WHERE id = ? AND doctor_id = ?
            ");
            $stmt->bind_param("iii", $pharmacy_id, $prescription_id, $doctor_id);
            $stmt->execute();
            
            // Mark consultation as completed
            $conn->query("
                UPDATE consultations 
                SET status = 'completed', completed_at = NOW() 
                WHERE id = {$prescriptionData['consultation_id']}
            ");
            
            // ========================================
            // SEND NOTIFICATIONS
            // ========================================
            require_once 'notification_service.php';
            $notificationService = new NotificationService($conn);
            
            // Notify pharmacy
            if ($pharmacy_id) {
                $notificationService->notifyPharmacyNewPrescription(
                    $pharmacy_id,
                    $prescription_id,
                    $prescriptionData['patient_name'],
                    $prescriptionData['doctor_name'],
                    $pharmacyPhone
                );
            }
            
            // Notify patient
            $patientMessage = "Your prescription from Dr. {$prescriptionData['doctor_name']} has been sent to {$pharmacyName}. Prescription ID: #{$prescription_id}";
            $notificationService->send(
                $prescriptionData['patient_id'],
                'all',
                'Prescription Ready',
                $patientMessage,
                [
                    'notification_type' => 'prescription_ready',
                    'related_id' => $prescription_id
                ]
            );
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'Prescription sent successfully',
                'pharmacy_name' => $pharmacyName ?? 'Pharmacy',
                'notifications_sent' => true
            ]);
            break;
            
        // ========================================
        // COMPLETE CONSULTATION
        // ========================================
        case 'complete_consultation':
            $consultation_id = $_POST['consultation_id'] ?? 0;
            
            if (!$consultation_id) {
                throw new Exception('Consultation ID is required');
            }
            
            // Update consultation
            $conn->query("
                UPDATE consultations 
                SET status = 'completed', completed_at = NOW() 
                WHERE id = $consultation_id AND doctor_id = $doctor_id
            ");
            
            // End session
            $conn->query("
                UPDATE consultation_sessions 
                SET ended_at = NOW(), duration_minutes = TIMESTAMPDIFF(MINUTE, started_at, NOW()) 
                WHERE consultation_id = $consultation_id
            ");
            
            // Get consultation fee from doctor profile
            $feeData = $conn->query("
                SELECT consultation_fee FROM doctor_profiles WHERE user_id = $doctor_id
            ")->fetch_assoc();
            
            $grossAmount = $feeData['consultation_fee'] ?? 50.00;
            $commissionPercent = 10.00;
            $commissionAmount = $grossAmount * ($commissionPercent / 100);
            $netAmount = $grossAmount - $commissionAmount;
            
            // Record earnings
            $stmt = $conn->prepare("
                INSERT INTO doctor_earnings 
                (doctor_id, consultation_id, gross_amount, platform_commission_percent, platform_commission_amount, net_amount, payment_status) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->bind_param("iidddd", $doctor_id, $consultation_id, $grossAmount, $commissionPercent, $commissionAmount, $netAmount);
            $stmt->execute();
            
            echo json_encode(['status' => 'success', 'message' => 'Consultation completed successfully']);
            break;
            
        // ========================================
        // GET PATIENT LIST
        // ========================================
        case 'get_patient_list':
            $search = $_GET['search'] ?? '';
            $filter = $_GET['filter'] ?? 'all'; // all, chronic, recent
            
            $query = "
                SELECT DISTINCT u.id, u.full_name, u.email, 
                       MAX(c.completed_at) as last_consultation,
                       COUNT(c.id) as total_consultations
                FROM users u
                JOIN consultations c ON u.id = c.patient_id
                WHERE c.doctor_id = $doctor_id AND c.status = 'completed'
            ";
            
            if ($search) {
                $search = $conn->real_escape_string($search);
                $query .= " AND (u.full_name LIKE '%$search%' OR u.email LIKE '%$search%')";
            }
            
            $query .= " GROUP BY u.id ORDER BY last_consultation DESC LIMIT 50";
            
            $patients = $conn->query($query);
            
            $data = [];
            while ($row = $patients->fetch_assoc()) {
                $data[] = $row;
            }
            
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;
            
        // ========================================
        // GET REVIEWS
        // ========================================
        case 'get_reviews':
            $reviews = $conn->query("
                SELECT r.*, u.full_name as patient_name 
                FROM doctor_reviews r 
                JOIN users u ON r.patient_id = u.id 
                WHERE r.doctor_id = $doctor_id 
                ORDER BY r.created_at DESC 
                LIMIT 50
            ");
            
            $data = [];
            while ($row = $reviews->fetch_assoc()) {
                $data[] = $row;
            }
            
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;
            
        // ========================================
        // RESPOND TO REVIEW
        // ========================================
        case 'respond_to_review':
            $review_id = $_POST['review_id'] ?? 0;
            $response = $_POST['response'] ?? '';
            
            if (!$review_id || !$response) {
                throw new Exception('Review ID and response are required');
            }
            
            $stmt = $conn->prepare("
                UPDATE doctor_reviews 
                SET doctor_response = ?, responded_at = NOW() 
                WHERE id = ? AND doctor_id = ?
            ");
            $stmt->bind_param("sii", $response, $review_id, $doctor_id);
            $stmt->execute();
            
            echo json_encode(['status' => 'success', 'message' => 'Response posted successfully']);
            break;
            
        // ========================================
        // GET SCHEDULE
        // ========================================
        case 'get_schedule':
            $availability = $conn->query("
                SELECT * FROM doctor_availability 
                WHERE doctor_id = $doctor_id 
                ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
            ");
            
            $schedule = [];
            while ($row = $availability->fetch_assoc()) {
                $schedule[] = $row;
            }
            
            // Get upcoming consultations
            $upcoming = $conn->query("
                SELECT c.*, u.full_name as patient_name 
                FROM consultations c 
                JOIN users u ON c.patient_id = u.id 
                WHERE c.doctor_id = $doctor_id AND c.status IN ('assigned', 'in_progress') 
                ORDER BY c.assigned_at ASC
            ");
            
            $upcomingConsultations = [];
            while ($row = $upcoming->fetch_assoc()) {
                $upcomingConsultations[] = $row;
            }
            
            echo json_encode([
                'status' => 'success',
                'availability' => $schedule,
                'upcoming_consultations' => $upcomingConsultations
            ]);
            break;
            
        // ========================================
        // UPDATE AVAILABILITY
        // ========================================
        case 'update_availability':
            $input = json_decode(file_get_contents('php://input'), true);
            $day = $input['day_of_week'] ?? '';
            $start_time = $input['start_time'] ?? '';
            $end_time = $input['end_time'] ?? '';
            $is_available = $input['is_available'] ?? true;
            
            if (!$day || !$start_time || !$end_time) {
                throw new Exception('Missing required fields');
            }
            
            // Check if exists
            $existing = $conn->query("
                SELECT id FROM doctor_availability 
                WHERE doctor_id = $doctor_id AND day_of_week = '$day'
            ")->fetch_assoc();
            
            if ($existing) {
                // Update
                $stmt = $conn->prepare("
                    UPDATE doctor_availability 
                    SET start_time = ?, end_time = ?, is_available = ? 
                    WHERE doctor_id = ? AND day_of_week = ?
                ");
                $stmt->bind_param("ssiis", $start_time, $end_time, $is_available, $doctor_id, $day);
            } else {
                // Insert
                $stmt = $conn->prepare("
                    INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, is_available) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("isssi", $doctor_id, $day, $start_time, $end_time, $is_available);
            }
            
            $stmt->execute();
            
            echo json_encode(['status' => 'success', 'message' => 'Availability updated successfully']);
            break;
            
        // ========================================
        // GET EARNINGS
        // ========================================
        case 'get_earnings':
            $period = $_GET['period'] ?? 'current_month'; // current_month, last_month, all_time
            
            $whereClause = "";
            if ($period === 'current_month') {
                $currentMonth = date('Y-m');
                $whereClause = "AND DATE_FORMAT(e.created_at, '%Y-%m') = '$currentMonth'";
            } elseif ($period === 'last_month') {
                $lastMonth = date('Y-m', strtotime('-1 month'));
                $whereClause = "AND DATE_FORMAT(e.created_at, '%Y-%m') = '$lastMonth'";
            }
            
            // Summary
            $summary = $conn->query("
                SELECT 
                    SUM(gross_amount) as total_gross,
                    SUM(platform_commission_amount) as total_commission,
                    SUM(net_amount) as total_net,
                    COUNT(*) as total_consultations
                FROM doctor_earnings e 
                WHERE doctor_id = $doctor_id $whereClause
            ")->fetch_assoc();
            
            // Detailed earnings
            $earnings = $conn->query("
                SELECT e.*, c.symptoms 
                FROM doctor_earnings e 
                JOIN consultations c ON e.consultation_id = c.id 
                WHERE e.doctor_id = $doctor_id $whereClause 
                ORDER BY e.created_at DESC
            ");
            
            $details = [];
            while ($row = $earnings->fetch_assoc()) {
                $details[] = $row;
            }
            
            echo json_encode([
                'status' => 'success',
                'summary' => $summary,
                'details' => $details
            ]);
            break;
            
        // ========================================
        // SAVE PRIVATE NOTES
        // ========================================
        case 'save_private_notes':
            $consultation_id = $_POST['consultation_id'] ?? 0;
            $notes = $_POST['notes'] ?? '';
            
            if (!$consultation_id) throw new Exception('ID required');
            
            $stmt = $conn->prepare("UPDATE consultations SET private_notes = ? WHERE id = ? AND doctor_id = ?");
            $stmt->bind_param("sii", $notes, $consultation_id, $doctor_id);
            $stmt->execute();
            
            echo json_encode(['status' => 'success']);
            break;

        // ========================================
        // GET CONSULTATION STATUS
        // ========================================
        case 'get_consultation_status':
            $consultation_id = $_GET['id'] ?? 0;
            if (!$consultation_id) throw new Exception('ID required');
            
            $res = $conn->query("SELECT doctor_id, status FROM consultations WHERE id = $consultation_id");
            $data = $res->fetch_assoc();
            
            echo json_encode(['status' => 'success', 'doctor_id' => $data['doctor_id'], 'consultation_status' => $data['status']]);
            break;

        // ========================================
        // UPDATE PROFILE
        // ========================================
        case 'update_profile':
            $input = json_decode(file_get_contents('php://input'), true);
            
            $consultation_fee = $input['consultation_fee'] ?? null;
            $languages = $input['languages_spoken'] ?? null;
            $bio = $input['bio'] ?? null;
            
            $updates = [];
            $params = [];
            $types = "";
            
            if ($consultation_fee !== null) {
                $updates[] = "consultation_fee = ?";
                $params[] = $consultation_fee;
                $types .= "d";
            }
            
            if ($languages !== null) {
                $updates[] = "languages_spoken = ?";
                $params[] = $languages;
                $types .= "s";
            }
            
            if ($bio !== null) {
                $updates[] = "bio = ?";
                $params[] = $bio;
                $types .= "s";
            }
            
            if (empty($updates)) {
                throw new Exception('No fields to update');
            }
            
            $sql = "UPDATE doctor_profiles SET " . implode(', ', $updates) . " WHERE user_id = ?";
            $params[] = $doctor_id;
            $types .= "i";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            
            echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully']);
            break;
            
        // ========================================
        // GET NOTIFICATIONS
        // ========================================
        case 'get_notifications':
            $notifications = $conn->query("
                SELECT * FROM doctor_notifications 
                WHERE doctor_id = $doctor_id 
                ORDER BY created_at DESC 
                LIMIT 20
            ");
            
            $data = [];
            while ($row = $notifications->fetch_assoc()) {
                $data[] = $row;
            }
            
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;
            
        // ========================================
        // MARK NOTIFICATION READ
        // ========================================
        case 'mark_notification_read':
            $notification_id = $input_data['notification_id'] ?? 0;
            
            $conn->query("
                UPDATE doctor_notifications 
                SET is_read = TRUE 
                WHERE id = $notification_id AND doctor_id = $doctor_id
            ");
            
            echo json_encode(['status' => 'success']);
            break;
            
        // ========================================
        // MARK ALL NOTIFICATIONS READ
        // ========================================
        case 'mark_all_notifications_read':
            $conn->query("
                UPDATE doctor_notifications 
                SET is_read = TRUE 
                WHERE doctor_id = $doctor_id AND is_read = FALSE
            ");
            
            echo json_encode(['status' => 'success', 'message' => 'All notifications marked as read']);
            break;
            
        
        case 'get_prescription_details':
            $id = $_GET['id'] ?? 0;
            $stmt = $conn->prepare("
                SELECT p.*, u_d.full_name as doctor_name, u_ph.full_name as pharmacy_name 
                FROM prescriptions_v2 p 
                JOIN users u_d ON p.doctor_id = u_d.id 
                LEFT JOIN users u_ph ON p.pharmacy_id = u_ph.id
                WHERE p.id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $p = $stmt->get_result()->fetch_assoc();
            
            if (!$p) throw new Exception("Prescription not found");
            
            $itemsStmt = $conn->prepare("SELECT * FROM prescription_items_v2 WHERE prescription_id = ?");
            $itemsStmt->bind_param("i", $id);
            $itemsStmt->execute();
            $items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode(['status' => 'success', 'prescription' => $p, 'items' => $items]);
            break;

        default:
            throw new Exception("Invalid action: $action");
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>

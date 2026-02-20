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

// Support both standard POST and JSON POST
$json_input = json_decode(file_get_contents('php://input'), true);
$action = $_POST['action'] ?? $_GET['action'] ?? $json_input['action'] ?? '';
$input_data = array_merge($_POST, $_GET, $json_input ?? []);

// SPECIAL CASE: Allow patients to view prescriptions
$patient_allowed_actions = ['get_prescription_details'];
$is_patient_allowed = in_array($action, $patient_allowed_actions) && isset($_SESSION['role']) && $_SESSION['role'] === 'patient';

// Check if user is logged in and is a doctor (OR patient for allowed actions)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Please login.']);
    exit;
}

if ($_SESSION['role'] !== 'doctor' && !$is_patient_allowed) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Doctor access required.']);
    exit;
}

$doctor_id = $_SESSION['user_id'];

try {
    switch ($action) {
        
        // ========================================
        // DASHBOARD STATS
        // ========================================
        case 'get_dashboard_stats':
            $today = date('Y-m-d');
            
            // Fetch doctor's specialization for matching unassigned requests
            $docProfile = $conn->query("SELECT specialization FROM doctor_profiles WHERE user_id = $doctor_id")->fetch_assoc();
            $specialization = $docProfile['specialization'] ?? '';

            // Helper condition for unassigned requests matching specialty
            // Handle cases where specialization might be 'General Physician' matching 'General' etc if needed, 
            // but for now strict matching or simple text matching.
            $specialtyCondition = "";
            if ($specialization) {
                // Robust matching: handle 'dermatology' vs 'Dermatologist' etc using LIKE
                $escapedSpecialty = $conn->real_escape_string($specialization);
                $specialtyCondition = " OR (c.doctor_id IS NULL AND (LOWER(TRIM(c.matched_specialty)) LIKE LOWER(TRIM('%$escapedSpecialty%')) OR LOWER(TRIM('%$escapedSpecialty%')) LIKE LOWER(TRIM(c.matched_specialty))))";
                error_log("DOCTOR_API: Filtering requests for specialty: $specialization");
            }

            // Today's consultations
            $todayConsultations = $conn->query("
                SELECT COUNT(*) as count FROM consultations 
                WHERE doctor_id = $doctor_id AND DATE(created_at) = '$today'
            ")->fetch_assoc()['count'];
            
            // Pending requests (paid consultations + paid appointments)
            $pendingConsults = $conn->query("
                SELECT COUNT(*) as count FROM consultations c
                WHERE (c.doctor_id = $doctor_id $specialtyCondition)
                  AND c.status IN ('pending', 'assigned')
                  AND c.payment_status = 'paid'
            ")->fetch_assoc()['count'];

            $pendingAppts = $conn->query("
                SELECT COUNT(*) as count FROM appointments 
                WHERE doctor_id = $doctor_id 
                  AND status = 'pending' 
                  AND payment_status = 'paid'
            ")->fetch_assoc()['count'];

            $pendingRequests = $pendingConsults + $pendingAppts;
            
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
            
            // Monthly earnings (ONLY completed consultations)
            $currentMonth = date('Y-m');
            $earningsData = $conn->query("
                SELECT SUM(net_amount) as total FROM doctor_earnings 
                WHERE doctor_id = $doctor_id 
                  AND DATE_FORMAT(created_at, '%Y-%m') = '$currentMonth'
                  AND payment_status = 'completed'
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
            // Fetch doctor's specialization first
            $docProfile = $conn->query("SELECT specialization FROM doctor_profiles WHERE user_id = $doctor_id")->fetch_assoc();
            $specialization = $docProfile['specialization'] ?? '';
            
            $specialtyCondition = "";
            if ($specialization) {
                $escapedSpecialty = $conn->real_escape_string($specialization);
                $specialtyCondition = " OR (c.doctor_id IS NULL AND (LOWER(TRIM(c.matched_specialty)) LIKE LOWER(TRIM('%$escapedSpecialty%')) OR LOWER(TRIM('%$escapedSpecialty%')) LIKE LOWER(TRIM(c.matched_specialty))))";
            }

            // SHOW:
            // 1. Paid consultations assigned to THIS doctor
            // 2. Paid consultations that are UNASSIGNED but match THIS doctor's specialty
            $requests = $conn->query("
                SELECT c.*, u.full_name as patient_name, u.email as patient_email,
                       p.gender as patient_gender,
                       TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as patient_age,
                       c.created_at as created_at_original
                FROM consultations c
                JOIN users u ON c.patient_id = u.id
                LEFT JOIN patient_profiles p ON u.id = p.user_id
                WHERE (c.doctor_id = $doctor_id $specialtyCondition)
                  AND c.status IN ('pending', 'assigned')
                  AND c.payment_status = 'paid'
                ORDER BY 
                    (CASE 
                        WHEN c.severity = 'high' OR c.urgency_score >= 75 THEN 1
                        WHEN c.severity = 'medium' OR c.urgency_score >= 50 THEN 2
                        ELSE 3 
                    END) ASC, 
                    c.created_at DESC
                LIMIT 50
            ");
            
            $data = [];
            while ($row = $requests->fetch_assoc()) {
                // AI-summarize symptoms (simplified - in production use actual NLP)
                $symptomsSummary = !empty($row['symptoms']) 
                    ? (strlen($row['symptoms']) > 100 ? substr($row['symptoms'], 0, 100) . '...' : $row['symptoms'])
                    : 'No symptoms provided';
                
                // Determine urgency badge
                $urgencyBadge = 'routine';
                if ($row['severity'] === 'high' || $row['urgency_score'] >= 75) {
                    $urgencyBadge = 'emergency';
                } elseif ($row['severity'] === 'medium' || $row['urgency_score'] >= 50) {
                    $urgencyBadge = 'priority';
                }
                
                $data[] = [
                    'id' => $row['id'],
                    'patient_id' => $row['patient_id'],
                    'patient_name' => $row['patient_name'],

                    'patient_age' => $row['patient_age'] ?? 'N/A',
                    'patient_gender' => $row['patient_gender'] ?? 'N/A',
                    'symptoms' => $row['symptoms'],
                    'symptoms_summary' => $symptomsSummary,
                    'severity' => $row['severity'],
                    'urgency_badge' => $urgencyBadge,
                    'urgency_score' => $row['urgency_score'],
                    'consultation_mode' => $row['consultation_mode'],
                    'consultation_fee' => $row['consultation_fee'] ?? '0.00',
                    'language_preference' => $row['language_preference'],
                    'duration' => $row['duration'],
                    'payment_status' => $row['payment_status'],
                    'created_at' => $row['created_at_original'] ?? $row['created_at']
                ];
            }
            
            echo json_encode(['status' => 'success', 'data' => $data, 'count' => count($data)]);
            break;
            
        // ========================================
        // GET APPOINTMENT REQUESTS (Scheduled Appointments)
        // ========================================
        case 'get_appointment_requests':
            // Fetch paid appointments for this doctor
            $appointments = $conn->query("
                SELECT a.*, u.full_name as patient_name, u.email as patient_email,
                       p.phone as patient_phone,
                       TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as patient_age,
                       (SELECT symptoms FROM consultations WHERE patient_id = a.patient_id ORDER BY created_at DESC LIMIT 1) as latest_consultation_symptoms
                FROM appointments a
                JOIN users u ON a.patient_id = u.id
                LEFT JOIN patient_profiles p ON u.id = p.user_id
                WHERE a.doctor_id = $doctor_id 
                  AND a.payment_status = 'paid' 
                  AND a.status = 'pending'
                ORDER BY a.scheduled_date ASC, a.scheduled_time ASC
                LIMIT 50
            ");
            
            $data = [];
            while ($row = $appointments->fetch_assoc()) {
                $data[] = [
                    'id' => $row['id'],
                    'patient_id' => $row['patient_id'],
                    'patient_name' => $row['patient_name'],

                    'patient_email' => $row['patient_email'],
                    'patient_phone' => $row['patient_phone'] ?? 'N/A',
                    'patient_age' => $row['patient_age'] ?? 'N/A',
                    'scheduled_date' => $row['scheduled_date'],
                    'scheduled_time' => $row['scheduled_time'],
                    'consultation_fee' => $row['consultation_fee'],
                    'notes' => $row['notes'] ?? '',
                    'reason' => (!empty($row['notes']) ? $row['notes'] : (!empty($row['latest_consultation_symptoms']) ? $row['latest_consultation_symptoms'] : 'No symptoms provided')),
                    'status' => $row['status'],
                    'payment_status' => $row['payment_status'],
                    'created_at' => $row['created_at'],
                    'type' => 'appointment' // Distinguish from consultations
                ];
            }
            
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;
            

        // ========================================
        // ACCEPT CONSULTATION - ENHANCED WITH EARNINGS TRACKING
        // ========================================
        case 'accept_consultation':
            $consultation_id = $_POST['consultation_id'] ?? 0;
            
            if (!$consultation_id) {
                throw new Exception('Consultation ID is required');
            }
            
            // Check doctor online status (if table exists)
            $onlineCheck = $conn->query("
                SELECT is_online FROM doctor_online_status 
                WHERE doctor_id = $doctor_id
            ");
            
            if ($onlineCheck && $onlineCheck->num_rows > 0) {
                $status = $onlineCheck->fetch_assoc();
                if (!$status['is_online']) {
                    throw new Exception('You must be online to accept consultations');
                }
            }
            
            // Fetch doctor's specialization to allow accepting matching unassigned requests
            $docProfile = $conn->query("SELECT specialization FROM doctor_profiles WHERE user_id = $doctor_id")->fetch_assoc();
            $specialization = $docProfile['specialization'] ?? '';
            
            $specialtyCondition = "";
            if ($specialization) {
                $escapedSpecialty = $conn->real_escape_string($specialization);
                // Allow if doctor_id matches OR (doctor_id is NULL AND specialty matches)
                $specialtyCondition = " OR (doctor_id IS NULL AND LOWER(TRIM(matched_specialty)) = LOWER(TRIM('$escapedSpecialty')))";
            }

            // Get consultation details first for earnings calculation
            $consultation = $conn->query("
                SELECT patient_id, consultation_fee, payment_transaction_id 
                FROM consultations 
                WHERE id = $consultation_id 
                  AND (doctor_id = $doctor_id $specialtyCondition)
                  AND status IN ('pending', 'assigned')
            ")->fetch_assoc();
            
            if (!$consultation) {
                throw new Exception('Consultation not found or not eligible for acceptance');
            }
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Update consultation status to 'accepted' AND assign to this doctor
                // We must handle both cases: already assigned to us, or unassigned matching specialty
                $stmt = $conn->prepare("
                    UPDATE consultations 
                    SET status = 'confirmed', 
                        assigned_at = NOW(),
                        doctor_id = ?
                    WHERE id = ? AND (doctor_id = ? OR doctor_id IS NULL) AND status IN ('pending', 'assigned')
                ");
                $stmt->bind_param("iii", $doctor_id, $consultation_id, $doctor_id);
                
                if (!$stmt->execute() || $stmt->affected_rows === 0) {
                    throw new Exception('Failed to update consultation status');
                }
                
                // Create earnings record (initially pending)
                $gross_amount = floatval($consultation['consultation_fee']);
                $commission_percent = 10.00; // Platform takes 10%
                $commission_amount = $gross_amount * ($commission_percent / 100);
                $net_amount = $gross_amount - $commission_amount;
                
                $stmt = $conn->prepare("
                    INSERT INTO doctor_earnings 
                    (doctor_id, consultation_id, gross_amount, platform_commission_percent, platform_commission_amount, net_amount, payment_status)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')
                ");
                $stmt->bind_param("iidddd", 
                    $doctor_id, 
                    $consultation_id, 
                    $gross_amount, 
                    $commission_percent, 
                    $commission_amount, 
                    $net_amount
                );
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to create earnings record');
                }
                
                // Log audit
                $conn->query("
                    INSERT IGNORE INTO consultation_audit_log (consultation_id, doctor_id, action, ip_address) 
                    VALUES ($consultation_id, $doctor_id, 'accepted_consultation', '{$_SERVER['REMOTE_ADDR']}')
                ");
                
                // Notify patient
                require_once 'notification_service.php';
                $notifService = getNotificationService();
                $notifService->send(
                    $consultation['patient_id'],
                    'all',
                    'Consultation Accepted',
                    'Doctor has accepted your consultation request. Your session is now active.',
                    ['notification_type' => 'consultation_accepted', 'related_id' => $consultation_id]
                );
                
                $conn->commit();
                
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Consultation accepted successfully',
                    'earnings_pending' => number_format($net_amount, 2)
                ]);
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;
            
        // ========================================
        // DECLINE/REJECT CONSULTATION - ENHANCED WITH REFUND LOGIC
        // ========================================
        case 'decline_consultation':
        case 'reject_consultation':
            $consultation_id = $_POST['consultation_id'] ?? 0;
            $reason = $_POST['reason'] ?? 'No reason provided';
            
            if (!$consultation_id) {
                throw new Exception('Consultation ID is required');
            }
            
            // Get consultation details
            $consultation = $conn->query("
                SELECT patient_id, consultation_fee, payment_transaction_id, payment_status
                FROM consultations 
                WHERE id = $consultation_id AND doctor_id = $doctor_id
            ")->fetch_assoc();
            
            if (!$consultation) {
                throw new Exception('Consultation not found or not authorized');
            }
            
            // Start transaction for refund process
            $conn->begin_transaction();
            
            try {
                // Update consultation status to 'declined'
                $stmt = $conn->prepare("
                    UPDATE consultations 
                    SET status = 'declined', updated_at = NOW() 
                    WHERE id = ? AND doctor_id = ?
                ");
                $stmt->bind_param("ii", $consultation_id, $doctor_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update consultation status');
                }
                
                // Log rejection
                $refund_amount = floatval($consultation['consultation_fee']);
                $stmt = $conn->prepare("
                    INSERT INTO consultation_rejections 
                    (consultation_id, doctor_id, patient_id, rejection_reason, refund_amount, refund_status)
                    VALUES (?, ?, ?, ?, ?, 'pending')
                ");
                $stmt->bind_param("iiisd", 
                    $consultation_id, 
                    $doctor_id, 
                    $consultation['patient_id'],
                    $reason,
                    $refund_amount
                );
                $stmt->execute();
                
                // Update payment transaction to refunded status
                if ($consultation['payment_transaction_id']) {
                    $conn->query("
                        UPDATE payment_transactions 
                        SET status = 'refunded', 
                            refund_amount = {$refund_amount},
                            refund_status = 'initiated',
                            refund_initiated_at = NOW()
                        WHERE id = {$consultation['payment_transaction_id']}
                    ");
                }
                
                // Mark any pending earnings as cancelled
                $conn->query("
                    UPDATE doctor_earnings 
                    SET payment_status = 'cancelled'
                    WHERE consultation_id = $consultation_id AND doctor_id = $doctor_id
                ");
                
                // Log audit
                $details = json_encode(['reason' => $reason, 'refund_amount' => $refund_amount]);
                $conn->query("
                    INSERT IGNORE INTO consultation_audit_log (consultation_id, doctor_id, action, action_details, ip_address) 
                    VALUES ($consultation_id, $doctor_id, 'declined_consultation', '$details', '{$_SERVER['REMOTE_ADDR']}')
                ");
                
                // Notify patient
                require_once 'notification_service.php';
                $notifService = getNotificationService();
                $notifService->send(
                    $consultation['patient_id'],
                    'all',
                    'Consultation Declined',
                    "Your consultation request has been declined. Reason: $reason. A full refund has been initiated.",
                    ['notification_type' => 'consultation_declined', 'related_id' => $consultation_id]
                );
                
                $conn->commit();
                
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Consultation declined. Refund initiated.',
                    'refund_amount' => number_format($refund_amount, 2)
                ]);
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;
            
        case 'get_active_consultations':
            // Show both active consultations and confirmed appointments
            $active = $conn->query("
                (SELECT c.id, c.patient_id, u.full_name as patient_name, u.email as patient_email,
                       p.gender as patient_gender,
                       TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as patient_age,
                       CAST('consultation' AS CHAR(20)) as type,
                       CAST(c.status AS CHAR) as status,
                       c.updated_at,
                       CAST(c.symptoms AS CHAR) as symptoms,
                       CAST(c.consultation_mode AS CHAR) as consultation_mode,
                       CAST((CASE 
                           WHEN c.severity = 'high' OR c.urgency_score >= 75 THEN 'emergency'
                           WHEN c.severity = 'medium' OR c.urgency_score >= 50 THEN 'priority'
                           ELSE 'routine' 
                       END) AS CHAR) as urgency_level,
                        CAST(NULL AS CHAR) as scheduled_date,
                        CAST(NULL AS CHAR) as scheduled_time,
                        c.id as linked_consultation_id
                FROM consultations c
                JOIN users u ON c.patient_id = u.id
                LEFT JOIN patient_profiles p ON u.id = p.user_id
                WHERE c.doctor_id = $doctor_id 
                  AND c.status IN ('accepted', 'confirmed', 'in_progress', 'paused'))
                
                UNION ALL
                
                (SELECT a.id, a.patient_id, u.full_name as patient_name, u.email as patient_email,
                       p.gender as patient_gender,
                       TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as patient_age,
                       CAST('appointment' AS CHAR(20)) as type,
                       CAST(a.status AS CHAR) as status,
                       a.created_at as updated_at,
                       CAST(a.notes AS CHAR) as symptoms,
                       CAST('offline' AS CHAR) as consultation_mode,
                       CAST('routine' AS CHAR) as urgency_level,
                        CAST(a.scheduled_date AS CHAR) as scheduled_date,
                        CAST(a.scheduled_time AS CHAR) as scheduled_time,
                        c_link.id as linked_consultation_id
                FROM appointments a
                JOIN users u ON a.patient_id = u.id
                LEFT JOIN patient_profiles p ON u.id = p.user_id
                LEFT JOIN consultations c_link ON a.id = c_link.appointment_id
                WHERE a.doctor_id = $doctor_id 
                  AND a.status IN ('confirmed', 'in_progress', 'paused')
                  AND a.payment_status = 'paid')
                  
                ORDER BY 
                    (CASE 
                        WHEN status = 'in_progress' THEN 1
                        WHEN status = 'paused' THEN 2
                        WHEN status = 'confirmed' THEN 3
                        WHEN status = 'accepted' THEN 4
                        ELSE 5
                    END) ASC,

                    updated_at DESC
            ");
            
            if (!$active) {
                error_log("Query Error: " . $conn->error);
                throw new Exception("Query failed: " . $conn->error);
            }

            
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
            
            // Update consultation status to 'in_progress'
            $conn->query("
                UPDATE consultations 
                SET status = 'in_progress', updated_at = NOW() 
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
        // START CONSULTATION - Begins timer when doctor clicks Start
        // ========================================
        case 'start_consultation':
            $consultation_id = $_POST['consultation_id'] ?? 0;
            $appointment_id = $_POST['appointment_id'] ?? 0;
            
            if (!$consultation_id && !$appointment_id) {
                throw new Exception('ID is required');
            }
            
            if ($appointment_id) {
                // Check if consultation already exists for this appointment
                $check = $conn->prepare("SELECT id FROM consultations WHERE appointment_id = ?");
                $check->bind_param("i", $appointment_id);
                $check->execute();
                $check_res = $check->get_result();
                
                if ($check_res->num_rows > 0) {
                    $consultation_id = $check_res->fetch_assoc()['id'];
                    $stmt = $conn->prepare("UPDATE consultations SET status = 'in_progress', start_time = NOW() WHERE id = ?");
                    $stmt->bind_param("i", $consultation_id);
                } else {
                    // Create new consultation from appointment
                    $stmt = $conn->prepare("
                        INSERT INTO consultations (patient_id, doctor_id, symptoms, consultation_mode, status, urgency_score, severity, appointment_id, start_time)
                        SELECT patient_id, doctor_id, notes, 'offline', 'in_progress', 0, 'medium', id, NOW()
                        FROM appointments 
                        WHERE id = ? AND doctor_id = ?
                    ");
                    $stmt->bind_param("ii", $appointment_id, $doctor_id);
                    $stmt->execute();
                    $consultation_id = $conn->insert_id;
                    $stmt = null; // Mark as done
                }
                
                // Update appointment status to in_progress (or you could use 'completed' if the consultation replaces it)
                $conn->query("UPDATE appointments SET status = 'in_progress' WHERE id = $appointment_id");
                
            } else {
                $stmt = $conn->prepare("
                    UPDATE consultations 
                    SET status = 'in_progress', 
                        start_time = NOW() 
                    WHERE id = ? AND doctor_id = ? AND status IN ('accepted', 'confirmed', 'pending')
                ");
                $stmt->bind_param("ii", $consultation_id, $doctor_id);
            }
            
            if ($stmt && (!$stmt->execute() || $stmt->affected_rows === 0)) {
                throw new Exception('Failed to start consultation. Please check status.');
            }
            
            if ($appointment_id || $consultation_id) {
                $target_id = $appointment_id ?: $consultation_id;
                $target_type = $appointment_id ? 'appointment' : 'consultation';
                $target_table = $appointment_id ? 'appointments' : 'consultations';
                
                // Create/update consultation session
                $conn->query("
                    INSERT INTO consultation_sessions (consultation_id, doctor_id, patient_id, started_at, session_type)
                    SELECT id, doctor_id, patient_id, NOW(), '$target_type'
                    FROM $target_table
                    WHERE id = $target_id
                    ON DUPLICATE KEY UPDATE started_at = NOW(), session_type = '$target_type'
                ");
            }
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'Consultation started successfully',
                'consultation_id' => $consultation_id
            ]);
            break;
            
        // ========================================
        // RESUME CONSULTATION - Resumes from paused state
        // ========================================
        case 'resume_consultation':
            $consultation_id = $_POST['consultation_id'] ?? 0;
            $appointment_id = $_POST['appointment_id'] ?? 0;
            
            if (!$consultation_id && !$appointment_id) {
                throw new Exception('ID is required');
            }
            
            if ($appointment_id) {
                $stmt = $conn->prepare("
                    UPDATE appointments 
                    SET status = 'in_progress' 
                    WHERE id = ? AND doctor_id = ? AND status = 'paused'
                ");
            } else {
                $stmt = $conn->prepare("
                    UPDATE consultations 
                    SET status = 'in_progress' 
                    WHERE id = ? AND doctor_id = ? AND status = 'paused'
                ");
            }
            $stmt->bind_param("ii", ($appointment_id ?: $consultation_id), $doctor_id);
            
            if (!$stmt->execute() || $stmt->affected_rows === 0) {
                throw new Exception('Failed to resume consultation or not paused');
            }
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'Consultation resumed successfully'
            ]);
            break;

        // ========================================
        // PAUSE CONSULTATION
        // ========================================
        case 'pause_consultation':
            $consultation_id = $_POST['consultation_id'] ?? 0;
            $appointment_id = $_POST['appointment_id'] ?? 0;
            
            if (!$consultation_id && !$appointment_id) {
                throw new Exception('ID is required');
            }
            
            if ($appointment_id) {
                $conn->query("UPDATE appointments SET status = 'paused' WHERE id = $appointment_id AND doctor_id = $doctor_id");
            } else {
                $conn->query("UPDATE consultations SET status = 'paused' WHERE id = $consultation_id AND doctor_id = $doctor_id");
            }
            
            echo json_encode(['status' => 'success', 'message' => 'Consultation paused']);
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
        // GET POST-CONSULTATION DATA
        // ========================================
        case 'get_post_consultation_data':
            $consultation_id = $_GET['consultation_id'] ?? 0;
            
            if (!$consultation_id) {
                throw new Exception('Consultation ID is required');
            }
            
            // Get consultation with patient info
            $consultation = $conn->query("
                SELECT c.*, u.full_name as patient_name, u.email as patient_email,
                       pp.date_of_birth, pp.gender, pp.blood_group
                FROM consultations c
                JOIN users u ON c.patient_id = u.id
                LEFT JOIN patient_profiles pp ON u.id = pp.user_id
                WHERE c.id = $consultation_id AND c.doctor_id = $doctor_id
            ")->fetch_assoc();
            
            if (!$consultation) {
                throw new Exception('Consultation not found');
            }
            
            // Get existing prescription if any
            $prescription = $conn->query("
                SELECT * FROM prescriptions_v2 
                WHERE consultation_id = $consultation_id
            ")->fetch_assoc();
            
            echo json_encode([
                'status' => 'success',
                'consultation' => $consultation,
                'prescription' => $prescription
            ]);
            break;
            
        // ========================================
        // SAVE DIAGNOSIS AND NOTES
        // ========================================
        case 'save_diagnosis_notes':
            $input = json_decode(file_get_contents('php://input'), true);
            
            $consultation_id = $input['consultation_id'] ?? 0;
            $diagnosis = $input['diagnosis'] ?? '';
            $medical_advice = $input['medical_advice'] ?? '';
            
            if (!$consultation_id) {
                throw new Exception('Consultation ID is required');
            }
            
            // Update consultation
            $stmt = $conn->prepare("
                UPDATE consultations 
                SET diagnosis = ?, medical_advice = ?, updated_at = NOW()
                WHERE id = ? AND doctor_id = ?
            ");
            $stmt->bind_param("ssii", $diagnosis, $medical_advice, $consultation_id, $doctor_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to save diagnosis: ' . $stmt->error);
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Diagnosis and notes saved successfully'
            ]);
            break;
            
        // ========================================
        // SCHEDULE FOLLOW-UP
        // ========================================
        case 'schedule_follow_up':
            $input = json_decode(file_get_contents('php://input'), true);
            
            $consultation_id = $input['consultation_id'] ?? 0;
            $follow_up_date = $input['follow_up_date'] ?? null;
            $follow_up_notes = $input['follow_up_notes'] ?? '';
            
            if (!$consultation_id || !$follow_up_date) {
                throw new Exception('Consultation ID and follow-up date are required');
            }
            
            // Update consultation
            $stmt = $conn->prepare("
                UPDATE consultations 
                SET follow_up_scheduled = ?, follow_up_notes = ?, updated_at = NOW()
                WHERE id = ? AND doctor_id = ?
            ");
            $stmt->bind_param("ssii", $follow_up_date, $follow_up_notes, $consultation_id, $doctor_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to schedule follow-up: ' . $stmt->error);
            }
            
            // Create notification for patient
            require_once 'notification_service.php';
            $notificationService = new NotificationService($conn);
            
            // Get patient ID
            $patient_id = $conn->query("
                SELECT patient_id FROM consultations WHERE id = $consultation_id
            ")->fetch_assoc()['patient_id'];
            
            $notificationService->send(
                $patient_id,
                'all',
                'Follow-up Scheduled',
                "Your follow-up consultation has been scheduled for " . date('F j, Y', strtotime($follow_up_date)),
                ['notification_type' => 'follow_up_reminder', 'related_id' => $consultation_id]
            );
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Follow-up scheduled successfully'
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
            $appointment_id = $input['appointment_id'] ?? 0;
            $patient_id = $input['patient_id'] ?? 0;
            $icd_code = $input['icd_code'] ?? null;
            $diagnosis = $input['diagnosis'] ?? '';
            $medicines = $input['medicines'] ?? [];
            $tests = $input['tests'] ?? [];
            $follow_up_date = $input['follow_up_date'] ?? null;
            $notes_patient = $input['notes_for_patient'] ?? '';
            $notes_pharmacy = $input['notes_for_pharmacy'] ?? '';
            
            // If consultation_id is missing but appointment_id is present, try to find the linked consultation
            if (!$consultation_id && $appointment_id) {
                $stmt_c = $conn->prepare("SELECT id FROM consultations WHERE appointment_id = ?");
                $stmt_c->bind_param("i", $appointment_id);
                $stmt_c->execute();
                $res_c = $stmt_c->get_result();
                if ($row_c = $res_c->fetch_assoc()) {
                    $consultation_id = $row_c['id'];
                }
            }

            if (!$consultation_id || !is_numeric($patient_id) || empty($diagnosis)) {
                $error_msg = 'Missing or invalid required fields (consultation_id, patient_id, diagnosis)';
                if (!$consultation_id && $appointment_id) {
                    $error_msg = "Could not find a started consultation for appointment #$appointment_id. Please start the session first.";
                }
                error_log("Prescription save error: $error_msg - Input: " . json_encode($input));
                throw new Exception($error_msg);
            }
            
            // Validate patient exists
            $checkPatient = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'patient'");
            $checkPatient->bind_param("i", $patient_id);
            $checkPatient->execute();
            if ($checkPatient->get_result()->num_rows === 0) {
                error_log("Prescription save error: Patient ID $patient_id does not exist or is not a patient.");
                throw new Exception("Invalid Patient ID. Please contact support.");
            }
            
            // Create prescription
            $stmt = $conn->prepare("
                INSERT INTO prescriptions_v2 
                (consultation_id, patient_id, doctor_id, icd_code, diagnosis, follow_up_date, notes_for_patient, notes_for_pharmacy, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'finalized')
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
            $appointment_id = $_POST['appointment_id'] ?? 0;
            
            if (!$consultation_id && !$appointment_id) {
                throw new Exception('ID is required');
            }
            
            if ($appointment_id) {
                $conn->query("UPDATE appointments SET status = 'completed' WHERE id = $appointment_id AND doctor_id = $doctor_id");
                
                // For appointments, we just mark as completed. 
                // We could also do session cleanup if we tracked it for appointments too.
                echo json_encode(['status' => 'success', 'message' => 'Appointment completed']);
                break;
            }
            
            // Get consultation details
            $consultationData = $conn->query("
                SELECT c.*, u.full_name as patient_name, u.email as patient_email
                FROM consultations c
                JOIN users u ON c.patient_id = u.id
                WHERE c.id = $consultation_id AND c.doctor_id = $doctor_id
            ")->fetch_assoc();
            
            if (!$consultationData) {
                throw new Exception('Consultation not found');
            }
            
            // Update consultation status
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
            
            // Update prescription status to 'finalized' if exists
            $conn->query("
                UPDATE prescriptions_v2 
                SET status = 'finalized', signature_timestamp = NOW()
                WHERE consultation_id = $consultation_id AND (status = 'draft' OR status = 'issued')
            ");
            
            // Get prescription details for notification
            $prescription = $conn->query("
                SELECT * FROM prescriptions_v2 
                WHERE consultation_id = $consultation_id
            ")->fetch_assoc();
            
            // Auto-send to pharmacy if prescription exists and pharmacy is assigned
            if ($prescription && $prescription['pharmacy_id']) {
                $conn->query("
                    UPDATE prescriptions_v2 
                    SET status = 'sent_to_pharmacy', sent_at = NOW(), auto_sent_to_pharmacy = TRUE
                    WHERE id = {$prescription['id']}
                ");
                
                // Notify pharmacy
                require_once 'notification_service.php';
                $notificationService = new NotificationService($conn);
                $notificationService->notifyPharmacyNewPrescription(
                    $prescription['pharmacy_id'],
                    $prescription['id'],
                    $consultationData['patient_name'],
                    $_SESSION['full_name'] ?? 'Doctor',
                    null
                );
            }
            
            // Update earnings from 'pending' to 'completed'
            // Earnings record was already created during accept_consultation
            $conn->query("
                UPDATE doctor_earnings 
                SET payment_status = 'completed'
                WHERE consultation_id = $consultation_id AND doctor_id = $doctor_id AND payment_status = 'pending'
            ");
            
            
            // Send notification to patient
            require_once 'notification_service.php';
            $notificationService = new NotificationService($conn);
            
            $message = "Your consultation has been completed. ";
            if ($prescription) {
                $message .= "Your prescription is ready to view.";
            }
            
            $notificationService->send(
                $consultationData['patient_id'],
                'all',
                'Consultation Completed',
                $message,
                ['notification_type' => 'consultation_completed', 'related_id' => $consultation_id]
            );
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'Consultation completed successfully',
                'earnings' => [
                    'gross' => $grossAmount,
                    'commission' => $commissionAmount,
                    'net' => $netAmount
                ],
                'prescription_sent' => $prescription && $prescription['pharmacy_id'] ? true : false
            ]);
            break;
            
        // ========================================
        // GET PATIENT LIST
        // ========================================

        // ========================================
        // CONFIRM APPOINTMENT
        // ========================================
        case 'confirm_appointment':
            $appointment_id = $_POST['appointment_id'] ?? 0;
            
            if (!$appointment_id) {
                throw new Exception('Appointment ID is required');
            }
            
            // Get appointment details
            $appointment = $conn->query("
                SELECT * FROM appointments 
                WHERE id = $appointment_id AND doctor_id = $doctor_id
            ")->fetch_assoc();
            
            if (!$appointment) {
                throw new Exception('Appointment not found or not authorized');
            }
            
            if ($appointment['status'] !== 'pending') {
                throw new Exception('Appointment is not pending');
            }
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Update status
                $stmt = $conn->prepare("
                    UPDATE appointments 
                    SET status = 'confirmed' 
                    WHERE id = ?
                ");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("i", $appointment_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update appointment status');
                }
                
                // Create earnings record (pending)
                // ... (skipping logs for calculation) ...
                $gross_amount = floatval($appointment['consultation_fee'] ?? 0);
                if ($gross_amount <= 0) {
                     $profile = $conn->query("SELECT consultation_fee FROM doctor_profiles WHERE user_id = $doctor_id")->fetch_assoc();
                     $gross_amount = floatval($profile['consultation_fee'] ?? 0);
                }
                
                // Log audit
                $auditRes = $conn->query("
                    INSERT IGNORE INTO consultation_audit_log (doctor_id, action, action_details, ip_address) 
                    VALUES ($doctor_id, 'confirmed_appointment', '{\"appointment_id\": $appointment_id}', '{$_SERVER['REMOTE_ADDR']}')
                ");
                
                // Notify patient
                require_once 'notification_service.php';
                $notifService = getNotificationService();
                $notifService->send(
                    $appointment['patient_id'],
                    'all',
                    'Appointment Confirmed',
                    "Your appointment on {$appointment['scheduled_date']} at {$appointment['scheduled_time']} has been confirmed.",
                    ['notification_type' => 'appointment_confirmed', 'related_id' => $appointment_id]
                );
                
                $conn->commit();
                
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Appointment confirmed successfully'
                ]);
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;

        // ========================================
        // DECLINE APPOINTMENT
        // ========================================
        case 'decline_appointment':
            $appointment_id = $_POST['appointment_id'] ?? 0;
            $reason = $_POST['reason'] ?? 'No reason provided';
            
            if (!$appointment_id) {
                throw new Exception('Appointment ID is required');
            }
            
            $conn->begin_transaction();
            
            try {
                // Update status
                $stmt = $conn->prepare("
                    UPDATE appointments 
                    SET status = 'cancelled', cancellation_reason = ? 
                    WHERE id = ? AND doctor_id = ?
                ");
                $stmt->bind_param("sii", $reason, $appointment_id, $doctor_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to decline appointment');
                }
                
                // Log audit
                $conn->query("
                    INSERT IGNORE INTO consultation_audit_log (doctor_id, action, action_details, ip_address) 
                    VALUES ($doctor_id, 'declined_appointment', '{\"appointment_id\": $appointment_id, \"reason\": \"$reason\"}', '{$_SERVER['REMOTE_ADDR']}')
                ");
                
                // Get patient ID for notification
                $appt = $conn->query("SELECT patient_id FROM appointments WHERE id = $appointment_id")->fetch_assoc();
                
                // Notify patient
                require_once 'notification_service.php';
                $notifService = getNotificationService();
                $notifService->send(
                    $appt['patient_id'],
                    'all',
                    'Appointment Declined',
                    "Your appointment request has been declined. Reason: $reason.",
                    ['notification_type' => 'appointment_declined', 'related_id' => $appointment_id]
                );
                
                $conn->commit();
                
                echo json_encode(['status' => 'success', 'message' => 'Appointment declined']);
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;

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

        case 'update_patient_info':
            $patient_id = $_POST['patient_id'] ?? 0;
            $allergies = $_POST['allergies'] ?? null;
            $history = $_POST['history'] ?? null;
            
            if (!$patient_id) throw new Exception('Patient ID required');

            $conn->begin_transaction();
            try {
                if ($allergies !== null) {
                    $stmt = $conn->prepare("UPDATE consultations SET existing_conditions = ? WHERE patient_id = ? AND doctor_id = ?");
                    $stmt->bind_param("sii", $allergies, $patient_id, $doctor_id);
                    $stmt->execute();
                }
                
                if ($history !== null) {
                    $stmt = $conn->prepare("UPDATE patient_profiles SET medical_history_summary = ? WHERE user_id = ?");
                    $stmt->bind_param("si", $history, $patient_id);
                    $stmt->execute();
                }
                
                $conn->commit();
                echo json_encode(['status' => 'success']);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
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

// Connection closed automatically by PHP at end of request
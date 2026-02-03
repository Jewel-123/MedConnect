<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$consultationId = $_GET['id'] ?? null;
if (!$consultationId) {
    die("Consultation ID required");
}

// Fetch consultation details with extended info
$stmt = $conn->prepare("
    SELECT c.*, 
           u_p.full_name as patient_name,
           u_d.full_name as doctor_name,
           TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as patient_age,
           p.gender as patient_gender,
           p.medical_history_summary
    FROM consultations c
    INNER JOIN users u_p ON c.patient_id = u_p.id
    LEFT JOIN users u_d ON c.doctor_id = u_d.id
    LEFT JOIN patient_profiles p ON u_p.id = p.user_id
    WHERE c.id = ?
");
$stmt->bind_param('i', $consultationId);
$stmt->execute();
$consultation = $stmt->get_result()->fetch_assoc();

if (!$consultation) {
    die("Consultation not found");
}

// Authorization check
if ($_SESSION['user_id'] != $consultation['patient_id'] && $_SESSION['user_id'] != $consultation['doctor_id']) {
    // Self-healing: If user name matches patient name, update patient_id to current user
    // This handles the duplicate user account issue transparently
    $userName = $_SESSION['user_name'] ?? '';
    // Fetch patient name if not in initial query (though it is: u_p.full_name)
    $patientName = $consultation['patient_name'];
    
    if ($_SESSION['role'] === 'patient' && 
        !empty($userName) && 
        !empty($patientName) && 
        strtolower(trim($userName)) === strtolower(trim($patientName))) {
        
        $newId = (int)$_SESSION['user_id'];
        $oldId = (int)$consultation['patient_id'];
        
        // Update Consultation
        $conn->query("UPDATE consultations SET patient_id = $newId WHERE id = $consultationId");
        
        // Update Messages (Sender/Receiver)
        $conn->query("UPDATE messages SET sender_id = $newId WHERE sender_id = $oldId AND consultation_id = $consultationId");
        $conn->query("UPDATE messages SET receiver_id = $newId WHERE receiver_id = $oldId AND consultation_id = $consultationId");
        
        // Refresh local variable
        $consultation['patient_id'] = $newId;
    } else {
        die("Unauthorized access to this consultation");
    }
}

$role = $_SESSION['role'];
$otherName = ($role === 'patient') ? $consultation['doctor_name'] : $consultation['patient_name'];

// --- [AUTOMATIC SESSION START FOR DOCTORS] ---
if ($role === 'doctor' && $_SESSION['user_id'] == $consultation['doctor_id']) {
    // If it's a doctor and status is not yet 'completed' or 'cancelled'
    if (in_array($consultation['status'], ['accepted', 'scheduled', 'waiting', 'paused'])) {
        // Update status to in_progress
        $conn->query("UPDATE consultations SET status = 'in_progress', updated_at = NOW() WHERE id = $consultationId");
        
        // Log session if token doesn't exist for this consultation
        $checkSession = $conn->query("SELECT id FROM consultation_sessions WHERE consultation_id = $consultationId");
        if ($checkSession->num_rows === 0) {
            $session_token = bin2hex(random_bytes(32));
            $mode = $consultation['consultation_mode'] ?? 'video';
            $stmt = $conn->prepare("INSERT INTO consultation_sessions (consultation_id, session_token, session_type) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $consultationId, $session_token, $mode);
            $stmt->execute();
        }
    }
}
// ---------------------------------------------

// Fetch Vitals
$vitalsStmt = $conn->prepare("SELECT * FROM patient_vitals WHERE patient_id = ? ORDER BY recorded_at DESC LIMIT 1");
$vitalsStmt->bind_param('i', $consultation['patient_id']);
$vitalsStmt->execute();
$vitals = $vitalsStmt->get_result()->fetch_assoc();

// Fetch Reports
$reportsStmt = $conn->prepare("SELECT * FROM medical_reports WHERE patient_id = ? ORDER BY uploaded_at DESC");
$reportsStmt->bind_param('i', $consultation['patient_id']);
$reportsStmt->execute();
$reports = $reportsStmt->get_result();

// Fetch Medical History Records
$historyStmt = $conn->prepare("SELECT * FROM patient_medical_history WHERE patient_id = ? ORDER BY record_date DESC");
$historyStmt->bind_param('i', $consultation['patient_id']);
$historyStmt->execute();
$historyRecords = $historyStmt->get_result();

// Fetch E-Prescriptions
$prescStmt = $conn->prepare("SELECT * FROM prescriptions_v2 WHERE patient_id = ? ORDER BY created_at DESC");
$prescStmt->bind_param('i', $consultation['patient_id']);
$prescStmt->execute();
$prescriptions = $prescStmt->get_result();

// Parse Allergies
$allergies = "None recorded";
if (!empty($consultation['medical_history_summary']) && stripos($consultation['medical_history_summary'], 'allergies:') !== false) {
    preg_match('/allergies:(.*?)(\n|$)/i', $consultation['medical_history_summary'], $matches);
    if (!empty($matches[1])) {
        $allergies = trim($matches[1]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telemedicine Hub | MedConnect</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0ea5e9;
            --primary-dark: #0284c7;
            --primary-light: #e0f2fe;
            --bg: #f8fafc;
            --text: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --white: #ffffff;
            --danger: #ef4444;
            --success: #22c55e;
            --sidebar-width: 340px;
            --video-width: 380px;
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            height: 100vh;
            overflow: hidden;
            display: flex;
        }

        /* Layout Structure */
        .app-container {
            display: flex;
            width: 100%;
            height: 100%;
        }

        /* Panels Shared Styles */
        .panel {
            background: var(--white);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        /* 1. Left Panel - Patient Clinical Information */
        .panel-left {
            width: var(--sidebar-width);
            border-right: 1px solid var(--border);
            z-index: 10;
        }

        .panel-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
        }

        .patient-card {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .avatar {
            width: 48px;
            height: 48px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
        }

        .patient-meta h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }

        .patient-meta p {
            margin: 2px 0 0;
            font-size: 13px;
            color: var(--text-muted);
        }

        /* Tabs Styles */
        .tab-nav {
            display: flex;
            padding: 0 8px;
            border-bottom: 1px solid var(--border);
            gap: 4px;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
            background: #fbfcfd;
        }
        .tab-nav::-webkit-scrollbar { display: none; }

        .tab-btn {
            padding: 14px 12px;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            background: none;
            border: none;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            white-space: nowrap;
            flex-shrink: 0;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .tab-btn:hover { color: var(--primary); }
        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .tab-content {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .content-group {
            margin-bottom: 20px;
        }

        .content-group h4 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 12px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .info-label { color: var(--text-muted); }
        .info-value { font-weight: 500; }

        /* 2. Center Panel - Consultation Hub */
        .panel-center {
            flex: 1;
            background: #f1f5f9;
        }

        .hub-container {
            height: 100%;
            display: flex;
            flex-direction: column;
            padding: 20px;
            gap: 20px;
        }

        .chat-area {
            flex: 1;
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .message {
            max-width: 80%;
            display: flex;
            flex-direction: column;
        }

        .message.sent { align-self: flex-end; align-items: flex-end; }

        .bubble {
            padding: 12px 16px;
            border-radius: 16px;
            font-size: 14px;
            line-height: 1.5;
            position: relative;
        }

        .sent .bubble {
            background: var(--primary);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .received .bubble {
            background: #f1f5f9;
            color: var(--text);
            border-bottom-left-radius: 4px;
        }

        .msg-meta {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 4px;
            display: flex;
            gap: 8px;
        }

        .chat-footer {
            padding: 16px;
            background: var(--white);
            border-top: 1px solid var(--border);
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .chat-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            outline: none;
            font-size: 14px;
        }

        /* Private Notes Panel */
        .private-notes {
            height: 180px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 16px;
            padding: 16px;
            display: flex;
            flex-direction: column;
        }

        .notes-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 600;
            color: #92400e;
        }

        .notes-textarea {
            flex: 1;
            background: transparent;
            border: none;
            resize: none;
            outline: none;
            font-family: inherit;
            font-size: 13px;
            color: #92400e;
        }

        /* 3. Right Panel - Interaction & Video */
        .panel-right {
            width: var(--video-width);
            background: #0f172a;
            color: white;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #334155 transparent;
        }
        .panel-right::-webkit-scrollbar { width: 6px; }
        .panel-right::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }

        .video-grid {
            display: flex;
            flex-direction: column;
            gap: 16px;
            flex: 1;
        }

        .video-feed {
            width: 100%;
            aspect-ratio: 16/9;
            background: #1e293b;
            border-radius: 16px;
            position: relative;
            overflow: hidden;
            border: 1px solid #334155;
        }

        .feed-label {
            position: absolute;
            bottom: 12px;
            left: 12px;
            background: rgba(0,0,0,0.6);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
            z-index: 5;
        }

        .quality-indicator {
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
        }

        .video-controls {
            display: flex;
            justify-content: center;
            gap: 12px;
            padding: 10px 0;
            flex-shrink: 0;
        }

        .control-btn {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #334155;
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: all 0.2s;
        }

        .control-btn:hover { background: #475569; transform: scale(1.05); }
        .control-btn.active { 
            background: var(--primary); 
            box-shadow: 0 0 15px rgba(14, 165, 233, 0.4);
        }
        .control-btn.danger { background: var(--danger); }
        .control-btn.danger:hover { background: #dc2626; }

        .action-button {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: opacity 0.2s;
        }

        .btn-prescription { background: var(--primary); color: white; }
        .btn-prescription:hover { background: var(--primary-dark); }

        /* Utility */
        .badge-live {
            background: #fee2e2;
            color: var(--danger);
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .live-dot {
            width: 6px;
            height: 6px;
            background: var(--danger);
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.5); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes blink {
            from { background-color: #991b1b; }
            to { background-color: #ef4444; }
        }

        .emergency-active {
            background-color: #991b1b !important;
            color: white !important;
        }

        .hidden { display: none; }

        /* Workflow Guideline Styles */
        .workflow-step {
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px dashed var(--border);
        }
        .workflow-step h5 {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0 0 4px 0;
            text-transform: none;
        }
        .workflow-step ul {
            margin: 4px 0;
            padding-left: 18px;
            font-size: 11px;
            color: var(--text-muted);
        }
        .workflow-step li { margin-bottom: 2px; }
        .workflow-example {
            font-style: italic;
            font-size: 11px;
            color: #475569;
            background: #f1f5f9;
            padding: 6px 10px;
            border-radius: 6px;
            margin: 6px 0 0 0;
            border-left: 3px solid var(--primary);
        }
        .guideline-section {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px;
            margin-top: 15px;
        }
        .guideline-title {
            font-size: 11px;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Left Panel: Patient Clinical Info -->
        <aside class="panel panel-left">
            <div class="panel-header">
                <div class="patient-card">
                    <div class="avatar"><?php echo strtoupper(substr($consultation['patient_name'], 0, 1)); ?></div>
                    <div class="patient-meta">
                        <h2><?php echo htmlspecialchars($consultation['patient_name']); ?></h2>
                        <p><?php echo $consultation['patient_age']; ?>y · <?php echo ucfirst($consultation['patient_gender']); ?> · ID: #<?php echo $consultation['patient_id']; ?></p>
                    </div>
                </div>
                <div class="badge-live <?php echo ($consultation['urgency_level'] === 'emergency' ? 'emergency-active' : ''); ?>">
                    <div class="live-dot"></div>
                    <?php echo ($consultation['urgency_level'] === 'emergency' ? 'EMERGENCY SESSION' : 'IN CONSULTATION'); ?> 
                    <span id="sessionTimer" style="margin-left:5px; font-variant-numeric: tabular-nums;">00:00:00</span>
                </div>
            </div>

            <?php if ($consultation['urgency_level'] === 'emergency'): ?>
            <div style="background: #991b1b; color: white; padding: 10px 20px; font-size: 13px; font-weight: 600; text-align: center; animation: blink 1s infinite alternate;">
                <i class="fas fa-exclamation-triangle"></i> EMERGENCY CASE: Restricted to Chat-only if protocol requires. Stay focused.
            </div>
            <?php endif; ?>

            <nav class="tab-nav">
                <button class="tab-btn active" onclick="showTab('symptoms', this)">Symptoms</button>
                <button class="tab-btn" onclick="showTab('history', this)">History</button>
                <button class="tab-btn" onclick="showTab('prescriptions', this)">Prescriptions</button>
                <button class="tab-btn" onclick="showTab('reports', this)">Reports</button>
                <button class="tab-btn" onclick="showTab('vitals', this)">Vitals</button>
                <button class="tab-btn" onclick="showTab('guidelines', this)">Workflow</button>
            </nav>

            <div class="tab-content" id="tabContent">
                <!-- Symptoms Tab -->
                <div id="tab-symptoms">
                    <div class="content-group" style="background: #fff1f2; border: 1px solid #e11d48; padding: 10px; border-radius: 8px; margin-bottom: 20px;">
                        <h4 style="color: #be123c; margin-bottom: 5px; font-size: 11px;">⚠️ ALLERGIES</h4>
                        <p style="color: #881337; font-weight: 600; font-size: 13px; margin: 0;"><?php echo htmlspecialchars($allergies); ?></p>
                    </div>

                    <div class="content-group">
                        <h4>Chief Complaint</h4>
                        <p style="font-size: 14px; line-height: 1.5; color: var(--text-muted);">
                            <?php echo htmlspecialchars($consultation['symptoms']); ?>
                        </p>
                    </div>
                    <div class="content-group">
                        <h4>Details</h4>
                        <div class="info-row"><span class="info-label">Urgency</span><span class="info-value status-badge urgency-<?php echo $consultation['urgency_level']; ?>"><?php echo strtoupper($consultation['urgency_level']); ?></span></div>
                        <div class="info-row"><span class="info-label">Onset</span><span class="info-value"><?php echo htmlspecialchars($consultation['duration']); ?></span></div>
                        <div class="info-row"><span class="info-label">Severity</span><span class="info-value" style="color: <?php echo ($consultation['severity']=='high'?'#ef4444':'#f59e0b'); ?>"><?php echo ucfirst($consultation['severity']); ?></span></div>
                        <div class="info-row"><span class="info-label">Mode</span><span class="info-value"><?php echo ucfirst($consultation['consultation_mode'] ?? 'text'); ?></span></div>
                    </div>
                </div>

                <!-- History Tab -->
                <div id="tab-history" class="hidden">
                    <div class="content-group">
                        <h4>Medical History</h4>
                        <p style="font-size: 13px; color: var(--text-muted);"><?php echo htmlspecialchars($consultation['medical_history_summary'] ?: 'No history recorded.'); ?></p>
                    </div>
                    <div class="content-group">
                        <h4>Recent Records</h4>
                        <?php while($h = $historyRecords->fetch_assoc()): ?>
                        <div style="margin-bottom: 12px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
                            <div style="font-size: 12px; font-weight: 600;"><?php echo htmlspecialchars($h['record_title']); ?></div>
                            <div style="font-size: 11px; color: var(--text-muted);"><?php echo date('M d, Y', strtotime($h['record_date'])); ?></div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Prescriptions Tab -->
                <div id="tab-prescriptions" class="hidden">
                    <div class="content-group">
                        <h4 style="display:flex; justify-content:space-between; align-items:center;">
                            E-Prescriptions History
                            <i class="fas fa-prescription" style="color:var(--primary)"></i>
                        </h4>
                        <?php if ($prescriptions->num_rows > 0): ?>
                            <?php 
                            // Reset pointer because it might have been used if we didn't use a separate result set
                            $prescriptions->data_seek(0);
                            while($p = $prescriptions->fetch_assoc()): ?>
                            <div style="display: flex; align-items: center; gap: 10px; padding: 12px; border: 1px solid var(--primary-light); background: var(--primary-light); border-radius: 8px; margin-bottom: 8px;">
                                <i class="fas fa-file-medical" style="color: var(--primary); font-size: 1.2rem;"></i>
                                <div style="flex: 1;">
                                    <div style="font-size: 13px; font-weight: 600; color: var(--primary-dark);">E-Prescription #<?php echo $p['id']; ?></div>
                                    <div style="font-size: 11px; color: var(--text-muted);"><?php echo date('M d, Y', strtotime($p['created_at'])); ?></div>
                                    <div style="font-size: 11px; margin-top: 2px;">Diagnosis: <?php echo htmlspecialchars($p['diagnosis']); ?></div>
                                </div>
                                <button class="control-btn" style="width:30px; height:30px; font-size:12px;" onclick="viewPrescription(<?php echo $p['id']; ?>)"><i class="fas fa-eye"></i></button>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="font-size: 12px; color: var(--text-muted); font-style: italic;">No digital prescriptions issued yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Reports Tab -->
                <div id="tab-reports" class="hidden">
                    <div class="content-group">
                        <h4>Uploaded Reports</h4>
                        <?php if ($reports->num_rows > 0): ?>
                            <?php while($r = $reports->fetch_assoc()): ?>
                            <div style="display: flex; align-items: center; gap: 10px; padding: 10px; border: 1px solid var(--border); border-radius: 8px; margin-bottom: 8px;">
                                <i class="fas fa-file-pdf" style="color: #ef4444;"></i>
                                <div style="flex: 1;">
                                    <div style="font-size: 12px; font-weight: 600;"><?php echo htmlspecialchars($r['report_name']); ?></div>
                                    <div style="font-size: 11px; color: var(--text-muted);"><?php echo date('M d, Y', strtotime($r['uploaded_at'])); ?></div>
                                </div>
                                <a href="<?php echo $r['file_path']; ?>" target="_blank"><i class="fas fa-download" style="color: var(--text-muted);"></i></a>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="font-size: 13px; color: var(--text-muted);">No reports available.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Vitals Tab -->
                <div id="tab-vitals" class="hidden">
                    <div class="content-group">
                        <h4>Latest Vitals</h4>
                        <?php if ($vitals): ?>
                        <div class="info-row"><span class="info-label">Weight</span><span class="info-value"><?php echo $vitals['weight']; ?> kg</span></div>
                        <div class="info-row"><span class="info-label">BP</span><span class="info-value"><?php echo $vitals['blood_pressure']; ?> mmHg</span></div>
                        <div class="info-row"><span class="info-label">Temp</span><span class="info-value"><?php echo $vitals['temperature']; ?> °C</span></div>
                        <div class="info-row"><span class="info-label">Heart Rate</span><span class="info-value"><?php echo $vitals['heart_rate']; ?> bpm</span></div>
                        <div class="info-row"><span class="info-label">SpO2</span><span class="info-value"><?php echo $vitals['oxygen_level']; ?>%</span></div>
                        <?php else: ?>
                        <p style="font-size: 13px; color: var(--text-muted);">No vitals data.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Guidelines Tab -->
                <div id="tab-guidelines" class="hidden">
                    <div class="content-group">
                        <h4 style="color:var(--primary-dark); border-bottom: 2px solid var(--primary-light); padding-bottom: 8px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-clipboard-check"></i>
                            COMMUNICATION PROTOCOL
                        </h4>

                        <!-- Global Rules -->
                        <div style="background:#fff1f2; border:1px solid #fda4af; border-radius:6px; padding:8px; margin-bottom:15px;">
                            <strong style="color:#be123c; font-size:11px;">MANDATORY RULES:</strong>
                            <ul style="margin:4px 0 0 16px; padding:0; font-size:11px; color:#be123c; line-height:1.3;">
                                <li>Every message MUST receive a response.</li>
                                <li>Redirect non-clinical input immediately.</li>
                                <li>Never end without a question (unless closing).</li>
                                <li>Identify Chief Complaint ASAP.</li>
                            </ul>
                        </div>

                        <!-- Message Classification Logic -->
                        <h5 style="margin:12px 0 6px 0; font-size:12px; color:var(--primary-dark);">1. MESSAGE CLASSIFICATION LOGIC</h5>
                        
                        <!-- Cat A -->
                        <div class="workflow-step" style="border-left: 3px solid #f59e0b;">
                            <div style="font-weight:700; color:#d97706; font-size:11px;">A. Non-Clinical ("hi", "hello", emojis)</div>
                            <ul style="margin-top:4px;">
                                <li>Briefly acknowledge.</li>
                                <li><strong>Immediately</strong> ask for clinical info.</li>
                            </ul>
                            <div class="workflow-example" style="background:#fffbeb; border:none; color:#92400e;">
                                "Hi! To get started, please tell me what symptoms you're experiencing today."
                            </div>
                        </div>

                        <!-- Cat B -->
                        <div class="workflow-step" style="border-left: 3px solid #3b82f6;">
                            <div style="font-weight:700; color:#2563eb; font-size:11px;">B. Partial Input ("pain", "not well")</div>
                            <ul style="margin-top:4px;">
                                <li>Ask structured follow-ups (Max 2-3).</li>
                            </ul>
                            <div class="workflow-example" style="background:#eff6ff; border:none; color:#1e40af;">
                                "Can you tell me where the pain is and how long it's been happening?"
                            </div>
                        </div>

                        <!-- Cat C -->
                        <div class="workflow-step" style="border-left: 3px solid #10b981;">
                            <div style="font-weight:700; color:#059669; font-size:11px;">C. Clear Input ("chest pain for 2 days")</div>
                            <ul>
                                <li>Proceed to Clinical Workflow.</li>
                            </ul>
                        </div>

                        <!-- Clinical Workflow -->
                        <h5 style="margin:16px 0 6px 0; font-size:12px; color:var(--primary-dark);">2. CLINICAL WORKFLOW</h5>
                        
                        <div class="workflow-step">
                            <h5>Step 1: Chief Complaint</h5>
                            <ul><li>Confirm Main Symptom, Duration, Severity.</li></ul>
                        </div>

                        <div class="workflow-step">
                            <h5>Step 2: HPI (History)</h5>
                            <ul><li>Onset, Progression, Triggers, Relief, Treatments.</li></ul>
                        </div>

                        <div class="workflow-step">
                            <h5>Step 3: Medical History</h5>
                            <ul><li>Only if relevant: Conditions, Meds, Allergies.</li></ul>
                        </div>

                        <div class="workflow-step">
                            <h5>Step 4: Assessment</h5>
                            <ul><li>Summarize findings. State preliminary impression.</li></ul>
                            <p class="workflow-example">"Based on what you've shared, this may be related to..."</p>
                        </div>

                        <div class="workflow-step">
                            <h5>Step 5: Plan</h5>
                            <ul><li>Advice, Tests, Rx, Red-flags, Follow-up.</li></ul>
                        </div>

                        <div class="workflow-step" style="border-bottom:none;">
                            <h5>Step 6: Confirm & Close</h5>
                            <ul><li>"Do you have questions?" -> Confirm -> Close.</li></ul>
                        </div>

                    </div>
                </div>
            </div>
        </aside>

        <!-- Center Panel: Communication Hub -->
        <main class="panel panel-center">
            <div class="hub-container">
                <div class="chat-area">
                    <div class="chat-header panel-header" style="background: white; display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-comments" style="color: var(--primary);"></i>
                            <h3 style="margin:0; font-size: 15px;">Conversation with <?php echo htmlspecialchars($otherName ?: 'Doctor'); ?></h3>
                        </div>
                        <div id="typingIndicator" style="font-size: 11px; color: var(--text-muted); visibility: hidden;">typing...</div>
                    </div>
                    
                    <div class="chat-messages" id="chatMessages">
                        <div class="message received">
                            <div class="bubble">Hello, I am Dr. <?php echo htmlspecialchars($consultation['doctor_name']); ?>. I am reviewing your chart. How can I help you today?</div>
                            <div class="msg-meta"><span>10:00 AM</span></div>
                        </div>
                    </div>

                    <div class="chat-footer">
                        <button class="control-btn" style="background:none; color: var(--text-muted);"><i class="fas fa-paperclip"></i></button>
                        <input type="text" class="chat-input" id="messageInput" placeholder="Type a message..." oninput="handleTyping()" onkeydown="if(event.key === 'Enter') { event.preventDefault(); sendMessage(); }">
                        <button class="control-btn active" onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </div>

                <?php if ($role === 'doctor'): ?>
                <div class="private-notes">
                    <div class="notes-header">
                        <span><i class="fas fa-lock"></i> PRIVATE CLINICAL NOTES</span>
                        <div style="display:flex; gap:10px; align-items:center;">
                            <button onclick="insertSOAP()" style="background:none; border:none; color:#b45309; font-size:11px; cursor:pointer; font-weight:600; text-decoration:underline;">+ SOAP Template</button>
                            <span>Auto-saving...</span>
                        </div>
                    </div>
                    <textarea class="notes-textarea" id="privateNotes" placeholder="Jot down symptoms, preliminary diagnosis, or exam notes here..."><?php echo htmlspecialchars($consultation['private_notes']); ?></textarea>
                </div>
                <?php endif; ?>
            </div>
        </main>

        <!-- Right Panel: Video Interaction -->
        <aside class="panel panel-right">
            <div class="video-grid">
                <div class="video-feed" id="remoteVideoWrapper">
                    <div class="feed-label">
                        <div class="quality-indicator" id="remoteQuality"></div>
                        <span>Dr. <?php echo htmlspecialchars($consultation['doctor_name']); ?></span>
                    </div>
                    <div id="remotePlaceholder" style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#64748b;">
                        <i class="fas fa-user-md fa-3x"></i>
                    </div>
                </div>
                <div class="video-feed" id="localVideoWrapper" style="height: 140px;">
                    <div class="feed-label">
                        <span>You</span>
                    </div>
                    <div id="localPlaceholder" style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#64748b;">
                        <i class="fas fa-camera-slash"></i>
                    </div>
                </div>
            </div>

            <div class="video-controls">
                <button class="control-btn hint--top" data-hint="Toggle Video" id="btnVideo" onclick="toggleVideo()"><i class="fas fa-video"></i></button>
                <button class="control-btn hint--top" data-hint="Mute/Unmute" id="btnAudio" onclick="toggleAudio()"><i class="fas fa-microphone"></i></button>
                <button class="control-btn hint--top" data-hint="Share Screen" id="btnShare"><i class="fas fa-desktop"></i></button>
                <button class="control-btn danger hint--top" data-hint="End Session" onclick="endConsultation()"><i class="fas fa-phone-slash"></i></button>
            </div>

            <div style="flex-shrink: 0; padding-top: 20px; border-top: 1px solid #334155;">
                <?php if ($role === 'doctor'): ?>
                <button class="action-button btn-prescription" onclick="showPrescriptionModal()" style="margin-bottom: 10px;">
                    <i class="fas fa-file-medical"></i>
                    Generate E-Prescription
                </button>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <button class="action-button" onclick="transferCase()" style="background: #334155; color: white; font-size: 13px;">
                        <i class="fas fa-exchange-alt"></i> Transfer
                    </button>
                    <button class="action-button" onclick="escalateToEmergency()" style="background: #991b1b; color: white; font-size: 13px;">
                        <i class="fas fa-exclamation-triangle"></i> Emergency
                    </button>
                </div>
                <?php endif; ?>
                <div style="margin-top: 16px; font-size: 11px; color: #64748b; text-align: center;">
                    <i class="fas fa-shield-halved"></i> End-to-End Encrypted Session
                </div>
            </div>
        </aside>
    </div>

    <!-- E-Prescription Modal -->
    <!-- E-Prescription Modal -->
    <div id="prescriptionModal" onclick="if(event.target === this) hidePrescriptionModal()" style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100; display: none; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div style="background: white; border-radius: 20px; width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
            <div style="padding: 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; background: white; z-index: 10;">
                <h2 style="margin:0; font-size: 18px; color: var(--text);"><i class="fas fa-file-medical" style="color: var(--primary); margin-right: 10px;"></i> Digital E-Prescription</h2>
                <button onclick="hidePrescriptionModal()" style="background: rgba(0,0,0,0.05); border:none; width: 32px; height: 32px; border-radius: 50%; color: var(--text); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s;" onmouseover="this.style.background='rgba(0,0,0,0.1)'" onmouseout="this.style.background='rgba(0,0,0,0.05)'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="padding: 24px;">
                <div id="prescriptionFormContainer">
                    <form id="prescriptionForm">
                        <div style="margin-bottom: 20px;">
                            <label style="display:block; font-size: 13px; font-weight: 600; margin-bottom: 8px;">Diagnosis / Clinical Impression</label>
                            <textarea name="diagnosis" style="width:100%; padding:12px; border:1px solid var(--border); border-radius:10px; font-size:14px; min-height:80px;" placeholder="Enter diagnosis..."></textarea>
                        </div>
                        
                        <div id="medicationItems">
                            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 12px; padding: 16px; margin-bottom: 16px;">
                                <div style="display:flex; gap: 12px; margin-bottom: 12px;">
                                    <div style="flex: 2;">
                                        <label style="display:block; font-size: 11px; color: var(--text-muted); margin-bottom: 4px;">Medicine Name</label>
                                        <input type="text" name="med_name[]" placeholder="e.g. Paracetamol" style="width:100%; padding:8px; border:1px solid var(--border); border-radius:6px; font-size:14px;">
                                    </div>
                                    <div style="flex: 1;">
                                        <label style="display:block; font-size: 11px; color: var(--text-muted); margin-bottom: 4px;">Dosage</label>
                                        <input type="text" name="med_dosage[]" placeholder="500mg" style="width:100%; padding:8px; border:1px solid var(--border); border-radius:6px; font-size:14px;">
                                    </div>
                                </div>
                                <div style="display:flex; gap: 12px;">
                                    <div style="flex: 1;">
                                        <label style="display:block; font-size: 11px; color: var(--text-muted); margin-bottom: 4px;">Frequency</label>
                                        <select name="med_freq[]" style="width:100%; padding:8px; border:1px solid var(--border); border-radius:6px; font-size:14px;">
                                            <option>Once Daily</option>
                                            <option>Twice Daily</option>
                                            <option>Three Times Daily</option>
                                            <option>As Needed</option>
                                        </select>
                                    </div>
                                    <div style="flex: 1;">
                                        <label style="display:block; font-size: 11px; color: var(--text-muted); margin-bottom: 4px;">Duration</label>
                                        <input type="text" name="med_duration[]" placeholder="5 days" style="width:100%; padding:8px; border:1px solid var(--border); border-radius:6px; font-size:14px;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" onclick="addMedicationRow()" style="background:none; border:1px dashed var(--primary); color:var(--primary); width:100%; padding:10px; border-radius:10px; font-size:12px; margin-bottom:20px; cursor:pointer;">
                            <i class="fas fa-plus"></i> Add Another Medication
                        </button>

                        <div style="margin-bottom: 24px;">
                            <label style="display:block; font-size: 13px; font-weight: 600; margin-bottom: 8px;">Special Instructions</label>
                            <input type="text" name="instructions" style="width:100%; padding:12px; border:1px solid var(--border); border-radius:10px; font-size:14px;" placeholder="After food, avoid cold drinks, etc.">
                        </div>

                        <div style="background: #ecfdf5; border: 1px dashed #059669; border-radius: 12px; padding: 16px; margin-bottom: 24px;">
                            <div style="display:flex; align-items:center; gap: 12px;">
                                <i class="fas fa-signature fa-2x" style="color: #059669;"></i>
                                <div>
                                    <div style="font-size: 13px; font-weight: 600; color: #065f46;">Digitalized Verification</div>
                                    <div style="font-size: 11px; color: #059669;">Verified by MedConnect E-Sign Protocols</div>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="action-button btn-prescription" onclick="submitPrescription()" style="width:100%;">
                            <i class="fas fa-paper-plane"></i> Sign & Finalize Prescription
                        </button>
                    </form>
                </div>
                <div id="prescriptionViewContainer" style="display:none;">
                    <!-- Filled dynamically via JS -->
                </div>
            </div>
        </div>
    </div>

    <script>
        const consultationId = <?php echo $consultationId; ?>;
        const role = '<?php echo $role; ?>';
        const userId = <?php echo $_SESSION['user_id']; ?>;
        const receiverId = <?php echo ($role === 'patient') ? ($consultation['doctor_id'] ?: 0) : $consultation['patient_id']; ?>;
        let lastMessageId = 0;
        let isTyping = false;
        let localStream;
        let peerConnection;
        
        const config = { 
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ] 
        };

        // --- Session Timer ---
        let startTime = new Date('<?php echo $consultation['assigned_at'] ?: $consultation['created_at']; ?>').getTime();
        function updateTimer() {
            const now = new Date().getTime();
            const diff = now - startTime;
            
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            document.getElementById('sessionTimer').textContent = 
                (hours < 10 ? '0' + hours : hours) + ':' + 
                (minutes < 10 ? '0' + minutes : minutes) + ':' + 
                (seconds < 10 ? '0' + seconds : seconds);
            
            // Highlight if session exceeds 30 mins
            if (minutes >= 30) {
                document.getElementById('sessionTimer').parentElement.style.background = '#fee2e2';
                document.getElementById('sessionTimer').parentElement.style.color = '#ef4444';
            }
        }
        setInterval(updateTimer, 1000);
        updateTimer(); // Initial call

        // --- Advanced Actions ---
        async function transferCase() {
            const specialty = prompt('Enter the specialty to transfer to (e.g., Cardiology):');
            if (!specialty) return;
            const reason = prompt('Reason for transfer:');
            
            const formData = new FormData();
            formData.append('action', 'transfer_consultation');
            formData.append('consultation_id', consultationId);
            formData.append('specialty', specialty);
            formData.append('reason', reason || '');

            try {
                const response = await fetch('doctor_api.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.status === 'success') {
                    alert('Case transferred. You will be redirected to dashboard.');
                    window.location.href = 'doctor_dashboard.php';
                }
            } catch (err) { alert('Transfer failed'); }
        }

        async function escalateToEmergency() {
            if (!confirm('Escalate this case to EMERGENCY?')) return;
            const notes = prompt('Emergency notes:');
            
            const formData = new FormData();
            formData.append('action', 'escalate_emergency');
            formData.append('consultation_id', consultationId);
            formData.append('notes', notes || '');

            try {
                const response = await fetch('doctor_api.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.status === 'success') {
                    alert('Emergency escalated!');
                    location.reload();
                }
            } catch (err) { alert('Escalation failed'); }
        }

        // --- Tab Management ---
        function showTab(tabId, el) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content > div');
            contents.forEach(div => div.classList.add('hidden'));
            
            // Remove active class from all buttons
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            // Show current tab
            const target = document.getElementById('tab-' + tabId);
            if (target) {
                target.classList.remove('hidden');
            } else {
                console.error("Tab content not found for showTab:", tabId);
            }
            
            // Add active class to clicked button
            if (el) {
                el.classList.add('active');
            } else if (window.event && window.event.currentTarget) {
                window.event.currentTarget.classList.add('active');
            } else {
                // Fallback to find by text if el not provided
                Array.from(document.querySelectorAll('.tab-btn')).find(b => b.textContent.toLowerCase().includes(tabId))?.classList.add('active');
            }
        }

        async function sendMessage() {
            console.log('[Chat] sendMessage() called');
            const input = document.getElementById('messageInput');
            const text = input.value.trim();
            console.log('[Chat] Message text:', text);
            console.log('[Chat] receiverId:', receiverId);
            
            if (!text) {
                console.log('[Chat] Empty message, ignoring');
                return;
            }
            if (!receiverId) {
                console.error('[Chat] ERROR: receiverId is not set!');
                alert("Cannot send message: Receiver not identified yet.");
                return;
            }

            const formData = new FormData();
            formData.append('action', 'send');
            formData.append('consultation_id', consultationId);
            formData.append('content', text);
            formData.append('receiver_id', receiverId);
            formData.append('type', 'text');

            try {
                console.log('[Chat] Sending message to chat_api.php...', { consultationId, receiverId, text });
                const response = await fetch('chat_api.php', { method: 'POST', body: formData });
                const data = await response.json();
                console.log('[Chat] Response received:', data);
                
                if (data.success) {
                    input.value = '';
                    const newMsg = {
                        id: data.message_id,
                        sender_id: userId,
                        message_content: text,
                        created_at: new Date().toISOString()
                    };
                    appendMessage(newMsg, true);
                    lastMessageId = Math.max(lastMessageId, data.message_id);
                    console.log('[Chat] Message sent successfully!', data.message_id);
                } else {
                    console.error('[Chat] Failed to send:', data.error);
                    alert("Failed to send message: " + (data.error || "Unknown error"));
                }
            } catch (err) { 
                console.error("Error sending message:", err);
                alert("Network error: Could not send message. Check console for details.");
            }
        }

        function handleTyping() {
            if (!isTyping) {
                isTyping = true;
                sendSignal({ type: 'typing', isTyping: true });
                setTimeout(() => {
                    isTyping = false;
                    sendSignal({ type: 'typing', isTyping: false });
                }, 3000);
            }
        }

        async function fetchMessages() {
            try {
                console.log('[Chat] Fetching messages - consultationId:', consultationId, 'lastMessageId:', lastMessageId);
                const response = await fetch(`chat_api.php?action=fetch&consultation_id=${consultationId}&last_id=${lastMessageId}`);
                const data = await response.json();
                console.log('[Chat] Fetch response:', data);
                
                if (data.success && data.messages.length > 0) {
                    console.log('[Chat] Received', data.messages.length, 'new messages');
                    data.messages.forEach(msg => {
                        if (msg.message_type === 'signal') {
                            try {
                                const signal = JSON.parse(msg.message_content);
                                if (signal.type === 'typing') {
                                    document.getElementById('typingIndicator').style.visibility = signal.isTyping ? 'visible' : 'hidden';
                                } else {
                                    if (typeof handleSignal === 'function') {
                                        handleSignal(signal);
                                    }
                                }
                            } catch (signalErr) {
                                console.error('[Chat] Signal processing error (non-fatal):', signalErr);
                            }
                        } else {
                            console.log('[Chat] Appending message:', msg.id, msg.message_content);
                            appendMessage(msg, msg.sender_id == userId);
                        }
                        lastMessageId = Math.max(lastMessageId, msg.id);
                    });
                    console.log('[Chat] Updated lastMessageId to:', lastMessageId);
                } else if (data.success) {
                    console.log('[Chat] No new messages');
                } else {
                    console.error('[Chat] Fetch failed:', data.error);
                }
            } catch (err) { console.error("[Chat] Error fetching messages:", err); }
        }

        function appendMessage(msg, isSent) {
            // Deduplication: Check if message already exists in DOM
            if (msg.id && document.getElementById('msg-' + msg.id)) return;

            const container = document.getElementById('chatMessages');
            const msgDiv = document.createElement('div');
            msgDiv.className = `message ${isSent ? 'sent' : 'received'}`;
            if (msg.id) msgDiv.id = 'msg-' + msg.id;
            
            const time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            msgDiv.innerHTML = `
                <div class="bubble">${msg.message_content}</div>
                <div class="msg-meta">
                    <span>${time}</span>
                    ${isSent ? (msg.is_read ? '<i class="fas fa-check-double" style="color:var(--primary)"></i>' : '<i class="fas fa-check" style="color:var(--text-muted)"></i>') : ''}
                </div>
            `;
            container.appendChild(msgDiv);
            container.scrollTop = container.scrollHeight;
        }

        // --- Prescription Hub ---
        function showPrescriptionModal() { 
            document.getElementById('prescriptionFormContainer').style.display = 'block';
            document.getElementById('prescriptionViewContainer').style.display = 'none';
            document.getElementById('prescriptionModal').style.display = 'flex'; 
        }
        function hidePrescriptionModal() { document.getElementById('prescriptionModal').style.display = 'none'; }
        function addMedicationRow() {
            const container = document.getElementById('medicationItems');
            const newRow = container.firstElementChild.cloneNode(true);
            newRow.querySelectorAll('input').forEach(i => i.value = '');
            container.appendChild(newRow);
        }

        async function submitPrescription() {
            const form = document.getElementById('prescriptionForm');
            const formData = new FormData(form);
            
            // Get patient_id from the consultation
            const patientId = <?php echo $consultation['patient_id']; ?>;
            
            // Extract diagnosis
            const diagnosis = formData.get('diagnosis');
            if (!diagnosis || diagnosis.trim() === '') {
                alert("Please enter a diagnosis before submitting the prescription.");
                return;
            }
            
            // Extract medicines
            const medNames = formData.getAll('med_name[]');
            const medDosages = formData.getAll('med_dosage[]');
            const medFreqs = formData.getAll('med_freq[]');
            const medDurations = formData.getAll('med_duration[]');
            
            const medicines = [];
            for (let i = 0; i < medNames.length; i++) {
                if (medNames[i].trim()) {
                    medicines.push({
                        name: medNames[i],
                        dosage: medDosages[i] || '',
                        frequency: medFreqs[i] || '',
                        duration: medDurations[i] || '',
                        instructions: formData.get('instructions') || '',
                        quantity: 1
                    });
                }
            }
            
            if (medicines.length === 0) {
                alert("Please add at least one medication.");
                return;
            }
            
            // Prepare JSON payload
            const payload = {
                consultation_id: consultationId,
                patient_id: patientId,
                diagnosis: diagnosis,
                medicines: medicines,
                tests: [],
                follow_up_date: null,
                notes_for_patient: formData.get('instructions') || '',
                notes_for_pharmacy: ''
            };
            
            try {
                const response = await fetch('doctor_api.php?action=save_prescription', { 
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                if (data.status === 'success') {
                    alert("Prescription signed and sent to patient & pharmacy!");
                    hidePrescriptionModal();
                    sendMessage("I have issued your e-prescription. You can view it in your 'Prescriptions' tab now.");
                } else {
                    alert("Error: " + (data.message || "Failed to save prescription"));
                }
            } catch (err) { console.error(err); alert("Failed to connect to server."); }
        }

        async function viewPrescription(id) {
            try {
                const response = await fetch(`doctor_api.php?action=get_prescription_details&id=${id}`);
                const data = await response.json();
                if (data.status === 'success') {
                    const p = data.prescription;
                    let itemsHtml = data.items.map(it => `
                        <div style="padding: 10px; border-bottom: 1px solid #f1f5f9;">
                            <div style="font-weight:600; font-size:14px;">${it.medicine_name} - ${it.dosage}</div>
                            <div style="font-size:12px; color:#64748b;">${it.frequency} | ${it.duration}</div>
                        </div>
                    `).join('');

                    // Show in dedicated view container
                    const viewContainer = document.getElementById('prescriptionViewContainer');
                    const formContainer = document.getElementById('prescriptionFormContainer');
                    
                    formContainer.style.display = 'none';
                    viewContainer.style.display = 'block';
                    
                    viewContainer.innerHTML = `
                        <div style="background:#f8fafc; padding:20px; border-radius:12px; margin-bottom:20px;">
                            <div style="font-weight:700; color:var(--primary-dark); margin-bottom:10px;">Prescription issued by Dr. ${p.doctor_name}</div>
                            <div style="font-size:13px; margin-bottom:5px;"><strong>Diagnosis:</strong> ${p.diagnosis}</div>
                            <div style="font-size:13px; margin-bottom:5px;"><strong>Instructions:</strong> ${p.notes_for_patient || 'None'}</div>
                            <div style="font-size:13px; color:#059669; font-weight:600;"><i class="fas fa-truck-medical"></i> Sent to: ${p.pharmacy_name || 'Generic Selection'}</div>
                        </div>
                        <div style="margin-bottom:20px;">
                            <label style="display:block; font-size:13px; font-weight:600; margin-bottom:8px;">Medications</label>
                            <div style="border:1px solid var(--border); border-radius:12px; overflow:hidden;">
                                ${itemsHtml}
                            </div>
                        </div>
                        <button type="button" class="action-button btn-prescription" onclick="hidePrescriptionModal()">Close</button>
                    `;
                    document.getElementById('prescriptionModal').style.display = 'flex';
                }
            } catch (err) { console.error(err); }
        }

        // --- WebRTC Functions ---
        async function toggleVideo(isAuto = false) {
            console.log("Toggling video...", isAuto ? "(auto-start)" : "(manual)");
            if (!localStream) {
                try {
                    localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                    const localVideoWrapper = document.getElementById('localVideoWrapper');
                    localVideoWrapper.innerHTML = `
                        <video id="localVid" autoplay playsinline muted style="width:100%; height:100%; object-fit: cover;"></video>
                        <div class="feed-label"><span>You</span></div>
                    `;
                    document.getElementById('localVid').srcObject = localStream;
                    document.getElementById('btnVideo').classList.add('active');
                    document.getElementById('btnAudio').classList.add('active');
                    
                    setupPeerConnection();
                    if (role === 'doctor') {
                        const offer = await peerConnection.createOffer();
                        await peerConnection.setLocalDescription(offer);
                        sendSignal({ target: 'webrtc', offer });
                    }
                } catch (err) { 
                    console.error("Camera access failed:", err);
                    if (!isAuto) alert("Camera/Microphone access denied. Please check your browser permissions.");
                }
            } else {
                const videoTrack = localStream.getVideoTracks()[0];
                if (videoTrack) {
                    videoTrack.enabled = !videoTrack.enabled;
                    document.getElementById('btnVideo').classList.toggle('active', videoTrack.enabled);
                    sendSignal({ type: 'media_status', video: videoTrack.enabled });
                }
            }
        }

        function setupPeerConnection() {
            if (peerConnection) return;
            peerConnection = new RTCPeerConnection(config);
            localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream));

            peerConnection.ontrack = (event) => {
                const remoteWrapper = document.getElementById('remoteVideoWrapper');
                remoteWrapper.innerHTML = `
                    <video id="remoteVid" autoplay playsinline style="width:100%; height:100%; object-fit: cover;"></video>
                    <div class="feed-label">
                        <div class="quality-indicator"></div>
                        <span>Dr. ${'<?php echo $consultation['doctor_name']; ?>'}</span>
                    </div>
                `;
                document.getElementById('remoteVid').srcObject = event.streams[0];
            };

            peerConnection.onicecandidate = (e) => e.candidate && sendSignal({ target: 'webrtc', candidate: e.candidate });
        }

        async function sendSignal(signal) {
            const formData = new FormData();
            formData.append('action', 'send');
            formData.append('consultation_id', consultationId);
            formData.append('content', JSON.stringify(signal));
            formData.append('receiver_id', receiverId);
            formData.append('type', 'signal');
            await fetch('chat_api.php', { method: 'POST', body: formData });
        }

        async function handleSignal(signal) {
            if (signal.target === 'webrtc') {
                if (!peerConnection) setupPeerConnection();
                if (signal.offer) {
                    await peerConnection.setRemoteDescription(new RTCSessionDescription(signal.offer));
                    const answer = await peerConnection.createAnswer();
                    await peerConnection.setLocalDescription(answer);
                    sendSignal({ target: 'webrtc', answer });
                } else if (signal.answer) {
                    await peerConnection.setRemoteDescription(new RTCSessionDescription(signal.answer));
                } else if (signal.candidate) {
                    await peerConnection.addIceCandidate(new RTCIceCandidate(signal.candidate));
                }
            } else if (signal.type === 'media_status') {
                // Update UI indicator if remote camera is off
            }
        }

        function toggleAudio() {
            if (localStream) {
                const track = localStream.getAudioTracks()[0];
                if (track) {
                    track.enabled = !track.enabled;
                    document.getElementById('btnAudio').classList.toggle('active', track.enabled);
                    sendSignal({ type: 'media_status', audio: track.enabled });
                }
            }
        }

        // --- Private Notes Auto-save ---
        const notesEl = document.getElementById('privateNotes');
        if (notesEl) {
            let timeout;
            notesEl.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(async () => {
                    const formData = new FormData();
                    formData.append('action', 'save_private_notes');
                    formData.append('consultation_id', consultationId);
                    formData.append('notes', notesEl.value);
                    await fetch('doctor_api.php', { method: 'POST', body: formData });
                }, 2000);
            });
        }

        async function endConsultation() {
            if(!confirm("Are you sure you want to end this session? This will mark it as completed and calculate your earnings.")) return;

            try {
                const formData = new FormData();
                formData.append('action', 'complete_consultation');
                formData.append('consultation_id', <?php echo $consultationId; ?>);

                const response = await fetch('doctor_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    alert('Consultation completed successfully!');
                    window.location.href = 'doctor_dashboard.php?view=earnings';
                } else {
                    alert('Error ending session: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Connection error. Please try again.');
            }
        }

        function insertSOAP() {
            const textarea = document.getElementById('privateNotes');
            if (textarea.value.trim() !== '' && !confirm("This will append the SOAP template. Continue?")) return;
            
            const template = `Subjective:
- Patient presents with: 
- History of present illness: 

Objective:
- Vitals: 
- Physical Exam: 

Assessment:
- Primary Diagnosis: 
- Differential Diagnosis: 

Plan:
- Medications: 
- Tests: 
- Follow-up: `;
            
            textarea.value = textarea.value + (textarea.value ? '\n\n' : '') + template;
            textarea.focus();
            // Trigger autosave
            textarea.dispatchEvent(new Event('input'));
        }


        window.addEventListener('load', () => {
            setTimeout(() => {
                // Video initialization (can fail, that's OK)
                try {
                    if (typeof toggleVideo === 'function') {
                        toggleVideo(true);
                    }
                } catch (err) {
                    console.error('[Video] Failed to initialize (non-fatal):', err);
                }
                
                // Chat initialization (critical, but protected)
                try {
                    fetchMessages();
                    console.log('[Chat] Initial fetch complete');
                } catch (err) {
                    console.error('[Chat] Initial fetch failed:', err);
                }
            }, 800);
        });

        // Polling with protection
        setInterval(() => {
            try {
                fetchMessages();
            } catch (err) {
                console.error('[Chat] Polling error:', err);
            }
        }, 3000);
    </script>
</body>
</html>

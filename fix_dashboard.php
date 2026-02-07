<?php
session_start();
require_once 'db.php';

$message = "";
$consultations_created = false;

// Check if logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    $message = "❌ NOT LOGGED IN\n\nPlease log in to the doctor dashboard first, then come back to this page.";
} else {
    $doctor_id = $_SESSION['user_id'];
    
    // Get doctor info
    $res = $conn->query("SELECT u.full_name FROM users u WHERE u.id = $doctor_id");
    $doctor = $res->fetch_assoc();
    $doctor_name = $doctor['full_name'] ?? 'Doctor';
    
    $message = "✓ Logged in as: $doctor_name (ID: $doctor_id)\n\n";
    
    // Check current consultations
    $check = $conn->query("
        SELECT COUNT(*) as count 
        FROM consultations 
        WHERE doctor_id = $doctor_id AND status IN ('pending', 'reassigned')
    ");
    $current_count = $check->fetch_assoc()['count'];
    $message .= "Current pending consultations: $current_count\n\n";
    
    // Create test consultations if button clicked
    if (isset($_POST['create'])) {
        $patientRes = $conn->query("SELECT id FROM users WHERE role = 'patient' LIMIT 1");
        if ($patientRes && $patient = $patientRes->fetch_assoc()) {
            $patient_id = $patient['id'];
            
            // Emergency consultation
            $conn->query("INSERT INTO consultations 
                (patient_id, doctor_id, symptoms, severity, urgency_level, urgency_score, 
                 consultation_mode, status, matched_specialty, duration, created_at) 
                VALUES 
                ($patient_id, $doctor_id, 'Severe chest pain radiating to left arm, shortness of breath', 
                 'high', 'emergency', 95, 'video', 'pending', 'Cardiology', 'Less than 24 hours', NOW())");
            $id1 = $conn->insert_id;
            
            // Urgent consultation  
            $conn->query("INSERT INTO consultations 
                (patient_id, doctor_id, symptoms, severity, urgency_level, urgency_score, 
                 consultation_mode, status, matched_specialty, duration, created_at) 
                VALUES 
                ($patient_id, $doctor_id, 'High fever 103°F, severe headache and body aches for 2 days', 
                 'medium', 'urgent', 75, 'text', 'pending', 'General Medicine', '1-2 days', NOW())");
            $id2 = $conn->insert_id;
            
            $consultations_created = true;
            $message .= "✓ Created consultation #$id1 (EMERGENCY)\n";
            $message .= "✓ Created consultation #$id2 (URGENT)\n\n";
            $message .= "Now refresh your doctor dashboard to see them!";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Fix Tool</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 600px; margin: 0 auto; }
        pre { background: #f8f8f8; padding: 15px; border-radius: 4px; white-space: pre-wrap; }
        button { 
            background: #0ea5e9; 
            color: white; 
            border: none; 
            padding: 12px 24px; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 16px;
            margin-top: 15px;
        }
        button:hover { background: #0284c7; }
        button:disabled { background: #ccc; cursor: not-allowed; }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Dashboard Fix Tool</h1>
        <pre><?php echo htmlspecialchars($message); ?></pre>
        
        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'doctor'): ?>
            <?php if (!$consultations_created): ?>
                <form method="POST">
                    <button type="submit" name="create">Create Test Consultations</button>
                </form>
            <?php else: ?>
                <p class="success">✓ Consultations created!</p>
                <a href="doctor_dashboard.php" style="display: inline-block; margin-top: 10px; background: #10b981; color: white; text-decoration: none; padding: 12px 24px; border-radius: 6px;">Go to Dashboard →</a>
            <?php endif; ?>
        <?php else: ?>
            <p class="error">Please log in first:</p>
            <a href="doctor_dashboard.php" style="display: inline-block; margin-top: 10px; background: #0ea5e9; color: white; text-decoration: none; padding: 12px 24px; border-radius: 6px;">Go to Login →</a>
        <?php endif; ?>
    </div>
</body>
</html>
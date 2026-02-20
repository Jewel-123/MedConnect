<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$patient_id = $_GET['patient_id'] ?? null;
if (!$patient_id) {
    die("Patient ID required.");
}

$patient = $conn->query("SELECT full_name, email FROM users WHERE id = $patient_id")->fetch_assoc();
if (!$patient) die("Patient not found.");

$role = $_SESSION['role'];
$user_name = $_SESSION['admin_name'] ?? $_SESSION['full_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Profile | <?php echo $patient['full_name']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root {
            --primary: #0d9488;
            --surface: #ffffff;
            --bg: #f8fafc;
            --border: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text-main); margin: 0; }
        .header { background: white; border-bottom: 1px solid var(--border); padding: 1.5rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
        .profile-card { background: white; border-radius: 12px; border: 1px solid var(--border); padding: 1.5rem; display: flex; align-items: center; gap: 1.5rem; margin-bottom: 2rem; }
        .avatar { width: 64px; height: 64px; background: #ccfbf1; color: #0d9488; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; }
        .tabs { display: flex; gap: 2rem; border-bottom: 1px solid var(--border); margin-bottom: 2rem; }
        .tab { padding: 0.75rem 0; cursor: pointer; color: var(--text-muted); font-weight: 600; border-bottom: 2px solid transparent; }
        .tab.active { color: var(--primary); border-color: var(--primary); }
        .btn { padding: 0.6rem 1rem; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; }
        .btn-primary { background: var(--primary); color: white; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <a href="medical_records.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ph ph-arrow-left"></i> Back to All Records
            </a>
            <h1 style="margin: 0.5rem 0 0; font-size: 1.5rem;">Patient Profile</h1>
        </div>
        <div style="text-align: right;">
            <div style="font-weight: 700;"><?php echo $user_name; ?></div>
            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: capitalize;"><?php echo $role; ?></div>
        </div>
    </div>

    <div class="container">
        <div class="profile-card">
            <div class="avatar"><?php echo strtoupper(substr($patient['full_name'],0,1)); ?></div>
            <div>
                <h2 style="margin: 0; font-size: 1.4rem;"><?php echo htmlspecialchars($patient['full_name']); ?></h2>
                <p style="margin: 0.25rem 0 0; color: var(--text-muted);"><?php echo htmlspecialchars($patient['email']); ?></p>
            </div>
            <?php if ($role !== 'patient'): ?>
            <button class="btn btn-primary" onclick="window.location.href='medical_records.php?patient_id=<?php echo $patient_id; ?>'" style="margin-left: auto;">
                <i class="ph ph-plus-circle"></i> Add Record
            </button>
            <?php endif; ?>
        </div>

        <!-- iframe integration for history to reuse medical_records.php logic without sidebar -->
        <div class="tabs">
            <div class="tab active" id="recordsTab" onclick="switchFrame('records')">Medical History</div>
            <div class="tab" id="remindersTab" onclick="switchFrame('reminders')">Reminders</div>
        </div>

        <iframe id="moduleFrame" src="medical_records_embedded.php?patient_id=<?php echo $patient_id; ?>&tab=records" style="width: 100%; height: 600px; border: none; border-radius: 12px; background: white; border: 1px solid var(--border);"></iframe>
    </div>

    <script>
        function switchFrame(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.getElementById(tab + 'Tab').classList.add('active');
            document.getElementById('moduleFrame').src = `medical_records_embedded.php?patient_id=<?php echo $patient_id; ?>&tab=${tab}`;
        }
    </script>
</body>
</html>

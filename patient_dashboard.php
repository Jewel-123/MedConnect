<?php
session_start();
require_once 'db.php';

// Rigorous Session & Role Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.php");
    exit();
}

$patientId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'Patient';

// --- Fetch Dashboard Statistics ---

// 1. Past Consultations Count
$consultsCountQuery = $conn->query("SELECT COUNT(*) as count FROM consultations WHERE patient_id = $patientId AND status IN ('completed', 'cancelled')");
$pastConsultsCount = $consultsCountQuery ? $consultsCountQuery->fetch_assoc()['count'] : 0;

// 2. Active Prescriptions Count
$activePrescriptionsQuery = $conn->query("
    SELECT COUNT(*) as count 
    FROM prescriptions_v2 
    WHERE patient_id = $patientId 
    AND status IN ('finalized', 'sent_to_pharmacy', 'in_progress', 'ready')
");
$activePrescriptionsCount = $activePrescriptionsQuery ? $activePrescriptionsQuery->fetch_assoc()['count'] : 0;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - MedConnect</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/custom_modal.css?v=<?php echo time(); ?>">
    <script src="assets/js/custom_modal.js?v=<?php echo time(); ?>"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background: #f8fafc; }
        .dashboard-container { min-height: 100vh; display: flex; }
        .main-area { flex: 1; padding: 2rem; }
    </style>
</head>

<body>

    <div class="dashboard-container fade-in">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h3><i class="ph ph-heartbeat"></i> MedConnect</h3>
            <ul>
                <li><a href="patient_dashboard.php" class="active"><i class="ph ph-squares-four"></i> Dashboard</a></li>
                <li><a href="symptom_checker.php"><i class="ph ph-clipboard-text"></i> Symptom Checker</a></li>
                <li><a href="appointment_booking.php"><i class="ph ph-calendar-check"></i> Book Appointment</a></li>
                <li><a href="patient_prescriptions.php"><i class="ph ph-prescription"></i> My Prescriptions</a></li>
                <li><a href="medical_records.php"><i class="ph ph-file-medical"></i> Medical Records</a></li>
                <li><a href="medical_records.php?tab=reminders"><i class="ph ph-bell"></i> My Reminders</a></li>
                <li><a href="payment_gateway.php"><i class="ph ph-credit-card"></i> Payments</a></li>
                <li><a href="#" onclick="showHistory()"><i class="ph ph-clock-counter-clockwise"></i> History</a></li>
                <li><a href="logout.php" style="color: #fee2e2;"><i class="ph ph-sign-out"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-area" id="dashboardContent">
            <div class="dash-header" style="margin-bottom: 2rem;">
                <h2 style="font-size: 2rem; font-weight: 700; color: #1e293b; margin: 0;">Welcome, <?php echo explode(' ', $fullName)[0]; ?>!</h2>
                <p style="color: #64748b; margin-top: 0.5rem;">Here's a summary of your health activity.</p>
            </div>

            <div class="stat-row">
                <div class="stat-box">
                    <div class="large-number" id="pastConsultationsCount"><?php echo $pastConsultsCount; ?></div>
                    <p>Past Consultations</p>
                </div>
                <div class="stat-box">
                    <div class="large-number" id="activePrescriptionCount"><?php echo $activePrescriptionsCount; ?></div>
                    <p>Active Prescriptions</p>
                </div>
            </div>

            <h3 style="font-size: 1.4rem; font-weight: 700; color: #1e293b; margin-bottom: 1.5rem;">Recent Activity</h3>
            <div id="recentActivityList" class="activity-card" style="background: white; padding: 1.5rem; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f1f5f9;">
                <p style="text-align: center; color: #64748b; padding: 2rem;">Loading activity...</p>
            </div>
        </main>
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script>
    <script>
        // Load activity data on page load
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof loadPatientDashboardData === 'function') {
                loadPatientDashboardData();
            }
        });

        // Toggle to history view
        function showHistory() {
            const content = document.getElementById('dashboardContent');
            content.innerHTML = `
                <div class="dash-header" style="margin-bottom: 2rem;">
                    <h2 style="font-size: 2.2rem; font-weight: 700; color: #1e293b; margin: 0;">Consultation History</h2>
                    <p style="color: #64748b; margin-top: 0.5rem;">A complete record of your medical interactions.</p>
                </div>
                <div id="historyActivityList" class="activity-card" style="background: white; padding: 1.5rem; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f1f5f9;">
                    <p style="text-align: center; color: #64748b; padding: 2rem;">Loading history...</p>
                </div>
            `;
            // Trigger history loading from script.js
            if (typeof loadConsultationHistory === 'function') {
                loadConsultationHistory();
            }
            
            // Update sidebar active state
            document.querySelectorAll('.sidebar li a').forEach(a => a.classList.remove('active'));
            document.querySelector('.sidebar li a i.ph-clock-counter-clockwise').parentElement.classList.add('active');
        }
    </script>
    <script src="js/custom_modals.js"></script>
</body>

</html>

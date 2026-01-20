<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'patient';
$userName = $_SESSION['user_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedConnect - Features</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { text-align: center; color: white; margin-bottom: 40px; }
        .header h1 { font-size: 36px; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 18px; }
        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; }
        .feature-card { background: white; border-radius: 16px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); transition: all 0.3s; text-decoration: none; color: inherit; display: block; }
        .feature-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.3); }
        .feature-icon { font-size: 48px; margin-bottom: 15px; }
        .feature-title { font-size: 22px; font-weight: 600; color: #1e293b; margin-bottom: 10px; }
        .feature-desc { color: #64748b; font-size: 15px; line-height: 1.6; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; margin-top: 10px; }
        .badge-patient { background: #dbeafe; color: #075985; }
        .badge-doctor { background: #dcfce7; color: #166534; }
        .badge-pharmacy { background: #fef3c7; color: #92400e; }
        .badge-admin { background: #fee2e2; color: #991b1b; }
        .back-btn { background: white; color: #667eea; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-block; margin-bottom: 30px; }
        .back-btn:hover { background: #f8f9ff; }
    </style>
</head>
<body>
    <div class="container">
        <a href="javascript:history.back()" class="back-btn">← Back to Dashboard</a>
        
        <div class="header">
            <h1>🏥 MedConnect Features</h1>
            <p>Welcome, <?php echo htmlspecialchars($userName); ?>! Choose from available features below:</p>
        </div>
        
        <div class="feature-grid">
            
            <?php if ($userRole === 'patient'): ?>
                
                <a href="symptom_checker.php" class="feature-card">
                    <div class="feature-icon">🏥</div>
                    <div class="feature-title">Symptom Checker</div>
                    <div class="feature-desc">Describe your symptoms using voice or text. Get instant NLP analysis and automatic doctor matching.</div>
                    <span class="badge badge-patient">Patient</span>
                </a>
                
                <a href="appointment_booking.php" class="feature-card">
                    <div class="feature-icon">📅</div>
                    <div class="feature-title">Book Appointment</div>
                    <div class="feature-desc">Schedule appointments with your preferred doctor. Choose date, time, and consultation mode.</div>
                    <span class="badge badge-patient">Patient</span>
                </a>
                
                <a href="prescription_api.php?action=get_my_prescriptions" class="feature-card">
                    <div class="feature-icon">💊</div>
                    <div class="feature-title">My Prescriptions</div>
                    <div class="feature-desc">View, download, and manage all your prescriptions. Track medication orders and delivery.</div>
                    <span class="badge badge-patient">Patient</span>
                </a>
                
                <a href="payment_api.php?action=get_payment_history" class="feature-card">
                    <div class="feature-icon">💳</div>
                    <div class="feature-title">Payment History</div>
                    <div class="feature-desc">View all your transactions, payment receipts, and billing details.</div>
                    <span class="badge badge-patient">Patient</span>
                </a>
            
            <?php endif; ?>
            
            <?php if ($userRole === 'doctor'): ?>
                
                <a href="appointment_api.php?action=get_appointments" class="feature-card">
                    <div class="feature-icon">📅</div>
                    <div class="feature-title">My Appointments</div>
                    <div class="feature-desc">View and manage your scheduled appointments. Confirm, reschedule, or cancel consultations.</div>
                    <span class="badge badge-doctor">Doctor</span>
                </a>
                
                <a href="doctor_dashboard.php" class="feature-card">
                    <div class="feature-icon">👨‍⚕️</div>
                    <div class="feature-title">Doctor Dashboard</div>
                    <div class="feature-desc">Access consultation requests, patient history, and earnings. Manage your practice efficiently.</div>
                    <span class="badge badge-doctor">Doctor</span>
                </a>
            
            <?php endif; ?>
            
            <?php if ($userRole === 'pharmacy'): ?>
                
                <a href="pharmacy_dashboard.php" class="feature-card">
                    <div class="feature-icon">🏪</div>
                    <div class="feature-title">Pharmacy Dashboard</div>
                    <div class="feature-desc">Complete pharmacy management: prescription queue, order management, and earnings tracking.</div>
                    <span class="badge badge-pharmacy">Pharmacy</span>
                </a>
                
                <a href="pharmacy_api.php?action=get_pending_prescriptions" class="feature-card">
                    <div class="feature-icon">📋</div>
                    <div class="feature-title">Prescription Queue</div>
                    <div class="feature-desc">View and accept pending prescriptions routed to your pharmacy. Manage fulfillment efficiently.</div>
                    <span class="badge badge-pharmacy">Pharmacy</span>
                </a>
                
                <a href="pharmacy_api.php?action=get_earnings&period=month" class="feature-card">
                    <div class="feature-icon">💰</div>
                    <div class="feature-title">Earnings & Reports</div>
                    <div class="feature-desc">Track your pharmacy earnings, commission breakdowns, and payout history.</div>
                    <span class="badge badge-pharmacy">Pharmacy</span>
                </a>
            
            <?php endif; ?>
            
            <?php if ($userRole === 'admin'): ?>
                
                <a href="admin_dashboard.php" class="feature-card">
                    <div class="feature-icon">⚙️</div>
                    <div class="feature-title">Admin Dashboard</div>
                    <div class="feature-desc">Main admin dashboard with user management, approvals, and system overview.</div>
                    <span class="badge badge-admin">Admin</span>
                </a>
                
                <a href="admin_revenue.php" class="feature-card">
                    <div class="feature-icon">💰</div>
                    <div class="feature-title">Revenue Management</div>
                    <div class="feature-desc">Manage platform revenue, approve payouts, configure commission splits, and view financial reports.</div>
                    <span class="badge badge-admin">Admin</span>
                </a>
            
            <?php endif; ?>
            
            <!-- Common Features for All -->
            <a href="test_core_modules.php" class="feature-card">
                <div class="feature-icon">🧪</div>
                <div class="feature-title">Test Core Modules</div>
                <div class="feature-desc">Test all API endpoints and verify system functionality. Developer testing interface.</div>
                <span class="badge badge-<?php echo $userRole; ?>"><?php echo ucfirst($userRole); ?></span>
            </a>
            
        </div>
    </div>
</body>
</html>

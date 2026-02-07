<?php
session_start();
require_once 'db.php';

// Security & Authorization - Ensure user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit;
}

$doctor_id = $_SESSION['user_id'];
$doctor_name = $_SESSION['admin_name'] ?? 'Doctor';

// Get doctor profile
$doctorProfile = $conn->query("
    SELECT u.full_name, u.email, d.specialization, d.license_number, d.consultation_fee, d.bio, d.languages_spoken
    FROM users u
    LEFT JOIN doctor_profiles d ON u.id = d.user_id
    WHERE u.id = $doctor_id
")->fetch_assoc();

$doctor_name = $doctorProfile['full_name'] ?? 'Doctor';
$specialization = $doctorProfile['specialization'] ?? 'General Physician';

$current_view = $_GET['view'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard | MedConnect</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <style>
        :root {
            /* Unified Teal/Emerald Theme - Matching Home Page */
            --primary: #0d9488;
            --primary-dark: #0f766e;
            --primary-light: #5eead4;
            --primary-gradient: linear-gradient(135deg, #0d9488 0%, #2dd4bf 100%);
            
            --secondary: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --surface: #ffffff;
            --bg: #f3f4f6;
            --border: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --sidebar-w: 260px;
            --header-h: 64px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.01);
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }

        /* Sidebar */
        aside { width: var(--sidebar-w); background: var(--surface); border-right: 1px solid var(--border); display: flex; flex-direction: column; z-index: 50; flex-shrink: 0; transition: transform 0.3s ease; }
        .logo { height: var(--header-h); display: flex; align-items: center; padding: 0 1.5rem; gap: 0.75rem; color: var(--primary); font-weight: 700; font-size: 1.25rem; border-bottom: 1px solid var(--border); }
        .logo i { font-size: 1.75rem; color: var(--danger); }
        
        .nav-menu { flex: 1; padding: 1.5rem 1rem; overflow-y: auto; }
        .nav-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); padding: 0.5rem 0.75rem; margin-top: 1rem; letter-spacing: 0.05em; }
        .nav-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; color: var(--secondary); text-decoration: none; border-radius: var(--radius-md); transition: all 0.2s; font-weight: 500; font-size: 0.95rem; margin-bottom: 0.25rem; }
        .nav-item:hover, .nav-item.active { background: var(--primary-light); color: var(--primary-dark); }
        .nav-item i { font-size: 1.25rem; }
        .nav-item .badge { margin-left: auto; background: var(--primary); color: white; padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.7rem; }

        /* Main Content */
        .wrapper { flex: 1; display: flex; flex-direction: column; overflow: hidden; min-width: 0; position: relative; }
        
        /* Header */
        header { height: var(--header-h); background: var(--surface); border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; padding: 0 2rem; flex-shrink: 0; }
        
        .header-actions { display: flex; align-items: center; gap: 1.5rem; }
        .availability-toggle { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: var(--bg); border-radius: 999px; border: 1px solid var(--border); }
        .toggle-switch { position: relative; width: 48px; height: 24px; background: #ccc; border-radius: 999px; cursor: pointer; transition: 0.3s; }
        .toggle-switch.active { background: var(--success); }
        .toggle-switch::after { content: ''; position: absolute; width: 18px; height: 18px; background: white; border-radius: 50%; top: 3px; left: 3px; transition: 0.3s; }
        .toggle-switch.active::after { left: 27px; }
        
        .notification-wrapper { position: relative; }
        .icon-btn { position: relative; background: none; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer; padding: 0.25rem; }
        .icon-btn:hover { color: var(--text-main); }
        .icon-btn .dot { position: absolute; top: 0; right: 0; width: 8px; height: 8px; background: var(--danger); border-radius: 50%; border: 2px solid var(--surface); display:none; }
        
        .notification-panel { position: absolute; top: calc(100% + 0.5rem); right: 0; width: 360px; max-height: 480px; background: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); border: 1px solid var(--border); display: none; z-index: 100; }
        .notification-panel.show { display: block; }
        .notification-header { padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .notification-header span { font-weight: 600; font-size: 1rem; }
        .notification-header a { font-size: 0.75rem; color: var(--primary); text-decoration: none; cursor: pointer; }
        .notification-header a:hover { text-decoration: underline; }
        .notif-list { max-height: 400px; overflow-y: auto; }
        .notif-item { padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); cursor: pointer; transition: background 0.2s; }
        .notif-item:hover { background: #f8fafc; }
        .notif-item.unread { background: #eff6ff; }
        .notif-item:last-child { border-bottom: none; }
        .notif-title { font-weight: 600; font-size: 0.9rem; margin-bottom: 0.25rem; color: var(--text-main); }
        .notif-message { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem; }
        .notif-time { font-size: 0.75rem; color: var(--text-muted); }
        .notif-empty { padding: 3rem 1.5rem; text-align: center; color: var(--text-muted); }
        
        .notif-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
        .notif-icon-action { background: #fee2e2; color: #ef4444; }
        .notif-icon-info { background: #e0f2fe; color: #0ea5e9; }
        .notif-icon-review { background: #f3e8ff; color: #a855f7; }
        .notif-icon-default { background: #f1f5f9; color: #64748b; }
        
        .profile { display: flex; align-items: center; gap: 0.75rem; }
        .avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; border: 2px solid white; box-shadow: 0 0 0 1px var(--border); }

        /* Scrollable Content */
        main { flex: 1; padding: 2rem; overflow-y: auto; }
        .container { max-width: 1600px; margin: 0 auto; }

        .page-title { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 2rem; }
        .page-title h1 { font-size: 1.75rem; font-weight: 700; color: var(--text-main); line-height: 1; }
        .page-title p { color: var(--text-muted); margin-top: 0.5rem; font-size: 0.95rem; }

        /* Cards & Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: var(--surface); padding: 1.5rem; border-radius: var(--radius-lg); border: 1px solid var(--border); box-shadow: var(--shadow); transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .stat-value { font-size: 2rem; font-weight: 700; color: var(--text-main); line-height: 1.2; }
        .stat-label { color: var(--text-muted); font-size: 0.9rem; font-weight: 500; }

        .panel { background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); box-shadow: var(--shadow); overflow: hidden; display: flex; flex-direction: column; margin-bottom: 2rem; }
        .panel-header { padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; background: #fff; }
        .panel-title { font-size: 1.1rem; font-weight: 600; color: var(--text-main); }
        .panel-body { padding: 1.25rem 1.5rem; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 0.75rem 1rem; background: #f8fafc; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em; border-bottom: 1px solid var(--border); }
        td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); font-size: 0.9rem; color: var(--text-main); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; display: inline-block; text-transform: capitalize; }
        .bg-green { background: #dcfce7; color: #166534; }
        .bg-yellow { background: #fef3c7; color: #92400e; }
        .bg-red { background: #fee2e2; color: #991b1b; }
        .bg-blue { background: #e0f2fe; color: #075985; }
        .bg-orange { background: #ffedd5; color: #9a3412; }

        .urgency-routine { background: #dcfce7; color: #166534; }
        .urgency-priority { background: #fef3c7; color: #92400e; }
        .urgency-emergency { background: #fee2e2; color: #991b1b; }

        .btn { padding: 0.6rem 1rem; border-radius: 6px; border: none; font-size: 0.9rem; font-weight: 500; cursor: pointer; transition: 0.2s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-outline { background: transparent; color: var(--text-muted); border: 1px solid var(--border); }
        .btn-outline:hover { background: #f1f5f9; }
        .btn-sm { padding: 0.4rem 0.75rem; font-size: 0.85rem; }

        .action-btn { padding: 0.4rem; border: 1px solid var(--border); background: #fff; border-radius: 6px; cursor: pointer; color: var(--text-muted); transition: all 0.2s; margin-left: 0.25rem; }
        .action-btn:hover { border-color: var(--primary); color: var(--primary); }

        /* Modal */
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.4); z-index: 1000; display: none; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
        .modal { background: #fff; width: 90%; max-width: 800px; max-height: 90vh; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); overflow: hidden; animation: modalIn 0.2s ease; display: flex; flex-direction: column; }
        @keyframes modalIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        .modal-header { padding: 1.25rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .modal-title { font-weight: 600; font-size: 1.1rem; }
        .modal-body { padding: 1.5rem; overflow-y: auto; flex: 1; }
        .modal-footer { padding: 1.25rem; background: #f8fafc; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 0.75rem; }
        
        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; font-size: 0.85rem; font-weight: 500; margin-bottom: 0.4rem; color: var(--text-muted); }
        .form-input, .form-select, .form-textarea { width: 100%; padding: 0.6rem; border: 1px solid var(--border); border-radius: 6px; font-size: 0.9rem; font-family: inherit; }
        .form-textarea { resize: vertical; min-height: 100px; }

        .toggle-menu { display: none; font-size: 1.5rem; background: none; border: none; cursor: pointer; color: var(--text-main); margin-right: 1rem; }
        
        @media (max-width: 1024px) {
            aside { transform: translateX(-100%); position: absolute; height: 100%; top: 0; bottom: 0; box-shadow: none; }
            aside.open { transform: translateX(0); box-shadow: 0 0 50px rgba(0,0,0,0.5); }
            .toggle-menu { display: block !important; }
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <aside id="sidebar">
        <div class="logo">
            <i class="ph-fill ph-stethoscope"></i>
            <span>MedConnect</span>
        </div>
        
        <div class="nav-menu">
            <a href="?view=dashboard" class="nav-item <?php echo $current_view == 'dashboard' ? 'active' : ''; ?>">
                <i class="ph ph-squares-four"></i>
                <span>Dashboard</span>
            </a>

            <div class="nav-label">Clinical</div>
            
            <a href="?view=consultations" class="nav-item <?php echo $current_view == 'consultations' ? 'active' : ''; ?>">
                <i class="ph ph-clipboard-text"></i>
                <span>Consultations</span>
                <span class="badge" id="pendingBadge" style="display:none;">0</span>
            </a>
            <a href="?view=patients" class="nav-item <?php echo $current_view == 'patients' ? 'active' : ''; ?>">
                <i class="ph ph-users"></i>
                <span>My Patients</span>
            </a>
            <a href="?view=prescriptions" class="nav-item <?php echo $current_view == 'prescriptions' ? 'active' : ''; ?>">
                <i class="ph ph-prescription"></i>
                <span>Prescriptions</span>
            </a>

            <div class="nav-label">Performance</div>
            <a href="?view=reviews" class="nav-item <?php echo $current_view == 'reviews' ? 'active' : ''; ?>">
                <i class="ph ph-star"></i>
                <span>Reviews & Ratings</span>
            </a>
            <a href="?view=schedule" class="nav-item <?php echo $current_view == 'schedule' ? 'active' : ''; ?>">
                <i class="ph ph-calendar"></i>
                <span>Schedule</span>
            </a>
            <a href="?view=earnings" class="nav-item <?php echo $current_view == 'earnings' ? 'active' : ''; ?>">
                <i class="ph ph-currency-dollar"></i>
                <span>Earnings</span>
            </a>

            <a href="javascript:void(0)" onclick="logout()" class="nav-item" style="color: var(--danger); margin-top: 2rem;">
                <i class="ph ph-sign-out"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <div class="wrapper">
        <!-- Top Header -->
        <header>
            <div style="display: flex; align-items: center;">
                <button class="toggle-menu" onclick="toggleSidebar()">
                    <i class="ph ph-list"></i>
                </button>
                <div>
                    <div style="font-weight: 600; font-size: 1rem;"><?php echo htmlspecialchars($doctor_name); ?></div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($specialization); ?></div>
                </div>
            </div>
            
            <div class="header-actions">
                <div class="availability-toggle">
                    <span style="font-size: 0.85rem; color: var(--text-muted);">Availability:</span>
                    <div class="toggle-switch" id="availabilityToggle" onclick="toggleAvailability()"></div>
                    <span id="availabilityText" style="font-size: 0.85rem; font-weight: 600;">Offline</span>
                </div>
                
                <div class="notification-wrapper">
                    <button class="icon-btn" onclick="toggleNotifications()">
                        <i class="ph ph-bell"></i>
                        <span class="dot" id="notifDot"></span>
                    </button>
                    <div class="notification-panel" id="notifPanel">
                        <div class="notification-header">
                            <span>Notifications</span>
                            <a onclick="markAllRead()">Mark all read</a>
                        </div>
                        <div class="notif-list" id="notifList">
                            <div class="notif-empty">
                                <i class="ph ph-bell-slash" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p style="margin-top: 1rem;">No notifications</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="profile">
                    <div class="avatar"><?php echo strtoupper(substr($doctor_name, 0, 1)); ?></div>
                </div>
            </div>
        </header>

        <!-- Dynamic Content -->
        <main>
            <div class="container" id="mainContent">
                <!-- Content will be loaded here via JavaScript -->
            </div>
        </main>
    </div>

    <!-- Prescription Modal -->
    <div class="modal-overlay" id="prescriptionModal">
        <div class="modal">
            <div class="modal-header">
                <span class="modal-title">Create E-Prescription</span>
                <button onclick="closeModal('prescriptionModal')" style="background:none; border:none; cursor:pointer;"><i class="ph ph-x" style="font-size:1.2rem;"></i></button>
            </div>
            <div class="modal-body">
                <form id="prescriptionForm">
                    <input type="hidden" id="prescConsultationId">
                    <input type="hidden" id="prescPatientId">
                    
                    <div class="form-group">
                        <label class="form-label">ICD-10 Diagnosis Code (Optional)</label>
                        <input type="text" id="icdCode" class="form-input" placeholder="e.g., J00 (Acute nasopharyngitis)">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Diagnosis *</label>
                        <textarea id="diagnosis" class="form-textarea" required placeholder="Enter diagnosis..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Medicines</label>
                        <div id="medicinesList"></div>
                        <button type="button" class="btn btn-outline btn-sm" onclick="addMedicine()">+ Add Medicine</button>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Follow-up Date</label>
                        <input type="date" id="followUpDate" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Notes for Patient</label>
                        <textarea id="notesPatient" class="form-textarea" placeholder="Instructions for the patient..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Notes for Pharmacy</label>
                        <textarea id="notesPharmacy" class="form-textarea" placeholder="Special instructions for pharmacy..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('prescriptionModal')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="savePrescription()">Save & Send</button>
            </div>
        </div>
    </div>

    <!-- Patient History Modal -->
    <div class="modal-overlay" id="patientHistoryModal">
        <div class="modal" style="max-width: 900px;">
            <div class="modal-header">
                <span class="modal-title">Patient Medical Summary</span>
                <button onclick="closeModal('patientHistoryModal')" style="background:none; border:none; cursor:pointer;"><i class="ph ph-x" style="font-size:1.2rem;"></i></button>
            </div>
            <div class="modal-body" id="patientHistoryContent">
                <!-- Content loaded via JS -->
                <div style="text-align: center; padding: 2rem; color: var(--text-muted);">Loading patient data...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('patientHistoryModal')">Close</button>
            </div>
        </div>
    </div>

    <script src="doctor_dashboard_v3.js?v=<?php echo time(); ?>"></script>
    <script src="appointment_functions.js?v=<?php echo time(); ?>"></script>
    <!-- Availability Modal -->
    <div class="modal-overlay" id="availabilityModal">
        <div class="modal" style="max-width: 600px;">
            <div class="modal-header">
                <span class="modal-title">Set Weekly Availability</span>
                <button onclick="closeModal('availabilityModal')" style="background:none; border:none; cursor:pointer;"><i class="ph ph-x" style="font-size:1.2rem;"></i></button>
            </div>
            <div class="modal-body">
                <form id="availabilityForm">
                    <?php 
                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    foreach ($days as $day): 
                    ?>
                    <div style="display: grid; grid-template-columns: 120px 1fr 1fr 100px; gap: 1rem; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                        <strong><?php echo $day; ?></strong>
                        <input type="time" name="start_<?php echo strtolower($day); ?>" class="form-input" value="09:00">
                        <input type="time" name="end_<?php echo strtolower($day); ?>" class="form-input" value="17:00">
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem;">
                            <input type="checkbox" name="active_<?php echo strtolower($day); ?>" checked> Active
                        </label>
                    </div>
                    <?php endforeach; ?>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('availabilityModal')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveAvailability()">Save Schedule</button>
            </div>
        </div>
    </div>
</body>
</html>
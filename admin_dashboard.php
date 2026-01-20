<?php
session_start();
require_once 'db.php';

// --- Security & Authorization ---
// Ensure user is logged in and is an admin
// In production, use: if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }
$admin_name = $_SESSION['admin_name'] ?? 'System Admin';

// --- Helper Functions ---
function getCount($conn, $table, $where = "") {
    $sql = "SELECT COUNT(*) as count FROM $table";
    if ($where) $sql .= " WHERE $where";
    $result = $conn->query($sql);
    if ($result) {
        return $result->fetch_assoc()['count'];
    }
    return 0;
}

// --- Data Fetching ---
$current_view = $_GET['view'] ?? 'overview';

// Global Stats
$totalPatients = getCount($conn, "users", "role = 'patient' AND status != 'rejected'");
$verifiedDoctors = getCount($conn, "users", "role = 'doctor' AND status = 'approved'");
$pendingDoctors = getCount($conn, "users", "role = 'doctor' AND status = 'pending'");
$activePharmacies = getCount($conn, "users", "role = 'pharmacy' AND status = 'approved'");

$totalConsultations = 0; // Default
$checkConsultations = $conn->query("SHOW TABLES LIKE 'consultations'");
if ($checkConsultations && $checkConsultations->num_rows > 0) {
    $totalConsultations = getCount($conn, "consultations");
}

// Revenue Mock Calculation
$estRevenue = $totalConsultations * 30;

// Chart Data
$chartLabels = [];
$chartData = [];
if ($checkConsultations && $checkConsultations->num_rows > 0) {
    $chartQuery = $conn->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM consultations GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 7");
    if ($chartQuery) {
        while($row = $chartQuery->fetch_assoc()) {
            array_unshift($chartLabels, date('M d', strtotime($row['date'])));
            array_unshift($chartData, $row['count']);
        }
    }
}
if (empty($chartLabels)) {
    $chartLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $chartData = [0, 0, 0, 0, 0, 0, 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | MedConnect</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary: #0ea5e9;
            --primary-dark: #0284c7;
            --primary-light: #e0f2fe;
            --secondary: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --surface: #ffffff;
            --bg: #f1f5f9;
            --border: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --sidebar-w: 260px;
            --header-h: 64px;
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
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
        .search-wrapper { position: relative; width: 320px; }
        .search-wrapper input { width: 100%; padding: 0.6rem 1rem 0.6rem 2.6rem; border: 1px solid var(--border); border-radius: 99px; background: var(--bg); font-size: 0.9rem; transition: border 0.2s; }
        .search-wrapper input:focus { outline: none; border-color: var(--primary); background: #fff; }
        .search-wrapper i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
        
        /* Search Dropdown */
        .search-results { position: absolute; top: 110%; left: 0; right: 0; background: #fff; border: 1px solid var(--border); border-radius: var(--radius-md); box-shadow: var(--shadow-lg); max-height: 400px; overflow-y: auto; display: none; z-index: 100; }
        .search-result-item { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); cursor: pointer; display: flex; align-items: center; gap: 0.75rem; }
        .search-result-item:hover { background: var(--bg); }
        .search-result-item:last-child { border-bottom: none; }
        .s-icon { width: 32px; height: 32px; border-radius: 8px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .s-info div { font-size: 0.9rem; font-weight: 600; }
        .s-info span { font-size: 0.75rem; color: var(--text-muted); text-transform: capitalize; }

        /* Notification Bell */
        .header-actions { display: flex; align-items: center; gap: 1.5rem; }
        .notification-wrapper { position: relative; }
        .icon-btn { position: relative; background: none; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer; padding: 0.25rem; }
        .icon-btn:hover { color: var(--text-main); }
        .icon-btn .dot { position: absolute; top: 0; right: 0; width: 8px; height: 8px; background: var(--danger); border-radius: 50%; border: 2px solid var(--surface); display:none; }
        
        .notification-panel { position: absolute; top: 120%; right: 0; width: 320px; background: #fff; border: 1px solid var(--border); border-radius: var(--radius-md); box-shadow: var(--shadow-lg); display: none; z-index: 100; overflow: hidden; }
        .notification-header { padding: 1rem; border-bottom: 1px solid var(--border); font-weight: 600; font-size: 0.95rem; display: flex; justify-content: space-between; align-items: center; }
        .notif-list { max-height: 300px; overflow-y: auto; }
        .notif-item { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); display: flex; gap: 0.75rem; align-items: flex-start; cursor: pointer; transition: background 0.2s; }
        .notif-item:hover { background: var(--bg); }
        .notif-item:last-child { border-bottom: none; }
        .notif-icon { flex-shrink: 0; width: 8px; height: 8px; margin-top: 6px; border-radius: 50%; }
        .notif-high { background: var(--danger); }
        .notif-medium { background: var(--warning); }
        .notif-title { font-size: 0.9rem; font-weight: 500; margin-bottom: 0.25rem; }
        .notif-time { font-size: 0.75rem; color: var(--text-muted); }

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
        .stat-trend { display: flex; align-items: center; gap: 0.25rem; font-size: 0.85rem; padding: 0.25rem 0.5rem; border-radius: 6px; font-weight: 600; }
        .trend-up { background: #dcfce7; color: #166534; }
        .trend-down { background: #fee2e2; color: #991b1b; }

        /* Two columns for Charts/Tables */
        .grid-2 { display: grid; grid-template-columns: 1.6fr 1.4fr; gap: 1.5rem; margin-bottom: 2rem; }
        
        .panel { background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); box-shadow: var(--shadow); overflow: hidden; display: flex; flex-direction: column; }
        .panel-header { padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; background: #fff; }
        .panel-title { font-size: 1.1rem; font-weight: 600; color: var(--text-main); }
        .panel-body { padding: 1.25rem 1.5rem; }
        .panel-table { padding: 0; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 0.75rem 1rem; background: #f8fafc; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em; border-bottom: 1px solid var(--border); }
        td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); font-size: 0.9rem; color: var(--text-main); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        
        .actions-cell { display: flex; gap: 0.4rem; align-items: center; min-width: 100px; }
        
        .user-info { display: flex; align-items: center; gap: 0.75rem; }
        .u-avatar { width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.8rem; color: var(--secondary); }
        
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 99px; font-size: 0.75rem; font-weight: 600; display: inline-block; text-transform: capitalize; }
        .bg-green { background: #dcfce7; color: #166534; }
        .bg-yellow { background: #fef3c7; color: #92400e; }
        .bg-red { background: #fee2e2; color: #991b1b; }
        .bg-blue { background: #e0f2fe; color: #075985; }

        .action-btn { padding: 0.4rem; border: 1px solid var(--border); background: #fff; border-radius: 6px; cursor: pointer; color: var(--text-muted); transition: all 0.2s; margin-left: 0.25rem; }
        .action-btn:hover { border-color: var(--primary); color: var(--primary); }
        .btn-green:hover { border-color: var(--success); color: var(--success); background: #f0fdf4; }
        .btn-red:hover { border-color: var(--danger); color: var(--danger); background: #fef2f2; }

        /* Modal Styles */
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.4); z-index: 1000; display: none; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
        .modal { background: #fff; width: 90%; max-width: 500px; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); overflow: hidden; animation: modalIn 0.2s ease; }
        @keyframes modalIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        .modal-header { padding: 1.25rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .modal-title { font-weight: 600; font-size: 1.1rem; }
        .modal-body { padding: 1.5rem; }
        .modal-footer { padding: 1.25rem; background: #f8fafc; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 0.75rem; }
        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; font-size: 0.85rem; font-weight: 500; margin-bottom: 0.4rem; color: var(--text-muted); }
        .form-input { width: 100%; padding: 0.6rem; border: 1px solid var(--border); border-radius: 6px; font-size: 0.9rem; }
        .btn { padding: 0.6rem 1rem; border-radius: 6px; border: none; font-size: 0.9rem; font-weight: 500; cursor: pointer; transition: 0.2s; }
        .btn-ghost { background: transparent; color: var(--text-muted); border: 1px solid var(--border); }
        .btn-ghost:hover { background: #f1f5f9; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: #dc2626; }

        /* Responsive */
        @media (max-width: 1200px) {
            .grid-2 { grid-template-columns: 1fr; }
        }
        @media (max-width: 1024px) {
            aside { transform: translateX(-100%); position: absolute; height: 100%; top: 0; bottom: 0; box-shadow: none; }
            aside.open { transform: translateX(0); box-shadow: 0 0 50px rgba(0,0,0,0.5); }
            .toggle-menu { display: block !important; }
        }
        .toggle-menu { display: none; font-size: 1.5rem; background: none; border: none; cursor: pointer; color: var(--text-main); margin-right: 1rem; }
        .filters { display:flex; gap:0.5rem; flex-wrap:wrap; }
        .filter-select { padding: 0.4rem; border: 1px solid var(--border); border-radius: 6px; font-size: 0.85rem; color: var(--text-main); }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <aside id="sidebar">
        <div class="logo">
            <i class="ph-fill ph-first-aid-kit"></i>
            <span>MedConnect</span>
            <button onclick="toggleSidebar()" style="margin-left:auto; background:none; border:none; color:var(--text-muted); cursor:pointer; display:none;" id="close-sidebar">
                <i class="ph ph-x"></i>
            </button>
        </div>
        
        <div class="nav-menu">
            <a href="?view=overview" class="nav-item <?php echo $current_view == 'overview' ? 'active' : ''; ?>">
                <i class="ph ph-squares-four"></i>
                <span>Dashboard</span>
            </a>

            <div class="nav-label">Management</div>
            
            <a href="?view=doctors" class="nav-item <?php echo $current_view == 'doctors' ? 'active' : ''; ?>">
                <i class="ph ph-stethoscope"></i>
                <span>Doctors</span>
                <?php if($pendingDoctors > 0): ?>
                    <span class="badge"><?php echo $pendingDoctors; ?></span>
                <?php endif; ?>
            </a>
            <a href="?view=patients" class="nav-item <?php echo $current_view == 'patients' ? 'active' : ''; ?>">
                <i class="ph ph-users"></i>
                <span>Patients</span>
            </a>
            <a href="?view=partners" class="nav-item <?php echo $current_view == 'partners' ? 'active' : ''; ?>">
                <i class="ph ph-buildings"></i>
                <span>Clinics & Pharmacies</span>
            </a>

            <div class="nav-label">Clinical & Finance</div>
            <a href="?view=consultations" class="nav-item <?php echo $current_view == 'consultations' ? 'active' : ''; ?>">
                <i class="ph ph-clipboard-text"></i>
                <span>Consultations</span>
            </a>
            <a href="?view=finance" class="nav-item <?php echo $current_view == 'finance' ? 'active' : ''; ?>">
                <i class="ph ph-currency-dollar"></i>
                <span>Finance & Revenue</span>
            </a>
            <a href="admin_revenue.php" class="nav-item">
                <i class="ph ph-chart-line"></i>
                <span>Revenue Management</span>
            </a>

            <div class="nav-label">System</div>
            <a href="?view=settings" class="nav-item <?php echo $current_view == 'settings' ? 'active' : ''; ?>">
                <i class="ph ph-gear"></i>
                <span>Settings</span>
            </a>
            <a href="javascript:void(0)" onclick="logout()" class="nav-item" style="color: var(--danger);">
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
                <div class="search-wrapper">
                    <i class="ph ph-magnifying-glass"></i>
                    <input type="text" id="globalSearch" placeholder="Search patients, doctors, IDs..." autocomplete="off">
                    <div class="search-results" id="searchResults"></div>
                </div>
            </div>
            
            <div class="header-actions">
                <div class="notification-wrapper">
                    <button class="icon-btn" onclick="toggleNotifications()">
                        <i class="ph ph-bell"></i>
                        <span class="dot" id="notifDot"></span>
                    </button>
                    <div class="notification-panel" id="notifPanel">
                        <div class="notification-header">
                            <span>Notifications</span>
                            <a href="javascript:void(0)" onclick="markAllRead()" style="font-size:0.75rem; color:var(--primary);">Mark all read</a>
                        </div>
                        <div class="notif-list" id="notifList">
                            <div style="padding:1rem; text-align:center; color:var(--text-muted); font-size:0.85rem;">No new notifications</div>
                        </div>
                    </div>
                </div>
                
                <div class="profile">
                    <div style="text-align: right; display:none; @media(min-width: 768px){display:block;}">
                        <div style="font-size: 0.9rem; font-weight: 600;"><?php echo htmlspecialchars($admin_name); ?></div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Admin</div>
                    </div>
                    <div class="avatar">A</div>
                </div>
            </div>
        </header>

        <!-- Dynamic Content -->
        <main>
            <div class="container">
                
                <?php if ($current_view == 'overview'): ?>
                    <!-- OVERVIEW DASHBOARD -->
                    <div class="page-title">
                        <div>
                            <h1>Dashboard Overview</h1>
                            <p>Real-time insights into MedConnect performance.</p>
                        </div>
                        <div style="display:flex; gap: 0.5rem;">
                            <button onclick="exportDashboardData()" style="padding: 0.5rem 1rem; background: #fff; border: 1px solid var(--border); border-radius: 8px; cursor: pointer; color: var(--text-muted);">
                                <i class="ph ph-download-simple"></i> Export
                            </button>
                        </div>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-icon" style="background: var(--primary-light); color: var(--primary);"><i class="ph ph-users-three"></i></div>
                                <div class="stat-trend trend-up"><i class="ph ph-trend-up"></i> +12%</div>
                            </div>
                            <div class="stat-value"><?php echo number_format($totalPatients); ?></div>
                            <div class="stat-label">Total Patients</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-icon" style="background: #dcfce7; color: #166534;"><i class="ph ph-stethoscope"></i></div>
                                <div class="stat-trend trend-up"><i class="ph ph-trend-up"></i> +5%</div>
                            </div>
                            <div class="stat-value"><?php echo number_format($verifiedDoctors + $pendingDoctors); ?></div>
                            <div class="stat-label">Doctors (<?php echo $pendingDoctors?> Pending)</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-icon" style="background: #f3e8ff; color: #7e22ce;"><i class="ph ph-clipboard-text"></i></div>
                                <div class="stat-trend trend-up"><i class="ph ph-trend-up"></i> +8%</div>
                            </div>
                            <div class="stat-value"><?php echo number_format($totalConsultations); ?></div>
                            <div class="stat-label">Total Consultations</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-icon" style="background: #fff7ed; color: #c2410c;"><i class="ph ph-currency-dollar"></i></div>
                                <div class="stat-trend trend-up"><i class="ph ph-trend-up"></i> +15%</div>
                            </div>
                            <div class="stat-value">$<?php echo number_format($estRevenue); ?></div>
                            <div class="stat-label">Estimated Revenue</div>
                        </div>
                    </div>

                    <div class="grid-2">
                        <!-- Chart Area -->
                        <div class="panel">
                            <div class="panel-header">
                                <span class="panel-title">Consultation Traffic</span>
                            </div>
                            <div class="panel-body" style="height: 300px; position:relative;">
                                <canvas id="trafficChart"></canvas>
                            </div>
                        </div>

                        <!-- Pending Approvals -->
                        <div class="panel panel-table">
                            <div class="panel-header">
                                <span class="panel-title">Pending Approvals</span>
                                <a href="?view=pending" style="font-size: 0.85rem; color: var(--primary); text-decoration: none;">View All</a>
                            </div>
                            <div style="overflow-x: auto;">
                            <?php 
                            $pendingApps = $conn->query("SELECT * FROM users WHERE status = 'pending' ORDER BY created_at ASC LIMIT 5");
                            if($pendingApps && $pendingApps->num_rows > 0): 
                            ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Applicant</th>
                                        <th>Role</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $pendingApps->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <div class="u-avatar"><?php echo strtoupper(substr($row['full_name'] ?? 'U', 0, 1)); ?></div>
                                                <div>
                                                    <div style="font-weight: 600; font-size: 0.85rem;"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="status-badge bg-yellow"><?php echo ucfirst($row['role']); ?></span></td>
                                        <td class="actions-cell">
                                            <button class="action-btn btn-green" onclick="handleAction(<?php echo $row['id']; ?>, 'approved')" title="Approve"><i class="ph-bold ph-check"></i></button>
                                            <button class="action-btn btn-red" onclick="handleAction(<?php echo $row['id']; ?>, 'rejected')" title="Reject"><i class="ph-bold ph-x"></i></button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div style="padding: 2rem; text-align: center; color: var(--text-muted);">
                                <i class="ph ph-check-circle" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                                <p>All caught up!</p>
                            </div>
                            <?php endif; ?>
                            </div>
                        </div>
                    </div>

                <?php elseif ($current_view == 'doctors' || $current_view == 'patients' || $current_view == 'partners' || $current_view == 'pending'): ?>
                    <!-- USER LISTS VIEW -->
                    <?php
                        $roleFilter = "role = 'doctor'";
                        $pageTitle = "Doctor Management";
                        if ($current_view == 'patients'){ $roleFilter = "role = 'patient'"; $pageTitle = "Patient Registry"; }
                        if ($current_view == 'partners'){ $roleFilter = "(role = 'pharmacy' OR role = 'clinic' OR role = 'hospital')"; $pageTitle = "Health Partners"; }
                        if ($current_view == 'pending'){ $roleFilter = "status = 'pending'"; $pageTitle = "Pending Approvals"; }
                        
                        $users = $conn->query("SELECT * FROM users WHERE ($roleFilter) AND status != 'rejected' ORDER BY created_at DESC");
                    ?>
                    <div class="page-title">
                        <h1><?php echo $pageTitle; ?></h1>
                    </div>
                    
                    <div class="panel panel-table">
                        <div class="panel-header">
                            <span class="panel-title">All Records (<?php echo $users->num_rows; ?>)</span>
                            <div class="filters">
                                <select id="statusFilter" class="filter-select" onchange="filterTable()">
                                    <option value="">All Status</option>
                                    <option value="approved">Approved</option>
                                    <option value="pending">Pending</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                                <input type="text" id="textFilter" placeholder="Filter List..." onkeyup="filterTable()" style="padding: 0.4rem; border: 1px solid var(--border); border-radius: 6px; font-size: 0.85rem;">
                            </div>
                        </div>
                        <div style="overflow-x: auto;">
                            <table id="dataTable">
                                <thead>
                                    <tr>
                                        <th>Name / Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Phone</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($u = $users->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <div class="u-avatar"><?php echo strtoupper(substr($u['full_name'] ?? 'U', 0, 1)); ?></div>
                                                <div>
                                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($u['full_name']); ?></div>
                                                    <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($u['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo ucfirst($u['role']); ?></td>
                                        <td>
                                            <?php 
                                            $st = $u['status'];
                                            $cls = ($st == 'approved') ? 'bg-green' : (($st == 'pending') ? 'bg-yellow' : 'bg-red');
                                            ?>
                                            <span class="status-badge <?php echo $cls; ?>"><?php echo ucfirst($st); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($u['phone'] ?? 'N/A'); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                        <td class="actions-cell">
                                            <?php if($st == 'pending'): ?>
                                                <button class="action-btn btn-green" onclick="handleAction(<?php echo $u['id']; ?>, 'approved')" title="Approve"><i class="ph-bold ph-check"></i></button>
                                                <button class="action-btn btn-red" onclick="handleAction(<?php echo $u['id']; ?>, 'rejected')" title="Reject"><i class="ph-bold ph-x"></i></button>
                                            <?php else: ?>
                                                <!-- Action Buttons with data attributes -->
                                                <button class="action-btn" onclick="openEditUser(this)" 
                                                    data-id="<?php echo $u['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($u['full_name']); ?>"
                                                    data-role="<?php echo $u['role']; ?>"
                                                    data-status="<?php echo $u['status']; ?>"
                                                    title="Edit User">
                                                    <i class="ph ph-pencil-simple"></i>
                                                </button>
                                                <button class="action-btn" style="color: var(--danger);" 
                                                    onclick="confirmDeleteUser(<?php echo $u['id']; ?>, '<?php echo $u['full_name']; ?>')"
                                                    title="Delete/Deactivate">
                                                    <i class="ph ph-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php elseif ($current_view == 'consultations'): ?>
                    <!-- CONSULTATIONS VIEW -->
                    <div class="page-title">
                        <h1>Consultations Oversight</h1>
                    </div>
                    <?php 
                    // Attempt to fetch consultations if table exists
                    if ($checkConsultations && $checkConsultations->num_rows > 0) {
                        $cons = $conn->query("
                            SELECT c.*, u.full_name as patient_name 
                            FROM consultations c 
                            JOIN users u ON c.patient_id = u.id 
                            ORDER BY c.created_at DESC
                        ");
                    } else {
                        $cons = false;
                    }
                    ?>
                    <div class="panel panel-table">
                        <div class="panel-header">
                            <span class="panel-title">Recent Consultations</span>
                            <div class="filters">
                                <select id="severityFilter" class="filter-select" onchange="filterTable()">
                                    <option value="">All Severity</option>
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                                <select id="statusFilter" class="filter-select" onchange="filterTable()">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div style="overflow-x: auto;">
                        <?php if ($cons && $cons->num_rows > 0): ?>
                        <table id="dataTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient</th>
                                    <th>Symptoms</th>
                                    <th>Method</th>
                                    <th>Severity</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($c = $cons->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $c['id']; ?></td>
                                    <td><?php echo htmlspecialchars($c['patient_name']); ?></td>
                                    <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php echo htmlspecialchars($c['symptoms']); ?>
                                    </td>
                                    <td>
                                        <?php if($c['input_method'] == 'voice'): ?>
                                            <i class="ph-fill ph-microphone"></i> Voice
                                        <?php else: ?>
                                            <i class="ph-fill ph-text-t"></i> Text
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $sev = $c['severity'];
                                        $color = ($sev == 'high') ? '#ef4444' : (($sev == 'medium') ? '#f59e0b' : '#10b981');
                                        echo "<span style='color:$color; font-weight:600;'>".ucfirst($sev)."</span>";
                                        ?>
                                    </td>
                                    <td><span class="status-badge bg-blue"><?php echo ucfirst($c['status']); ?></span></td>
                                    <td><?php echo date('M d, H:i', strtotime($c['created_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div style="padding: 2rem; text-align: center;">No consultations found.</div>
                        <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($current_view == 'finance'): ?>
                    <!-- FINANCE VIEW (MOCK) -->
                    <div class="page-title">
                        <h1>Financial Performance</h1>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-label">Total Revenue</div>
                            <div class="stat-value">$<?php echo number_format($estRevenue); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Platform Commission (10%)</div>
                            <div class="stat-value">$<?php echo number_format($estRevenue * 0.1); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Pending Payouts</div>
                            <div class="stat-value">$0.00</div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel-body">
                            <h3 style="margin-bottom: 1rem;">Revenue Stream</h3>
                            <div style="height: 300px; display: flex; align-items: center; justify-content: center; background: #f8fafc; border-radius: 8px; color: var(--text-muted);">
                                Chart data requires active transaction history.
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- SETTINGS / DEFAULT -->
                    <div class="page-title"><h1>System Settings</h1></div>
                    <div class="panel">
                        <div class="panel-body">
                            <p>Global platform configurations, notification settings, and audit logs will appear here.</p>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <!-- Edit User Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <div class="modal-header">
                <span class="modal-title">Edit User Details</span>
                <button onclick="closeModal('editModal')" style="background:none; border:none; cursor:pointer;"><i class="ph ph-x" style="font-size:1.2rem;"></i></button>
            </div>
            <div class="modal-body">
                <form id="editForm" onsubmit="saveUserChanges(event)">
                    <input type="hidden" id="editUserId">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" id="editFullName" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select id="editRole" class="form-input">
                            <option value="patient">Patient</option>
                            <option value="doctor">Doctor</option>
                            <option value="clinic">Clinic</option>
                            <option value="pharmacy">Pharmacy</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select id="editStatus" class="form-input">
                            <option value="approved">Approved</option>
                            <option value="pending">Pending</option>
                            <option value="rejected">Rejected / Suspended</option>
                        </select>
                    </div>
                    <div style="margin-top:1rem; font-size:0.85rem; color:var(--text-muted); background:#f8fafc; padding:0.75rem; border-radius:6px;">
                        <i class="ph-fill ph-info"></i> Edits are logged for audit purposes. Changing role may affect user permissions immediately.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('editModal')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('editForm').dispatchEvent(new Event('submit'))">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Scripting -->
    <script>
        // Sidebar Toggle
        const sidebar = document.getElementById('sidebar');
        const closeBtn = document.getElementById('close-sidebar');
        function logout() {
            sessionStorage.removeItem('currentUser');
            window.location.href = 'logout.php';
        }

        function markAllRead() {
            const formData = new FormData();
            formData.append('action', 'mark_all_read');
            fetch('admin_actions.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    document.getElementById('notifList').innerHTML = '<div style="padding:1rem; text-align:center; color:var(--text-muted); font-size:0.85rem;">No new notifications</div>';
                    document.getElementById('notifDot').style.display = 'none';
                }
            });
        }

        function toggleSidebar() {
            sidebar.classList.toggle('open');
            if(sidebar.classList.contains('open')) {
                closeBtn.style.display = 'block';
            } else {
                closeBtn.style.display = 'none';
            }
        }

        // Global Search with Autocomplete
        const searchInput = document.getElementById('globalSearch');
        const searchResults = document.getElementById('searchResults');
        let searchTimeout = null;

        if(searchInput) {
            searchInput.addEventListener('keyup', function(e) {
                const term = e.target.value;
                if(term.length < 2) {
                    searchResults.style.display = 'none';
                    return;
                }

                // Debounce
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const formData = new FormData();
                    formData.append('action', 'search');
                    formData.append('query', term);

                    fetch('admin_actions.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if(data.status === 'success' && data.data.length > 0) {
                            let html = '';
                            data.data.forEach(item => {
                                let link = '?view=overview';
                                if (item.category === 'user') {
                                    if (['pharmacy', 'clinic', 'hospital'].includes(item.type)) link = '?view=partners';
                                    else if (item.type === 'doctor') link = '?view=doctors';
                                    else if (item.type === 'patient') link = '?view=patients';
                                } else if (item.category === 'consultation') {
                                    link = '?view=consultations';
                                }

                                html += `
                                    <div class="search-result-item" onclick="window.location.href='${link}'">
                                        <div class="s-icon"><i class="ph-bold ph-magnifying-glass"></i></div>
                                        <div class="s-info">
                                            <div>${item.title}</div>
                                            <span>${item.category} • ${item.type}</span>
                                        </div>
                                    </div>
                                `;
                            });
                            searchResults.innerHTML = html;
                            searchResults.style.display = 'block';
                        } else {
                            searchResults.innerHTML = '<div style="padding:1rem; text-align:center; color:gray;">No results found</div>';
                            searchResults.style.display = 'block';
                        }
                    });
                }, 300);
            });

            // Close search when clicking outside
            document.addEventListener('click', function(e) {
                if(!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.style.display = 'none';
                }
            });
        }

        // Notifications
        function toggleNotifications() {
            const panel = document.getElementById('notifPanel');
            const list = document.getElementById('notifList');
            const dot = document.getElementById('notifDot');
            
            if(panel.style.display === 'block') {
                panel.style.display = 'none';
            } else {
                // Fetch notifications
                const formData = new FormData();
                formData.append('action', 'get_notifications');

                fetch('admin_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success' && data.data.length > 0) {
                        let html = '';
                        data.data.forEach(n => {
                            html += `
                                <div class="notif-item" onclick="window.location.href='${n.link}'">
                                    <div class="notif-icon notif-${n.priority}"></div>
                                    <div>
                                        <div class="notif-title">${n.title}</div>
                                        <div class="notif-time">${n.time}</div>
                                    </div>
                                </div>
                            `;
                        });
                        list.innerHTML = html;
                    } else {
                        list.innerHTML = '<div style="padding:1rem; text-align:center; color:gray;">No new notifications</div>';
                    }
                    panel.style.display = 'block';
                    dot.style.display = 'none'; // Mark read
                });
            }
        }

        // Check for notifications on load
        window.addEventListener('load', () => {
            const formData = new FormData();
            formData.append('action', 'get_notifications');
            fetch('admin_actions.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success' && data.data.length > 0) {
                    document.getElementById('notifDot').style.display = 'block';
                }
            });
        });

        // Filter Table Functionality (Supports multiple filters)
        function filterTable() {
            const table = document.getElementById('dataTable');
            if(!table) return;

            const textFilter = document.getElementById('textFilter')?.value.toLowerCase() || '';
            const statusFilter = document.getElementById('statusFilter')?.value.toLowerCase() || '';
            const severityFilter = document.getElementById('severityFilter')?.value.toLowerCase() || '';

            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                let show = true;

                // 1. Text Filter (All columns)
                if (textFilter && !row.innerText.toLowerCase().includes(textFilter)) {
                    show = false;
                }

                // 2. Status Filter
                if (show && statusFilter) {
                    const isConsultation = !!document.getElementById('severityFilter');
                    const statusCol = isConsultation ? 5 : 2;
                    const cellValue = row.cells[statusCol]?.innerText.toLowerCase() || '';
                    if (!cellValue.includes(statusFilter)) {
                        show = false;
                    }
                }

                // 3. Severity Filter (Consultations only)
                if (show && severityFilter) {
                    const cellValue = row.cells[4]?.innerText.toLowerCase() || '';
                    if (!cellValue.includes(severityFilter)) {
                        show = false;
                    }
                }
                
                row.style.display = show ? '' : 'none';
            });
        }

        // Action Handler
        function handleAction(userId, status) {
            if(!confirm(`Are you sure you want to set status to ${status}?`)) return;
            postAction('update_status', { user_id: userId, status: status });
        }

        function postAction(action, data) {
            const formData = new FormData();
            formData.append('action', action);
            for (const key in data) {
                formData.append(key, data[key]);
            }

            fetch('admin_actions.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(response => {
                if(response.status === 'success') {
                    alert(response.message || 'Success');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            })
            .catch(err => alert('System error occurred.'));
        }

        // Export Data
        function exportDashboardData() {
            const date = new Date().toISOString().split('T')[0];
            const filename = `medconnect_report_${date}.csv`;
            
            const data = [
                ['Metric', 'Value'],
                ['Total Patients', '<?php echo $totalPatients; ?>'],
                ['Docs (Approved)', '<?php echo $verifiedDoctors; ?>'],
                ['Docs (Pending)', '<?php echo $pendingDoctors; ?>'],
                ['Total Consultations', '<?php echo $totalConsultations; ?>'],
                ['Est. Revenue', '<?php echo $estRevenue; ?>']
            ];

            let csvContent = "data:text/csv;charset=utf-8," 
                + data.map(e => e.join(",")).join("\n");

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Modal Logic
        function openEditUser(btn) {
            document.getElementById('editUserId').value = btn.dataset.id;
            document.getElementById('editFullName').value = btn.dataset.name;
            document.getElementById('editRole').value = btn.dataset.role;
            document.getElementById('editStatus').value = btn.dataset.status;
            
            const modal = document.getElementById('editModal');
            modal.style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function saveUserChanges(e) {
            e.preventDefault();
            const id = document.getElementById('editUserId').value;
            const name = document.getElementById('editFullName').value;
            const role = document.getElementById('editRole').value;
            const status = document.getElementById('editStatus').value;

            postAction('update_user', {
                user_id: id,
                full_name: name,
                role: role,
                status: status
            });
        }

        function confirmDeleteUser(id, name) {
            if(confirm(`WARNING: You are about to deactivate user: ${name}.\n\nThis will prevent them from accessing the system but will NOT delete historical data to preserve audit trails.\n\nContinue?`)) {
                postAction('delete_user', { user_id: id });
            }
        }

        // Close modal on click outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = "none";
            }
        }

        // Initialize Chart only on Dashboard
        <?php if($current_view == 'overview'): ?>
        const ctx = document.getElementById('trafficChart');
        if(ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chartLabels); ?>,
                    datasets: [{
                        label: 'New Consultations',
                        data: <?php echo json_encode($chartData); ?>,
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>

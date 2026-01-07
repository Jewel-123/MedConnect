<?php
session_start();
require_once 'db.php';

// Authentication check - Simple version for demonstration
// In a real app, you should check for a valid session and admin role
$admin_name = $_SESSION['admin_name'] ?? 'System Admin';

// Fetch Live Statistics
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role != 'admin'")->fetch_assoc()['count'];
$pendingApplications = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")->fetch_assoc()['count'];
$approvedDoctors = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'doctor' AND status = 'approved'")->fetch_assoc()['count'];
$clinicsPharmacies = $conn->query("SELECT COUNT(*) as count FROM users WHERE (role = 'clinic' OR role = 'pharmacy') AND status = 'approved'")->fetch_assoc()['count'];

// Fetch Pending Applications
$pendingQuery = $conn->query("
    SELECT u.id, u.full_name, u.email, u.role, u.created_at, u.phone
    FROM users u 
    WHERE u.status = 'pending' 
    ORDER BY u.created_at DESC
");

// Fetch Recent Users (excluding Admin)
$usersQuery = $conn->query("
    SELECT id, full_name, email, role, status, created_at 
    FROM users 
    WHERE role != 'admin' 
    ORDER BY created_at DESC 
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedConnect | Admin Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root {
            --primary: #0284c7;
            --primary-light: #f0f9ff;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --bg-body: #f8fafc;
            --sidebar-width: 260px;
            --topbar-height: 70px;
            --radius: 12px;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background-color: var(--bg-body); color: var(--text-main); display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar { width: var(--sidebar-width); background: #fff; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 100; transition: transform 0.3s ease; }
        .sidebar-header { padding: 1.5rem; display: flex; align-items: center; gap: 0.75rem; color: var(--primary); }
        .sidebar-header i { font-size: 2rem; }
        .sidebar-header span { font-size: 1.25rem; font-weight: 700; letter-spacing: -0.5px; }
        
        .nav-list { list-style: none; padding: 1rem; flex: 1; }
        .nav-item { margin-bottom: 0.5rem; }
        .nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; color: var(--text-muted); text-decoration: none; border-radius: var(--radius); transition: all 0.2s; font-weight: 500; }
        .nav-link:hover, .nav-link.active { background: var(--primary-light); color: var(--primary); }
        .nav-link i { font-size: 1.25rem; }

        /* Main Content */
        .main-content { margin-left: var(--sidebar-width); flex: 1; display: flex; flex-direction: column; width: calc(100% - var(--sidebar-width)); }
        
        /* Top Bar */
        .topbar { height: var(--topbar-height); background: #fff; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; padding: 0 2rem; position: sticky; top: 0; z-index: 90; }
        .search-box { position: relative; width: 300px; }
        .search-box input { width: 100%; padding: 0.5rem 1rem 0.5rem 2.5rem; border: 1px solid #e2e8f0; border-radius: 20px; font-family: inherit; font-size: 0.9rem; }
        .search-box i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); }

        .admin-profile { display: flex; align-items: center; gap: 1rem; }
        .admin-info { text-align: right; }
        .admin-name { font-weight: 600; font-size: 0.95rem; display: block; }
        .admin-role { font-size: 0.8rem; color: var(--text-muted); }
        .avatar { width: 40px; height: 40px; border-radius: 50%; background: #e0f2fe; display: flex; align-items: center; justify-content: center; color: var(--primary); font-weight: 700; border: 2px solid #fff; box-shadow: 0 0 0 1px #e2e8f0; }

        /* Content Area */
        .content { padding: 2rem; max-width: 1400px; margin: 0 auto; width: 100%; }
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem; }
        .page-header p { color: var(--text-muted); font-size: 0.95rem; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: #fff; padding: 1.5rem; border-radius: var(--radius); border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 1.25rem; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow); }
        .stat-icon { width: 54px; height: 54px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .count-info h3 { font-size: 1.5rem; font-weight: 700; line-height: 1.2; }
        .count-info p { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; }

        .icon-blue { background: #e0f2fe; color: #0284c7; }
        .icon-amber { background: #fef3c7; color: #d97706; }
        .icon-green { background: #dcfce7; color: #16a34a; }
        .icon-purple { background: #f3e8ff; color: #9333ea; }

        /* Tables & Lists */
        .section-card { background: #fff; border-radius: var(--radius); border: 1px solid #e2e8f0; margin-bottom: 2rem; overflow: hidden; }
        .section-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; }
        .section-header h2 { font-size: 1.1rem; font-weight: 600; }
        
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 1rem 1.5rem; background: #f8fafc; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); }
        td { padding: 1rem 1.5rem; border-top: 1px solid #e2e8f0; font-size: 0.9rem; vertical-align: middle; }
        
        .user-cell { display: flex; align-items: center; gap: 0.75rem; }
        .user-avatar { width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.75rem; color: var(--text-muted); }
        
        .badge { padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: capitalize; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #dcfce7; color: #166534; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .badge-onboarding { background: #e0f2fe; color: #075985; }

        .btn-action { padding: 0.5rem; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; }
        .btn-approve:hover { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
        .btn-reject:hover { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
        
        .empty-state { padding: 4rem 2rem; text-align: center; color: var(--text-muted); }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; }

        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; width: 100%; }
            .topbar { padding: 0 1rem; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="ph-fill ph-heartbeat"></i>
            <span>MedConnect</span>
        </div>
        <ul class="nav-list">
            <li class="nav-item">
                <a href="#" class="nav-link active">
                    <i class="ph ph-squares-four"></i>
                    <span>Overview</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="ph ph-users"></i>
                    <span>Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="ph ph-clipboard-text"></i>
                    <span>Applications</span>
                </a>
            </li>
            <li class="nav-item" style="margin-top: auto;">
                <a href="logout.php" class="nav-link" style="color: var(--danger);">
                    <i class="ph ph-sign-out"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <header class="topbar">
            <div class="search-box">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" placeholder="Search patients, doctors...">
            </div>
            <div class="admin-profile">
                <div class="admin-info">
                    <span class="admin-name"><?php echo htmlspecialchars($admin_name); ?></span>
                    <span class="admin-role">Administrator</span>
                </div>
                <div class="avatar">AD</div>
            </div>
        </header>

        <!-- Content -->
        <div class="content">
            <div class="page-header">
                <h1>Platform Overview</h1>
                <p>Welcome back! Here's what's happening with MedConnect today.</p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon icon-blue">
                        <i class="ph ph-users-three"></i>
                    </div>
                    <div class="count-info">
                        <h3><?php echo number_format($totalUsers); ?></h3>
                        <p>Total Community</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-amber">
                        <i class="ph ph-hourglass-medium"></i>
                    </div>
                    <div class="count-info">
                        <h3><?php echo number_format($pendingApplications); ?></h3>
                        <p>Pending Review</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-green">
                        <i class="ph ph-stethoscope"></i>
                    </div>
                    <div class="count-info">
                        <h3><?php echo number_format($approvedDoctors); ?></h3>
                        <p>Active Doctors</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-purple">
                        <i class="ph ph-hospital"></i>
                    </div>
                    <div class="count-info">
                        <h3><?php echo number_format($clinicsPharmacies); ?></h3>
                        <p>Health Partners</p>
                    </div>
                </div>
            </div>

            <!-- Applications Area -->
            <div class="section-card">
                <div class="section-header">
                    <h2>Pending Applications</h2>
                    <span class="badge badge-pending" style="border-radius: 6px;">
                        <?php echo $pendingQuery->num_rows; ?> Required Action
                    </span>
                </div>
                <div class="table-responsive">
                    <?php if ($pendingQuery->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Role</th>
                                    <th>Contact</th>
                                    <th>Applied Date</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($app = $pendingQuery->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <div class="user-avatar"><?php echo strtoupper(substr($app['full_name'], 0, 1)); ?></div>
                                                <div>
                                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($app['full_name']); ?></div>
                                                    <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($app['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge" style="background: #f1f5f9; color: #475569;"><?php echo ucfirst($app['role']); ?></span></td>
                                        <td><?php echo htmlspecialchars($app['phone'] ?: 'N/A'); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($app['created_at'])); ?></td>
                                        <td style="text-align: right;">
                                            <button class="btn-action btn-approve" onclick="updateStatus(<?php echo $app['id']; ?>, 'approved')" title="Approve">
                                                <i class="ph-bold ph-check"></i>
                                            </button>
                                            <button class="btn-action btn-reject" onclick="updateStatus(<?php echo $app['id']; ?>, 'rejected')" title="Reject">
                                                <i class="ph-bold ph-x"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="ph ph-folder-open"></i>
                            <p>No pending applications at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="section-card">
                <div class="section-header">
                    <h2>Recent Activations</h2>
                    <a href="#" style="font-size: 0.85rem; color: var(--primary); text-decoration: none; font-weight: 600;">View All</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Joined Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($user = $usersQuery->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></div>
                                            <div>
                                                <div style="font-weight: 500;"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($user['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo ucfirst($user['role'] ?: 'Unassigned'); ?></td>
                                    <td>
                                        <?php 
                                            $s = $user['status'];
                                            $class = ($s == 'approved') ? 'badge-approved' : (($s == 'pending') ? 'badge-pending' : (($s == 'pending_onboarding') ? 'badge-onboarding' : 'badge-rejected'));
                                            $label = str_replace('_', ' ', $s);
                                        ?>
                                        <span class="badge <?php echo $class; ?>"><?php echo $label; ?></span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        function updateStatus(userId, status) {
            const actionText = status === 'approved' ? 'approve' : 'reject';
            if (!confirm(`Are you sure you want to ${actionText} this application?`)) return;

            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('user_id', userId);
            formData.append('status', status);

            fetch('admin_actions.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Modern notification or just reload
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => console.error('Error:', err));
        }
    </script>
</body>
</html>

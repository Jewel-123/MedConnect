<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacy') {
    header('Location: login.php');
    exit;
}

$pharmacyId = $_SESSION['user_id'];

// Get pharmacy profile
$profile = $conn->query("
    SELECT pp.*, u.full_name, u.email
    FROM pharmacy_profiles pp
    JOIN users u ON pp.user_id = u.id
    WHERE pp.user_id = $pharmacyId
")->fetch_assoc();

if (!$profile) {
    echo "Please complete your pharmacy profile first.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profile['pharmacy_name']); ?> - Pharmacy Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        
        :root {
            /* Unified Teal/Emerald Theme - Matching Home Page */
            --primary: #0d9488;
            --primary-dark: #0f766e;
            --primary-light: #5eead4;
            --secondary: #2dd4bf;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --dark: #1e293b;
            --light: #f8fafc;
            --border: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.01);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #ccfbf1 0%, #a5f3fc 100%);
            min-height: 100vh;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 1.5rem 2rem;
            box-shadow: var(--shadow-lg);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .notification-bell {
            position: relative;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s;
        }
        
        .notification-bell:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 700;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Navigation */
        .nav {
            background: white;
            box-shadow: var(--shadow);
            position: sticky;
            top: 80px;
            z-index: 99;
        }
        
        .nav-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            gap: 0;
            overflow-x: auto;
        }
        
        .nav-item {
            padding: 1rem 1.5rem;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            color: #64748b;
            font-weight: 600;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav-item:hover {
            color: var(--primary);
            background: #fef3c7;
        }
        
        .nav-item.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background: #fef3c7;
        }
        
        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary);
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-card.success { border-left-color: var(--success); }
        .stat-card.danger { border-left-color: var(--danger); }
        .stat-card.info { border-left-color: var(--info); }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-icon.primary { background: #fef3c7; color: var(--primary); }
        .stat-icon.success { background: #d1fae5; color: var(--success); }
        .stat-icon.danger { background: #fee2e2; color: var(--danger); }
        .stat-icon.info { background: #dbeafe; color: var(--info); }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0.5rem 0;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .stat-change {
            font-size: 0.75rem;
            margin-top: 0.5rem;
        }
        
        .stat-change.positive { color: var(--success); }
        .stat-change.negative { color: var(--danger); }
        
        /* Content Sections */
        .content-section {
            display: none;
        }
        
        .content-section.active {
            display: block;
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Cards */
        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border);
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Prescription Card */
        .prescription-card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            margin-bottom: 1rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--success);
            transition: all 0.3s;
        }
        
        .prescription-card:hover {
            transform: translateX(4px);
            box-shadow: var(--shadow-lg);
        }
        
        .prescription-card.urgent {
            border-left-color: var(--danger);
            background: linear-gradient(to right, #fee2e2 0%, white 10%);
        }
        
        .prescription-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .patient-info h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }
        
        .patient-info p {
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-preparing { background: #dbeafe; color: #1e40af; }
        .badge-ready { background: #d1fae5; color: #065f46; }
        .badge-delivered { background: #e9d5ff; color: #5b21b6; }
        .badge-urgent { background: #fee2e2; color: #991b1b; }
        
        .prescription-items {
            background: var(--light);
            padding: 1rem;
            border-radius: 12px;
            margin: 1rem 0;
        }
        
        .item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
        }
        
        .item:last-child { border-bottom: none; }
        
        .item-name {
            font-weight: 600;
            color: var(--dark);
        }
        
        .item-dosage {
            color: #64748b;
            font-size: 0.875rem;
        }
        
        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
            color: white;
        }
        
        .btn-secondary {
            background: var(--light);
            color: #64748b;
        }
        
        .actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
            animation: fadeIn 0.3s;
        }
        
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            animation: slideUp 0.3s;
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal h3 {
            margin-bottom: 1.5rem;
            color: var(--dark);
            font-size: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #475569;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 0.875rem;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }
        
        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            display: none;
            align-items: center;
            gap: 1rem;
            z-index: 2000;
            animation: slideInRight 0.3s;
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
        
        .toast.show {
            display: flex;
        }
        
        .toast.success { border-left: 4px solid var(--success); }
        .toast.error { border-left: 4px solid var(--danger); }
        .toast.info { border-left: 4px solid var(--info); }
        
        /* Loading Spinner */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header h1 { font-size: 1.25rem; }
            .stats-grid { grid-template-columns: 1fr; }
            .prescription-header { flex-direction: column; gap: 1rem; }
            .actions { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <h1>
                <i class="fas fa-pills"></i>
                <?php echo htmlspecialchars($profile['pharmacy_name']); ?>
            </h1>
            <div class="header-actions">
                <div class="notification-bell" onclick="toggleNotifications()">
                    <i class="fas fa-bell" style="font-size: 1.5rem;"></i>
                    <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                </div>
                <a href="logout.php" class="btn btn-secondary" style="text-decoration: none;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <div class="nav">
        <div class="nav-content">
            <div class="nav-item active" onclick="showSection('dashboard')">
                <i class="fas fa-home"></i> Dashboard
            </div>
            <div class="nav-item" onclick="showSection('pending')">
                <i class="fas fa-file-prescription"></i> Pending Prescriptions
            </div>
            <div class="nav-item" onclick="showSection('orders')">
                <i class="fas fa-box"></i> Active Orders
            </div>
            <div class="nav-item" onclick="showSection('history')">
                <i class="fas fa-history"></i> History
            </div>
            <div class="nav-item" onclick="showSection('analytics')">
                <i class="fas fa-chart-line"></i> Analytics
            </div>
            <div class="nav-item" onclick="showSection('notifications')">
                <i class="fas fa-bell"></i> Notifications
            </div>
        </div>
    </div>
    
    <!-- Container -->
    <div class="container">
        <!-- Dashboard Section -->
        <div id="dashboard" class="content-section active">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-file-prescription"></i>
                    </div>
                    <div class="stat-value" id="pendingCount">-</div>
                    <div class="stat-label">Pending Prescriptions</div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-icon success">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-value" id="activeCount">-</div>
                    <div class="stat-label">Active Orders</div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-icon info">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="stat-value" id="monthEarnings">₹0</div>
                    <div class="stat-label">This Month Earnings</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-value" id="fulfillmentRate">0%</div>
                    <div class="stat-label">Fulfillment Rate</div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-clock"></i>
                        Recent Activity
                    </h2>
                </div>
                <div id="recentActivity"></div>
            </div>
        </div>
        
        <!-- Pending Prescriptions Section -->
        <div id="pending" class="content-section">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-file-prescription"></i>
                        Pending Prescriptions
                    </h2>
                </div>
                <div id="pendingList"></div>
            </div>
        </div>
        
        <!-- Orders Section -->
        <div id="orders" class="content-section">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-box"></i>
                        Active Orders
                    </h2>
                </div>
                <div id="ordersList"></div>
            </div>
        </div>
        
        <!-- History Section -->
        <div id="history" class="content-section">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-history"></i>
                        Prescription History
                    </h2>
                </div>
                <div id="historyList"></div>
            </div>
        </div>
        
        <!-- Analytics Section -->
        <div id="analytics" class="content-section">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-chart-line"></i>
                        Analytics & Reports
                    </h2>
                </div>
                <div id="analyticsData"></div>
            </div>
        </div>
        
        <!-- Notifications Section -->
        <div id="notifications" class="content-section">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-bell"></i>
                        Notifications
                    </h2>
                    <button class="btn btn-secondary" onclick="markAllRead()">
                        <i class="fas fa-check-double"></i> Mark All Read
                    </button>
                </div>
                <div id="notificationsList"></div>
            </div>
        </div>
    </div>
    
    <!-- Accept Prescription Modal -->
    <div id="acceptModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-check-circle"></i> Accept Prescription</h3>
            <div class="form-group">
                <label>Total Amount (₹)</label>
                <input type="number" id="totalAmount" placeholder="Enter total amount" required>
            </div>
            <div class="form-group">
                <label>Delivery Available?</label>
                <select id="deliveryAvailable">
                    <option value="true">Yes - Home Delivery</option>
                    <option value="false">No - Pickup Only</option>
                </select>
            </div>
            <div class="actions">
                <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button class="btn btn-success" onclick="confirmAccept()">
                    <i class="fas fa-check"></i> Accept & Create Order
                </button>
            </div>
        </div>
    </div>
    
    <!-- Reject Prescription Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-times-circle"></i> Reject Prescription</h3>
            <div class="form-group">
                <label>Rejection Reason</label>
                <textarea id="rejectionReason" rows="4" placeholder="Enter reason for rejection..." required></textarea>
            </div>
            <div class="actions">
                <button class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                <button class="btn btn-danger" onclick="confirmReject()">
                    <i class="fas fa-times"></i> Reject Prescription
                </button>
            </div>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-sync"></i> Update Order Status</h3>
            <div class="form-group">
                <label>New Status</label>
                <select id="newStatus">
                    <option value="preparing">Preparing</option>
                    <option value="ready">Ready for Pickup/Delivery</option>
                    <option value="out_for_delivery">Out for Delivery</option>
                    <option value="delivered">Delivered</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <input type="text" id="statusNotes" placeholder="Any additional notes...">
            </div>
            <div class="actions">
                <button class="btn btn-secondary" onclick="closeStatusModal()">Cancel</button>
                <button class="btn btn-primary" onclick="confirmStatusUpdate()">
                    <i class="fas fa-check"></i> Update Status
                </button>
            </div>
        </div>
    </div>
    
    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toastMessage"></span>
    </div>

    <script src="pharmacy_dashboard_enhanced.js"></script>
</body>
</html>
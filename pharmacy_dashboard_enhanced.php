<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>City Pharmacy - Pharmacy Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/custom_modal.css?v=<?php echo time(); ?>">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            /* Homepage Palette: Modern Emerald/Teal */
            --primary: #0d9488;
            --primary-dark: #0f766e;
            --primary-light: #5eead4;
            --primary-gradient: linear-gradient(135deg, #0d9488 0%, #2dd4bf 100%);
            
            --new-color: #f68338;
            --process-color: #317fdb;
            --complete-color: #10b981;
            --alert-color: #f43f5e; /* Matching homepage accent */
            
            --bg-color: #f3f4f6; /* Matching homepage bg-body */
            --border-color: #e5e7eb;
            --text-dark: #111827;
            --text-muted: #4b5563;
        }
        
        /* Custom Modal Style Overrides if any */
        .modal-overlay { z-index: 9999 !important; }
        
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(at 0% 0%, rgba(45, 212, 191, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(244, 63, 94, 0.05) 0px, transparent 50%);
            background-attachment: fixed;
            color: var(--text-dark);
            line-height: 1.6;
        }
        
        /* Layout */
        .dashboard-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }

        /* Header */
        .top-header {
            background: var(--primary-gradient);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 12px 12px 0 0;
            box-shadow: 0 4px 15px rgba(13, 148, 136, 0.2);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-logo {
            font-size: 1.25rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #eee;
            border: 2px solid rgba(255,255,255,0.3);
        }

        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 20px 0;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .stat-icon-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            flex-shrink: 0;
        }

        .stat-card.new .stat-icon-circle { background-color: var(--new-color); }
        .stat-card.process .stat-icon-circle { background-color: var(--process-color); }
        .stat-card.complete .stat-icon-circle { background-color: var(--complete-color); }
        .stat-card.alert .stat-icon-circle { background-color: var(--alert-color); }

        .stat-info {
            display: flex;
            flex-direction: column;
        }

        .stat-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        .stat-number {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .stat-card::after {
            content: '';
            position: absolute;
            right: -10px;
            bottom: -10px;
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 4rem;
            opacity: 0.05;
        }
        .stat-card.new::after { content: '\f484'; }
        .stat-card.process::after { content: '\f46d'; }
        .stat-card.complete::after { content: '\f058'; }
        .stat-card.alert::after { content: '\f071'; }

        /* Main Section Card */
        .main-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .card-header-title {
            padding: 20px 30px;
            border-bottom: 1px solid var(--border-color);
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8fafc;
            text-align: left;
            padding: 12px 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            border-bottom: 2px solid var(--border-color);
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.9rem;
        }

        .badge-status {
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: capitalize;
            color: white;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .priority-urgent { color: var(--alert-color); font-weight: 700; }
        .priority-normal { color: var(--text-muted); }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.7rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn-view { background: var(--process-color); color: white; }
        .btn-verify { background: var(--primary-gradient); color: white; box-shadow: 0 4px 10px rgba(13, 148, 136, 0.2); }
        .btn-dispense { background: #6366f1; color: white; }
        .btn-dismiss { background: var(--alert-color); color: white; }
        .btn-complete { background: var(--complete-color); color: white; }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
            filter: brightness(1.1);
        }

        /* Prescription Form Section */
        .form-header {
            background: var(--primary-gradient);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 12px 12px 0 0;
            box-shadow: 0 4px 15px rgba(13, 148, 136, 0.15);
        }

        .patient-details-row {
            padding: 20px 25px;
            display: flex;
            gap: 40px;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.95rem;
        }

        .detail-item span { color: var(--text-muted); margin-right: 5px; }
        .detail-item strong { color: var(--primary-dark); }

        .form-section-label {
            padding: 15px 25px 5px;
            color: var(--primary-dark);
            font-weight: 700;
            font-size: 0.9rem;
        }

        .diagnosis-box {
            padding: 10px 25px;
        }

        .diagnosis-content {
            width: 100%;
            padding: 15px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background: #fcfcfc;
            font-size: 1rem;
            color: var(--text-dark);
        }

        .med-input-group {
            display: flex;
            padding: 15px 25px;
            gap: 15px;
            align-items: center;
        }

        .med-select {
            flex: 2;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
        }

        .med-dosage, .med-freq, .med-dur {
            flex: 1;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
        }

        .form-footer {
            padding: 20px 25px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-top: 1px solid var(--border-color);
        }

        .btn-draft { background: white; color: var(--primary); border: 1px solid var(--primary); padding: 10px 25px; border-radius: 6px; font-weight: 700; cursor: pointer; }
        .btn-send { background: #0d9488; color: white; border: none; padding: 10px 25px; border-radius: 6px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; }

        @media (max-width: 900px) {
            .stats-row { grid-template-columns: repeat(2, 1fr); }
            .patient-details-row { flex-wrap: wrap; gap: 15px; }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <header class="top-header">
            <div class="header-left">
                <div class="header-logo">
                    <i class="fas fa-plus-circle"></i> MedConnect . Pharmacy Dashboard
                </div>
            </div>
            <div class="header-actions">
                <i class="far fa-bell"></i>
                <i class="far fa-comment-dots"></i>
                <div class="user-profile">
                    <img src="https://ui-avatars.com/api/?name=City+Pharmacy&background=0D8ABC&color=fff" class="user-avatar" alt="Avatar">
                    <a href="logout.php" style="color: white; text-decoration: none; font-size: 0.8rem;"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
        </header>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card new">
                <div class="stat-icon-circle">
                    <i class="fas fa-file-prescription"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">New Prescriptions</span>
                    <span class="stat-number" id="newPrescriptionsCount">0</span>
                </div>
            </div>
            <div class="stat-card process">
                <div class="stat-icon-circle">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">In Process</span>
                    <span class="stat-number" id="inProcessCount">0</span>
                </div>
            </div>
            <div class="stat-card complete">
                <div class="stat-icon-circle">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Completed Today</span>
                    <span class="stat-number" id="completedTodayCount">0</span>
                </div>
            </div>
            <div class="stat-card alert">
                <div class="stat-icon-circle">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Low Stock Alerts</span>
                    <span class="stat-number" id="lowStockCount">0</span>
                </div>
            </div>
        </div>

        <!-- Prescription Queue Table -->
        <div class="main-card">
            <div class="card-header-title">Prescription Queue</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Prescription ID</th>
                            <th>Patient Name</th>
                            <th>Doctor Name</th>
                            <th>Date & Time</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="prescriptionQueueBody">
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px;">
                                <i class="fas fa-spinner fa-spin"></i> Loading queue...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- History Section -->
        <div class="main-card">
            <div class="card-header-title" style="background: #f8fafc; color: var(--text-muted);">
                <i class="fas fa-history"></i> Prescription History (Completed)
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient Name</th>
                            <th>Doctor</th>
                            <th>Completed At</th>
                            <th>Total (₹)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="historyQueueBody">
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 20px;">
                                No completed records yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Medicine Inventory Section -->
        <div class="main-card">
            <div class="card-header-title" style="background: linear-gradient(135deg, #0d9488 0%, #2dd4bf 100%); color: white; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-pills"></i> Medicine Inventory
                </div>
                <div style="display:flex; gap:10px; align-items:center;">
                    <input type="text" id="medicineSearchInput" placeholder="Search medicines..." style="padding: 8px 12px; border: 1px solid rgba(255,255,255,0.3); border-radius: 6px; width: 200px; background: rgba(255,255,255,0.9);">
                    <select id="categoryFilter" style="padding: 8px 12px; border: 1px solid rgba(255,255,255,0.3); border-radius: 6px; background: rgba(255,255,255,0.9);">
                        <option value="">All Categories</option>
                    </select>
                    <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                        <input type="checkbox" id="lowStockFilter" style="cursor: pointer;">
                        <span style="font-size: 0.9rem;">Low Stock Only</span>
                    </label>
                    <button onclick="openAddMedicineModal()" style="padding: 8px 16px; background: #f68338; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 5px;">
                        <i class="fas fa-plus"></i> Add New Medicine
                    </button>
                </div>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Medicine Name</th>
                            <th>Generic Name</th>
                            <th>Category</th>
                            <th>Price (₹)</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="medicineInventoryBody">
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px;">
                                <i class="fas fa-spinner fa-spin"></i> Loading inventory...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- E-Prescription Form Row -->
        <div class="main-card">
            <div class="form-header">
                <div class="header-logo"><i class="fas fa-file-signature"></i> E-Prescription Form</div>
                <div class="header-actions">
                    <i class="far fa-bell"></i>
                    <i class="far fa-comment-dots"></i>
                    <img src="https://ui-avatars.com/api/?name=City+Pharmacy&background=0D8ABC&color=fff" class="user-avatar" alt="Avatar">
                </div>
            </div>
            
            <div id="eprescription-details">
                <div class="patient-details-row">
                    <div class="detail-item"><span>Patient:</span> <strong id="form-patient-name">Select from Queue</strong></div>
                    <div class="detail-item"><span>Age:</span> <strong id="form-patient-age">--</strong></div>
                    <div class="detail-item"><span>Gender:</span> <strong id="form-patient-gender">--</strong></div>
                    <div class="detail-item"><span>Patient ID:</span> <strong id="form-patient-id">#-----</strong></div>
                </div>

                <div class="form-section-label">Diagnosis</div>
                <div class="diagnosis-box">
                    <div class="diagnosis-content" id="form-diagnosis">--</div>
                </div>

                <div class="form-section-label">Medications</div>
                <div id="medications-list">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Medicine Name</th>
                                    <th>Dosage</th>
                                    <th>Frequency</th>
                                    <th>Duration</th>
                                    <th style="text-align:right;">Price</th>
                                    <th style="text-align:center;">Qty</th>
                                    <th style="text-align:right;">Total</th>
                                </tr>
                            </thead>
                            <tbody id="form-medications-body">
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 20px;">
                                        Please select a prescription from the queue to view details.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Add New Medicine Modal -->
    <div id="addMedicineModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; overflow-y: auto; padding: 20px;">
        <div style="background:white; padding:30px; border-radius:12px; max-width:600px; width:100%; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom:20px;">
                <h3 style="margin:0;">Add New Medicine</h3>
                <button onclick="closeAddMedicineModal()" style="background:none; border:none; font-size:24px; cursor:pointer; color:#666;">&times;</button>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Medicine Name <span style="color:red;">*</span></label>
                    <input type="text" id="newMedicineName" placeholder="e.g., Paracetamol 500mg" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                </div>
                
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Generic Name</label>
                    <input type="text" id="newMedicineGeneric" placeholder="e.g., Acetaminophen" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Category</label>
                        <input type="text" id="newMedicineCategory" placeholder="e.g., Analgesic" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                    </div>
                    
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Unit</label>
                        <select id="newMedicineUnit" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                            <option value="tablet">Tablet</option>
                            <option value="capsule">Capsule</option>
                            <option value="syrup">Syrup</option>
                            <option value="injection">Injection</option>
                            <option value="cream">Cream</option>
                            <option value="drops">Drops</option>
                            <option value="sachet">Sachet</option>
                            <option value="bottle">Bottle</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Price (₹) <span style="color:red;">*</span></label>
                        <input type="number" id="newMedicinePrice" min="0" step="0.01" placeholder="0.00" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                    </div>
                    
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Initial Stock <span style="color:red;">*</span></label>
                        <input type="number" id="newMedicineStock" min="0" placeholder="0" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                    </div>
                </div>
                
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Low Stock Threshold</label>
                    <input type="number" id="newMedicineLowStock" min="0" value="10" placeholder="10" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                </div>
                
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Manufacturer</label>
                    <input type="text" id="newMedicineManufacturer" placeholder="e.g., Pfizer" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                </div>
                
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Description</label>
                    <textarea id="newMedicineDescription" rows="3" placeholder="Additional information about the medicine..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; resize: vertical;"></textarea>
                </div>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top: 20px;">
                <button onclick="closeAddMedicineModal()" style="padding:10px 20px; border:none; background:#eee; border-radius:6px; cursor:pointer; font-weight:600;">Cancel</button>
                <button onclick="submitNewMedicine()" style="padding:10px 20px; border:none; background:#0d9488; color:white; border-radius:6px; cursor:pointer; font-weight:600;">Add Medicine</button>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- Accept Modal -->
    <div id="acceptModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:white; padding:30px; border-radius:12px; width:400px;">
            <h3 style="margin-bottom:20px;">Accept Prescription</h3>
            <div style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Total Amount (₹)</label>
                <input type="number" id="modalTotalAmount" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:5px;">Delivery Available?</label>
                <select id="modalDelivery" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                    <option value="true">Yes - Home Delivery</option>
                    <option value="false">No - Pickup Only</option>
                </select>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button onclick="document.getElementById('acceptModal').style.display='none'" style="padding:8px 15px; border:none; background:#eee; border-radius:4px; cursor:pointer;">Cancel</button>
                <button onclick="confirmAccept()" style="padding:8px 15px; border:none; background:#10b981; color:white; border-radius:4px; cursor:pointer;">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Status Modal -->
    <div id="statusModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:white; padding:30px; border-radius:12px; width:400px;">
            <h3 style="margin-bottom:20px;">Update Status</h3>
            <div style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">New Status</label>
                <select id="newStatus" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                    <option value="preparing">Preparing</option>
                    <option value="ready">Ready for Pickup/Delivery</option>
                    <option value="out_for_delivery">Out for Delivery</option>
                    <option value="delivered">Delivered</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:5px;">Notes</label>
                <input type="text" id="statusNotes" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;" placeholder="Optional notes...">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button onclick="document.getElementById('statusModal').style.display='none'" style="padding:8px 15px; border:none; background:#eee; border-radius:4px; cursor:pointer;">Cancel</button>
                <button onclick="confirmStatusUpdate()" style="padding:8px 15px; border:none; background:#317fdb; color:white; border-radius:4px; cursor:pointer;">Update</button>
            </div>
        </div>
    </div>

    <script src="pharmacy_dashboard_enhanced.js"></script>
    <script src="assets/js/custom_modal.js"></script>
</body>
</html>

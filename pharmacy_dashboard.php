<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacy') {
    header('Location: login.php');
    exit;
}

// Redirect to enhanced pharmacy dashboard
header('Location: pharmacy_dashboard_enhanced.php');
exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profile['pharmacy_name']); ?> - Pharmacy Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f1f5f9; }
        .header { background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); color: white; padding: 20px 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header h1 { font-size: 24px; }
        .header p { opacity: 0.9; margin-top: 5px; font-size: 14px; }
        .nav { background: white; padding: 0 40px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; gap: 30px; }
        .nav-item { padding: 15px 0; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.3s; color: #64748b; font-weight: 500; }
        .nav-item:hover { color: #0d9488; }
        .nav-item.active { color: #0d9488; border-bottom-color: #0d9488; }
        .container { padding: 30px 40px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #0d9488; }
        .stat-value { font-size: 32px; font-weight: 700; color: #1e293b; margin: 10px 0; }
        .stat-label { color: #64748b; font-size: 14px; }
        .content-section { display: none; }
        .content-section.active { display: block; }
        .prescription-card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #10b981; }
        .prescription-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; }
        .patient-info { flex: 1; }
        .patient-name { font-size: 18px; font-weight: 600; color: #1e293b; }
        .doctor-name { color: #64748b; font-size: 14px; margin-top: 5px; }
        .prescription-items { background: #f8fafc; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
        .item:last-child { border-bottom: none; }
        .item-name { font-weight: 500; color: #1e293b; }
        .item-dosage { color: #64748b; font-size: 14px; }
        .actions { display: flex; gap: 10px; margin-top: 15px; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .btn-accept { background: #10b981; color: white; }
        .btn-accept:hover { background: #059669; }
        .btn-status { background: #3b82f6; color: white; }
        .btn-status:hover { background: #2563eb; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%; }
        .modal h3 { margin-bottom: 20px; color: #1e293b; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #475569; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #f59e0b; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-preparing { background: #dbeafe; color: #1e40af; }
        .badge-ready { background: #d1fae5; color: #065f46; }
        .badge-delivered { background: #e9d5ff; color: #5b21b6; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏪 <?php echo htmlspecialchars($profile['pharmacy_name']); ?></h1>
        <p><?php echo htmlspecialchars($profile['full_name']); ?> • <?php echo htmlspecialchars($profile['email']); ?></p>
    </div>
    
    <div class="nav">
        <div class="nav-item active" onclick="showSection('dashboard')">Dashboard</div>
        <div class="nav-item" onclick="showSection('pending')">Pending Prescriptions</div>
        <div class="nav-item" onclick="showSection('orders')">My Orders</div>
        <div class="nav-item" onclick="showSection('earnings')">Earnings</div>
    </div>
    
    <div class="container">
        <!-- Dashboard Section -->
        <div id="dashboard" class="content-section active">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Pending Prescriptions</div>
                    <div class="stat-value" id="pendingCount">-</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Active Orders</div>
                    <div class="stat-value" id="activeCount">-</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">This Month Earnings</div>
                    <div class="stat-value" id="monthEarnings">₹0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-value" id="totalOrders">-</div>
                </div>
            </div>
        </div>
        
        <!-- Pending Prescriptions Section -->
        <div id="pending" class="content-section">
            <h2 style="margin-bottom: 20px; color: #1e293b;">📋 Pending Prescriptions</h2>
            <div id="pendingList"></div>
        </div>
        
        <!-- Orders Section -->
        <div id="orders" class="content-section">
            <h2 style="margin-bottom: 20px; color: #1e293b;">📦 My Orders</h2>
            <div id="ordersList"></div>
        </div>
        
        <!-- Earnings Section -->
        <div id="earnings" class="content-section">
            <h2 style="margin-bottom: 20px; color: #1e293b;">💰 Earnings Overview</h2>
            <div id="earningsData"></div>
        </div>
    </div>
    
    <!-- Accept Prescription Modal -->
    <div id="acceptModal" class="modal">
        <div class="modal-content">
            <h3>Accept Prescription</h3>
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
                <button class="btn" onclick="closeModal()" style="background: #e2e8f0; color: #475569;">Cancel</button>
                <button class="btn btn-accept" onclick="confirmAccept()">Accept & Create Order</button>
            </div>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <h3>Update Order Status</h3>
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
                <button class="btn" onclick="closeStatusModal()" style="background: #e2e8f0; color: #475569;">Cancel</button>
                <button class="btn btn-status" onclick="confirmStatusUpdate()">Update Status</button>
            </div>
        </div>
    </div>

    <script>
        let currentPrescriptionId = null;
        let currentOrderId = null;

        // Load dashboard stats
        async function loadDashboard() {
            // Pending prescriptions
            const pendingRes = await fetch('pharmacy_api.php?action=get_pending_prescriptions');
            const pending = await pendingRes.json();
            document.getElementById('pendingCount').textContent = pending.prescriptions?.length || 0;
            
            // Active orders
            const ordersRes = await fetch('pharmacy_api.php?action=get_orders&status=all');
            const orders = await ordersRes.json();
            const activeOrders = orders.orders?.filter(o => !['completed', 'cancelled'].includes(o.order_status)) || [];
            document.getElementById('activeCount').textContent = activeOrders.length;
            document.getElementById('totalOrders').textContent = orders.orders?.length || 0;
            
            // Earnings
            const earningsRes = await fetch('pharmacy_api.php?action=get_earnings&period=month');
            const earnings = await earningsRes.json();
            document.getElementById('monthEarnings').textContent = '₹' + (earnings.earnings?.total_net || 0).toFixed(2);
        }

        // Load pending prescriptions
        async function loadPendingPrescriptions() {
            const response = await fetch('pharmacy_api.php?action=get_pending_prescriptions');
            const data = await response.json();
            
            const container = document.getElementById('pendingList');
            
            if (!data.prescriptions || data.prescriptions.length === 0) {
                container.innerHTML = '<p style="color: #64748b;">No pending prescriptions</p>';
                return;
            }
            
            container.innerHTML = data.prescriptions.map(rx => `
                <div class="prescription-card">
                    <div class="prescription-header">
                        <div class="patient-info">
                            <div class="patient-name">${rx.patient_name}</div>
                            <div class="doctor-name">Prescribed by Dr. ${rx.doctor_name} (${rx.specialization})</div>
                            <div style="margin-top: 10px; color: #64748b; font-size: 14px;">
                                📅 ${new Date(rx.sent_at).toLocaleString()}
                            </div>
                        </div>
                        <span class="badge badge-pending">Pending</span>
                    </div>
                    
                    <div style="background: #fffbeb; padding: 12px; border-radius: 8px; margin: 10px 0;">
                        <strong>Diagnosis:</strong> ${rx.diagnosis}
                    </div>
                    
                    <div class="prescription-items">
                        <strong style="display: block; margin-bottom: 10px; color: #1e293b;">Medications:</strong>
                        ${rx.items.map(item => `
                            <div class="item">
                                <div>
                                    <div class="item-name">${item.medicine_name}</div>
                                    <div class="item-dosage">${item.dosage} • ${item.frequency} • ${item.duration}</div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    
                    <div class="actions">
                        <button class="btn btn-accept" onclick="openAcceptModal(${rx.id})">
                            ✓ Accept & Create Order
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // Load orders
        async function loadOrders() {
            const response = await fetch('pharmacy_api.php?action=get_orders&status=all');
            const data = await response.json();
            
            const container = document.getElementById('ordersList');
            
            if (!data.orders || data.orders.length === 0) {
                container.innerHTML = '<p style="color: #64748b;">No orders yet</p>';
                return;
            }
            
            container.innerHTML = data.orders.map(order => {
                const statusClass = {
                    'preparing': 'preparing',
                    'ready': 'ready',
                    'out_for_delivery': 'ready',
                    'delivered': 'delivered',
                    'completed': 'delivered'
                }[order.order_status] || 'pending';
                
                return `
                <div class="prescription-card">
                    <div class="prescription-header">
                        <div class="patient-info">
                            <div class="patient-name">Order #${order.order_number}</div>
                            <div class="doctor-name">Patient: ${order.patient_name}</div>
                            <div style="margin-top: 5px; color: #10b981; font-weight: 600;">₹${order.total_amount}</div>
                        </div>
                        <span class="badge badge-${statusClass}">${order.order_status.replace('_', ' ').toUpperCase()}</span>
                    </div>
                    
                    <div style="color: #64748b; font-size: 14px; margin: 10px 0;">
                        Fulfillment: ${order.fulfillment_type === 'delivery' ? '🚚 Home Delivery' : '🏪 Pickup'}
                    </div>
                    
                    ${!['completed', 'delivered'].includes(order.order_status) ? `
                    <div class="actions">
                        <button class="btn btn-status" onclick="openStatusModal(${order.id})">
                            Update Status
                        </button>
                    </div>
                    ` : ''}
                </div>
            `}).join('');
        }

        // Load earnings
        async function loadEarnings() {
            const response = await fetch('pharmacy_api.php?action=get_earnings&period=all');
            const data = await response.json();
            
            const container = document.getElementById('earningsData');
            const e = data.earnings;
            
            container.innerHTML = `
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Gross Earnings</div>
                        <div class="stat-value">₹${(e.total_gross || 0).toFixed(2)}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Platform Commission</div>
                        <div class="stat-value" style="color: #ef4444;">-₹${(e.total_commission || 0).toFixed(2)}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Net Earnings</div>
                        <div class="stat-value" style="color: #10b981;">₹${(e.total_net || 0).toFixed(2)}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Total Orders</div>
                        <div class="stat-value">${e.order_count || 0}</div>
                    </div>
                </div>
            `;
        }

        function showSection(section) {
            // Update nav
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            event.currentTarget.classList.add('active');
            
            // Show section
            document.querySelectorAll('.content-section').forEach(sec => sec.classList.remove('active'));
            document.getElementById(section).classList.add('active');
            
            // Load data
            if (section === 'pending') loadPendingPrescriptions();
            else if (section === 'orders') loadOrders();
            else if (section === 'earnings') loadEarnings();
            else if (section === 'dashboard') loadDashboard();
        }

        function openAcceptModal(prescriptionId) {
            currentPrescriptionId = prescriptionId;
            document.getElementById('acceptModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('acceptModal').classList.remove('active');
            currentPrescriptionId = null;
        }

        async function confirmAccept() {
            const amount = parseFloat(document.getElementById('totalAmount').value);
            const delivery = document.getElementById('deliveryAvailable').value === 'true';
            
            if (!amount || amount <= 0) {
                alert('Please enter a valid amount');
                return;
            }
            
            const response = await fetch('pharmacy_api.php?action=accept_prescription', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    prescription_id: currentPrescriptionId,
                    total_amount: amount,
                    delivery_available: delivery
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Prescription accepted successfully!');
                closeModal();
                loadPendingPrescriptions();
                loadDashboard();
            } else {
                alert(data.error || 'Failed to accept prescription');
            }
        }

        function openStatusModal(orderId) {
            currentOrderId = orderId;
            document.getElementById('statusModal').classList.add('active');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.remove('active');
            currentOrderId = null;
        }

        async function confirmStatusUpdate() {
            const status = document.getElementById('newStatus').value;
            const notes = document.getElementById('statusNotes').value;
            
            const formData = new FormData();
            formData.append('order_id', currentOrderId);
            formData.append('status', status);
            formData.append('notes', notes);
            
            const response = await fetch('pharmacy_api.php?action=update_order_status', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Order status updated!');
                closeStatusModal();
                loadOrders();
            } else {
                alert(data.error || 'Failed to update status');
            }
        }

        // Initial load
        loadDashboard();
    </script>
</body>
</html>

<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue Management - MedConnect Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f1f5f9; }
        .header { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 20px 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header h1 { font-size: 24px; }
        .nav { background: white; padding: 0 40px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; gap: 30px; }
        .nav-item { padding: 15px 0; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.3s; color: #64748b; font-weight: 500; }
        .nav-item:hover { color: #ef4444; }
        .nav-item.active { color: #ef4444; border-bottom-color: #ef4444; }
        .container { padding: 30px 40px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .stat-value { font-size: 32px; font-weight: 700; color: #1e293b; margin: 10px 0; }
        .stat-label { color: #64748b; font-size: 14px; }
        .stat-trend { font-size: 13px; margin-top: 8px; }
        .trend-up { color: #10b981; }
        .trend-down { color: #ef4444; }
        .content-section { display: none; }
        .content-section.active { display: block; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .card h2 { color: #1e293b; margin-bottom: 20px; font-size: 20px; }
        table { width: 100%; border-collapse: collapse; }
        table th { background: #f8fafc; padding: 15px; text-align: left; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0; }
        table td { padding: 15px; border-bottom: 1px solid #f1f5f9; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-completed { background: #d1fae5; color: #065f46; }
        .badge-processing { background: #dbeafe; color: #1e40af; }
        .btn { padding: 8px 16px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s; font-size: 14px; }
        .btn-approve { background: #10b981; color: white; }
        .btn-approve:hover { background: #059669; }
        .btn-reject { background: #ef4444; color: white; }
        .btn-reject:hover { background: #dc2626; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #475569; }
        .form-group input { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; }
        .split-config { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>💰 Revenue & Payout Management</h1>
    </div>
    
    <div class="nav">
        <div class="nav-item active" onclick="showSection('dashboard')">Dashboard</div>
        <div class="nav-item" onclick="showSection('payouts')">Pending Payouts</div>
        <div class="nav-item" onclick="showSection('config')">Commission Config</div>
        <div class="nav-item" onclick="showSection('reports')">Reports</div>
    </div>
    
    <div class="container">
        <!-- Dashboard Section -->
        <div id="dashboard" class="content-section active">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Platform Revenue</div>
                    <div class="stat-value" id="totalRevenue">₹0</div>
                    <div class="stat-trend trend-up" id="revenueTrend"></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pending Payouts</div>
                    <div class="stat-value" id="pendingPayouts">₹0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Doctor Earnings (This Month)</div>
                    <div class="stat-value" id="doctorEarnings">₹0</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pharmacy Earnings (This Month)</div>
                    <div class="stat-value" id="pharmacyEarnings">₹0</div>
                </div>
            </div>
            
            <div class="card">
                <h2>Revenue Breakdown</h2>
                <div id="revenueChart" style="text-align: center; color: #64748b;">Loading revenue data...</div>
            </div>
        </div>
        
        <!-- Payouts Section -->
        <div id="payouts" class="content-section">
            <div class="card">
                <h2>Pending Doctor Payouts</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Doctor Name</th>
                            <th>Consultations</th>
                            <th>Gross Amount</th>
                            <th>Commission</th>
                            <th>Net Payout</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="doctorPayoutsList">
                        <tr><td colspan="6" style="text-align: center; color: #64748b;">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h2>Pending Pharmacy Payouts</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Pharmacy Name</th>
                            <th>Orders</th>
                            <th>Gross Amount</th>
                            <th>Commission</th>
                            <th>Net Payout</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="pharmacyPayoutsList">
                        <tr><td colspan="6" style="text-align: center; color: #64748b;">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Configuration Section -->
        <div id="config" class="content-section">
            <div class="card">
                <h2>Revenue Split Configuration</h2>
                <p style="color: #64748b; margin-bottom: 20px;">Set platform commission percentages for consultations and medications</p>
                
                <div class="split-config">
                    <div>
                        <h3 style="color: #1e293b; margin-bottom: 15px;">Consultation Fees</h3>
                        <div class="form-group">
                            <label>Platform Commission (%)</label>
                            <input type="number" id="consultationCommission" value="10" min="0" max="100" step="0.1">
                        </div>
                        <div class="form-group">
                            <label>Doctor Share (%)</label>
                            <input type="number" id="doctorShare" value="90" readonly style="background: #f8fafc;">
                        </div>
                        <button class="btn btn-approve" onclick="updateCommission('consultation')">Update Configuration</button>
                    </div>
                    
                    <div>
                        <h3 style="color: #1e293b; margin-bottom: 15px;">Medication Sales</h3>
                        <div class="form-group">
                            <label>Platform Commission (%)</label>
                            <input type="number" id="medicationCommission" value="5" min="0" max="100" step="0.1">
                        </div>
                        <div class="form-group">
                            <label>Pharmacy Share (%)</label>
                            <input type="number" id="pharmacyShare" value="95" readonly style="background: #f8fafc;">
                        </div>
                        <button class="btn btn-approve" onclick="updateCommission('medication')">Update Configuration</button>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h2>Current Commission History</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Service Type</th>
                            <th>Platform Commission</th>
                            <th>Provider Share</th>
                            <th>Effective From</th>
                        </tr>
                    </thead>
                    <tbody id="commissionHistory">
                        <tr><td colspan="4" style="text-align: center; color: #64748b;">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Reports Section -->
        <div id="reports" class="content-section">
            <div class="card">
                <h2>Revenue Reports</h2>
                <div id="reportsData"></div>
            </div>
        </div>
    </div>

    <script>
        // Load dashboard data
        async function loadDashboard() {
            // Simulated data - in production, fetch from API
            document.getElementById('totalRevenue').textContent = '₹' + (125000).toFixed(2);
            document.getElementById('revenueTrend').textContent = '↑ 12% from last month';
            document.getElementById('pendingPayouts').textContent = '₹' + (45000).toFixed(2);
            document.getElementById('doctorEarnings').textContent = '₹' + (85000).toFixed(2);
            document.getElementById('pharmacyEarnings').textContent = '₹' + (28000).toFixed(2);
            
            document.getElementById('revenueChart').innerHTML = `
                <div style="text-align: left;">
                    <div style="margin: 15px 0; padding: 15px; background: #f8fafc; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span>Consultation Revenue</span>
                            <span style="font-weight: 600;">₹95,000</span>
                        </div>
                        <div style="background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden;">
                            <div style="background: #667eea; height: 100%; width: 76%;"></div>
                        </div>
                    </div>
                    <div style="margin: 15px 0; padding: 15px; background: #f8fafc; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span>Medication Revenue</span>
                            <span style="font-weight: 600;">₹30,000</span>
                        </div>
                        <div style="background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden;">
                            <div style="background: #f59e0b; height: 100%; width: 24%;"></div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Load payouts
        async function loadPayouts() {
            // Simulated doctor payouts
            const doctorData = [
                {name: 'Dr. Sarah Johnson', consultations: 12, gross: 12000, commission: 1200, net: 10800},
                {name: 'Dr. Michael Chen', consultations: 8, gross: 8000, commission: 800, net: 7200},
                {name: 'Dr. Priya Patel', consultations: 15, gross: 15000, commission: 1500, net: 13500}
            ];
            
            document.getElementById('doctorPayoutsList').innerHTML = doctorData.map(d => `
                <tr>
                    <td>${d.name}</td>
                    <td>${d.consultations}</td>
                    <td>₹${d.gross.toFixed(2)}</td>
                    <td style="color: #ef4444;">-₹${d.commission.toFixed(2)}</td>
                    <td style="font-weight: 600; color: #10b981;">₹${d.net.toFixed(2)}</td>
                    <td>
                        <button class="btn btn-approve" onclick="approvePayout('doctor', ${d.net})">Approve</button>
                    </td>
                </tr>
            `).join('');
            
            // Simulated pharmacy payouts
            const pharmacyData = [
                {name: 'MediPlus Pharmacy', orders: 25, gross: 18000, commission: 900, net: 17100},
                {name: 'HealthCare Chemist', orders: 18, gross: 12000, commission: 600, net: 11400}
            ];
            
            document.getElementById('pharmacyPayoutsList').innerHTML = pharmacyData.map(p => `
                <tr>
                    <td>${p.name}</td>
                    <td>${p.orders}</td>
                    <td>₹${p.gross.toFixed(2)}</td>
                    <td style="color: #ef4444;">-₹${p.commission.toFixed(2)}</td>
                    <td style="font-weight: 600; color: #10b981;">₹${p.net.toFixed(2)}</td>
                    <td>
                        <button class="btn btn-approve" onclick="approvePayout('pharmacy', ${p.net})">Approve</button>
                    </td>
                </tr>
            `).join('');
        }

        // Load commission config
        async function loadCommissionConfig() {
            const history = [
                {type: 'Consultation', platform: '10%', provider: '90%', date: '2026-01-01'},
                {type: 'Medication', platform: '5%', provider: '95%', date: '2026-01-01'}
            ];
            
            document.getElementById('commissionHistory').innerHTML = history.map(h => `
                <tr>
                    <td>${h.type}</td>
                    <td>${h.platform}</td>
                    <td>${h.provider}</td>
                    <td>${h.date}</td>
                </tr>
            `).join('');
        }

        // Update commission percentage
        function updateCommission(type) {
            const commission = type === 'consultation' ? 
                document.getElementById('consultationCommission').value :
                document.getElementById('medicationCommission').value;
            
            const providerShare = 100 - parseFloat(commission);
            
            if (type === 'consultation') {
                document.getElementById('doctorShare').value = providerShare.toFixed(1);
            } else {
                document.getElementById('pharmacyShare').value = providerShare.toFixed(1);
            }
            
            alert(`Commission updated: Platform ${commission}%, Provider ${providerShare.toFixed(1)}%`);
        }

        // Auto-calculate provider share
        document.getElementById('consultationCommission')?.addEventListener('input', function() {
            const commission = parseFloat(this.value) || 0;
            document.getElementById('doctorShare').value = (100 - commission).toFixed(1);
        });

        document.getElementById('medicationCommission')?.addEventListener('input', function() {
            const commission = parseFloat(this.value) || 0;
            document.getElementById('pharmacyShare').value = (100 - commission).toFixed(1);
        });

        // Approve payout
        function approvePayout(type, amount) {
            if (confirm(`Approve ${type} payout of ₹${amount.toFixed(2)}?`)) {
                alert('Payout approved and processed!');
                loadPayouts();
            }
        }

        // Navigation
        function showSection(section) {
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            event.currentTarget.classList.add('active');
            
            document.querySelectorAll('.content-section').forEach(sec => sec.classList.remove('active'));
            document.getElementById(section).classList.add('active');
            
            if (section === 'payouts') loadPayouts();
            else if (section === 'config') loadCommissionConfig();
            else if (section === 'dashboard') loadDashboard();
        }

        // Initial load
        loadDashboard();
    </script>
</body>
</html>
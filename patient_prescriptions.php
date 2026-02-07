<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Patient';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Prescriptions - MedConnect</title>
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.0.3/src/regular/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f1f5f9; }
        
        .header { background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); color: white; padding: 20px 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header h1 { font-size: 28px; margin-bottom: 5px; }
        .header p { opacity: 0.9; font-size: 14px; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
        .back-btn { display: inline-flex; align-items: center; gap: 8px; background: white; color: #0d9488; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; margin-bottom: 20px; transition: all 0.3s; }
        .back-btn:hover { background: #f0fdfa; transform: translateX(-5px); }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #0d9488; }
        .stat-value { font-size: 32px; font-weight: 700; color: #1e293b; margin: 10px 0; }
        .stat-label { color: #64748b; font-size: 14px; }
        
        .prescription-card { background: white; padding: 25px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #10b981; transition: all 0.3s; }
        .prescription-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.12); transform: translateY(-2px); }
        
        .prescription-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f1f5f9; }
        .prescription-number { font-size: 18px; font-weight: 700; color: #1e293b; }
        .prescription-date { color: #64748b; font-size: 14px; margin-top: 5px; }
        
        .badge { display: inline-block; padding: 6px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-issued { background: #d1fae5; color: #065f46; }
        .badge-sent { background: #dbeafe; color: #1e40af; }
        .badge-filled { background: #fef3c7; color: #92400e; }
        
        .doctor-info { margin-bottom: 20px; }
        .doctor-name { font-size: 16px; font-weight: 600; color: #1e293b; margin-bottom: 5px; }
        .doctor-specialty { color: #64748b; font-size: 14px; }
        
        .diagnosis-box { background: #fffbeb; padding: 15px; border-radius: 8px; border-left: 3px solid #f59e0b; margin-bottom: 20px; }
        .diagnosis-box strong { color: #92400e; }
        
        .medications-list { background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .medication-item { padding: 12px 0; border-bottom: 1px solid #e2e8f0; }
        .medication-item:last-child { border-bottom: none; }
        .medication-name { font-weight: 600; color: #1e293b; margin-bottom: 5px; }
        .medication-details { color: #64748b; font-size: 14px; }
        
        .actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: #0d9488; color: white; }
        .btn-primary:hover { background: #0f766e; }
        .btn-secondary { background: #10b981; color: white; }
        .btn-secondary:hover { background: #059669; }
        .btn-outline { background: white; color: #0d9488; border: 2px solid #0d9488; }
        .btn-outline:hover { background: #f0fdfa; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
        .empty-state i { font-size: 64px; opacity: 0.3; margin-bottom: 20px; }
        
        .loading { text-align: center; padding: 40px; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>💊 My Prescriptions</h1>
        <p>View and manage all your medical prescriptions</p>
    </div>
    
    <div class="container">
        <a href="features.php" class="back-btn">
            <i class="ph ph-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Prescriptions</div>
                <div class="stat-value" id="totalCount">0</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Active Prescriptions</div>
                <div class="stat-value" id="activeCount">0</div>
            </div>
        </div>
        
        <div id="prescriptionsList" class="loading">
            <p>Loading your prescriptions...</p>
        </div>
    </div>
    
    <script>
        async function loadPrescriptions() {
            try {
                const response = await fetch('prescription_api.php?action=get_my_prescriptions&status=all');
                const data = await response.json();
                
                const container = document.getElementById('prescriptionsList');
                
                if (!data.success) {
                    container.innerHTML = `<div class="empty-state"><p>Error loading prescriptions: ${data.error}</p></div>`;
                    return;
                }
                
                const prescriptions = data.prescriptions || [];
                
                // Update stats
                document.getElementById('totalCount').textContent = prescriptions.length;
                document.getElementById('activeCount').textContent = prescriptions.filter(p => 
                    ['finalized', 'sent_to_pharmacy', 'in_progress', 'ready'].includes(p.status)
                ).length;
                
                if (prescriptions.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="ph ph-prescription"></i>
                            <h3>No Prescriptions Yet</h3>
                            <p>Your prescriptions from consultations will appear here</p>
                            <a href="symptom_checker.php" class="btn btn-primary" style="margin-top: 20px;">
                                <i class="ph ph-stethoscope"></i> Start Consultation
                            </a>
                        </div>
                    `;
                    return;
                }
                
                container.innerHTML = prescriptions.map(rx => {
                    const statusBadge = {
                        'finalized': 'badge-issued',
                        'sent_to_pharmacy': 'badge-sent',
                        'in_progress': 'badge-filled',
                        'ready': 'badge-filled',
                        'completed': 'badge-issued',
                        'cancelled': 'badge-sent'
                    }[rx.status] || 'badge-issued';
                    
                    const statusText = {
                        'finalized': '✅ Finalized',
                        'sent_to_pharmacy': '📤 Sent to Pharmacy',
                        'in_progress': '⚗️ Being Prepared',
                        'ready': '✨ Ready for Pickup',
                        'completed': '🎉 Completed',
                        'cancelled': '❌ Cancelled'
                    }[rx.status] || rx.status;
                    
                    return `
                        <div class="prescription-card">
                            <div class="prescription-header">
                                <div>
                                    <div class="prescription-number">Prescription #${rx.id}</div>
                                    <div class="prescription-date">
                                        <i class="ph ph-calendar"></i> ${new Date(rx.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}
                                    </div>
                                </div>
                                <span class="badge ${statusBadge}">${statusText}</span>
                            </div>
                            
                            <div class="doctor-info">
                                <div class="doctor-name">
                                    <i class="ph ph-user-circle"></i> Dr. ${rx.doctor_name}
                                </div>
                                <div class="doctor-specialty">${rx.specialization || 'General Physician'}</div>
                            </div>
                            
                            <div class="diagnosis-box">
                                <strong>Diagnosis:</strong> ${rx.diagnosis}
                            </div>
                            
                            <div class="medications-list">
                                <strong style="display: block; margin-bottom: 10px; color: #1e293b;">
                                    <i class="ph ph-pill"></i> Medications:
                                </strong>
                                ${rx.items.map(item => `
                                    <div class="medication-item">
                                        <div class="medication-name">${item.medicine_name}</div>
                                        <div class="medication-details">
                                            ${item.dosage} • ${item.frequency} • ${item.duration}
                                            ${item.instructions ? `<br><em>${item.instructions}</em>` : ''}
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                            
                            ${rx.notes_for_patient ? `
                                <div style="background: #f1f5f9; padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 14px; color: #475569;">
                                    <strong>Instructions:</strong> ${rx.notes_for_patient}
                                </div>
                            ` : ''}
                            
                            ${rx.pharmacy_name ? `
                                <div style="color: #10b981; font-size: 14px; margin-bottom: 15px;">
                                    <i class="ph ph-storefront"></i> Sent to: ${rx.pharmacy_name}
                                </div>
                            ` : ''}
                            
                            
                            <div class="actions">
                                <a href="prescription_api.php?action=download_prescription&prescription_id=${rx.id}" target="_blank" class="btn btn-primary">
                                    <i class="ph ph-download"></i> Download
                                </a>
                                
                                ${rx.status === 'finalized' || rx.status === 'issued' ? `
                                    <button class="btn btn-secondary" onclick="proceedWithPrescription(${rx.id})">
                                        <i class="ph ph-shopping-cart"></i> Order Medicine
                                    </button>
                                ` : ''}
                                
                                ${rx.status === 'sent_to_pharmacy' ? `
                                    <button class="btn btn-outline" disabled>
                                        <i class="ph ph-clock"></i> Waiting for Pharmacy
                                    </button>
                                ` : ''}
                                
                                ${rx.status === 'in_progress' ? `
                                    <button class="btn btn-outline" disabled>
                                        <i class="ph ph-flask"></i> Being Prepared
                                    </button>
                                ` : ''}
                                
                                ${rx.status === 'ready' ? `
                                    <a href="prescription_payment.php?prescription_id=${rx.id}" class="btn btn-secondary">
                                        <i class="ph ph-credit-card"></i> Pay Now
                                    </a>
                                ` : ''}
                                
                                ${rx.status === 'completed' && !rx.review_submitted ? `
                                    <a href="prescription_review.php?prescription_id=${rx.id}" class="btn btn-secondary">
                                        <i class="ph ph-star"></i> Submit Review
                                    </a>
                                ` : ''}
                                
                                ${rx.order_number ? `
                                    <button class="btn btn-outline" onclick="viewOrderDetails(${rx.id}, '${rx.order_number}', '${rx.order_status}')">
                                        <i class="ph ph-package"></i> Order #${rx.order_number}
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    `;
                }).join('');
                
            } catch (error) {
                console.error('Error loading prescriptions:', error);
                document.getElementById('prescriptionsList').innerHTML = `
                    <div class="empty-state">
                        <p>Network error. Please try again later.</p>
                    </div>
                `;
            }
        }
        
        
        // Proceed with prescription - send to pharmacy
        async function proceedWithPrescription(prescriptionId) {
            if (!confirm('Send this prescription to the pharmacy for processing?')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'send_to_pharmacy');
                formData.append('prescription_id', prescriptionId);
                
                const response = await fetch('patient_prescription_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Prescription sent to pharmacy successfully!\n\nThe pharmacy will review and prepare your order.');
                    loadPrescriptions(); // Reload to show updated status
                } else {
                    alert('❌ Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Network error. Please try again.');
            }
        }
        
        // View order details
        function viewOrderDetails(prescriptionId, orderNumber, orderStatus) {
            const statusText = {
                'pending': 'Pending - Waiting for pharmacy acceptance',
                'accepted': 'Accepted - Pharmacy is preparing your order',
                'preparing': 'Preparing - Your medicines are being prepared',
                'in_progress': 'In Progress - Your order is being processed',
                'ready': 'Ready - Your order is ready for pickup/payment',
                'out_for_delivery': 'Out for Delivery - Your order is on the way',
                'delivered': 'Delivered - Your order has been delivered',
                'completed': 'Completed - Order completed successfully',
                'cancelled': 'Cancelled - Order was cancelled'
            }[orderStatus] || orderStatus;
            
            alert(`Order #${orderNumber}\n\nStatus: ${statusText}`);
        }
        
        // Load prescriptions on page load
        loadPrescriptions();
    </script>
</body>
</html>
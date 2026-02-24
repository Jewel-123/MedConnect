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
    <link rel="stylesheet" href="assets/css/custom_modal.css?v=<?php echo time(); ?>">
    <script src="assets/js/custom_modal.js?v=<?php echo time(); ?>"></script>
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.0.3/src/regular/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Outfit', 'Segoe UI', sans-serif; background: #f0fdfa; color: #111827; }
        
        .header { 
            background: linear-gradient(135deg, #0d9488 0%, #2dd4bf 100%); 
            color: white; 
            padding: 30px 40px; 
            box-shadow: 0 4px 20px rgba(13, 148, 136, 0.15); 
        }
        .header h1 { font-size: 32px; font-weight: 700; margin-bottom: 8px; }
        .header p { opacity: 0.9; font-size: 16px; font-weight: 300; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        
        .back-btn { 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            background: white; 
            color: #0d9488; 
            padding: 12px 24px; 
            border-radius: 50px; 
            text-decoration: none; 
            font-weight: 600; 
            margin-bottom: 30px; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .back-btn:hover { 
            background: #f0fdfa; 
            transform: translateX(-5px); 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-bottom: 40px; }
        .stat-card { 
            background: white; 
            padding: 30px; 
            border-radius: 20px; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); 
            border: 1px solid rgba(13, 148, 136, 0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-value { font-size: 42px; font-weight: 800; color: #0f766e; margin: 10px 0; letter-spacing: -1px; }
        .stat-label { color: #64748b; font-size: 15px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .prescription-card { 
            background: white; 
            padding: 30px; 
            border-radius: 20px; 
            margin-bottom: 24px; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); 
            border: 1px solid rgba(0,0,0,0.05);
            border-left: 6px solid #0d9488; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        .prescription-card:hover { 
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); 
            transform: translateY(-4px); 
        }
        
        .prescription-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 2px solid #f0fdfa; }
        .prescription-number { font-size: 20px; font-weight: 700; color: #111827; }
        .prescription-date { color: #64748b; font-size: 14px; margin-top: 5px; display: flex; align-items: center; gap: 6px; }
        
        .badge { display: inline-flex; align-items: center; padding: 6px 14px; border-radius: 50px; font-size: 13px; font-weight: 600; gap: 6px; }
        .badge-issued { background: #d1fae5; color: #065f46; }
        .badge-sent { background: #ccfbf1; color: #0f766e; }
        .badge-filled { background: #ffedd5; color: #9a3412; }
        
        .doctor-info { margin-bottom: 25px; display: flex; align-items: center; gap: 15px; }
        .doctor-avatar { width: 48px; height: 48px; background: #f0fdfa; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #0d9488; font-size: 24px; }
        .doctor-details h3 { font-size: 16px; font-weight: 700; color: #111827; margin-bottom: 2px; }
        .doctor-details p { color: #64748b; font-size: 14px; }
        
        .diagnosis-box { 
            background: #fff1f2; 
            padding: 20px; 
            border-radius: 16px; 
            border-left: 4px solid #f43f5e; 
            margin-bottom: 25px; 
        }
        .diagnosis-box strong { color: #be123c; display: block; margin-bottom: 5px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        .diagnosis-content { color: #881337; font-weight: 500; font-size: 16px; }
        
        .medications-list { background: #f8fafc; padding: 25px; border-radius: 16px; margin-bottom: 25px; border: 1px solid #e2e8f0; }
        .medication-item { padding: 15px 0; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .medication-item:last-child { border-bottom: none; padding-bottom: 0; }
        .medication-item:first-child { padding-top: 0; }
        .medication-name { font-weight: 700; color: #1e293b; font-size: 16px; }
        .medication-details { color: #64748b; font-size: 14px; text-align: right; }
        
        .actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 25px; }
        .btn { 
            padding: 12px 24px; 
            border: none; 
            border-radius: 50px; 
            cursor: pointer; 
            font-weight: 600; 
            transition: all 0.3s; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            font-size: 14px;
        }
        .btn-primary { 
            background: linear-gradient(135deg, #0d9488 0%, #2dd4bf 100%); 
            color: white; 
            box-shadow: 0 4px 15px rgba(13, 148, 136, 0.3);
        }
        .btn-primary:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 25px rgba(13, 148, 136, 0.4); 
        }
        .btn-secondary { background: #10b981; color: white; } /* Keep green for actions like Pay */
        .btn-secondary:hover { background: #059669; }
        .btn-outline { background: white; color: #0d9488; border: 2px solid #0d9488; }
        .btn-outline:hover { background: #f0fdfa; }
        
        .empty-state { text-align: center; padding: 80px 20px; color: #64748b; }
        .empty-state i { font-size: 80px; opacity: 0.2; margin-bottom: 20px; color: #0d9488; }
        
        .loading { text-align: center; padding: 60px; color: #64748b; }
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
                    const status = (rx.status || '').toLowerCase();
                    const statusBadge = {
                        'finalized': 'badge-issued',
                        'verified': 'badge-issued',
                        'sent_to_pharmacy': 'badge-sent',
                        'in_progress': 'badge-filled',
                        'ready': 'badge-filled',
                        'awaiting payment': 'badge-filled',
                        'paid': 'badge-issued',
                        'dispensed': 'badge-issued',
                        'completed': 'badge-issued',
                        'cancelled': 'badge-sent'
                    }[status] || 'badge-issued';
                    
                    const statusText = {
                        'finalized': '✅ Issued',
                        'verified': '✔️ Verified',
                        'sent_to_pharmacy': '📤 Sent to Pharmacy',
                        'in_progress': '⚗️ Being Prepared',
                        'ready': '✨ Ready',
                        'awaiting payment': '💳 Awaiting Payment',
                        'paid': '💰 Paid',
                        'dispensed': '💊 Dispensed',
                        'completed': '🎉 Completed',
                        'cancelled': '❌ Cancelled'
                    }[status] || rx.status;
                    
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
                                
                                ${rx.status === 'finalized' ? `
                                    <button class="btn btn-secondary" onclick="proceedWithPrescription(${rx.id})">
                                        <i class="ph ph-shopping-cart"></i> Order Medicine
                                    </button>
                                ` : ''}
                                
                                ${status === 'awaiting payment' ? `
                                    <a href="payment_gateway.php?type=medication&related_id=${rx.order_id || rx.id}&amount=${rx.order_amount || rx.total_amount || 0}" class="btn btn-secondary">
                                        <i class="ph ph-credit-card"></i> Pay Now
                                    </a>
                                ` : ''}
                                                               ${(() => {
                                    const isCompleted = status === 'completed';
                                    const isPaid = (rx.payment_status || '').toLowerCase() === 'paid';
                                    const hasReviewed = parseInt(rx.review_submitted) === 1;

                                    if (hasReviewed) {
                                        return `
                                            <button class="btn btn-outline" disabled style="opacity: 0.7; cursor: not-allowed;">
                                                <i class="ph ph-check-circle"></i> Review Submitted
                                            </button>
                                        `;
                                    }

                                    if (isCompleted && isPaid) {
                                        return `
                                            <a href="prescription_review.php?prescription_id=${rx.id}" class="btn btn-secondary">
                                                <i class="ph ph-star"></i> Submit Review
                                            </a>
                                        `;
                                    } else if (isPaid && !isCompleted) {
                                        return `
                                            <span style="color: #64748b; font-size: 13px; font-style: italic; display: flex; align-items: center; gap: 5px;">
                                                <i class="ph ph-info"></i> Review available after completion
                                            </span>
                                        `;
                                    } else if (status === 'awaiting payment') {
                                        return `
                                            <span style="color: #64748b; font-size: 13px; font-style: italic; display: flex; align-items: center; gap: 5px;">
                                                <i class="ph ph-lock"></i> Review available after payment
                                            </span>
                                        `;
                                    }
                                    return '';
                                })()}
                                
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
            if (!await confirm('Send this prescription to the pharmacy for processing?')) {
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
                    await alert('✅ Prescription sent to pharmacy successfully!\n\nThe pharmacy will review and prepare your order.');
                    loadPrescriptions(); // Reload to show updated status
                } else {
                    await alert('❌ Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                await alert('Network error. Please try again.');
            }
        }
        
        // View order details
        async function viewOrderDetails(prescriptionId, orderNumber, orderStatus) {
            const statusText = {
                'Pending': 'Pending - Waiting for pharmacy verification',
                'Verified': 'Verified - Pharmacy has priced your order',
                'Awaiting Payment': 'Awaiting Payment - Please pay to continue',
                'Paid': 'Paid - Payment confirmed, awaiting dispensing',
                'Dispensed': 'Dispensed - Your medicine is ready for pickup/delivery',
                'Completed': 'Completed - Order completed successfully',
                'Cancelled': 'Cancelled - Order was cancelled'
            }[orderStatus] || orderStatus;
            
            await alert(`Order #${orderNumber}\n\nStatus: ${statusText}`);
        }
        
        // Load prescriptions on page load
        loadPrescriptions();
    </script>
</body>
</html>

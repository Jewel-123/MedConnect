/**
 * Enhanced Pharmacy Dashboard JavaScript
 * Handles all interactive functionality, real-time updates, and API calls
 */

let currentPrescriptionId = null;
let currentOrderId = null;
let refreshInterval = null;

// Initialize dashboard on load
document.addEventListener('DOMContentLoaded', function () {
    loadDashboard();
    startAutoRefresh();
});

// Auto-refresh every 30 seconds
function startAutoRefresh() {
    refreshInterval = setInterval(() => {
        const activeSection = document.querySelector('.content-section.active').id;
        if (activeSection === 'dashboard') {
            loadDashboard();
        } else if (activeSection === 'pending') {
            loadPendingPrescriptions();
        } else if (activeSection === 'orders') {
            loadOrders();
        }
        loadNotifications();
    }, 30000);
}

// Show toast notification
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');

    toast.className = `toast ${type} show`;
    toastMessage.textContent = message;

    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// Load dashboard stats
async function loadDashboard() {
    try {
        // Use the new pharmacy_dashboard_api for real-time data
        const response = await fetch('pharmacy_dashboard_api.php?action=get_dashboard_summary');
        const data = await response.json();

        if (data.success) {
            const stats = data.summary;

            document.getElementById('pendingCount').textContent = stats.pending_prescriptions;
            document.getElementById('activeCount').textContent = stats.active_orders;
            document.getElementById('monthEarnings').textContent = '₹' + stats.month_earnings.toFixed(2);

            // Calculate fulfillment rate
            const fulfillmentRate = stats.total_orders > 0
                ? Math.round((stats.total_orders - stats.active_orders) / stats.total_orders * 100)
                : 0;
            document.getElementById('fulfillmentRate').textContent = fulfillmentRate + '%';

            // Update notification badge
            if (stats.pending_prescriptions > 0) {
                const badge = document.getElementById('notificationBadge');
                badge.textContent = stats.pending_prescriptions;
                badge.style.display = 'flex';
            }

            // Load recent activity
            loadRecentActivity();
        }
    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

// Load recent activity
async function loadRecentActivity() {
    try {
        const response = await fetch('pharmacy_api_enhanced.php?action=get_orders&status=all');
        const data = await response.json();

        if (data.success && data.orders) {
            const container = document.getElementById('recentActivity');
            const recentOrders = data.orders.slice(0, 5);

            if (recentOrders.length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i><p>No recent activity</p></div>';
                return;
            }

            container.innerHTML = recentOrders.map(order => `
                <div class="prescription-card">
                    <div class="prescription-header">
                        <div class="patient-info">
                            <h3>Order #${order.order_number}</h3>
                            <p>${order.patient_name} • ₹${order.total_amount}</p>
                        </div>
                        <span class="badge badge-${getStatusClass(order.order_status)}">${order.order_status.replace('_', ' ')}</span>
                    </div>
                    <p style="color: #64748b; font-size: 0.875rem;">
                        <i class="fas fa-clock"></i> ${formatDate(order.created_at)}
                    </p>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error loading recent activity:', error);
    }
}

// Load pending prescriptions
async function loadPendingPrescriptions() {
    try {
        const response = await fetch('pharmacy_api_enhanced.php?action=get_pending_prescriptions');
        const data = await response.json();

        const container = document.getElementById('pendingList');

        if (!data.success || !data.prescriptions || data.prescriptions.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-file-prescription"></i><p>No pending prescriptions</p></div>';
            return;
        }

        container.innerHTML = data.prescriptions.map(rx => `
            <div class="prescription-card ${rx.urgency_level === 'emergency' ? 'urgent' : ''}">
                <div class="prescription-header">
                    <div class="patient-info">
                        <h3>${rx.patient_name}</h3>
                        <p>Prescribed by Dr. ${rx.doctor_name} (${rx.specialization || 'General'})</p>
                        <p style="margin-top: 0.5rem; color: #64748b; font-size: 0.875rem;">
                            <i class="fas fa-clock"></i> ${formatDate(rx.sent_at)}
                        </p>
                    </div>
                    <span class="badge ${rx.urgency_level === 'emergency' ? 'badge-urgent' : 'badge-pending'}">
                        ${rx.urgency_level || 'Pending'}
                    </span>
                </div>
                
                <div style="background: #fffbeb; padding: 0.75rem; border-radius: 8px; margin: 0.75rem 0;">
                    <strong>Diagnosis:</strong> ${rx.diagnosis}
                </div>
                
                <div class="prescription-items">
                    <strong style="display: block; margin-bottom: 0.75rem;">Medications:</strong>
                    ${rx.items.map(item => `
                        <div class="item">
                            <div>
                                <div class="item-name">${item.medicine_name}</div>
                                <div class="item-dosage">${item.dosage} • ${item.frequency} • ${item.duration}</div>
                                ${item.instructions ? `<div class="item-dosage"><i class="fas fa-info-circle"></i> ${item.instructions}</div>` : ''}
                            </div>
                        </div>
                    `).join('')}
                </div>
                
                <div class="actions">
                    <button class="btn btn-success" onclick="openAcceptModal(${rx.id})">
                        <i class="fas fa-check"></i> Accept & Create Order
                    </button>
                    <button class="btn btn-danger" onclick="openRejectModal(${rx.id})">
                        <i class="fas fa-times"></i> Reject
                    </button>
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading pending prescriptions:', error);
        showToast('Error loading prescriptions', 'error');
    }
}

// Load orders
async function loadOrders() {
    try {
        const response = await fetch('pharmacy_api_enhanced.php?action=get_orders&status=all');
        const data = await response.json();

        const container = document.getElementById('ordersList');

        if (!data.success || !data.orders || data.orders.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-box"></i><p>No orders yet</p></div>';
            return;
        }

        // Filter active orders
        const activeOrders = data.orders.filter(o => !['completed', 'cancelled', 'delivered'].includes(o.order_status));

        if (activeOrders.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-box"></i><p>No active orders</p></div>';
            return;
        }

        container.innerHTML = activeOrders.map(order => `
            <div class="prescription-card">
                <div class="prescription-header">
                    <div class="patient-info">
                        <h3>Order #${order.order_number}</h3>
                        <p>${order.patient_name} • ${order.patient_phone || 'No phone'}</p>
                        <p style="margin-top: 0.5rem; font-weight: 600; color: var(--success);">₹${order.total_amount}</p>
                    </div>
                    <span class="badge badge-${getStatusClass(order.order_status)}">${order.order_status.replace('_', ' ')}</span>
                </div>
                
                <div style="color: #64748b; font-size: 0.875rem; margin: 0.75rem 0;">
                    <i class="fas fa-${order.fulfillment_type === 'delivery' ? 'truck' : 'store'}"></i>
                    ${order.fulfillment_type === 'delivery' ? 'Home Delivery' : 'Pickup'}
                </div>
                
                ${order.payment_status === 'pending' ? `
                    <div style="background: #fef3c7; padding: 0.75rem; border-radius: 8px; margin: 0.75rem 0;">
                        <i class="fas fa-exclamation-triangle"></i> Payment Pending
                    </div>
                ` : ''}
                
                <div class="actions">
                    ${order.payment_status === 'pending' ? `
                        <button class="btn btn-primary" onclick="confirmPayment(${order.id})">
                            <i class="fas fa-money-bill"></i> Confirm Payment
                        </button>
                    ` : ''}
                    <button class="btn btn-primary" onclick="openStatusModal(${order.id})">
                        <i class="fas fa-sync"></i> Update Status
                    </button>
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading orders:', error);
        showToast('Error loading orders', 'error');
    }
}

// Load prescription history
async function loadHistory() {
    try {
        const response = await fetch('pharmacy_api_enhanced.php?action=get_prescription_history&limit=50');
        const data = await response.json();

        const container = document.getElementById('historyList');

        if (!data.success || !data.prescriptions || data.prescriptions.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-history"></i><p>No prescription history</p></div>';
            return;
        }

        container.innerHTML = data.prescriptions.map(rx => `
            <div class="prescription-card">
                <div class="prescription-header">
                    <div class="patient-info">
                        <h3>${rx.patient_name}</h3>
                        <p>Dr. ${rx.doctor_name} • ${formatDate(rx.created_at)}</p>
                        ${rx.order_number ? `<p style="margin-top: 0.25rem;">Order #${rx.order_number}</p>` : ''}
                    </div>
                    <div>
                        <span class="badge badge-${getStatusClass(rx.order_status || rx.status)}">${rx.order_status || rx.status}</span>
                        ${rx.total_amount ? `<p style="margin-top: 0.5rem; font-weight: 600;">₹${rx.total_amount}</p>` : ''}
                    </div>
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading history:', error);
        showToast('Error loading history', 'error');
    }
}

// Load analytics
async function loadAnalytics() {
    try {
        // Get total earnings from new API
        const response = await fetch('pharmacy_dashboard_api.php?action=get_total_earnings');
        const data = await response.json();

        const container = document.getElementById('analyticsData');

        if (data.success && data.earnings) {
            const e = data.earnings;

            container.innerHTML = `
                <div class="stats-grid">
                    <div class="stat-card success">
                        <div class="stat-icon success">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                        <div class="stat-value">₹${(e.gross || 0).toFixed(2)}</div>
                        <div class="stat-label">Gross Earnings</div>
                    </div>
                    
                    <div class="stat-card danger">
                        <div class="stat-icon danger">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="stat-value">-₹${(e.commission || 0).toFixed(2)}</div>
                        <div class="stat-label">Platform Commission (${e.avg_commission_percent || 0}%)</div>
                    </div>
                    
                    <div class="stat-card info">
                        <div class="stat-icon info">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="stat-value">₹${(e.net || 0).toFixed(2)}</div>
                        <div class="stat-label">Net Earnings</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-value">${e.order_count || 0}</div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                </div>
                
                <div class="card" style="margin-top: 2rem;">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-history"></i>
                            Payment History
                        </h2>
                    </div>
                    <div id="paymentHistoryList"></div>
                </div>
            `;

            // Load payment history
            loadPaymentHistory();
        }
    } catch (error) {
        console.error('Error loading analytics:', error);
        showToast('Error loading analytics', 'error');
    }
}

// Load payment history
async function loadPaymentHistory() {
    try {
        const response = await fetch('pharmacy_dashboard_api.php?action=get_payment_history&limit=20');
        const data = await response.json();

        const container = document.getElementById('paymentHistoryList');

        if (!data.success || !data.payments || data.payments.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-receipt"></i><p>No payment history</p></div>';
            return;
        }

        container.innerHTML = data.payments.map(payment => `
            <div class="prescription-card">
                <div class="prescription-header">
                    <div class="patient-info">
                        <h3>Order #${payment.order_number}</h3>
                        <p>${payment.patient_name} • ${payment.patient_email}</p>
                        <p style="margin-top: 0.5rem; color: #64748b; font-size: 0.875rem;">
                            <i class="fas fa-clock"></i> ${formatDate(payment.created_at)}
                        </p>
                    </div>
                    <div>
                        <span class="badge badge-${payment.status === 'completed' ? 'ready' : 'pending'}">${payment.status}</span>
                        <p style="margin-top: 0.5rem; font-weight: 600; color: var(--success);">₹${payment.amount.toFixed(2)}</p>
                    </div>
                </div>
                <div style="color: #64748b; font-size: 0.875rem; margin-top: 0.75rem;">
                    <i class="fas fa-credit-card"></i> ${payment.payment_method.toUpperCase()}
                    ${payment.razorpay_payment_id ? ` • ID: ${payment.razorpay_payment_id}` : ''}
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading payment history:', error);
    }
}

// Load notifications
async function loadNotifications() {
    try {
        const response = await fetch('pharmacy_api_enhanced.php?action=get_notifications&limit=50');
        const data = await response.json();

        if (data.success) {
            // Update badge
            const badge = document.getElementById('notificationBadge');
            if (data.unread_count > 0) {
                badge.textContent = data.unread_count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }

            // Update notifications list
            const container = document.getElementById('notificationsList');

            if (!data.notifications || data.notifications.length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-bell"></i><p>No notifications</p></div>';
                return;
            }

            container.innerHTML = data.notifications.map(notif => `
                <div class="prescription-card" style="opacity: ${notif.is_read ? '0.6' : '1'}; border-left-color: ${notif.is_read ? '#e2e8f0' : 'var(--primary)'};">
                    <div class="prescription-header">
                        <div class="patient-info">
                            <h3>${notif.title}</h3>
                            <p>${notif.message}</p>
                            <p style="margin-top: 0.5rem; color: #64748b; font-size: 0.875rem;">
                                <i class="fas fa-clock"></i> ${formatDate(notif.created_at)}
                            </p>
                        </div>
                        ${!notif.is_read ? `
                            <button class="btn btn-secondary" onclick="markNotificationRead(${notif.id})">
                                <i class="fas fa-check"></i> Mark Read
                            </button>
                        ` : ''}
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
}

// Mark notification as read
async function markNotificationRead(notificationId) {
    try {
        const response = await fetch('pharmacy_api_enhanced.php?action=mark_notification_read', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ notification_id: notificationId })
        });

        const data = await response.json();

        if (data.success) {
            loadNotifications();
        }
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
}

// Mark all notifications as read
async function markAllRead() {
    try {
        const response = await fetch('pharmacy_api_enhanced.php?action=mark_notification_read', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mark_all: true })
        });

        const data = await response.json();

        if (data.success) {
            showToast('All notifications marked as read');
            loadNotifications();
        }
    } catch (error) {
        console.error('Error marking all as read:', error);
    }
}

// Show section
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
    else if (section === 'history') loadHistory();
    else if (section === 'analytics') loadAnalytics();
    else if (section === 'notifications') loadNotifications();
    else if (section === 'dashboard') loadDashboard();
}

// Toggle notifications panel
function toggleNotifications() {
    showSection('notifications');
    document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
    document.querySelectorAll('.nav-item')[5].classList.add('active');
}

// Open accept modal
function openAcceptModal(prescriptionId) {
    currentPrescriptionId = prescriptionId;
    document.getElementById('acceptModal').classList.add('active');
}

// Close modal
function closeModal() {
    document.getElementById('acceptModal').classList.remove('active');
    currentPrescriptionId = null;
}

// Confirm accept
async function confirmAccept() {
    const amount = parseFloat(document.getElementById('totalAmount').value);
    const delivery = document.getElementById('deliveryAvailable').value === 'true';

    if (!amount || amount <= 0) {
        showToast('Please enter a valid amount', 'error');
        return;
    }

    try {
        const response = await fetch('pharmacy_api_enhanced.php?action=accept_prescription', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                prescription_id: currentPrescriptionId,
                total_amount: amount,
                delivery_available: delivery
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Prescription accepted successfully!');
            closeModal();
            loadPendingPrescriptions();
            loadDashboard();
        } else {
            showToast(data.error || 'Failed to accept prescription', 'error');
        }
    } catch (error) {
        console.error('Error accepting prescription:', error);
        showToast('Error accepting prescription', 'error');
    }
}

// Open reject modal
function openRejectModal(prescriptionId) {
    currentPrescriptionId = prescriptionId;
    document.getElementById('rejectModal').classList.add('active');
}

// Close reject modal
function closeRejectModal() {
    document.getElementById('rejectModal').classList.remove('active');
    currentPrescriptionId = null;
}

// Confirm reject
async function confirmReject() {
    const reason = document.getElementById('rejectionReason').value.trim();

    if (!reason) {
        showToast('Please enter a rejection reason', 'error');
        return;
    }

    try {
        const response = await fetch('pharmacy_api_enhanced.php?action=reject_prescription', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                prescription_id: currentPrescriptionId,
                reason: reason
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Prescription rejected');
            closeRejectModal();
            loadPendingPrescriptions();
            loadDashboard();
        } else {
            showToast(data.error || 'Failed to reject prescription', 'error');
        }
    } catch (error) {
        console.error('Error rejecting prescription:', error);
        showToast('Error rejecting prescription', 'error');
    }
}

// Open status modal
function openStatusModal(orderId) {
    currentOrderId = orderId;
    document.getElementById('statusModal').classList.add('active');
}

// Close status modal
function closeStatusModal() {
    document.getElementById('statusModal').classList.remove('active');
    currentOrderId = null;
}

// Confirm status update
async function confirmStatusUpdate() {
    const status = document.getElementById('newStatus').value;
    const notes = document.getElementById('statusNotes').value;

    try {
        const response = await fetch('pharmacy_api_enhanced.php?action=update_order_status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: currentOrderId,
                status: status,
                notes: notes
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Order status updated!');
            closeStatusModal();
            loadOrders();
            loadDashboard();
        } else {
            showToast(data.error || 'Failed to update status', 'error');
        }
    } catch (error) {
        console.error('Error updating status:', error);
        showToast('Error updating status', 'error');
    }
}

// Confirm payment
async function confirmPayment(orderId) {
    if (!confirm('Confirm that payment has been received for this order?')) {
        return;
    }

    try {
        const response = await fetch('pharmacy_api_enhanced.php?action=confirm_payment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: orderId,
                payment_method: 'cash'
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Payment confirmed!');
            loadOrders();
            loadDashboard();
        } else {
            showToast(data.error || 'Failed to confirm payment', 'error');
        }
    } catch (error) {
        console.error('Error confirming payment:', error);
        showToast('Error confirming payment', 'error');
    }
}

// Helper: Get status class
function getStatusClass(status) {
    const statusMap = {
        'pending': 'pending',
        'accepted': 'preparing',
        'preparing': 'preparing',
        'ready': 'ready',
        'out_for_delivery': 'ready',
        'delivered': 'delivered',
        'completed': 'delivered',
        'sent_to_pharmacy': 'pending',
        'filled': 'ready'
    };
    return statusMap[status] || 'pending';
}

// Helper: Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    if (days < 7) return `${days} day${days > 1 ? 's' : ''} ago`;

    return date.toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

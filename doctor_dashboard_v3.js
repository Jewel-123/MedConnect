/**
 * Doctor Dashboard JavaScript
 * Handles all client-side functionality for the doctor dashboard
 */

console.log('Doctor Dashboard JS Loaded - v2 (Patient History Included)');

// Global state
let currentView = 'dashboard';
let dashboardStats = {};
let medicines = [];
let notifications = [];

// Initialize on page load
document.addEventListener('DOMContentLoaded', function () {
    loadDashboardView();
    loadNotifications();
    setInterval(loadNotifications, 60000); // Refresh every minute
});

// ========================================
// NAVIGATION & VIEW MANAGEMENT
// ========================================

function loadDashboardView() {
    const urlParams = new URLSearchParams(window.location.search);
    currentView = urlParams.get('view') || 'dashboard';

    switch (currentView) {
        case 'dashboard':
            loadDashboard();
            break;
        case 'consultations':
            loadConsultations();
            break;
        case 'patients':
            loadPatients();
            break;
        case 'prescriptions':
            loadPrescriptions();
            break;
        case 'reviews':
            loadReviews();
            break;
        case 'schedule':
            loadSchedule();
            break;
        case 'earnings':
            loadEarnings();
            break;
        case 'profile':
            loadProfile();
            break;
        default:
            loadDashboard();
    }
}

// ========================================
// DASHBOARD HOME
// ========================================

async function loadDashboard() {
    try {
        const response = await fetch('doctor_api.php?action=get_dashboard_stats');
        const result = await response.json();

        if (result.status === 'success') {
            dashboardStats = result.data;
            renderDashboard();
        }
    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

function renderDashboard() {
    const content = `
        <div class="page-title">
            <div>
                <h1>Dashboard Overview</h1>
                <p>Welcome back! Here's your practice summary.</p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon" style="background: var(--primary-light); color: var(--primary);"><i class="ph ph-clipboard-text"></i></div>
                </div>
                <div class="stat-value">${dashboardStats.today_consultations || 0}</div>
                <div class="stat-label">Today's Consultations</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon" style="background: #fef3c7; color: #f59e0b;"><i class="ph ph-clock"></i></div>
                </div>
                <div class="stat-value">${dashboardStats.pending_requests || 0}</div>
                <div class="stat-label">Pending Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon" style="background: #dcfce7; color: #10b981;"><i class="ph ph-calendar-check"></i></div>
                </div>
                <div class="stat-value">${dashboardStats.followups_due || 0}</div>
                <div class="stat-label">Follow-ups Due</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon" style="background: #f3e8ff; color: #a855f7;"><i class="ph ph-star"></i></div>
                </div>
                <div class="stat-value">${dashboardStats.average_rating || '0.0'}</div>
                <div class="stat-label">Average Rating (${dashboardStats.total_reviews || 0} reviews)</div>
            </div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon" style="background: #fff7ed; color: #f59e0b;"><i class="ph ph-currency-dollar"></i></div>
                </div>
                <div class="stat-value">₹${dashboardStats.monthly_earnings || '0.00'}</div>
                <div class="stat-label">Monthly Earnings</div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">Incoming Consultation Requests</span>
                <button class="btn btn-primary btn-sm" onclick="window.location.href='?view=consultations'">View All</button>
            </div>
            <div class="panel-body">
                <div id="dashboardRequests">Loading...</div>
            </div>
        </div>
    `;

    document.getElementById('mainContent').innerHTML = content;
    loadDashboardRequests();

    // Update pending badge
    if (dashboardStats.pending_requests > 0) {
        document.getElementById('pendingBadge').textContent = dashboardStats.pending_requests;
        document.getElementById('pendingBadge').style.display = 'inline-block';
    }
}

async function loadDashboardRequests() {
    try {
        const [consRes, apptRes] = await Promise.all([
            fetch('doctor_api.php?action=get_consultation_requests'),
            fetch('doctor_api.php?action=get_appointment_requests')
        ]);

        const consResult = await consRes.json();
        const apptResult = await apptRes.json();

        const container = document.getElementById('dashboardRequests');
        if (!container) return;

        let allRequests = [];
        if (consResult.status === 'success') {
            allRequests = allRequests.concat(consResult.data.map(c => ({ ...c, type: 'consultation' })));
        }
        if (apptResult.status === 'success') {
            allRequests = allRequests.concat(apptResult.data.map(a => ({ ...a, type: 'appointment' })));
        }

        if (allRequests.length === 0) {
            container.innerHTML = '<p style="text-align:center; color: var(--text-muted); padding: 2rem;">No pending requests</p>';
            return;
        }

        // Sort by created_at (newest first)
        allRequests.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));


        const html = allRequests.map(req => {
            const isEmergency = (req.urgency_level === 'emergency' || req.urgency_badge === 'emergency');
            const highlightStyle = isEmergency ? 'border-left: 4px solid #ef4444; background: #fef2f2;' : '';

            let typeBadge = '';
            if (req.type === 'appointment') {
                typeBadge = '<span class="status-badge" style="font-size: 0.7rem; background: #e0f2fe; color: #075985;">APPOINTMENT</span>';
            } else {
                typeBadge = `<span class="status-badge urgency-${req.urgency_badge || req.urgency_level}" style="font-size: 0.7rem;">${(req.urgency_badge || req.urgency_level).toUpperCase()}</span>`;
            }

            const name = req.patient_name;
            const rawDescription = req.type === 'appointment' ? req.reason : req.symptoms_summary;
            const description = rawDescription === 'No symptoms provided' ? rawDescription : `Symptoms: ${rawDescription}`;

            const timeInfo = `<span><i class="ph ph-clock"></i> ${new Date(req.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                              ${req.type === 'appointment' ? `<span><i class="ph ph-calendar"></i> ${req.scheduled_date}</span>` : `<span><i class="ph ph-globe"></i> ${req.language_preference}</span>`}`;

            const actionButtons = req.type === 'appointment' ? `
                <button class="btn btn-success btn-sm" onclick="confirmAppointment(${req.id})" style="padding: 0.25rem 0.75rem;">Confirm</button>
                <button class="btn btn-outline btn-sm" onclick="declineAppointment(${req.id})" style="padding: 0.25rem 0.75rem;">Decline</button>
            ` : `
                <button class="btn btn-success btn-sm" onclick="acceptConsultation(${req.id})" style="padding: 0.25rem 0.75rem;">Accept</button>
                <button class="btn btn-outline btn-sm" onclick="declineConsultation(${req.id})" style="padding: 0.25rem 0.75rem;">Decline</button>
            `;

            return `
            <div style="border-bottom: 1px solid var(--border); padding: 1rem; ${highlightStyle}">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div style="flex: 1;">
                        <div style="font-weight: 600; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 0.5rem;">
                            ${name}
                            ${typeBadge}
                        </div>
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.5rem; line-height: 1.4;">${description}</p>
                        <div style="display: flex; gap: 0.75rem; font-size: 0.8rem; color: var(--text-muted);">
                            ${timeInfo}
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.4rem;">
                        ${actionButtons}
                    </div>
                </div>
            </div>
        `;
        }).join('');

        container.innerHTML = html;

        // Trigger alert if new emergency arrives
        const currentEmergencies = allRequests.filter(r => r.type !== 'appointment' && r.urgency_level === 'emergency').map(r => r.id);
        if (currentEmergencies.length > 0) {
            const lastKnown = window.lastKnownEmergencies || [];
            const hasNew = currentEmergencies.some(id => !lastKnown.includes(id));

            if (hasNew) {
                console.log("URGENT: New Emergency Consultation Request!");
                // showNotification("Emergency Request", "A new emergency consultation request requires your immediate attention.");
            }
            window.lastKnownEmergencies = currentEmergencies;
        } else {
            window.lastKnownEmergencies = [];
        }
    } catch (error) {
        console.error('Error loading requests:', error);
    }
}

// ========================================
// CONSULTATIONS VIEW
// ========================================

async function loadConsultations() {
    try {
        const [consReqRes, apptReqRes, activeRes] = await Promise.all([
            fetch('doctor_api.php?action=get_consultation_requests'),
            fetch('doctor_api.php?action=get_appointment_requests'),
            fetch('doctor_api.php?action=get_active_consultations')
        ]);

        const consReqResult = await consReqRes.json();
        const apptReqResult = await apptReqRes.json();
        const active = await activeRes.json();

        let allRequests = [];
        if (consReqResult.status === 'success') {
            allRequests = allRequests.concat(consReqResult.data.map(c => ({ ...c, type: 'consultation' })));
        }
        if (apptReqResult.status === 'success') {
            allRequests = allRequests.concat(apptReqResult.data.map(a => ({ ...a, type: 'appointment' })));
        }

        // Sort by created_at (newest first)
        allRequests.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

        renderConsultations(allRequests, active.data || []);
    } catch (error) {
        console.error('Error loading consultations:', error);
    }
}

function renderConsultations(requests, active) {
    const urlParams = new URLSearchParams(window.location.search);
    const highlightId = urlParams.get('highlight');

    const content = `
        <div class="page-title">
            <h1>Consultation Management</h1>
        </div>

        <div class="panel" style="margin-bottom: 2rem;">
            <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center;">
                <span class="panel-title">Active Consultations</span>
                <span class="badge" style="background: var(--primary); color: white; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.8rem;">${active.length}</span>
            </div>
            <div class="panel-body">
                ${active.length === 0 ? '<p style="text-align:center; color: var(--text-muted); padding: 2rem;">No active consultations</p>' :
            active.map(cons => {
                const urgencyBadge = cons.urgency_level || 'routine';

                // Status mapping
                let statusLabel = 'Scheduled';
                let statusClass = 'pending';

                switch (cons.status) {
                    case 'scheduled':
                    case 'accepted':
                    case 'confirmed':
                        statusLabel = cons.type === 'appointment' ? 'Confirmed' : 'Not Started';
                        statusClass = 'pending';
                        break;
                    case 'waiting':
                        statusLabel = 'Waiting';
                        statusClass = 'priority';
                        break;
                    case 'in_progress':
                        statusLabel = 'In Progress';
                        statusClass = 'success';
                        break;
                    case 'paused':
                        statusLabel = 'Paused';
                        statusClass = 'warning';
                        break;
                }

                const isHighlighted = highlightId == cons.id;
                const highlightStyle = isHighlighted ? 'background: #fffbeb; border-left: 4px solid var(--warning);' : '';
                const typeBadge = cons.type === 'appointment' ?
                    '<span class="status-badge" style="font-size: 0.7rem; background: #e0f2fe; color: #075985; border: 1px solid #bae6fd;">APPOINTMENT</span>' :
                    `<span class="status-badge urgency-${urgencyBadge}" style="font-size: 0.7rem;">${urgencyBadge.toUpperCase()}</span>`;

                const rawDesc = cons.type === 'appointment' ? (cons.symptoms || 'No symptoms provided') : (cons.symptoms || 'No symptoms provided');
                const description = rawDesc === 'No symptoms provided' ? rawDesc : `Symptoms: ${rawDesc.substring(0, 80)}`;

                let displayId = cons.type === 'appointment' ? `app-${cons.id}` : `cons-${cons.id}`;
                let targetId = cons.id;
                let targetType = cons.type;

                // If appointment has a linked consultation, use it
                if (cons.type === 'appointment' && cons.linked_consultation_id) {
                    targetId = cons.linked_consultation_id;
                    targetType = 'consultation';
                    displayId = `cons-${targetId}`;
                }

                const timerId = `timer-${displayId}`;

                // State detection for button visibility
                const isNotStarted = (cons.status === 'accepted' || cons.status === 'confirmed');
                const isStarted = (cons.status === 'in_progress');
                const isPaused = (cons.status === 'paused');


                return `
                    <div id="${displayId}" class="active-item" 
                         data-id="${targetId}" 
                         data-type="${targetType}" 
                         data-status="${cons.status}"
                         data-accumulated="${cons.accumulated_seconds || 0}"
                         data-last-resume="${cons.last_resume_at || ''}"
                         onclick="window.location.href='consultation_room.php?id=${targetId}&type=${targetType}'"
                         style="border-bottom: 1px solid var(--border); padding: 1.25rem 0; cursor: pointer; ${highlightStyle}">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0 1rem;">
                            <div>
                                <div style="font-weight: 600; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                    ${cons.patient_name}
                                    ${typeBadge}
                                    <span class="status-badge" style="font-size: 0.7rem; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;">${statusLabel}</span>
                                    <span id="${timerId}" style="font-family: monospace; font-weight: 700; color: var(--primary); margin-left: 0.5rem;">00:00</span>
                                </div>
                                <div style="font-size: 0.85rem; color: var(--text-muted);">${description}...</div>
                                ${cons.type === 'appointment' ? `<div style="font-size: 0.75rem; color: var(--primary); font-weight: 500;"><i class="ph ph-calendar"></i> ${cons.scheduled_date} at ${cons.scheduled_time}</div>` : ''}
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                ${isNotStarted ? `
                                    <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); startConsultation(${targetId}, '${targetType}')">
                                        <i class="ph ph-play-circle"></i> Start
                                    </button>
                                ` : ''}

                                ${isStarted ? `
                                    <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); window.location.href='consultation_room.php?id=${targetId}&type=${targetType}'">
                                        <i class="ph ph-video-camera"></i> Join Room
                                    </button>
                                    <button class="btn btn-outline btn-sm" onclick="event.stopPropagation(); pauseConsultation(${targetId}, '${targetType}')">
                                        <i class="ph ph-pause-circle"></i> Pause
                                    </button>
                                ` : ''}

                                ${isPaused ? `
                                    <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); resumeConsultation(${targetId}, '${targetType}')">
                                        <i class="ph ph-play-circle"></i> Resume
                                    </button>
                                ` : ''}

                                <button class="ph ph-prescription action-btn" title="Prescribe" onclick="event.stopPropagation(); openPrescriptionModal(${targetId}, ${cons.patient_id}, '${targetType}')"></button>
                                <button class="ph ph-check action-btn" title="Complete" onclick="event.stopPropagation(); completeConsultation(${targetId}, '${targetType}')"></button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('')}
            </div>
        </div>

        <div class="panel">
            <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center;">
                <span class="panel-title">Incoming Requests</span>
                <span class="badge" style="background: #ef4444; color: white; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.8rem;">${requests.length}</span>
            </div>
            <div class="panel-body">
                ${requests.length === 0 ? '<p style="text-align:center; color: var(--text-muted); padding: 2rem;">No pending requests</p>' :
            requests.map(req => {
                const isEmergency = req.urgency_level === 'emergency' || req.urgency_badge === 'emergency';
                const isHighlighted = highlightId == req.id;
                let highlightStyle = isEmergency ? 'border: 2px solid #ef4444; background: #fef2f2; animation: pulse-red 2s infinite;' : '';
                if (isHighlighted) highlightStyle = 'border: 2px solid var(--primary); background: #f0f9ff;';

                const typeBadge = req.type === 'appointment' ? '<span class="status-badge" style="font-size: 0.75rem; background: #e0f2fe; color: #075985;">APPOINTMENT</span>' : `<span class="status-badge urgency-${req.urgency_badge || req.urgency_level}" style="font-size: 0.75rem;">${(req.urgency_badge || req.urgency_level).toUpperCase()}</span>`;
                const rawDescription = req.type === 'appointment' ? req.reason : req.symptoms_summary;
                const description = rawDescription === 'No symptoms provided' ? rawDescription : `Symptoms: ${rawDescription}`;
                const timeStr = new Date(req.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                return `
                    <div id="cons-${req.id}" style="border-bottom: 1px solid var(--border); padding: 1.25rem; margin-bottom: 0.5rem; border-radius: 8px; ${highlightStyle}">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div style="flex: 1;">
                                <div style="font-weight: 700; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                    ${req.patient_name} (${req.patient_age})
                                    ${typeBadge}
                                    ${isEmergency ? '<span style="color: #ef4444; font-weight: 800; font-size: 0.7rem; text-transform: uppercase;"><i class="ph ph-warning-circle"></i> HIGH PRIORITY</span>' : ''}
                                </div>
                                <p style="color: #475569; font-size: 0.95rem; margin-bottom: 0.75rem; font-weight: 500;">${description}</p>
                                <div style="display: flex; gap: 1.25rem; font-size: 0.85rem; color: #64748b;">
                                    <span><i class="ph ph-${req.type === 'appointment' ? 'calendar' : (req.consultation_mode === 'video' ? 'video-camera' : req.consultation_mode === 'audio' ? 'microphone' : 'chat-circle')}"></i> ${req.type === 'appointment' ? req.scheduled_date : req.consultation_mode}</span>
                                    ${req.type === 'appointment' ? `<span><i class="ph ph-clock"></i> ${req.scheduled_time}</span>` : `<span><i class="ph ph-globe"></i> ${req.language_preference}</span>`}
                                    ${req.duration ? `<span><i class="ph ph-hourglass"></i> ${req.duration}</span>` : ''}
                                    <span><i class="ph ph-bell"></i> ${timeStr}</span>
                                </div>
                            </div>
                            <div style="display: flex; gap: 0.75rem; align-items: center; height: 100%;">
                                ${req.type === 'appointment' ? `
                                    <button class="btn btn-success" style="padding: 0.5rem 1.25rem;" onclick="confirmAppointment(${req.id})">Confirm</button>
                                    <button class="btn btn-outline" style="padding: 0.5rem 1.25rem;" onclick="declineAppointment(${req.id})">Decline</button>
                                ` : `
                                    <button class="btn btn-success" style="padding: 0.5rem 1.25rem;" onclick="acceptConsultation(${req.id})">Accept</button>
                                    <button class="btn btn-outline" style="padding: 0.5rem 1.25rem;" onclick="declineConsultation(${req.id})">Decline</button>
                                `}
                            </div>
                        </div>
                    </div>
                `}).join('')}
            </div>
        </div>

        <style>
            @keyframes pulse-red {
                0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
                70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
                100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
            }
        </style>
    `;

    document.getElementById('mainContent').innerHTML = content;

    // Scroll to highlighted item
    if (highlightId) {
        setTimeout(() => {
            const el = document.getElementById(`cons-${highlightId}`);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 100);
    }
}

async function acceptConsultation(consultationId) {
    if (!await confirm('Accept this consultation request?')) return;

    const element = document.getElementById(`cons-${consultationId}`);
    try {
        if (element) {
            element.style.opacity = '0.5';
            element.style.pointerEvents = 'none';
        }

        const formData = new FormData();
        formData.append('action', 'accept_consultation');
        formData.append('consultation_id', consultationId);

        const response = await fetch('doctor_api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status === 'success') {
            await alert('Consultation accepted successfully!');
            if (element) {
                element.style.transition = 'all 0.3s ease';
                element.style.transform = 'translateX(20px)';
                element.style.opacity = '0';
                setTimeout(() => {
                    element.remove();
                    refreshDashboardContext();
                }, 300);
            } else {
                refreshDashboardContext();
            }
        } else {
            if (element) {
                element.style.opacity = '1';
                element.style.pointerEvents = 'auto';
            }
            await alert('Error: ' + result.message);
        }
    } catch (error) {
        if (element) {
            element.style.opacity = '1';
            element.style.pointerEvents = 'auto';
        }
        console.error('Error accepting consultation:', error);
        await alert('Failed to accept consultation');
    }
}

async function declineConsultation(consultationId) {
    const reason = await prompt('Please provide a reason for declining (optional):');
    if (reason === null) return; // User cancelled

    try {
        const formData = new FormData();
        formData.append('action', 'decline_consultation');
        formData.append('consultation_id', consultationId);
        formData.append('reason', reason || '');

        const response = await fetch('doctor_api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status === 'success') {
            await alert('Consultation declined successfully');
            refreshDashboardContext();
        } else {
            await alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error declining consultation:', error);
        await alert('Failed to decline consultation');
    }
}

// Helper to refresh all relevant dashboard components
function refreshDashboardContext() {
    loadDashboard();
    loadDashboardRequests();
    if (currentView === 'consultations') {
        loadConsultations();
    }
}

async function startSession(consultationId, patientId) {
    try {
        const formData = new FormData();
        formData.append('action', 'start_session');
        formData.append('consultation_id', consultationId);

        const response = await fetch('doctor_api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status === 'success') {
            // Navigate to the consultation room
            window.location.href = `consultation_room.php?id=${consultationId}&type=consultation`;
        } else {
            console.warn('Start session API returned:', result);
            // Fallback: Still try to go to the room if it's an error about session already existing 
            // or just to be safe if the status was updated.
            window.location.href = `consultation_room.php?id=${consultationId}&type=consultation`;
        }
    } catch (error) {
        console.error('Error starting session:', error);
        // Final fallback: redirect anyway so the user isn't stuck
        window.location.href = `consultation_room.php?id=${consultationId}&type=consultation`;
    }
}

// ========================================
// PRESCRIPTION MODAL
// ========================================

function openPrescriptionModal(requestId, patientId, type = 'consultation') {
    console.log("[Prescription] Opening modal for:", type, "ID:", requestId, "PatientID:", patientId);

    // Reset form FIRST, so we don't clear the IDs we set below
    document.getElementById('prescriptionForm').reset();

    if (type === 'appointment') {
        document.getElementById('prescAppointmentId').value = requestId;
        document.getElementById('prescConsultationId').value = '';
    } else {
        document.getElementById('prescConsultationId').value = requestId;
        document.getElementById('prescAppointmentId').value = '';
    }
    document.getElementById('prescPatientId').value = patientId;

    medicines = [];
    document.getElementById('medicinesList').innerHTML = '';
    addMedicine(); // Add one medicine field by default
    document.getElementById('prescriptionModal').style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

async function confirmAppointment(appointmentId) {
    if (!await confirm('Confirm this appointment?')) return;

    const element = document.querySelector(`[onclick="confirmAppointment(${appointmentId})"]`)?.closest('div[style*="border-bottom"]');

    try {
        if (element) {
            element.style.opacity = '0.5';
            element.style.pointerEvents = 'none';
        }

        const formData = new FormData();
        formData.append('action', 'confirm_appointment');
        formData.append('appointment_id', appointmentId);

        const response = await fetch('doctor_api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status === 'success') {
            await alert('Appointment confirmed successfully!');
            if (element) {
                element.style.transition = 'all 0.3s ease';
                element.style.transform = 'translateX(20px)';
                element.style.opacity = '0';
                setTimeout(() => {
                    element.remove();
                    refreshDashboardContext();
                }, 300);
            } else {
                refreshDashboardContext();
            }
        } else {
            if (element) {
                element.style.opacity = '1';
                element.style.pointerEvents = 'auto';
            }
            await alert('Error: ' + result.message);
        }
    } catch (error) {
        if (element) {
            element.style.opacity = '1';
            element.style.pointerEvents = 'auto';
        }
        console.error('Error confirming appointment:', error);
        await alert('Failed to confirm appointment');
    }
}

async function declineAppointment(appointmentId) {
    const reason = await prompt('Please provide a reason for declining (optional):');
    if (reason === null) return; // User cancelled

    try {
        const formData = new FormData();
        formData.append('action', 'decline_appointment');
        formData.append('appointment_id', appointmentId);
        formData.append('reason', reason || '');

        const response = await fetch('doctor_api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status === 'success') {
            await alert('Appointment declined successfully');
            refreshDashboardContext();
        } else {
            await alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error declining appointment:', error);
        await alert('Failed to decline appointment');
    }
}

function addMedicine() {
    const index = medicines.length;
    medicines.push({});

    const html = `
        <div class="medicine-item" style="border: 1px solid var(--border); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 0.5rem; margin-bottom: 0.5rem;">
                <input type="text" class="form-input" placeholder="Medicine name" id="medName${index}" required>
                <input type="text" class="form-input" placeholder="Dosage" id="medDosage${index}" required>
                <input type="number" class="form-input" placeholder="Qty" id="medQty${index}" value="1" min="1">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                <input type="text" class="form-input" placeholder="Frequency (e.g., 3 times daily)" id="medFreq${index}" required>
                <input type="text" class="form-input" placeholder="Duration (e.g., 7 days)" id="medDuration${index}" required>
            </div>
            <textarea class="form-input" placeholder="Instructions (optional)" id="medInstr${index}" style="margin-top: 0.5rem; min-height: 60px;"></textarea>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeMedicine(${index})" style="margin-top: 0.5rem;">Remove</button>
        </div>
    `;

    document.getElementById('medicinesList').insertAdjacentHTML('beforeend', html);
}

function removeMedicine(index) {
    medicines[index] = null;
    document.querySelectorAll('.medicine-item')[index].remove();
}

async function savePrescription() {
    const consultationId = document.getElementById('prescConsultationId').value;
    const appointmentId = document.getElementById('prescAppointmentId').value;
    const patientId = document.getElementById('prescPatientId').value;
    const icdCode = document.getElementById('icdCode').value;
    const diagnosis = document.getElementById('diagnosis').value;
    const followUpDate = document.getElementById('followUpDate').value;
    const notesPatient = document.getElementById('notesPatient').value;
    const notesPharmacy = document.getElementById('notesPharmacy').value;

    if (!diagnosis) {
        await alert('Diagnosis is required');
        return;
    }

    // Collect medicines
    const medicineData = [];
    medicines.forEach((med, index) => {
        if (med !== null) {
            const name = document.getElementById(`medName${index}`)?.value;
            const dosage = document.getElementById(`medDosage${index}`)?.value;
            const frequency = document.getElementById(`medFreq${index}`)?.value;
            const duration = document.getElementById(`medDuration${index}`)?.value;
            const quantity = document.getElementById(`medQty${index}`)?.value || 1;
            const instructions = document.getElementById(`medInstr${index}`)?.value || '';

            if (name && dosage && frequency && duration) {
                medicineData.push({
                    name, dosage, frequency, duration, quantity, instructions
                });
            }
        }
    });

    // ========================================
    // INTELLIGENT SAFETY CHECKS (Mock DB)
    // ========================================
    const warnings = [];
    const medsList = medicineData.map(m => m.name.toLowerCase());

    // 1. Drug-Drug Interactions
    if (medsList.includes('aspirin') && medsList.includes('warfarin')) {
        warnings.push("CRITICAL: Aspirin and Warfarin interaction risk (Bleeding).");
    }
    if (medsList.includes('sildenafil') && medsList.includes('nitroglycerin')) {
        warnings.push("CRITICAL: Sildenafil and Nitroglycerin interaction (Hypotension).");
    }

    // 2. Allergy Check (Mock - in production this comes from patient DB)
    // We assume the frontend has access to 'knownAllergies' variable or we check common ones
    const currentAllergies = "penicillin"; // This should be dynamic
    if (medsList.some(m => m.includes('penicillin') || m.includes('amoxicillin')) && currentAllergies.includes('penicillin')) {
        warnings.push("PATIENT ALLERGY ALERT: Patient is allergic to Penicillin.");
    }

    if (warnings.length > 0) {
        const proceed = await confirm(`SAFETY WARNINGS DETECTED:\n\n${warnings.join('\n')}\n\nDo you want to override and proceed?`);
        if (!proceed) return;
    }

    try {
        const response = await fetch('doctor_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'save_prescription',
                consultation_id: consultationId || null,
                appointment_id: appointmentId || null,
                patient_id: patientId,
                icd_code: icdCode,
                diagnosis: diagnosis,
                medicines: medicineData,
                tests: [],
                follow_up_date: followUpDate,
                notes_for_patient: notesPatient,
                notes_for_pharmacy: notesPharmacy
            })
        });

        const result = await response.json();
        console.log('Prescription save response:', result); // Debug logging

        if (result.status === 'success') {
            await alert('Prescription saved successfully!');
            closeModal('prescriptionModal');

            // Ask if they want to complete the consultation/appointment
            if (await confirm(`Mark this ${consultationId ? 'consultation' : 'appointment'} as completed?`)) {
                completeConsultation(consultationId || appointmentId, consultationId ? 'consultation' : 'appointment');
            }
        } else {
            await alert('Error: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error saving prescription:', error);
        await alert('Failed to save prescription: ' + error.message);
    }
}

async function completeConsultation(requestId, type = 'consultation') {
    try {
        const formData = new FormData();
        formData.append('action', 'complete_consultation');
        if (type === 'appointment') {
            formData.append('appointment_id', requestId);
        } else {
            formData.append('consultation_id', requestId);
        }

        const response = await fetch('doctor_api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status === 'success') {
            await alert('Consultation completed successfully!');
            loadConsultations();
            loadDashboard();
        } else {
            await alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error completing consultation:', error);
    }
}


// Start consultation - change from 'accepted' to 'in_progress' and go to consultation room
async function startConsultation(requestId, type = 'consultation') {
    if (!await confirm('Start this consultation?')) return;

    try {
        const formData = new FormData();
        formData.append('action', 'start_consultation');
        if (type === 'appointment') {
            formData.append('appointment_id', requestId);
        } else {
            formData.append('consultation_id', requestId);
        }

        const response = await fetch('doctor_api.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.status === 'success') {
            showNotification(data.message || 'Consultation started successfully', 'success');
            // Redirect to consultation room using the (possibly new) consultation_id
            const targetId = data.consultation_id || requestId;
            window.location.href = `consultation_room.php?id=${targetId}&type=consultation`;
        } else {
            showNotification(data.message || data.error || 'Failed to start consultation', 'error');
        }
    } catch (error) {
        showNotification('Error starting consultation', 'error');
        console.error('Start consultation error:', error);
    }
}

// Resume consultation - change from 'paused' to 'in_progress'
async function resumeConsultation(requestId, type = 'consultation') {
    try {
        const formData = new FormData();
        formData.append('action', 'resume_consultation');
        if (type === 'appointment') {
            formData.append('appointment_id', requestId);
        } else {
            formData.append('consultation_id', requestId);
        }

        const response = await fetch('doctor_api.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.status === 'success') {
            showNotification(data.message || 'Consultation resumed', 'success');
            loadConsultations();
            loadDashboard();
        } else {
            showNotification(data.message || data.error || 'Failed to resume consultation', 'error');
        }
    } catch (error) {
        showNotification('Error resuming consultation', 'error');
        console.error('Resume consultation error:', error);
    }
}

// Pause consultation - change from 'in_progress' to 'paused'
async function pauseConsultation(requestId, type = 'consultation') {
    try {
        const formData = new FormData();
        formData.append('action', 'pause_consultation');
        if (type === 'appointment') {
            formData.append('appointment_id', requestId);
        } else {
            formData.append('consultation_id', requestId);
        }

        const response = await fetch('doctor_api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status === 'success') {
            showNotification(result.message || 'Consultation paused', 'success');
            loadConsultations();
            loadDashboard();
        } else {
            showNotification(result.message || 'Error pausing consultation', 'error');
        }
    } catch (error) {
        console.error('Error pausing consultation:', error);
    }
}

// ========================================
// REVIEWS & RATINGS VIEW
// ========================================

async function loadReviews() {
    try {
        const response = await fetch('doctor_api.php?action=get_reviews');
        const result = await response.json();

        if (result.status === 'success') {
            renderReviews(result.data);
        }
    } catch (error) {
        console.error('Error loading reviews:', error);
    }
}

function renderReviews(reviews) {
    const avgRating = reviews.length > 0
        ? (reviews.reduce((acc, r) => acc + parseInt(r.rating), 0) / reviews.length).toFixed(1)
        : '0.0';

    const content = `
        <div class="page-title">
            <h1>Reviews & Ratings</h1>
            <p>What your patients are saying about their experience.</p>
        </div>

        <div class="stats-grid" style="margin-bottom: 2rem;">
            <div class="stat-card">
                <div class="stat-label">Average Rating</div>
                <div class="stat-value" style="display: flex; align-items: center; gap: 0.5rem; color: #f59e0b;">
                    ${avgRating} <i class="ph-fill ph-star"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Reviews</div>
                <div class="stat-value">${reviews.length}</div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">Approved Feedback</span>
            </div>
            <div class="panel-body">
                ${reviews.length === 0 ? `
                    <div style="text-align: center; padding: 4rem 2rem; color: var(--text-muted);">
                        <i class="ph ph-star-half" style="font-size: 3rem; opacity: 0.2;"></i>
                        <p style="margin-top: 1rem;">No approved reviews yet.</p>
                    </div>
                ` : reviews.map(rev => `
                    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); last-child: border-bottom: none;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                            <div>
                                <div style="font-weight: 600; font-size: 1rem; margin-bottom: 0.25rem;">${rev.patient_name}</div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">${new Date(rev.created_at).toLocaleDateString()}</div>
                            </div>
                            <div style="display: flex; gap: 2px; color: #f59e0b;">
                                ${Array(5).fill(0).map((_, i) => `<i class="ph-fill ph-star" style="${i < rev.rating ? '' : 'color: #e2e8f0;'}"></i>`).join('')}
                            </div>
                        </div>
                        <p style="color: #475569; line-height: 1.6; font-size: 0.95rem;">${rev.review_text || '<i>No comment left</i>'}</p>
                    </div>
                `).join('')}
            </div>
        </div>
    `;

    document.getElementById('mainContent').innerHTML = content;
}

// ========================================
// PATIENTS VIEW
// ========================================

async function loadPatients() {
    try {
        const response = await fetch('doctor_api.php?action=get_patient_list');
        const result = await response.json();

        if (result.status === 'success') {
            renderPatients(result.data);
        }
    } catch (error) {
        console.error('Error loading patients:', error);
    }
}

function renderPatients(patients) {
    const content = `
        <div class="page-title">
            <h1>My Patients</h1>
        </div>

        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">Patient List (${patients.length})</span>
                <input type="text" placeholder="Search patients..." style="padding: 0.5rem; border: 1px solid var(--border); border-radius: 6px;" onkeyup="searchPatients(this.value)">
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Email</th>
                            <th>Last Consultation</th>
                            <th>Total Consultations</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${patients.map(patient => `
                            <tr>
                                <td>${patient.full_name}</td>
                                <td>${patient.email}</td>
                                <td>${patient.last_consultation ? new Date(patient.last_consultation).toLocaleDateString() : 'N/A'}</td>
                                <td>${patient.total_consultations}</td>
                                <td>
                                    <button class="btn btn-outline btn-sm" onclick="viewPatientHistory(${patient.id})">View History</button>
                                    <button class="btn btn-outline btn-sm" onclick="window.location.href='medical_records.php?patient_id=${patient.id}'">Medical Records</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;

    document.getElementById('mainContent').innerHTML = content;
}

async function viewPatientHistory(patientId) {
    const modal = document.getElementById('patientHistoryModal');
    const content = document.getElementById('patientHistoryContent');
    modal.style.display = 'flex';
    content.innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--text-muted);"><i class="ph ph-spinner ph-spin" style="font-size: 2rem;"></i><p>Loading patient record...</p></div>';

    try {
        const response = await fetch(`doctor_api.php?action=get_patient_history&patient_id=${patientId}`);
        const result = await response.json();

        if (result.status === 'success') {
            const p = result.patient;

            // Extract allergies from medical summary if possible, or show "None on file"
            let allergies = "None recorded";
            if (p.medical_history_summary && p.medical_history_summary.toLowerCase().includes('allergies:')) {
                const parts = p.medical_history_summary.split(/allergies:/i);
                if (parts[1]) {
                    allergies = parts[1].split('\n')[0].trim();
                }
            }

            content.innerHTML = `
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
                    <div>
                        <div style="background: var(--bg); padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem;">
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                <div class="avatar" style="width: 64px; height: 64px; font-size: 1.5rem;">${p.full_name.charAt(0)}</div>
                                <div>
                                    <h3 style="margin:0;">${p.full_name}</h3>
                                    <p style="color: var(--text-muted); font-size: 0.9rem;">${p.email}</p>
                                </div>
                            </div>
                            <div style="font-size: 0.9rem;">
                                <div style="margin-bottom: 0.5rem;"><strong>DOB:</strong> ${new Date(p.date_of_birth).toLocaleDateString()}</div>
                                <div style="margin-bottom: 0.5rem;"><strong>Gender:</strong> ${p.gender || 'N/A'}</div>
                                <div style="margin-bottom: 0.5rem;"><strong>Phone:</strong> ${p.phone || 'N/A'}</div>
                            </div>
                        </div>

                        <div style="background: #fff1f2; border: 1px solid #e11d48; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                            <h4 style="color: #be123c; margin-bottom: 0.5rem; font-size: 0.9rem; text-transform: uppercase;">⚠️ Allergies</h4>
                            <p style="color: #881337; font-weight: 500;">${allergies}</p>
                        </div>

                        <div style="margin-bottom: 1rem;">
                            <h4 style="margin-bottom: 0.5rem;">Medical Summary</h4>
                            <p style="font-size: 0.9rem; line-height: 1.6; color: var(--text-muted); background: var(--bg); padding: 1rem; border-radius: 8px;">
                                ${p.medical_history_summary || 'No medical summary available.'}
                            </p>
                        </div>
                    </div>

                    <div style="overflow-y: auto; max-height: 500px; padding-right: 0.5rem;">
                        <h4 style="border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1rem;">Recent Consultations</h4>
                        <div style="margin-bottom: 2rem;">
                            ${result.consultation_history.length === 0 ? '<p class="text-muted">No past consultations</p>' :
                    result.consultation_history.map(c => `
                                    <div style="border-left: 3px solid var(--primary); padding-left: 1rem; margin-bottom: 1rem;">
                                        <div style="display: flex; justify-content: space-between;">
                                            <strong style="font-size: 0.95rem;">${new Date(c.created_at).toLocaleDateString()}</strong>
                                            <span class="status-badge bg-blue">${c.status}</span>
                                        </div>
                                        <p style="margin: 0.25rem 0; font-size: 0.9rem;">${c.symptoms}</p>
                                        ${c.private_notes ? `<div style="margin-top:0.5rem; font-size:0.85rem; color:var(--secondary); background:var(--bg); padding:0.5rem; border-radius:4px; white-space: pre-wrap;"><strong>Clinical Notes:</strong><br>${c.private_notes}</div>` : ''}
                                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-top:0.25rem;">Dr. ${c.doctor_name || 'Unknown'}</div>
                                    </div>
                                `).join('')}
                        </div>

                        <h4 style="border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1rem;">Medication History</h4>
                        <div>
                            ${result.prescription_history.length === 0 ? '<p class="text-muted">No prescriptions found</p>' :
                    result.prescription_history.map(rx => `
                                    <div style="background: var(--bg); padding: 1rem; border-radius: 8px; margin-bottom: 0.75rem;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                            <strong>${new Date(rx.created_at).toLocaleDateString()}</strong>
                                            <span style="font-size: 0.85rem; color: var(--text-muted);">Dr. ${rx.doctor_name}</span>
                                        </div>
                                        <div style="font-size: 0.9rem;"><strong>Diagnosis:</strong> ${rx.diagnosis}</div>
                                    </div>
                                `).join('')}
                        </div>
                    </div>
                </div>
            `;
        } else {
            content.innerHTML = `<div style="text-align: center; padding: 2rem; color: var(--danger);">Error loading data: ${result.message}</div>`;
        }
    } catch (error) {
        console.error('Error loading patient history:', error);
        content.innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--danger);">Failed to load patient record.</div>';
    }
}

// ========================================
// PRESCRIPTIONS VIEW
// ========================================

async function loadPrescriptions() {
    const content = `
        <div class="page-title">
            <h1>Prescriptions</h1>
        </div>
        <div class="panel">
            <div class="panel-body">
                <p style="text-align: center; color: var(--text-muted); padding: 3rem;">
                    Prescription history will be displayed here.
                </p>
            </div>
        </div>
    `;

    document.getElementById('mainContent').innerHTML = content;
}

// ========================================
// REVIEWS VIEW
// ========================================

async function loadReviews() {
    try {
        const response = await fetch('doctor_api.php?action=get_reviews');
        const result = await response.json();

        if (result.status === 'success') {
            renderReviews(result.data);
        }
    } catch (error) {
        console.error('Error loading reviews:', error);
    }
}

function renderReviews(reviews) {
    const content = `
        <div class="page-title">
            <h1>Reviews & Ratings</h1>
        </div>

        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">Patient Feedback (${reviews.length})</span>
            </div>
            <div class="panel-body">
                ${reviews.length === 0 ? '<p style="text-align:center; color: var(--text-muted); padding: 2rem;">No reviews yet</p>' :
            reviews.map(review => `
                    <div style="border-bottom: 1px solid var(--border); padding: 1rem 0;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <div>
                                <strong>${review.patient_name}</strong>
                                <span style="margin-left: 1rem; color: var(--warning);">${'★'.repeat(review.rating)}${'☆'.repeat(5 - review.rating)}</span>
                            </div>
                            <span style="font-size: 0.85rem; color: var(--text-muted);">${new Date(review.created_at).toLocaleDateString()}</span>
                        </div>
                        <p style="color: var(--text-muted); margin-bottom: 0.5rem;">${review.review_text || 'No comment'}</p>
                        ${review.doctor_response ?
                    `<div style="background: var(--bg); padding: 0.75rem; border-radius: 6px; margin-top: 0.5rem;">
                                <strong>Your Response:</strong> ${review.doctor_response}
                            </div>` :
                    `<button class="btn btn-outline btn-sm" onclick="respondToReview(${review.id})">Respond</button>`
                }
                    </div>
                `).join('')}
            </div>
        </div>
    `;

    document.getElementById('mainContent').innerHTML = content;
}

async function respondToReview(reviewId) {
    const response = await prompt('Enter your response:');
    if (!response) return;

    try {
        const formData = new FormData();
        formData.append('action', 'respond_to_review');
        formData.append('review_id', reviewId);
        formData.append('response', response);

        const res = await fetch('doctor_api.php', {
            method: 'POST',
            body: formData
        });

        const result = await res.json();

        if (result.status === 'success') {
            await alert('Response posted successfully!');
            loadReviews();
        } else {
            await alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error responding to review:', error);
        await alert('Failed to post response');
    }
}

// ========================================
// SCHEDULE VIEW
// ========================================

async function loadSchedule() {
    try {
        const response = await fetch('doctor_api.php?action=get_schedule');
        const result = await response.json();

        if (result.status === 'success') {
            renderSchedule(result.availability, result.upcoming_consultations);
        }
    } catch (error) {
        console.error('Error loading schedule:', error);
    }
}

function renderSchedule(availability, upcoming) {
    const content = `
        <div class="page-title">
            <h1>Schedule & Availability</h1>
        </div>

        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">Weekly Availability</span>
            </div>
            <div class="panel-body">
                ${availability.length === 0 ? '<p style="text-align:center; color: var(--text-muted);">No availability set</p>' :
            availability.map(slot => `
                    <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                        <div>
                            <strong>${slot.day_of_week}</strong>
                            <span style="margin-left: 1rem; color: var(--text-muted);">${slot.start_time} - ${slot.end_time}</span>
                        </div>
                        <span class="status-badge ${slot.is_available ? 'bg-green' : 'bg-red'}">${slot.is_available ? 'Available' : 'Unavailable'}</span>
                    </div>
                `).join('')}
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">Upcoming Consultations</span>
            </div>
            <div class="panel-body">
                ${upcoming.length === 0 ? '<p style="text-align:center; color: var(--text-muted);">No upcoming consultations</p>' :
            upcoming.map(cons => `
                    <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                        <strong>${cons.patient_name}</strong>
                        <span style="margin-left: 1rem; color: var(--text-muted);">${new Date(cons.assigned_at).toLocaleString()}</span>
                    </div>
                `).join('')}
            </div>
        </div>
    `;

    document.getElementById('mainContent').innerHTML = content;
}

// ========================================
// EARNINGS VIEW
// ========================================

async function loadEarnings() {
    console.log('Loading Earnings...');
    try {
        const response = await fetch('doctor_api.php?action=get_earnings&period=current_month');
        const text = await response.text(); // Get raw text first to debug
        console.log('Earnings Raw Response:', text);

        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('JSON Parse Error:', e);
            document.getElementById('mainContent').innerHTML = `<div class="error-state">Server Error: Invalid JSON response. <br><pre>${text.substring(0, 100)}</pre></div>`;
            return;
        }

        if (result.status === 'success') {
            console.log('Earnings Data:', result);
            renderEarnings(result.summary || {}, result.details || []);
        } else {
            console.error('API Error:', result.message);
            document.getElementById('mainContent').innerHTML = `<div class="error-state">API Error: ${result.message}</div>`;
        }
    } catch (error) {
        console.error('Error loading earnings:', error);
        document.getElementById('mainContent').innerHTML = `<div class="error-state">Network Error: ${error.message}</div>`;
    }
}

function renderEarnings(summary, details) {
    const content = `
        <div class="page-title">
            <h1>Earnings & Analytics</h1>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Gross</div>
                <div class="stat-value">₹${parseFloat(summary.total_gross || 0).toFixed(2)}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Platform Commission (10%)</div>
                <div class="stat-value">₹${parseFloat(summary.total_commission || 0).toFixed(2)}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Net Earnings</div>
                <div class="stat-value">₹${parseFloat(summary.total_net || 0).toFixed(2)}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Consultations</div>
                <div class="stat-value">${summary.total_consultations || 0}</div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">Earnings Breakdown</span>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Consultation</th>
                            <th>Gross Amount</th>
                            <th>Commission</th>
                            <th>Net Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${details.map(earning => `
                            <tr>
                                <td>${new Date(earning.created_at).toLocaleDateString()}</td>
                                <td>${earning.symptoms.substring(0, 50)}...</td>
                                <td>₹${parseFloat(earning.gross_amount).toFixed(2)}</td>
                                <td>₹${parseFloat(earning.platform_commission_amount).toFixed(2)}</td>
                                <td>₹${parseFloat(earning.net_amount).toFixed(2)}</td>
                                <td><span class="status-badge bg-green">Completed</span></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;

    document.getElementById('mainContent').innerHTML = content;
}

// ========================================
// PROFILE VIEW
// ========================================

async function loadProfile() {
    const content = `
        <div class="page-title">
            <h1>Profile & Settings</h1>
        </div>

        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">Professional Information</span>
            </div>
            <div class="panel-body">
                <p style="text-align: center; color: var(--text-muted); padding: 3rem;">
                    Profile management interface will be displayed here.
                </p>
            </div>
        </div>
    `;

    document.getElementById('mainContent').innerHTML = content;
}

// ========================================
// NOTIFICATIONS
// ========================================

async function loadNotifications() {
    try {
        const response = await fetch('doctor_api.php?action=get_notifications');
        const result = await response.json();

        if (result.status === 'success') {
            notifications = result.data;
            renderNotifications();

            // Show dot if there are unread notifications
            const unreadCount = notifications.filter(n => !n.is_read).length;
            if (unreadCount > 0) {
                document.getElementById('notifDot').style.display = 'block';
            } else {
                document.getElementById('notifDot').style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
}

function renderNotifications() {
    const notifList = document.getElementById('notifList');

    if (notifications.length === 0) {
        notifList.innerHTML = `
            <div class="notif-empty">
                <i class="ph ph-bell-slash" style="font-size: 3rem; opacity: 0.3;"></i>
                <p style="margin-top: 1rem;">No notifications</p>
            </div>
        `;
        return;
    }

    const html = notifications.map(notif => {
        const timeAgo = getTimeAgo(notif.created_at);
        const unreadClass = notif.is_read ? '' : 'unread';

        // Define icons and colors based on type
        let icon = '<i class="ph ph-bell"></i>';
        let iconClass = 'notif-icon-default';
        let priorityStyle = '';

        if (notif.notification_type === 'new_consultation' || notif.notification_type === 'consultation_assigned') {
            icon = '<i class="ph ph-clipboard-text"></i>';
            iconClass = 'notif-icon-action';
            priorityStyle = 'border-left: 3px solid var(--danger);';
        } else if (notif.notification_type === 'consultation_accepted') {
            icon = '<i class="ph ph-info"></i>';
            iconClass = 'notif-icon-info';
        } else if (notif.notification_type === 'review_received') {
            icon = '<i class="ph ph-chat-centered-dots"></i>';
            iconClass = 'notif-icon-review';
        }

        return `
            <div class="notif-item ${unreadClass}" onclick="handleNotificationClick(${notif.id}, ${notif.related_id}, '${notif.notification_type}')" style="${priorityStyle}">
                <div style="display: flex; gap: 0.75rem;">
                    <div class="notif-icon ${iconClass}">${icon}</div>
                    <div style="flex: 1;">
                        <div class="notif-title">${notif.title}</div>
                        <div class="notif-message">${notif.message}</div>
                        <div class="notif-time">${timeAgo}</div>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    notifList.innerHTML = html;
}

function getTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);

    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
    if (seconds < 604800) return Math.floor(seconds / 86400) + ' days ago';
    return date.toLocaleDateString();
}

function toggleNotifications() {
    const panel = document.getElementById('notifPanel');
    panel.classList.toggle('show');

    // Close when clicking outside
    if (panel.classList.contains('show')) {
        setTimeout(() => {
            document.addEventListener('click', closeNotificationsOnClickOutside);
        }, 100);
    } else {
        document.removeEventListener('click', closeNotificationsOnClickOutside);
    }
}

function closeNotificationsOnClickOutside(event) {
    const panel = document.getElementById('notifPanel');
    const wrapper = document.querySelector('.notification-wrapper');

    if (!wrapper.contains(event.target)) {
        panel.classList.remove('show');
        document.removeEventListener('click', closeNotificationsOnClickOutside);
    }
}

async function handleNotificationClick(notifId, relatedId, type) {
    // Mark as read
    await markNotificationRead(notifId);

    // Navigate based on type
    if (type === 'new_consultation' || type === 'consultation_assigned') {
        // Navigate to consultations and potentially scroll to the specific one
        window.location.href = `?view=consultations&highlight=${relatedId}`;
    } else if (type === 'follow_up_due') {
        window.location.href = '?view=patients';
    } else if (type === 'review_received') {
        window.location.href = '?view=reviews';
    } else if (type === 'consultation_accepted') {
        // Just refresh the current view or show a message, it's informational
        console.log('Informational notification clicked:', relatedId);
    }
}

async function markNotificationRead(notifId) {
    try {
        const formData = new FormData();
        formData.append('action', 'mark_notification_read');
        formData.append('notification_id', notifId);

        await fetch('doctor_api.php', {
            method: 'POST',
            body: formData
        });

        // Update local state
        const notif = notifications.find(n => n.id === notifId);
        if (notif) notif.is_read = true;
        renderNotifications();

        // Update dot
        const unreadCount = notifications.filter(n => !n.is_read).length;
        document.getElementById('notifDot').style.display = unreadCount > 0 ? 'block' : 'none';
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
}

async function markAllRead() {
    try {
        const formData = new FormData();
        formData.append('action', 'mark_all_notifications_read');

        const response = await fetch('doctor_api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status === 'success') {
            // Clear all notifications
            notifications = [];
            renderNotifications();
            document.getElementById('notifDot').style.display = 'none';
        }
    } catch (error) {
        console.error('Error marking all as read:', error);
    }
}

// ========================================
// AVAILABILITY TOGGLE
// ========================================

function toggleAvailability() {
    const toggle = document.getElementById('availabilityToggle');
    const text = document.getElementById('availabilityText');

    toggle.classList.toggle('active');

    if (toggle.classList.contains('active')) {
        text.textContent = 'Online';
        text.style.color = 'var(--success)';
    } else {
        text.textContent = 'Offline';
        text.style.color = 'var(--text-muted)';
    }
}


// ========================================
// UTILITY FUNCTIONS
// ========================================

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}

async function logout() {
    if (await confirm('Are you sure you want to logout?')) {
        sessionStorage.removeItem('currentUser');
        window.location.href = 'logout.php';
    }
}

function searchPatients(query) {
    // Implement patient search
    console.log('Searching for:', query);
}

// End of file cleanup - removing duplicates

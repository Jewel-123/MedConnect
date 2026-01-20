/**
 * Doctor Dashboard JavaScript
 * Handles all client-side functionality for the doctor dashboard
 */

console.log('Doctor Dashboard JS Loaded - v2 (Patient History Included)');

// Global state
let currentView = 'dashboard';
let dashboardStats = {};
let medicines = [];

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
                <div class="stat-value">$${dashboardStats.monthly_earnings || '0.00'}</div>
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
        const response = await fetch('doctor_api.php?action=get_consultation_requests');
        const result = await response.json();

        if (result.status === 'success') {
            const container = document.getElementById('dashboardRequests');

            if (result.data.length === 0) {
                container.innerHTML = '<p style="text-align:center; color: var(--text-muted); padding: 2rem;">No pending requests</p>';
                return;
            }

            const html = result.data.slice(0, 5).map(req => `
                <div style="border-bottom: 1px solid var(--border); padding: 1rem 0;">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; margin-bottom: 0.5rem;">
                                ${req.patient_name} (${req.patient_age})
                                <span class="status-badge urgency-${req.urgency_badge}" style="margin-left: 0.5rem;">${req.urgency_badge.toUpperCase()}</span>
                            </div>
                            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.5rem;">${req.symptoms_summary}</p>
                            <div style="display: flex; gap: 1rem; font-size: 0.85rem; color: var(--text-muted);">
                                <span><i class="ph ph-${req.consultation_mode === 'video' ? 'video-camera' : req.consultation_mode === 'audio' ? 'microphone' : 'chat-circle'}"></i> ${req.consultation_mode}</span>
                                <span><i class="ph ph-globe"></i> ${req.language_preference}</span>
                                <span><i class="ph ph-clock"></i> ${req.duration}</span>
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.5rem;">
                            <button class="btn btn-success btn-sm" onclick="acceptConsultation(${req.id})">Accept</button>
                            <button class="btn btn-outline btn-sm" onclick="declineConsultation(${req.id})">Decline</button>
                        </div>
                    </div>
                </div>
            `).join('');

            container.innerHTML = html;
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
        const [requestsRes, activeRes] = await Promise.all([
            fetch('doctor_api.php?action=get_consultation_requests'),
            fetch('doctor_api.php?action=get_active_consultations')
        ]);

        const requests = await requestsRes.json();
        const active = await activeRes.json();

        renderConsultations(requests.data || [], active.data || []);
    } catch (error) {
        console.error('Error loading consultations:', error);
    }
}

function renderConsultations(requests, active) {
    const content = `
        <div class="page-title">
            <h1>Consultation Management</h1>
        </div>

        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">Active Consultations (${active.length})</span>
            </div>
            <div class="panel-body">
                ${active.length === 0 ? '<p style="text-align:center; color: var(--text-muted); padding: 2rem;">No active consultations</p>' :
            active.map(cons => {
                let urgencyBadge = 'routine';
                if (cons.severity === 'high' || cons.urgency_score >= 75) urgencyBadge = 'emergency';
                else if (cons.severity === 'medium' || cons.urgency_score >= 50) urgencyBadge = 'priority';

                return `
                    <div style="border-bottom: 1px solid var(--border); padding: 1rem 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 600;">
                                    ${cons.patient_name}
                                    <span class="status-badge urgency-${urgencyBadge}" style="margin-left: 0.5rem; font-size: 0.7rem;">${urgencyBadge.toUpperCase()}</span>
                                </div>
                                <div style="font-size: 0.85rem; color: var(--text-muted);">${cons.symptoms.substring(0, 100)}...</div>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <button class="btn btn-primary btn-sm" onclick="startSession(${cons.id}, ${cons.patient_id})">Start Session</button>
                                <button class="btn btn-success btn-sm" onclick="openPrescriptionModal(${cons.id}, ${cons.patient_id})">Create Prescription</button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('')}
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <span class="panel-title">Incoming Requests (${requests.length})</span>
            </div>
            <div class="panel-body">
                ${requests.length === 0 ? '<p style="text-align:center; color: var(--text-muted); padding: 2rem;">No pending requests</p>' :
            requests.map(req => `
                    <div style="border-bottom: 1px solid var(--border); padding: 1rem 0;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; margin-bottom: 0.5rem;">
                                    ${req.patient_name} (${req.patient_age})
                                    <span class="status-badge urgency-${req.urgency_badge}" style="margin-left: 0.5rem;">${req.urgency_badge.toUpperCase()}</span>
                                </div>
                                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.5rem;">${req.symptoms_summary}</p>
                                <div style="display: flex; gap: 1rem; font-size: 0.85rem; color: var(--text-muted);">
                                    <span><i class="ph ph-${req.consultation_mode === 'video' ? 'video-camera' : req.consultation_mode === 'audio' ? 'microphone' : 'chat-circle'}"></i> ${req.consultation_mode}</span>
                                    <span><i class="ph ph-globe"></i> ${req.language_preference}</span>
                                    <span><i class="ph ph-clock"></i> ${req.duration}</span>
                                </div>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <button class="btn btn-success btn-sm" onclick="acceptConsultation(${req.id})">Accept</button>
                                <button class="btn btn-outline btn-sm" onclick="declineConsultation(${req.id})">Decline</button>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;

    document.getElementById('mainContent').innerHTML = content;
}

async function acceptConsultation(consultationId) {
    if (!confirm('Accept this consultation request?')) return;

    try {
        const formData = new FormData();
        formData.append('action', 'accept_consultation');
        formData.append('consultation_id', consultationId);

        const response = await fetch('doctor_api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status === 'success') {
            alert('Consultation accepted successfully!');
            loadConsultations();
            loadDashboard(); // Refresh stats
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error accepting consultation:', error);
        alert('Failed to accept consultation');
    }
}

async function declineConsultation(consultationId) {
    const reason = prompt('Reason for declining (optional):');
    if (reason === null) return; // User cancelled

    try {
        const formData = new FormData();
        formData.append('action', 'decline_consultation');
        formData.append('consultation_id', consultationId);
        formData.append('reason', reason);

        const response = await fetch('doctor_api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status === 'success') {
            alert('Consultation declined');
            loadConsultations();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error declining consultation:', error);
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
            alert(`Session started! Mode: ${result.consultation_mode}\nSession Token: ${result.session_token}`);
            // In production, this would open the video/audio/chat interface
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error starting session:', error);
    }
}

// ========================================
// PRESCRIPTION MODAL
// ========================================

function openPrescriptionModal(consultationId, patientId) {
    document.getElementById('prescConsultationId').value = consultationId;
    document.getElementById('prescPatientId').value = patientId;
    document.getElementById('prescriptionForm').reset();
    medicines = [];
    document.getElementById('medicinesList').innerHTML = '';
    addMedicine(); // Add one medicine field by default
    document.getElementById('prescriptionModal').style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
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
    const patientId = document.getElementById('prescPatientId').value;
    const icdCode = document.getElementById('icdCode').value;
    const diagnosis = document.getElementById('diagnosis').value;
    const followUpDate = document.getElementById('followUpDate').value;
    const notesPatient = document.getElementById('notesPatient').value;
    const notesPharmacy = document.getElementById('notesPharmacy').value;

    if (!diagnosis) {
        alert('Diagnosis is required');
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

    try {
        const response = await fetch('doctor_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'save_prescription',
                consultation_id: consultationId,
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

        if (result.status === 'success') {
            alert('Prescription saved successfully!');
            closeModal('prescriptionModal');

            // Ask if they want to complete the consultation
            if (confirm('Mark this consultation as completed?')) {
                completeConsultation(consultationId);
            }
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error saving prescription:', error);
        alert('Failed to save prescription');
    }
}

async function completeConsultation(consultationId) {
    try {
        const formData = new FormData();
        formData.append('action', 'complete_consultation');
        formData.append('consultation_id', consultationId);

        const response = await fetch('doctor_api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status === 'success') {
            alert('Consultation completed successfully!');
            loadConsultations();
            loadDashboard();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error completing consultation:', error);
    }
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
    const response = prompt('Enter your response:');
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
            alert('Response posted successfully!');
            loadReviews();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error responding to review:', error);
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
    try {
        const response = await fetch('doctor_api.php?action=get_earnings&period=current_month');
        const result = await response.json();

        if (result.status === 'success') {
            renderEarnings(result.summary, result.details);
        }
    } catch (error) {
        console.error('Error loading earnings:', error);
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
                <div class="stat-value">$${parseFloat(summary.total_gross || 0).toFixed(2)}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Platform Commission (10%)</div>
                <div class="stat-value">$${parseFloat(summary.total_commission || 0).toFixed(2)}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Net Earnings</div>
                <div class="stat-value">$${parseFloat(summary.total_net || 0).toFixed(2)}</div>
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
                                <td>$${parseFloat(earning.gross_amount).toFixed(2)}</td>
                                <td>$${parseFloat(earning.platform_commission_amount).toFixed(2)}</td>
                                <td>$${parseFloat(earning.net_amount).toFixed(2)}</td>
                                <td><span class="status-badge bg-yellow">${earning.payment_status}</span></td>
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

let notifications = [];

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

        return `
            <div class="notif-item ${unreadClass}" onclick="handleNotificationClick(${notif.id}, ${notif.related_id}, '${notif.notification_type}')">
                <div class="notif-title">${notif.title}</div>
                <div class="notif-message">${notif.message}</div>
                <div class="notif-time">${timeAgo}</div>
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
    if (type === 'new_consultation') {
        window.location.href = '?view=consultations';
    } else if (type === 'follow_up_due') {
        window.location.href = '?view=patients';
    } else if (type === 'review_received') {
        window.location.href = '?view=reviews';
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
        const unreadIds = notifications.filter(n => !n.is_read).map(n => n.id);

        for (const id of unreadIds) {
            const formData = new FormData();
            formData.append('action', 'mark_notification_read');
            formData.append('notification_id', id);

            await fetch('doctor_api.php', {
                method: 'POST',
                body: formData
            });
        }

        // Update all to read
        notifications.forEach(n => n.is_read = true);
        renderNotifications();
        document.getElementById('notifDot').style.display = 'none';
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

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        sessionStorage.removeItem('currentUser');
        window.location.href = 'logout.php';
    }
}

function searchPatients(query) {
    // Implement patient search
    console.log('Searching for:', query);
}

/**
 * MedConnect Logic & Navigation
 */

let currentUser = null;
try {
    const storedUser = sessionStorage.getItem('currentUser');
    if (storedUser) {
        currentUser = JSON.parse(storedUser);
    }
} catch (e) {
    console.error('Failed to initialize user state:', e);
}

const state = {
    currentUser: currentUser,
    activePage: 'landing', // Default
    consultationActive: false
};

// --- Logic functions ---

function logout() {
    sessionStorage.removeItem('currentUser');
    window.location.href = 'logout.php';
}

function toggleMenu() {
    const navLinks = document.getElementById('navLinks');
    if (navLinks) navLinks.classList.toggle('active');
}

function navigateToSection(sectionId) {
    // If on landing page (index.php), scroll to section
    if (window.location.pathname.endsWith('index.php') || window.location.pathname === '/' || window.location.pathname.endsWith('medconnect/')) {
        const el = document.getElementById(sectionId);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth' });
        }
    } else {
        // Redirect to index.php with anchor
        window.location.href = `index.php#${sectionId}`;
    }

    // Close mobile menu if open
    const navLinks = document.getElementById('navLinks');
    if (navLinks && navLinks.classList.contains('active')) {
        navLinks.classList.remove('active');
    }
}

function navigateToDashboard() {
    const userJson = sessionStorage.getItem('currentUser');
    if (!userJson) return window.location.href = 'login.php';
    const user = JSON.parse(userJson);

    if (user.role === 'patient') window.location.href = 'patient_dashboard.php';
    else if (user.role === 'doctor') window.location.href = 'doctor_dashboard.php';
    else if (user.role === 'pharmacy') window.location.href = 'pharmacy_dashboard.php';
    else if (user.role === 'clinic' || user.role === 'hospital') window.location.href = 'clinic_dashboard.php';
    else if (user.role === 'admin') window.location.href = 'admin_dashboard.php';
}

// Load patient dashboard activity (recent activity list)
async function loadPatientDashboardData() {
    const activityList = document.getElementById('recentActivityList');
    if (!activityList) return;

    try {
        const response = await fetch('get_consultations.php');
        const result = await response.json();

        if (result.success) {
            const consultations = result.consultations;

            if (consultations.length === 0) {
                activityList.innerHTML = '<p style="text-align: center; color: #94a3b8; padding: 3rem;">No recent activity. Start your first consultation today!</p>';
            } else {
                activityList.innerHTML = consultations.slice(0, 5).map(consultation => `
                    <div class="activity-item" style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 0; border-bottom: 1px solid #f1f5f9;">
                        <div>
                            <strong>${consultation.status === 'pending' ? 'Seeking Doctor' : (consultation.doctor_name || 'Assigned Specialist')}</strong><br>
                            <small style="color: #64748b;">${consultation.symptoms_preview} • ${consultation.created_at_formatted}</small>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1.5rem;">
                            <span class="status-badge status-${consultation.status}" style="padding: 0.25rem 0.75rem; border-radius: 99px; font-size: 0.75rem; font-weight: 600;">
                                ${consultation.status.replace('_', ' ')}
                            </span>
                            ${(consultation.status === 'assigned' || consultation.status === 'in_progress') ? `
                                <a href="consultation_room.php?id=${consultation.id}&type=${consultation.type || 'consultation'}" class="btn btn-primary btn-sm">Join</a>
                            ` : ''}
                        </div>
                    </div>
                `).join('');
            }
        }
    } catch (error) {
        console.error('Error loading dashboard data:', error);
        activityList.innerHTML = '<p style="text-align: center; color: #ef4444; padding: 2rem;">Unable to load activity data.</p>';
    }
}

// Load complete consultation history
async function loadConsultationHistory() {
    const historyList = document.getElementById('historyActivityList');
    if (!historyList) return;

    try {
        const response = await fetch('get_consultations.php');
        const result = await response.json();

        if (result.success) {
            if (result.consultations.length === 0) {
                historyList.innerHTML = `
                    <div style="text-align: center; padding: 4rem 2rem; color: #94a3b8;">
                        <i class="ph ph-clipboard-text" style="font-size: 4rem; opacity: 0.2;"></i>
                        <p style="margin-top: 1rem;">No consultations yet</p>
                    </div>
                `;
            } else {
                historyList.innerHTML = result.consultations.map(consult => `
                    <div class="activity-item" style="padding: 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <strong>${consult.doctor_name || (consult.status === 'pending' ? 'Pending Assignment' : 'N/A')}</strong><br>
                            <small>${consult.symptoms_preview} • ${consult.created_at_formatted}</small>
                            <p style="font-size: 0.9rem; color: #475569; margin-top: 0.5rem;">Notes: ${consult.symptoms}</p>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <span class="status-badge status-${consult.status}">
                                ${consult.status.replace('_', ' ')}
                            </span>
                        </div>
                    </div>
                `).join('');
            }
        }
    } catch (error) {
        console.error('Error loading history:', error);
    }
}

// Doctor Profile Modal
function viewDoctorProfile(name, specialty, experience, bio) {
    const modal = document.createElement('div');
    modal.id = 'doctorModal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 118, 110, 0.2);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        animation: fadeIn 0.4s ease forwards;
    `;

    modal.innerHTML = `
        <div style="
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            padding: 3rem;
            border-radius: 32px;
            max-width: 600px;
            width: 90%;
            box-shadow: 0 40px 100px rgba(0,0,0,0.2);
            border: 1px solid rgba(255, 255, 255, 0.8);
            position: relative;
        ">
            <button onclick="closeDoctorModal()" style="
                position: absolute;
                top: 1.5rem;
                right: 1.5rem;
                background: rgba(0,0,0,0.05);
                border: none;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                color: #64748b;
            ">
                <i class="ph ph-x"></i>
            </button>
            
            <h2 style="text-align: center; margin-bottom: 0.5rem;">${name}</h2>
            <p style="text-align: center; color: var(--primary); font-weight: 700; margin-bottom: 0.5rem;">${specialty}</p>
            <p style="text-align: center; color: #64748b; font-size: 0.9rem; margin-bottom: 2rem;">${experience}</p>
            
            <div style="background: #f0fdfa; padding: 1.5rem; border-radius: 16px; margin-bottom: 2rem;">
                <p style="color: #475569; line-height: 1.6;">${bio}</p>
            </div>
            
            <div style="display: flex; gap: 1rem;">
                ${state.currentUser
            ? `<button onclick="window.location.href='appointment_booking.php'; closeDoctorModal();" class="btn btn-primary" style="flex: 2;">Book Appointment</button>`
            : `<a href="login.php" class="btn btn-primary" style="flex: 2; text-decoration: none; text-align: center;">Login to Book</a>`
        }
                <button onclick="closeDoctorModal()" class="btn btn-outline" style="flex: 1;">Dismiss</button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
}

function closeDoctorModal() {
    const modal = document.getElementById('doctorModal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = 'auto';
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Scroll to anchor if present
    if (window.location.hash) {
        const id = window.location.hash.substring(1);
        const el = document.getElementById(id);
        if (el) setTimeout(() => el.scrollIntoView({ behavior: 'smooth' }), 500);
    }

    // Load dashboard data if on dashboard page
    if (window.location.pathname.includes('patient_dashboard.php')) {
        loadPatientDashboardData();
    }
});

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
                            ${(consultation.status === 'completed' && !consultation.review_id && consultation.doctor_id) ? `
                                <button onclick="openReviewModal(${consultation.id}, ${consultation.doctor_id}, '${consultation.doctor_name || 'Doctor'}')" class="btn btn-outline btn-sm">Leave Review</button>
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
                            ${(consult.status === 'completed' && !consult.review_id && consult.doctor_id) ? `
                                <button onclick="openReviewModal(${consult.id}, ${consult.doctor_id}, '${consult.doctor_name || 'Doctor'}')" class="btn btn-primary btn-sm">Leave Review</button>
                            ` : ''}
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

// --- Review System ---
function openReviewModal(consultationId, doctorId, doctorName) {
    const modal = document.createElement('div');
    modal.id = 'reviewModal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.4);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        animation: fadeIn 0.3s ease;
    `;

    modal.innerHTML = `
        <div style="background: white; padding: 2rem; border-radius: 20px; max-width: 500px; width: 90%; box-shadow: 0 20px 50px rgba(0,0,0,0.1); border: 1px solid #f1f5f9;">
            <h3 style="margin-bottom: 0.5rem; color: #1e293b;">Rate your experience</h3>
            <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1.5rem;">How was your consultation with ${doctorName}?</p>
            
            <div id="starRating" style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; justify-content: center; font-size: 2rem;">
                <i class="ph ph-star star-btn" data-value="1" style="cursor: pointer; color: #e2e8f0; transition: 0.2s;"></i>
                <i class="ph ph-star star-btn" data-value="2" style="cursor: pointer; color: #e2e8f0; transition: 0.2s;"></i>
                <i class="ph ph-star star-btn" data-value="3" style="cursor: pointer; color: #e2e8f0; transition: 0.2s;"></i>
                <i class="ph ph-star star-btn" data-value="4" style="cursor: pointer; color: #e2e8f0; transition: 0.2s;"></i>
                <i class="ph ph-star star-btn" data-value="5" style="cursor: pointer; color: #e2e8f0; transition: 0.2s;"></i>
            </div>

            <textarea id="reviewText" style="width: 100%; padding: 1rem; border: 1px solid #e2e8f0; border-radius: 12px; min-height: 120px; font-family: inherit; font-size: 0.95rem; margin-bottom: 1.5rem; outline: none; transition: border-color 0.2s;" placeholder="Describe your experience (optional)..."></textarea>
            
            <div style="display: flex; gap: 1rem;">
                <button onclick="closeReviewModal()" class="btn btn-outline" style="flex: 1;">Cancel</button>
                <button onclick="submitReview(${consultationId}, ${doctorId})" class="btn btn-primary" style="flex: 2;">Submit Feedback</button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';

    // Handle star interactions
    let selectedRating = 0;
    const stars = modal.querySelectorAll('.star-btn');
    stars.forEach(star => {
        star.addEventListener('mouseover', () => {
            const val = parseInt(star.dataset.value);
            stars.forEach((s, i) => {
                if (i < val) {
                    s.classList.add('ph-fill');
                    s.style.color = '#fbbf24';
                }
            });
        });
        star.addEventListener('mouseleave', () => {
            stars.forEach((s, i) => {
                if (i >= selectedRating) {
                    s.classList.remove('ph-fill');
                    s.style.color = '#e2e8f0';
                } else {
                    s.classList.add('ph-fill');
                    s.style.color = '#fbbf24';
                }
            });
        });
        star.addEventListener('click', () => {
            selectedRating = parseInt(star.dataset.value);
            modal.dataset.rating = selectedRating;
        });
    });

    const reviewText = document.getElementById('reviewText');
    reviewText.addEventListener('focus', () => { reviewText.style.borderColor = 'var(--primary)'; });
    reviewText.addEventListener('blur', () => { reviewText.style.borderColor = '#e2e8f0'; });
}

function closeReviewModal() {
    const modal = document.getElementById('reviewModal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = 'auto';
    }
}

async function submitReview(consultationId, doctorId) {
    const modal = document.getElementById('reviewModal');
    const rating = parseInt(modal.dataset.rating || 0);
    const reviewText = document.getElementById('reviewText').value;

    if (rating === 0) {
        await alert('Please select a star rating.');
        return;
    }

    try {
        const response = await fetch('patient_api.php?action=submit_feedback', {
            method: 'POST',
            body: JSON.stringify({
                consultation_id: consultationId,
                doctor_id: doctorId,
                rating: rating,
                review_text: reviewText
            }),
            headers: {
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            const errorText = await response.text();
            let errorJson;
            try {
                errorJson = JSON.parse(errorText);
            } catch (e) { }

            await alert('Error: ' + (errorJson ? errorJson.message : 'Server error (' + response.status + ')'));
            return;
        }

        const result = await response.json();
        if (result.status === 'success') {
            await alert('Thank you for your feedback! It has been submitted for review.');
            closeReviewModal();
            // Refresh views
            loadPatientDashboardData();
            if (document.getElementById('historyActivityList')) {
                loadConsultationHistory();
            }
        } else {
            await alert('Error: ' + result.message);
        }
    } catch (e) {
        console.error('Failed to submit review:', e);
        await alert('Failed to submit feedback. Check console for details.');
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

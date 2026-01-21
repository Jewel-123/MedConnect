// Basic State to handle simulated navigation
const state = {
    currentUser: null, // 'patient', 'doctor', 'admin'
    activePage: 'landing',
    consultationActive: false
};

// --- DOM Elements ---
const app = document.getElementById('app');

// --- Page Templates ---

const PAGES = {
    landing: () => `
        <section class="hero-section fade-in">
            <div class="hero-text">
                <h1>Your Health, <br><span class="text-gradient">Connected.</span></h1>
                <p>Expert medical consultation, AI-driven symptom checking, and instant e-prescriptions—all from the comfort of your home.</p>
                <div style="display: flex; gap: 1rem;">
                    <button class="btn btn-primary" onclick="showPage('auth')">Login to Portal</button>
                    <button class="btn btn-outline" style="background:white;" onclick="document.querySelector('#doctors').scrollIntoView()">Find a Doctor</button>
                </div>
            </div>
            <div class="hero-visual">
                <div class="stats-card">
                    <h2>24/7</h2>
                    <p>Doctor Availability</p>
                    <hr style="margin: 1rem 0; opacity: 0.2">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="ph ph-shield-check" style="font-size: 2rem; color: var(--primary)"></i>
                        <div>
                            <strong>Secure & Private</strong><br>
                            <small>HIPAA Compliant</small>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="services" class="features-grid fade-in">
            <div class="feature-card">
                <i class="ph ph-stethoscope feature-icon"></i>
                <h3>Symptom Checker</h3>
                <p>AI-powered analysis to guide you to the right specialist instantly.</p>
            </div>
            <div class="feature-card">
                <i class="ph ph-video-camera feature-icon"></i>
                <h3>Video Consults</h3>
                <p>HD video calls with certified doctors without the waiting room.</p>
            </div>
            <div class="feature-card">
                <i class="ph ph-pill feature-icon"></i>
                <h3>E-Prescriptions</h3>
                <p>Sent directly to your nearest pharmacy for easy pickup.</p>
            </div>
        </section>
    `,

    auth: () => `
        <div class="auth-container fade-in">
            <div style="margin-bottom: 2rem; color: var(--primary);">
                <i class="ph ph-heartbeat" style="font-size: 3rem;"></i>
            </div>
            <h2>Welcome Back</h2>
            <p>Please log in to continue</p>
            
            <div class="form-group" style="margin-top: 1.5rem;">
                <label>I am a:</label>
                <select id="roleSelect" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; background: #fff;">
                    <option value="patient">Patient</option>
                    <option value="doctor">Doctor</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" placeholder="user@example.com" value="demo@medconnect.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" value="password123">
            </div>

            <button class="btn btn-primary" style="width: 100%" onclick="handleLogin()">Login</button>
            <p style="margin-top: 1rem; font-size: 0.9rem; color: #666;">
                Don't have an account? <a href="#" onclick="showPage('signup')" style="color: var(--primary)">Sign Up</a>
            </p>
        </div>
    `,

    signup: () => `
        <div class="auth-container fade-in">
            <h2>Create Account</h2>
            <p>Join MedConnect today</p>
            
             <div class="form-group" style="margin-top: 1.5rem;">
                <label>I am a:</label>
                <select id="signupRole" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; background: #fff;">
                    <option value="patient">Patient</option>
                    <option value="doctor">Doctor</option>
                    <option value="pharmacy">Pharmacy</option>
                </select>
            </div>

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" placeholder="John Doe">
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" placeholder="user@example.com">
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" placeholder="Create a password">
            </div>

            <button class="btn btn-primary" style="width: 100%" onclick="handleSignup()">Sign Up</button>
            <p style="margin-top: 1rem; font-size: 0.9rem; color: #666;">
                Already have an account? <a href="#" onclick="showPage('auth')" style="color: var(--primary)">Login</a>
            </p>
        </div>
    `,

    patientDashboard: () => `
        <div class="dashboard-container fade-in">
            <aside class="sidebar">
                <h3><i class="ph ph-user-circle"></i> Patient Portal</h3>
                <ul>
                    <li><a href="#" class="active"><i class="ph ph-squares-four"></i> Dashboard</a></li>
                    <li><a href="#" onclick="showPage('symptomChecker')"><i class="ph ph-clipboard-text"></i> Symptom Checker</a></li>
                    <li><a href="#"><i class="ph ph-clock-counter-clockwise"></i> History</a></li>
                    <li><a href="#" onclick="logout()"><i class="ph ph-sign-out"></i> Logout</a></li>
                </ul>
            </aside>
            <main class="main-area">
                <div class="dash-header">
                    <h2>Hello, Neenu</h2>
                    <button class="btn btn-primary" onclick="showPage('symptomChecker')">+ New Consultation</button>
                </div>
                
                <div class="stat-row">
                    <div class="stat-box">
                        <div class="large-number">3</div>
                        <p>Past Consultations</p>
                    </div>
                    <div class="stat-box">
                        <div class="large-number text-gradient">1</div>
                        <p>Active Prescription</p>
                    </div>
                </div>

                <h3>Recent Activity</h3>
                <div class="symptom-box" style="margin-top: 1rem; width: 100%; max-width: 100%;">
                    <div style="display: flex; justify-content: space-between; padding: 1rem 0; border-bottom: 1px solid #eee;">
                        <div>
                            <strong>Dr. Emily Smith</strong><br>
                            <small>General Physician • Yesterday</small>
                        </div>
                        <span style="color: green">Completed</span>
                    </div>
                </div>
            </main>
        </div>
    `,

    symptomChecker: () => `
        <div class="dashboard-container fade-in">
            <aside class="sidebar">
                <h3><i class="ph ph-user-circle"></i> Patient Portal</h3>
                <ul>
                    <li><a href="#" onclick="showPage('patientDashboard')"><i class="ph ph-squares-four"></i> Dashboard</a></li>
                    <li><a href="#" class="active"><i class="ph ph-clipboard-text"></i> Symptom Checker</a></li>
                    <li><a href="#" onclick="logout()"><i class="ph ph-sign-out"></i> Logout</a></li>
                </ul>
            </aside>
            <main class="main-area">
                <h2>Symptom Assistant</h2>
                <div class="symptom-box">
                    <p style="margin-bottom: 1rem;">Describe your symptoms naturally, or use voice input.</p>
                    
                    <button class="voice-input-btn">
                        <i class="ph ph-microphone" style="color: var(--primary); font-size: 1.5rem;"></i>
                        <span>Tap to Speak</span>
                    </button>

                    <div class="form-group">
                        <label>Written Description</label>
                        <textarea style="width: 100%; padding: 1rem; border: 1px solid #ddd; border-radius: 8px; font-family: inherit;" rows="4" placeholder="e.g., I have a bad headache and mild fever since morning..."></textarea>
                    </div>

                    <button class="btn btn-primary" style="width: 100%" onclick="simulateMatching()">Analyze & Find Doctor</button>
                </div>
            </main>
        </div>
    `,

    doctorDashboard: () => `
         <div class="dashboard-container fade-in">
             <aside class="sidebar" style="background: #2c3e50;">
                <h3><i class="ph ph-stethoscope"></i> Doctor Portal</h3>
                <ul>
                    <li><a href="#" class="active"><i class="ph ph-calendar-check"></i> Appointments</a></li>
                    <li><a href="#"><i class="ph ph-users"></i> Patients</a></li>
                    <li><a href="#" onclick="logout()"><i class="ph ph-sign-out"></i> Logout</a></li>
                </ul>
            </aside>
             <main class="main-area">
                <div class="dash-header">
                    <h2>Dr. Smith's Schedule</h2>
                </div>

                <div class="symptom-box" style="width: 100%; max-width: 100%;">
                    <h3>Upcoming Requests</h3>
                    <div style="display: flex; align-items: center; justify-content: space-between; background: #e0f7fa; padding: 1rem; border-radius: 8px; margin-top: 1rem;">
                        <div>
                            <strong>Patient: Neenu</strong><br>
                            <small>Symptoms: Headache, Fever</small><br>
                            <span style="font-size: 0.8rem; background: yellow; padding: 2px 5px; border-radius: 4px;">High Match (98%)</span>
                        </div>
                        <button class="btn btn-primary" onclick="showPage('consultation')">Join Call</button>
                    </div>
                </div>
            </main>
        </div>
    `,

    adminDashboard: () => `
        <div class="dashboard-container fade-in">
             <aside class="sidebar" style="background: #34495e;">
                <h3><i class="ph ph-shield-check"></i> Admin Panel</h3>
                <ul>
                    <li><a href="#" class="active"><i class="ph ph-chart-line-up"></i> Overview</a></li>
                    <li><a href="#"><i class="ph ph-users-three"></i> User Mgmt</a></li>
                    <li><a href="#"><i class="ph ph-hospital"></i> Clinics</a></li>
                    <li><a href="#" onclick="logout()"><i class="ph ph-sign-out"></i> Logout</a></li>
                </ul>
            </aside>
             <main class="main-area">
                <div class="dash-header">
                    <h2>Platform Overview</h2>
                    <span style="background: #dff0d8; color: #3c763d; padding: 0.5rem 1rem; border-radius: 20px;">System Healthy</span>
                </div>

                <div class="stat-row">
                    <div class="stat-box">
                        <div class="large-number text-gradient">1,204</div>
                        <p>Active Patients</p>
                    </div>
                    <div class="stat-box">
                        <div class="large-number">85</div>
                        <p>Verified Doctors</p>
                    </div>
                    <div class="stat-box">
                        <div class="large-number">420</div>
                        <p>Consultations Today</p>
                    </div>
                </div>

                <h3>Pending Approvals</h3>
                <div class="symptom-box" style="width: 100%; max-width: 100%; margin-top: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; border-bottom: 1px solid #eee;">
                         <div>
                            <strong>Dr. John Doe</strong><br>
                            <small>Cardiologist • License #998877</small>
                        </div>
                         <div style="display: flex; gap: 0.5rem;">
                            <button class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Review</button>
                            <button class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Approve</button>
                        </div>
                    </div>
                     <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0;">
                         <div>
                            <strong>City Care Pharmacy</strong><br>
                            <small>Retail Pharmacy • New York</small>
                        </div>
                         <div style="display: flex; gap: 0.5rem;">
                            <button class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Review</button>
                            <button class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Approve</button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    `,

    consultation: () => `
        <div class="fade-in">
            <div style="margin-bottom: 1rem;">
                <button class="btn btn-outline" onclick="history.back()">Exit Consultation</button>
            </div>
            <div class="consultation-room">
                <div class="video-feed">
                    <div style="text-align: center;">
                        <i class="ph ph-user-focus" style="font-size: 4rem; opacity: 0.5;"></i>
                        <p>Video Feed Active</p>
                        <div style="margin-top: 1rem; display: flex; gap: 1rem; justify-content: center;">
                            <button class="btn btn-secondary"><i class="ph ph-microphone"></i></button>
                            <button class="btn btn-secondary"><i class="ph ph-video-camera"></i></button>
                            <button class="btn btn-primary" style="background: red;"><i class="ph ph-phone-disconnect"></i></button>
                        </div>
                    </div>
                </div>
                <div class="chat-box">
                    <div style="padding: 1rem; border-bottom: 1px solid #eee; font-weight: bold;">Live Chat</div>
                    <div class="messages" id="chatMessages">
                        <div class="message msg-doctor">Hello! I'm Dr. Smith. How can I help you today?</div>
                    </div>
                    <div class="input-area">
                        <input type="text" id="msgInput" placeholder="Type a message..." style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                        <button class="btn btn-primary" onclick="sendMessage()"><i class="ph ph-paper-plane-right"></i></button>
                    </div>
                </div>
            </div>
        </div>
    `
};

// --- Logic functions ---

function showPage(pageName) {
    if (!PAGES[pageName]) return;
    app.innerHTML = PAGES[pageName]();
    state.activePage = pageName;
    window.scrollTo(0, 0);
}

function handleLogin() {
    const roleSelect = document.getElementById('roleSelect');
    const selectedRole = roleSelect.value; // 'patient', 'doctor', 'admin'

    // Simple routing based on selection
    if (selectedRole === 'patient') {
        showPage('patientDashboard');
    } else if (selectedRole === 'doctor') {
        showPage('doctorDashboard');
    } else if (selectedRole === 'admin') {
        showPage('adminDashboard');
    }
}

function handleSignup() {
    const role = document.getElementById('signupRole').value;
    alert("Account created successfully! Please login.");
    showPage('auth');
    // Pre-select the role they just signed up as
    setTimeout(() => {
        const select = document.getElementById('roleSelect');
        if (select) select.value = role;
    }, 100);
}

function logout() {
    showPage('auth');
}

function simulateMatching() {
    // Simulate AI thinking
    const btn = document.querySelector('.symptom-box .btn-primary');
    const originalText = btn.innerText;
    btn.innerText = 'Analyzing Symptoms...';
    btn.disabled = true;

    setTimeout(() => {
        alert('Match Found! Dr. Smith (General Physician) is available.');
        btn.innerText = originalText;
        btn.disabled = false;
        showPage('patientDashboard');
    }, 2000);
}

function sendMessage() {
    const input = document.getElementById('msgInput');
    const text = input.value.trim();
    if (!text) return;

    const chatDiv = document.getElementById('chatMessages');

    // Add User Message
    const userMsg = document.createElement('div');
    userMsg.className = 'message msg-patient';
    userMsg.innerText = text;
    chatDiv.appendChild(userMsg);

    input.value = '';
    chatDiv.scrollTop = chatDiv.scrollHeight;

    // Simulate Reply
    setTimeout(() => {
        const reply = document.createElement('div');
        reply.className = 'message msg-doctor';
        reply.innerText = "I see. Could you tell me more about the duration?";
        chatDiv.appendChild(reply);
        chatDiv.scrollTop = chatDiv.scrollHeight;
    }, 1500);
}

// Initialize
showPage('landing');

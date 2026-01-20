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
    activePage: 'landing',
    consultationActive: false
};

// --- Page Templates ---

const PAGES = {
    landing: () => `
        <div class="blob-container">
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
        </div>

        <section id="landing" class="hero-section fade-in">
            <div class="hero-text">
                <h1>Your Health, <br><span class="text-gradient">Now Connected.</span></h1>
                <p>Experience the future of healthcare with expert medical consultation, AI-driven symptom analysis, and instant e-prescriptions—right at your fingertips.</p>
                <div style="display: flex; gap: 1rem;">
                    ${state.currentUser
            ? `<button class="btn btn-primary" onclick="navigateToDashboard()">Go to Dashboard</button>`
            : `<a href="login.php" class="btn btn-primary">Join the Portal</a>`
        }
                    <button class="btn btn-outline" style="background:white;" onclick="document.querySelector('#doctors').scrollIntoView()">Explore Specialists</button>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-image-container">
                    <img src="assets/img/hero.png" alt="Medical Consultation">
                </div>
                <div class="stats-card">
                    <h2>24/7</h2>
                    <p>Specialist Access</p>
                    <hr style="margin: 1rem 0; opacity: 0.2">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="ph ph-shield-check" style="font-size: 2rem; color: var(--primary)"></i>
                        <div>
                            <strong>Fully Secure</strong><br>
                            <small>Safe & Private</small>
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

        <section id="doctors" class="fade-in" style="margin-top: 4rem;">
            <h2 style="text-align: center; margin-bottom: 2rem;">Our Specialists</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <img src="assets/img/doctors/dr_emily_smith.png" alt="Dr. Emily Smith" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;">
                    <h3>Dr. Emily Smith</h3>
                    <p>General Physician</p>
                    <p style="font-size: 0.85rem; color: #64748b; margin-top: 0.5rem;">15+ years experience • MBBS, MD</p>
                    <button class="btn btn-outline" style="margin-top: 1rem;" onclick="viewDoctorProfile('Dr. Emily Smith', 'General Physician', '15+ years experience', 'MBBS, MD from Harvard Medical School. Specializes in preventive care and chronic disease management.')">View Profile</button>
                </div>
                <div class="feature-card">
                    <img src="assets/img/doctors/dr_james_wilson.png" alt="Dr. James Wilson" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;">
                    <h3>Dr. James Wilson</h3>
                    <p>Cardiologist</p>
                    <p style="font-size: 0.85rem; color: #64748b; margin-top: 0.5rem;">20+ years experience • MBBS, DM</p>
                    <button class="btn btn-outline" style="margin-top: 1rem;" onclick="viewDoctorProfile('Dr. James Wilson', 'Cardiologist', '20+ years experience', 'MBBS, DM in Cardiology from Johns Hopkins. Expert in interventional cardiology and heart disease prevention.')">View Profile</button>
                </div>
                <div class="feature-card">
                    <img src="assets/img/doctors/dr_sarah_lee.png" alt="Dr. Sarah Lee" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;">
                    <h3>Dr. Sarah Lee</h3>
                    <p>Pediatrician</p>
                    <p style="font-size: 0.85rem; color: #64748b; margin-top: 0.5rem;">12+ years experience • MBBS, DCH</p>
                    <button class="btn btn-outline" style="margin-top: 1rem;" onclick="viewDoctorProfile('Dr. Sarah Lee', 'Pediatrician', '12+ years experience', 'MBBS, DCH from Stanford Medical School. Specializes in child development and pediatric care.')">View Profile</button>
                </div>
                <div class="feature-card">
                    <img src="assets/img/doctors/dr_michael_brown.png" alt="Dr. Michael Brown" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;">
                    <h3>Dr. Michael Brown</h3>
                    <p>Neurologist</p>
                    <p style="font-size: 0.85rem; color: #64748b; margin-top: 0.5rem;">18+ years experience • MBBS, MD, DM</p>
                    <button class="btn btn-outline" style="margin-top: 1rem;" onclick="viewDoctorProfile('Dr. Michael Brown', 'Neurologist', '18+ years experience', 'MBBS, MD, DM in Neurology from Mayo Clinic. Expert in stroke management and neurological disorders.')">View Profile</button>
                </div>
                <div class="feature-card">
                    <img src="assets/img/doctors/dr_sophia_martinez.png" alt="Dr. Sophia Martinez" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;">
                    <h3>Dr. Sophia Martinez</h3>
                    <p>Dermatologist</p>
                    <p style="font-size: 0.85rem; color: #64748b; margin-top: 0.5rem;">10+ years experience • MBBS, MD</p>
                    <button class="btn btn-outline" style="margin-top: 1rem;" onclick="viewDoctorProfile('Dr. Sophia Martinez', 'Dermatologist', '10+ years experience', 'MBBS, MD in Dermatology from Yale School of Medicine. Specialist in medical and cosmetic dermatology.')">View Profile</button>
                </div>
                <div class="feature-card">
                    <img src="assets/img/doctors/dr_david_chen.png" alt="Dr. David Chen" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;">
                    <h3>Dr. David Chen</h3>
                    <p>Orthopedic Surgeon</p>
                    <p style="font-size: 0.85rem; color: #64748b; margin-top: 0.5rem;">16+ years experience • MBBS, MS, MCh</p>
                    <button class="btn btn-outline" style="margin-top: 1rem;" onclick="viewDoctorProfile('Dr. David Chen', 'Orthopedic Surgeon', '16+ years experience', 'MBBS, MS, MCh from Cleveland Clinic. Expert in joint replacement and sports medicine.')">View Profile</button>
                </div>
                <div class="feature-card">
                    <img src="assets/img/doctors/dr_elena_rodriguez.png" alt="Dr. Elena Rodriguez" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;">
                    <h3>Dr. Elena Rodriguez</h3>
                    <p>Psychiatrist</p>
                    <p style="font-size: 0.85rem; color: #64748b; margin-top: 0.5rem;">14+ years experience • MBBS, MD (Psych)</p>
                    <button class="btn btn-outline" style="margin-top: 1rem;" onclick="viewDoctorProfile('Dr. Elena Rodriguez', 'Psychiatrist', '14+ years experience', 'MBBS, MD from Columbia University. Specialist in clinical psychiatry and mental health wellness.')">View Profile</button>
                </div>
                <div class="feature-card">
                    <img src="assets/img/doctors/dr_robert_taylor.png" alt="Dr. Robert Taylor" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;">
                    <h3>Dr. Robert Taylor</h3>
                    <p>Ophthalmologist</p>
                    <p style="font-size: 0.85rem; color: #64748b; margin-top: 0.5rem;">22+ years experience • MBBS, MS</p>
                    <button class="btn btn-outline" style="margin-top: 1rem;" onclick="viewDoctorProfile('Dr. Robert Taylor', 'Ophthalmologist', '22+ years experience', 'MBBS, MS from University of Pennsylvania. Expert in cataract surgery and retinal health.')">View Profile</button>
                </div>
                <div class="feature-card">
                    <img src="assets/img/doctors/dr_lisa_wong.png" alt="Dr. Lisa Wong" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;">
                    <h3>Dr. Lisa Wong</h3>
                    <p>ENT Specialist</p>
                    <p style="font-size: 0.85rem; color: #64748b; margin-top: 0.5rem;">11+ years experience • MBBS, DLO</p>
                    <button class="btn btn-outline" style="margin-top: 1rem;" onclick="viewDoctorProfile('Dr. Lisa Wong', 'ENT Specialist', '11+ years experience', 'MBBS, DLO from UCLA Health. Specializes in otolaryngology and head/neck surgery.')">View Profile</button>
                </div>
                <div class="feature-card">
                    <img src="assets/img/doctors/dr_jennifer_adams.png" alt="Dr. Jennifer Adams" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;">
                    <h3>Dr. Jennifer Adams</h3>
                    <p>Gynecologist</p>
                    <p style="font-size: 0.85rem; color: #64748b; margin-top: 0.5rem;">15+ years experience • MBBS, MD</p>
                    <button class="btn btn-outline" style="margin-top: 1rem;" onclick="viewDoctorProfile('Dr. Jennifer Adams', 'Gynecologist', '15+ years experience', 'MBBS, MD from Northwestern Memorial Hospital. expert in womens reproductive health and maternity care.')">View Profile</button>
                </div>
                <div class="feature-card">
                    <img src="assets/img/doctors/dr_kevin_park.png" alt="Dr. Kevin Park" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;">
                    <h3>Dr. Kevin Park</h3>
                    <p>Gastroenterologist</p>
                    <p style="font-size: 0.85rem; color: #64748b; margin-top: 0.5rem;">13+ years experience • MBBS, MD, DM</p>
                    <button class="btn btn-outline" style="margin-top: 1rem;" onclick="viewDoctorProfile('Dr. Kevin Park', 'Gastroenterologist', '13+ years experience', 'MBBS, MD, DM from Massachusetts General Hospital. Specializes in digestive disorders and endoscopy.')">View Profile</button>
                </div>
                <div class="feature-card">
                    <img src="assets/img/doctors/dr_amanda_white.png" alt="Dr. Amanda White" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;">
                    <h3>Dr. Amanda White</h3>
                    <p>Endocrinologist</p>
                    <p style="font-size: 0.85rem; color: #64748b; margin-top: 0.5rem;">17+ years experience • MBBS, MD</p>
                    <button class="btn btn-outline" style="margin-top: 1rem;" onclick="viewDoctorProfile('Dr. Amanda White', 'Endocrinologist', '17+ years experience', 'MBBS, MD from Duke University. Expert in diabetes management and hormonal disorders.')">View Profile</button>
                </div>
            </div>
        </section>

        <section id="contact" class="fade-in" style="margin-top: 4rem; background: var(--white); padding: 3rem; border-radius: 12px; box-shadow: var(--shadow);">
            <div style="display: flex; flex-wrap: wrap; gap: 2rem;">
                <div style="flex: 1;">
                    <h2>Contact Us</h2>
                    <p style="margin-bottom: 1.5rem;">Have questions? We're here to help.</p>
                    <div style="display: flex; gap: 1rem; margin-bottom: 1rem; align-items: center;">
                        <i class="ph ph-envelope" style="font-size: 1.5rem; color: var(--primary);"></i>
                        <span>support@medconnect.com</span>
                    </div>
                    <div style="display: flex; gap: 1rem; margin-bottom: 1rem; align-items: center;">
                        <i class="ph ph-phone" style="font-size: 1.5rem; color: var(--primary);"></i>
                        <span>+1 (555) 123-4567</span>
                    </div>
                </div>
                <div style="flex: 1;">
                    <form id="contactForm" onsubmit="submitContactForm(event)">
                        <div class="form-group">
                            <label>Name <span style="color: red;">*</span></label>
                            <input type="text" id="contactName" placeholder="Your Name" required>
                        </div>
                        <div class="form-group">
                            <label>Email <span style="color: red;">*</span></label>
                            <input type="email" id="contactEmail" placeholder="your@email.com" required>
                        </div>
                        <div class="form-group">
                            <label>Message <span style="color: red;">*</span></label>
                            <textarea id="contactMessage" placeholder="How can we help?" rows="4" style="width: 100%; padding: 0.9rem 1rem; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 1rem; background: #f9fafb; font-family: inherit; resize: vertical;" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" id="contactSubmitBtn" style="margin-top: 1rem;">Send Message</button>
                        <div id="contactFormMessage" style="margin-top: 1rem; padding: 1rem; border-radius: 8px; display: none;"></div>
                    </form>
                </div>
            </div>
        </section>
    `,

    // Auth pages removed - now using login.php and signup.php

    patientDashboard: () => `
        <div class="dashboard-container fade-in">
            <aside class="sidebar">
                <h3><i class="ph ph-user-circle"></i> Patient Portal</h3>
                <ul>
                    <li><a href="#" class="active"><i class="ph ph-squares-four"></i> Dashboard</a></li>
                    <li><a href="symptom_checker.php"><i class="ph ph-clipboard-text"></i> Symptom Checker</a></li>
                    <li><a href="appointment_booking.php"><i class="ph ph-calendar-check"></i> Book Appointment</a></li>
                    <li><a href="payment_gateway.php"><i class="ph ph-credit-card"></i> Payments</a></li>
                    <li><a href="#" onclick="showPage('consultationHistory')"><i class="ph ph-clock-counter-clockwise"></i> History</a></li>
                    <li><a href="#" onclick="logout()"><i class="ph ph-sign-out"></i> Logout</a></li>
                </ul>
            </aside>
            <main class="main-area">
                <div class="dash-header">
                    <h2>Hello </h2>
                    <div style="display: flex; gap: 0.75rem;">
                        <a href="symptom_checker.php" class="btn btn-primary" style="text-decoration: none; display: inline-flex; align-items: center;">🩺 Symptom Checker</a>
                        <a href="appointment_booking.php" class="btn btn-outline" style="text-decoration: none; display: inline-flex; align-items: center;">📅 Book Appointment</a>
                    </div>
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
                    <li><a href="#" class="active"><i class="ph ph-clipboard-text"></i> Start Consultation</a></li>
                    <li><a href="appointment_booking.php"><i class="ph ph-calendar-check"></i> Book Appointment</a></li>
                    <li><a href="#" onclick="showPage('consultationHistory')"><i class="ph ph-clock-counter-clockwise"></i> History</a></li>
                    <li><a href="#" onclick="logout()"><i class="ph ph-sign-out"></i> Logout</a></li>
                </ul>
            </aside>
            <main class="main-area">
                <h2>Start New Consultation</h2>
                <div class="symptom-box">
                    <p style="margin-bottom: 1.5rem; color: #64748b;">Describe your symptoms using text or voice input. Our system will help match you with the right doctor.</p>
                    
                    <!-- Voice Input Section -->
                    <div style="text-align: center; margin-bottom: 2rem;">
                        <button class="voice-input-btn" id="voiceBtn" onclick="toggleVoiceInput()">
                            <i class="ph ph-microphone" id="voiceIcon" style="color: var(--primary); font-size: 1.5rem;"></i>
                            <span id="voiceText">Tap to Speak</span>
                        </button>
                        <p id="voiceStatus" style="margin-top: 0.5rem; font-size: 0.85rem; color: #64748b; min-height: 20px;"></p>
                    </div>

                    <!-- Consultation Form -->
                    <form id="consultationForm" onsubmit="submitConsultation(event)">
                        <!-- Symptoms -->
                        <div class="form-group">
                            <label>Symptoms <span style="color: red;">*</span></label>
                            <textarea 
                                id="symptomsInput" 
                                name="symptoms"
                                style="width: 100%; padding: 1rem; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; min-height: 120px;" 
                                rows="5" 
                                placeholder="Describe your symptoms in detail (e.g., I have a bad headache and mild fever since morning...)"
                                required
                            ></textarea>
                        </div>

                        <!-- Duration -->
                        <div class="form-group">
                            <label>Duration <span style="color: red;">*</span></label>
                            <select 
                                id="durationInput" 
                                name="duration"
                                style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-family: inherit;"
                                required
                            >
                                <option value="">Select duration</option>
                                <option value="Less than 24 hours">Less than 24 hours</option>
                                <option value="1-2 days">1-2 days</option>
                                <option value="3-7 days">3-7 days</option>
                                <option value="1-2 weeks">1-2 weeks</option>
                                <option value="2-4 weeks">2-4 weeks</option>
                                <option value="More than a month">More than a month</option>
                            </select>
                        </div>

                        <!-- Severity -->
                        <div class="form-group">
                            <label>Severity <span style="color: red;">*</span></label>
                            <div class="severity-options" style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                                <label class="severity-option">
                                    <input type="radio" name="severity" value="low" required>
                                    <span class="severity-label severity-low">🟢 Low</span>
                                </label>
                                <label class="severity-option">
                                    <input type="radio" name="severity" value="medium" required>
                                    <span class="severity-label severity-medium">🟡 Medium</span>
                                </label>
                                <label class="severity-option">
                                    <input type="radio" name="severity" value="high" required>
                                    <span class="severity-label severity-high">🔴 High</span>
                                </label>
                            </div>
                        </div>

                        <!-- Consultation Mode -->
                        <div class="form-group" style="margin-top: 1.5rem;">
                            <label>Preferred Consultation Mode <span style="color: red;">*</span></label>
                            <div class="mode-options" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 0.5rem;">
                                <label class="mode-option selected" onclick="selectConsultationMode(this)" style="border-radius: 12px; padding: 1.5rem; text-align: center; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                    <input type="radio" name="consultation_mode" value="text" checked required style="display:none;">
                                    <i class="ph ph-chat-circle" style="font-size: 2rem; display: block; margin-bottom: 0.75rem; color: #64748b;"></i>
                                    <span style="font-weight: 500; color: #475569;">Text Chat</span>
                                </label>
                                <label class="mode-option" onclick="selectConsultationMode(this)" style="border-radius: 12px; padding: 1.5rem; text-align: center; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                    <input type="radio" name="consultation_mode" value="audio" required style="display:none;">
                                    <i class="ph ph-microphone" style="font-size: 2rem; display: block; margin-bottom: 0.75rem; color: #64748b;"></i>
                                    <span style="font-weight: 500; color: #475569;">Audio Call</span>
                                </label>
                                <label class="mode-option" onclick="selectConsultationMode(this)" style="border-radius: 12px; padding: 1.5rem; text-align: center; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                    <input type="radio" name="consultation_mode" value="video" required style="display:none;">
                                    <i class="ph ph-video-camera" style="font-size: 2rem; display: block; margin-bottom: 0.75rem; color: #64748b;"></i>
                                    <span style="font-weight: 500; color: #475569;">Video Call</span>
                                </label>
                            </div>
                        </div>

                        <!-- File Upload -->
                        <div class="form-group" style="margin-top: 1.5rem;">
                            <label>Attach Medical Reports (Optional)</label>
                            <div style="border: 2px dashed #ddd; border-radius: 8px; padding: 2rem; text-align: center; cursor: pointer; position: relative;" onclick="document.getElementById('reportInput').click()">
                                <i class="ph ph-file-arrow-up" style="font-size: 2rem; color: #64748b;"></i>
                                <p style="margin-top: 1rem; font-size: 0.9rem; color: #64748b;">Drop files here or click to upload (PDF, JPG, PNG)</p>
                                <input type="file" id="reportInput" style="display:none;" accept=".pdf,.jpg,.jpeg,.png" onchange="handleFileSelect(this)">
                                <div id="fileName" style="margin-top: 0.5rem; font-weight: 600; color: var(--primary);"></div>
                            </div>
                        </div>

                        <!-- Optional Fields -->
                        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0;">
                            <h4 style="margin-bottom: 1rem; color: #64748b; font-size: 0.9rem;">Optional Information (helps with diagnosis)</h4>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <!-- Age -->
                                <div class="form-group">
                                    <label>Age</label>
                                    <input 
                                        type="number" 
                                        id="ageInput" 
                                        name="age"
                                        min="1" 
                                        max="120"
                                        style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;"
                                        placeholder="Your age"
                                    >
                                </div>

                                <!-- Gender -->
                                <div class="form-group">
                                    <label>Gender</label>
                                    <select 
                                        id="genderInput" 
                                        name="gender"
                                        style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;"
                                    >
                                        <option value="">Select gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Existing Conditions -->
                            <div class="form-group" style="margin-top: 1rem;">
                                <label>Existing Medical Conditions</label>
                                <textarea 
                                    id="conditionsInput" 
                                    name="existing_conditions"
                                    style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-family: inherit;" 
                                    rows="3" 
                                    placeholder="Any chronic conditions, allergies, or ongoing treatments (optional)"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                            <button type="button" class="btn btn-outline" onclick="showPage('patientDashboard')" style="flex: 1;">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="submitBtn" style="flex: 2;">Submit Consultation</button>
                        </div>
                    </form>

                    <!-- Success/Error Messages -->
                    <div id="formMessage" style="margin-top: 1rem; padding: 1rem; border-radius: 8px; display: none;"></div>
                </div>
            </main>
        </div>
    `,

    consultationHistory: () => `
        <div class="dashboard-container fade-in">
            <aside class="sidebar">
                <h3><i class="ph ph-user-circle"></i> Patient Portal</h3>
                <ul>
                    <li><a href="#" onclick="showPage('patientDashboard')"><i class="ph ph-squares-four"></i> Dashboard</a></li>
                    <li><a href="#" onclick="showPage('symptomChecker')"><i class="ph ph-clipboard-text"></i> Start Consultation</a></li>
                    <li><a href="appointment_booking.php"><i class="ph ph-calendar-check"></i> Book Appointment</a></li>
                    <li><a href="#" class="active"><i class="ph ph-clock-counter-clockwise"></i> History</a></li>
                    <li><a href="#" onclick="logout()"><i class="ph ph-sign-out"></i> Logout</a></li>
                </ul>
            </aside>
            <main class="main-area">
                <div class="dash-header">
                    <h2>Consultation History</h2>
                    <button class="btn btn-primary" onclick="window.location.href='symptom_checker.php'">+ New Consultation</button>
                </div>
                
                <div id="consultationsList" style="margin-top: 1.5rem;">
                    <p style="text-align: center; color: #64748b;">Loading consultations...</p>
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
        </div>
    `,

    clinicDashboard: () => `
        <div class="dashboard-container fade-in">
             <aside class="sidebar" style="background: #8e44ad;">
                <h3><i class="ph ph-hospital"></i> Clinic Portal</h3>
                <ul>
                    <li><a href="#" class="active"><i class="ph ph-squares-four"></i> Overview</a></li>
                    <li><a href="#"><i class="ph ph-users-three"></i> Staff</a></li>
                    <li><a href="#" onclick="logout()"><i class="ph ph-sign-out"></i> Logout</a></li>
                </ul>
            </aside>
             <main class="main-area">
                <div class="dash-header">
                    <h2>Clinic Overview</h2>
                    <span style="background: #dff0d8; color: #3c763d; padding: 0.5rem 1rem; border-radius: 20px;">Fully Verified</span>
                </div>

                <div class="stat-row">
                    <div class="stat-box">
                        <div class="large-number text-gradient">8</div>
                        <p>Specialists</p>
                    </div>
                    <div class="stat-box">
                        <div class="large-number">24</div>
                        <p>Appointments Today</p>
                    </div>
                </div>

                <h3>Recent Activity</h3>
                <div class="symptom-box" style="width: 100%; max-width: 100%; margin-top: 1rem;">
                    <p>No pending activities.</p>
                </div>
            </main>
        </div>
    `,

    pharmacyDashboard: () => `
        <div class="dashboard-container fade-in">
             <aside class="sidebar" style="background: #27ae60;">
                <h3><i class="ph ph-first-aid-kit"></i> Pharmacy Portal</h3>
                <ul>
                    <li><a href="#" class="active"><i class="ph ph-prescription"></i> Orders</a></li>
                    <li><a href="#"><i class="ph ph-package"></i> Inventory</a></li>
                    <li><a href="#" onclick="logout()"><i class="ph ph-sign-out"></i> Logout</a></li>
                </ul>
            </aside>
             <main class="main-area">
                <div class="dash-header">
                    <h2>Pharmacy Orders</h2>
                    <span style="background: #dff0d8; color: #3c763d; padding: 0.5rem 1rem; border-radius: 20px;">Open for Business</span>
                </div>

                <div class="stat-row">
                    <div class="stat-box">
                        <div class="large-number text-gradient">12</div>
                        <p>New Prescriptions</p>
                    </div>
                </div>

                <h3>Recent Requests</h3>
                <div class="symptom-box" style="width: 100%; max-width: 100%; margin-top: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0;">
                         <div>
                            <strong>Rx #8899 - Patient: John Doe</strong><br>
                            <small>Amoxicillin 500mg</small>
                        </div>
                         <button class="btn btn-primary">Process</button>
                    </div>
                </div>
            </main>
        </div>
    `
};

// --- Logic functions ---

function showPage(pageName) {
    if (!PAGES[pageName]) return;
    const app = document.getElementById('app');
    if (app) {
        app.innerHTML = PAGES[pageName]();
        state.activePage = pageName;

        // Load consultations when viewing history
        if (pageName === 'consultationHistory') {
            setTimeout(loadConsultationHistory, 100);
        }
    }
}

// Auth functions removed - now handled by login.php and signup.php

function logout() {
    sessionStorage.removeItem('currentUser');
    window.location.href = 'login.php';
}


// --- Consultation Functions ---

let recognition = null;
let isRecording = false;
let inputMethod = 'text';

// Initialize speech recognition
function initSpeechRecognition() {
    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SpeechRecognition();
        recognition.continuous = false;
        recognition.interimResults = true;
        recognition.lang = 'en-US';

        recognition.onstart = function () {
            isRecording = true;
            const voiceBtn = document.getElementById('voiceBtn');
            const voiceIcon = document.getElementById('voiceIcon');
            const voiceText = document.getElementById('voiceText');
            const voiceStatus = document.getElementById('voiceStatus');

            if (voiceBtn) voiceBtn.classList.add('recording');
            if (voiceIcon) voiceIcon.style.color = '#ef4444';
            if (voiceText) voiceText.textContent = 'Listening...';
            if (voiceStatus) voiceStatus.textContent = 'Speak now...';
        };

        recognition.onresult = function (event) {
            let interimTranscript = '';
            let finalTranscript = '';

            for (let i = event.resultIndex; i < event.results.length; i++) {
                const transcript = event.results[i][0].transcript;
                if (event.results[i].isFinal) {
                    finalTranscript += transcript + ' ';
                } else {
                    interimTranscript += transcript;
                }
            }

            const symptomsInput = document.getElementById('symptomsInput');
            if (symptomsInput) {
                if (finalTranscript) {
                    symptomsInput.value += finalTranscript;
                    inputMethod = 'voice';
                }
                const voiceStatus = document.getElementById('voiceStatus');
                if (voiceStatus && interimTranscript) {
                    voiceStatus.textContent = interimTranscript;
                }
            }
        };

        recognition.onerror = function (event) {
            console.error('Speech recognition error:', event.error);
            const voiceStatus = document.getElementById('voiceStatus');
            if (voiceStatus) {
                voiceStatus.textContent = 'Error: ' + event.error;
                voiceStatus.style.color = '#ef4444';
            }
            stopVoiceInput();
        };

        recognition.onend = function () {
            stopVoiceInput();
        };
    }
}

function toggleVoiceInput() {
    if (!recognition) {
        initSpeechRecognition();
    }

    if (!recognition) {
        alert('Voice input is not supported in your browser. Please use Chrome, Edge, or Safari.');
        return;
    }

    if (isRecording) {
        recognition.stop();
    } else {
        recognition.start();
    }
}

function stopVoiceInput() {
    isRecording = false;
    const voiceBtn = document.getElementById('voiceBtn');
    const voiceIcon = document.getElementById('voiceIcon');
    const voiceText = document.getElementById('voiceText');
    const voiceStatus = document.getElementById('voiceStatus');

    if (voiceBtn) voiceBtn.classList.remove('recording');
    if (voiceIcon) voiceIcon.style.color = 'var(--primary)';
    if (voiceText) voiceText.textContent = 'Tap to Speak';
    if (voiceStatus) {
        setTimeout(() => {
            voiceStatus.textContent = '';
            voiceStatus.style.color = '#64748b';
        }, 2000);
    }
}

// Submit consultation form
async function submitConsultation(event) {
    event.preventDefault();

    const form = document.getElementById('consultationForm');
    const submitBtn = document.getElementById('submitBtn');
    const formMessage = document.getElementById('formMessage');

    // Get form data
    const formData = {
        symptoms: document.getElementById('symptomsInput').value.trim(),
        duration: document.getElementById('durationInput').value,
        severity: document.querySelector('input[name="severity"]:checked')?.value,
        consultation_mode: document.querySelector('input[name="consultation_mode"]:checked')?.value || 'text',
        age: document.getElementById('ageInput').value || null,
        gender: document.getElementById('genderInput').value || null,
        existing_conditions: document.getElementById('conditionsInput').value.trim() || null,
        input_method: inputMethod,
        attachment_base64: window.tempReportBase64 || null,
        attachment_name: window.tempReportName || null
    };

    // Validate required fields
    if (!formData.symptoms || !formData.duration || !formData.severity) {
        showFormMessage('Please fill in all required fields.', 'error');
        return;
    }

    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';

    try {
        const response = await fetch('start_consultation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (result.success) {
            showFormMessage('Consultation submitted successfully! Redirecting...', 'success');
            form.reset();
            window.tempReportBase64 = null;
            window.tempReportName = null;
            inputMethod = 'text';

            // Redirect to consultation room after 2 seconds
            setTimeout(() => {
                window.location.href = 'consultation_room.php?id=' + result.consultation_id;
            }, 2000);
        } else {
            showFormMessage(result.error || 'Failed to submit consultation. Please try again.', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Consultation';
        }
    } catch (error) {
        console.error('Error submitting consultation:', error);

        // Try to get raw text if it was a parsing error
        let errorMsg = 'Network error. Please check your connection and try again.';
        if (error instanceof SyntaxError) {
            console.log('JSON Parsing failed. Response might be corrupted.');
        }

        showFormMessage(errorMsg, 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Consultation';
    }
}

function selectConsultationMode(element) {
    // Remove selected class from all siblings
    const options = document.querySelectorAll('.mode-option');
    options.forEach(opt => opt.classList.remove('selected'));

    // Add selected class to clicked element
    element.classList.add('selected');

    // Ensure the radio button inside is checked
    const radio = element.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
}

function handleFileSelect(input) {
    const file = input.files[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
        alert('File size exceeds 5MB limit.');
        input.value = '';
        return;
    }

    const fileNameDisplay = document.getElementById('fileName');
    if (fileNameDisplay) fileNameDisplay.textContent = 'Selected: ' + file.name;

    const reader = new FileReader();
    reader.onload = function (e) {
        window.tempReportBase64 = e.target.result.split(',')[1];
        window.tempReportName = file.name;
    };
    reader.readAsDataURL(file);
}

function showFormMessage(message, type) {
    const formMessage = document.getElementById('formMessage');
    if (!formMessage) return;

    formMessage.textContent = message;
    formMessage.style.display = 'block';
    formMessage.style.backgroundColor = type === 'success' ? '#d4edda' : '#f8d7da';
    formMessage.style.color = type === 'success' ? '#155724' : '#721c24';
    formMessage.style.border = `1px solid ${type === 'success' ? '#c3e6cb' : '#f5c6cb'}`;
}

// Load consultation history
async function loadConsultationHistory() {
    const consultationsList = document.getElementById('consultationsList');
    if (!consultationsList) return;

    try {
        const response = await fetch('get_consultations.php');
        const result = await response.json();

        if (result.success) {
            if (result.consultations.length === 0) {
                consultationsList.innerHTML = `
                    <div style="text-align: center; padding: 3rem; color: #64748b;">
                        <i class="ph ph-clipboard-text" style="font-size: 4rem; opacity: 0.3;"></i>
                        <p style="margin-top: 1rem;">No consultations yet</p>
                        <button class="btn btn-primary" onclick="showPage('symptomChecker')" style="margin-top: 1rem;">Start Your First Consultation</button>
                    </div>
                `;
            } else {
                consultationsList.innerHTML = result.consultations.map(consultation => `
                    <div class="consultation-card" style="background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <span class="status-badge status-${consultation.status}" style="padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 500;">
                                        ${consultation.status.charAt(0).toUpperCase() + consultation.status.slice(1)}
                                    </span>
                                    <span style="color: #94a3b8; font-size: 0.85rem;">
                                        ${consultation.created_at_formatted}
                                    </span>
                                </div>
                                <p style="color: #1e293b; margin-bottom: 0.5rem;">
                                    <strong>Symptoms:</strong> ${consultation.symptoms_preview}
                                </p>
                                <div style="display: flex; gap: 1.5rem; font-size: 0.9rem; color: #64748b;">
                                    <span><strong>Duration:</strong> ${consultation.duration}</span>
                                    <span><strong>Severity:</strong> <span class="severity-${consultation.severity}">${consultation.severity.toUpperCase()}</span></span>
                                    ${consultation.input_method === 'voice' ? '<span style="color: var(--primary);"><i class="ph ph-microphone"></i> Voice Input</span>' : ''}
                                </div>
                                ${(consultation.status === 'assigned' || consultation.status === 'in_progress') ? `
                                    <div style="margin-top: 1rem;">
                                        <a href="consultation_room.php?id=${consultation.id}" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem; text-decoration: none; display: inline-block;">Join Consultation</a>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `).join('');
            }
        } else {
            consultationsList.innerHTML = `
                <div style="text-align: center; padding: 2rem; color: #ef4444;">
                    <p>Error loading consultations: ${result.error}</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading consultations:', error);
        consultationsList.innerHTML = `
            <div style="text-align: center; padding: 2rem; color: #ef4444;">
                <p>Network error. Please try again later.</p>
            </div>
        `;
    }
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

function toggleMenu() {
    const navLinks = document.getElementById('navLinks');
    navLinks.classList.toggle('active');
}

function navigateToSection(sectionId) {
    // If it's a page that is NOT landing, load landing first
    if (sectionId === 'landing' || sectionId === 'services' || sectionId === 'doctors' || sectionId === 'contact') {
        if (state.activePage !== 'landing') {
            showPage('landing');
            // Allow DOM to update then scroll
            setTimeout(() => {
                if (sectionId !== 'landing') {
                    const el = document.getElementById(sectionId);
                    if (el) el.scrollIntoView({ behavior: 'smooth' });
                }
            }, 100);
        } else {
            // Already on landing, just scroll
            if (sectionId !== 'landing') {
                const el = document.getElementById(sectionId);
                if (el) el.scrollIntoView({ behavior: 'smooth' });
            } else {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
    } else {
        // It's a page like 'auth' or others (though currently only landing sections are passed here usually)
        // If we want to support direct page names here:
        showPage(sectionId);
    }

    // Close mobile menu if open
    const navLinks = document.getElementById('navLinks');
    if (navLinks.classList.contains('active')) {
        navLinks.classList.remove('active');
    }
}

function navigateToDashboard() {
    const userJson = sessionStorage.getItem('currentUser');
    if (!userJson) return window.location.href = 'login.php';
    const user = JSON.parse(userJson);

    if (user.role === 'patient') showPage('patientDashboard');
    else if (user.role === 'doctor') window.location.href = 'doctor_dashboard.php';
    else if (user.role === 'pharmacy') showPage('pharmacyDashboard');
    else if (user.role === 'clinic' || user.role === 'hospital') showPage('clinicDashboard');
    else if (user.role === 'admin') window.location.href = 'admin_dashboard.php';
}

// Contact Form Submission
function submitContactForm(event) {
    event.preventDefault();

    const name = document.getElementById('contactName').value.trim();
    const email = document.getElementById('contactEmail').value.trim();
    const message = document.getElementById('contactMessage').value.trim();
    const submitBtn = document.getElementById('contactSubmitBtn');
    const formMessage = document.getElementById('contactFormMessage');

    // Validate
    if (!name || !email || !message) {
        showContactMessage('Please fill in all fields.', 'error');
        return;
    }

    // Disable button
    submitBtn.disabled = true;
    submitBtn.textContent = 'Sending...';

    // Simulate sending (in real app, this would be an API call)
    setTimeout(() => {
        showContactMessage('Thank you for contacting us! We\'ll get back to you within 24 hours.', 'success');
        document.getElementById('contactForm').reset();
        submitBtn.disabled = false;
        submitBtn.textContent = 'Send Message';

        // Hide message after 5 seconds
        setTimeout(() => {
            formMessage.style.display = 'none';
        }, 5000);
    }, 1000);
}

function showContactMessage(message, type) {
    const formMessage = document.getElementById('contactFormMessage');
    if (!formMessage) return;

    formMessage.textContent = message;
    formMessage.style.display = 'block';
    formMessage.style.backgroundColor = type === 'success' ? '#d4edda' : '#f8d7da';
    formMessage.style.color = type === 'success' ? '#155724' : '#721c24';
    formMessage.style.border = `1px solid ${type === 'success' ? '#c3e6cb' : '#f5c6cb'}`;
}

// Doctor Profile Modal
function viewDoctorProfile(name, specialty, experience, bio) {
    // Create modal overlay
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
            animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
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
                font-size: 1.2rem;
                cursor: pointer;
                color: #64748b;
                transition: all 0.2s;
            " onmouseover="this.style.background='rgba(244, 63, 94, 0.1)'; this.style.color='#f43f5e'" onmouseout="this.style.background='rgba(0,0,0,0.05)'; this.style.color='#64748b'">
                <i class="ph ph-x"></i>
            </button>
            
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="width: 100px; height: 100px; background: var(--secondary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; box-shadow: 0 10px 20px rgba(13, 148, 136, 0.1);">
                    <i class="ph ph-user-circle" style="font-size: 5rem; color: var(--primary);"></i>
                </div>
            </div>
            
            <h2 style="text-align: center; margin-bottom: 0.5rem; font-size: 2rem;">${name}</h2>
            <p style="text-align: center; color: var(--primary); font-weight: 700; font-size: 1.2rem; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 1px;">${specialty}</p>
            <p style="text-align: center; color: #64748b; font-size: 0.95rem; margin-bottom: 2rem; font-weight: 500;">${experience}</p>
            
            <div style="background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%); padding: 2rem; border-radius: 20px; margin-bottom: 2.5rem; border: 1px solid rgba(13, 148, 136, 0.1);">
                <h4 style="margin-bottom: 0.75rem; color: var(--primary-dark); font-size: 1rem; text-transform: uppercase; letter-spacing: 1px;">Professional Background</h4>
                <p style="color: #475569; line-height: 1.7; font-size: 1rem;">${bio}</p>
            </div>
            
            <div style="display: flex; gap: 1.2rem;">
                ${state.currentUser
            ? `<button onclick="showPage('symptomChecker'); closeDoctorModal();" class="btn btn-primary" style="flex: 2;">Instant Consultation</button>`
            : `<a href="login.php" class="btn btn-primary" style="flex: 2; text-decoration: none;">Login to Book</a>`
        }
                <button onclick="closeDoctorModal()" class="btn btn-outline" style="flex: 1; border-color: #e2e8f0; color: #64748b;">Dismiss</button>
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

// Close modal on outside click
document.addEventListener('click', function (event) {
    const modal = document.getElementById('doctorModal');
    if (modal && event.target === modal) {
        closeDoctorModal();
    }
});

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    // Initialize landing page
    const urlParams = new URLSearchParams(window.location.search);
    // Always show landing page - login/signup are now separate PHP pages
    showPage('landing');
});

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - MedConnect</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root {
            --primary: #0284c7;
            --primary-hover: #0369a1;
            --bg-light: #f8fafc;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }
        .step-container { display: none; }
        .step-active { display: block; animation: fadeIn 0.5s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .step-indicator { display: flex; justify-content: space-between; margin-bottom: 2.5rem; position: relative; padding: 0 10px; }
        .step-indicator::before { content: ''; position: absolute; top: 15px; left: 0; right: 0; height: 2px; background: #e2e8f0; z-index: 1; }
        .step-dot { width: 32px; height: 32px; border-radius: 50%; background: #fff; border: 2px solid #e2e8f0; z-index: 2; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #94a3b8; font-size: 0.85rem; }
        .step-dot.active { border-color: var(--primary); color: var(--primary); box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.1); }
        .step-dot.completed { background: var(--primary); border-color: var(--primary); color: #fff; }
        
        .role-cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-top: 1.5rem; }
        .role-card { border: 2px solid #f1f5f9; border-radius: 16px; padding: 1.5rem 1rem; text-align: center; cursor: pointer; transition: all 0.2s ease; background: #fff; }
        .role-card:hover { border-color: var(--primary); transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); }
        .role-card.active { border-color: var(--primary); background: #f0f7ff; }
        .role-card i { font-size: 2.5rem; color: var(--primary); margin-bottom: 0.75rem; display: block; }
        .role-card span { font-weight: 600; font-size: 0.95rem; color: var(--text-dark); }
        
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.9rem; color: var(--text-dark); }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; border-radius: 10px; font-family: inherit; font-size: 1rem; transition: border-color 0.2s; }
        .form-group input:focus { outline: none; border-color: var(--primary); ring: 2px var(--primary); }
        
        .btn { padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s; border: none; font-size: 1rem; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-primary:disabled { background: #cbd5e1; cursor: not-allowed; }
        .btn-outline { background: transparent; border: 1px solid #e2e8f0; color: var(--text-muted); }
        .btn-outline:hover { background: #f8fafc; }
    </style>
</head>
<body>
    <div class="auth-container fade-in" style="margin-top: 3rem; max-width: 500px; padding: 2.5rem; background: #fff; border-radius: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <div style="margin-bottom: 2rem; color: var(--primary); text-align: center;">
            <i class="ph ph-heartbeat" style="font-size: 3.5rem;"></i>
            <h2 id="stepTitle" style="margin-top: 1rem; font-size: 1.75rem;">Create Account</h2>
            <p id="stepSub" style="color: var(--text-muted);">Join MedConnect today</p>
        </div>

        <div class="step-indicator">
            <div class="step-dot active" id="dot1">1</div>
            <div class="step-dot" id="dot2">2</div>
            <div class="step-dot" id="dot3">3</div>
        </div>

        <!-- Step 1: Account Creation -->
        <div id="step1" class="step-container step-active">
            <form id="signupForm" onsubmit="handleSignup(event)">
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label>Full Name</label>
                    <input type="text" id="signupName" placeholder="John Doe" required>
                </div>
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label>Email Address</label>
                    <input type="email" id="signupEmail" placeholder="user@example.com" required>
                </div>
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label>Phone Number</label>
                    <input type="tel" id="signupPhone" placeholder="+1 234 567 890">
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>Password</label>
                    <input type="password" id="signupPassword" placeholder="Create a password" minlength="6" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%">Continue</button>
            </form>
            <p style="margin-top: 1.5rem; font-size: 0.95rem; text-align: center; color: var(--text-muted);">
                Already have an account? <a href="login.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Login</a>
            </p>
        </div>



        <!-- Step 2: Role Selection -->
        <div id="step2" class="step-container">
            <p style="text-align: center; color: var(--text-muted); margin-bottom: 1.5rem;">Choose your account type to proceed</p>
            <div class="role-cards">
                <div class="role-card" onclick="selectRole('patient')">
                    <i class="ph ph-user"></i>
                    <span>Patient</span>
                </div>
                <div class="role-card" onclick="selectRole('doctor')">
                    <i class="ph ph-stethoscope"></i>
                    <span>Doctor</span>
                </div>
                <div class="role-card" onclick="selectRole('clinic')">
                    <i class="ph ph-hospital"></i>
                    <span>Clinic</span>
                </div>
                <div class="role-card" onclick="selectRole('pharmacy')">
                    <i class="ph ph-pill"></i>
                    <span>Pharmacy</span>
                </div>
            </div>
            <input type="hidden" id="selectedRole">
            <button id="roleContinueBtn" onclick="goToOnboarding()" class="btn btn-primary" style="width: 100%; margin-top: 2rem;" disabled>Continue</button>
        </div>

        <!-- Step 3: Conditional Onboarding -->
        <div id="step3" class="step-container">
            <form id="onboardingForm" onsubmit="handleOnboarding(event)">
                <div id="onboardingFields">
                    <!-- Fields injected dynamicly -->
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;">Complete Registration</button>
            </form>
        </div>

        <p style="margin-top: 2rem; font-size: 0.95rem; text-align: center;">
            <a href="index.php" style="color: var(--text-muted); text-decoration: none;">← Back to Home</a>
        </p>
    </div>

    <script>
        let userData = {
            id: null,
            email: null,
            role: null
        };

        // Initialize from URL parameters
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            const step = parseInt(urlParams.get('step'));
            const email = urlParams.get('email');
            
            if (email) {
                userData.email = email;
            }

            if (step === 2) {
                const tempUser = JSON.parse(sessionStorage.getItem('tempUser'));
                if (tempUser) {
                    userData.id = tempUser.id;
                    userData.email = tempUser.email;
                    showStep(2);
                }
            }
        };

        function showStep(step) {
            document.querySelectorAll('.step-container').forEach(el => el.classList.remove('step-active'));
            document.getElementById('step' + step).classList.add('step-active');
            
            // Update dots
            document.querySelectorAll('.step-dot').forEach((dot, index) => {
                const stepNum = index + 1;
                if (stepNum < step) dot.className = 'step-dot completed';
                else if (stepNum === step) dot.className = 'step-dot active';
                else dot.className = 'step-dot';
                
                if (stepNum < step) dot.innerHTML = '<i class="ph ph-check"></i>';
                else dot.innerHTML = stepNum;
            });

            // Update titles
            const titles = {
                1: ["Create Account", "Join MedConnect today"],
                2: ["Account Type", "How will you use the platform?"],
                3: ["Profile Setup", "Just a few more details"]
            };
            document.getElementById('stepTitle').innerText = titles[step][0];
            document.getElementById('stepSub').innerText = titles[step][1];
        }

        function handleSignup(event) {
            event.preventDefault();
            const btn = event.submitter;
            btn.disabled = true;
            btn.innerText = 'Creating account...';

            const formData = new FormData();
            formData.append('action', 'signup');
            formData.append('name', document.getElementById('signupName').value);
            formData.append('email', document.getElementById('signupEmail').value);
            formData.append('phone', document.getElementById('signupPhone').value);
            formData.append('password', document.getElementById('signupPassword').value);

            fetch('auth.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        userData.id = data.id;
                        userData.email = data.email;
                        showStep(2);
                    } else {
                        alert(data.message);
                        btn.disabled = false;
                        btn.innerText = 'Continue';
                    }
                })
                .catch(err => {
                    alert("Error: " + err.message);
                    btn.disabled = false;
                    btn.innerText = 'Continue';
                });
        }



        function selectRole(role) {
            document.querySelectorAll('.role-card').forEach(c => c.classList.remove('active'));
            event.currentTarget.classList.add('active');
            document.getElementById('selectedRole').value = role;
            document.getElementById('roleContinueBtn').disabled = false;
        }

        function goToOnboarding() {
            const role = document.getElementById('selectedRole').value;
            userData.role = role;
            const fields = document.getElementById('onboardingFields');
            fields.innerHTML = '';

            if (role === 'patient') {
                fields.innerHTML = `
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label>Gender</label>
                        <select name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Medical History (Optional)</label>
                        <textarea name="medical_history" rows="3" placeholder="Tell us about any chronic conditions or allergies..."></textarea>
                    </div>
                `;
            } else if (role === 'doctor') {
                fields.innerHTML = `
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label>Medical License Number</label>
                        <input type="text" name="license" placeholder="MED-123456" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label>Specialization</label>
                        <input type="text" name="specialization" placeholder="e.g. Cardiologist" required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label>Experience (Years)</label>
                            <input type="number" name="experience" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Consultation Fee ($)</label>
                            <input type="number" name="fees" min="0" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Languages Spoken</label>
                        <input type="text" name="languages" placeholder="e.g. English, French" required>
                    </div>
                `;
            } else if (role === 'clinic') {
                fields.innerHTML = `
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label>Clinic Name</label>
                        <input type="text" name="org_name" placeholder="City Wellness Clinic" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label>Registration Number</label>
                        <input type="text" name="reg_number" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label>Departments</label>
                        <input type="text" name="departments" placeholder="OPD, Radiology, etc." required>
                    </div>
                    <div class="form-group">
                        <label>Full Address</label>
                        <textarea name="address" rows="2" required></textarea>
                    </div>
                `;
            } else if (role === 'pharmacy') {
                fields.innerHTML = `
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label>Pharmacy Name</label>
                        <input type="text" name="pharmacy_name" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label>License Details</label>
                        <input type="text" name="license" required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label>Operating Hours</label>
                            <input type="text" name="hours" placeholder="9 AM - 9 PM" required>
                        </div>
                        <div class="form-group">
                            <label>Delivery</label>
                            <select name="delivery" required>
                                <option value="pickup">Pickup</option>
                                <option value="delivery">Delivery</option>
                                <option value="both">Both</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" rows="2" required></textarea>
                    </div>
                `;
            }
            showStep(3);
        }

        function handleOnboarding(event) {
            event.preventDefault();
            const btn = event.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerText = 'Saving profile...';

            const formData = new FormData(event.target);
            formData.append('action', 'complete_onboarding');
            formData.append('user_id', userData.id);
            formData.append('role', userData.role);

            fetch('auth.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        if (userData.role === 'patient') {
                            alert("Welcome to MedConnect! Your account is active.");
                        } else {
                            alert("Thank you! Your profile has been submitted for admin verification. You'll be notified once approved.");
                        }
                        window.location.href = 'login.php';
                    } else {
                        alert(data.message);
                        btn.disabled = false;
                        btn.innerText = 'Complete Registration';
                    }
                });
        }
    </script>
</body>
</html>
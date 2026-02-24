<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - MedConnect</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="assets/css/custom_modal.css?v=<?php echo time(); ?>">
    <script src="assets/js/custom_modal.js?v=<?php echo time(); ?>"></script>
    <style>
        .step-indicator {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 2rem;
            gap: 1rem;
        }
        
        .step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #999;
            font-size: 0.9rem;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .step.active {
            color: var(--primary);
        }
        
        .step.active .step-number {
            background: var(--primary);
            color: white;
        }
        
        .step.completed .step-number {
            background: #4CAF50;
            color: white;
        }
        
        .step-divider {
            width: 40px;
            height: 2px;
            background: #ddd;
        }
        
        .form-step {
            display: none;
        }
        
        .form-step.active {
            display: block;
        }
        
        .otp-input {
            text-align: center;
            font-size: 1.5rem;
            letter-spacing: 0.5rem;
            font-weight: 600;
        }
        
        .timer {
            text-align: center;
            margin-top: 1rem;
            color: #666;
            font-size: 0.9rem;
        }
        
        .timer.expired {
            color: #f44336;
        }
        
        .resend-link {
            color: var(--primary);
            cursor: pointer;
            text-decoration: underline;
        }
        
        .resend-link.disabled {
            color: #999;
            cursor: not-allowed;
            text-decoration: none;
        }
        
        .debug-box {
            margin-top: 1rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            border: 2px solid #667eea;
            border-radius: 12px;
            font-family: 'Courier New', monospace;
            color: #333;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .debug-box strong {
            color: #667eea;
            font-size: 1rem;
            display: block;
            margin-bottom: 0.75rem;
        }
        
        .debug-otp {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            letter-spacing: 0.5rem;
            text-align: center;
            margin: 1rem 0;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            border: 2px dashed #667eea;
            user-select: all;
        }
        
        .copy-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: block;
            margin: 1rem auto 0;
        }
        
        .copy-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }
        
        .copy-btn:active {
            transform: translateY(0);
        }
        
        .debug-info {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.5rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="auth-container fade-in" style="margin-top: 5rem;">
        <div style="margin-bottom: 2rem; color: var(--primary);">
            <i class="ph ph-lock-key" style="font-size: 3rem;"></i>
        </div>
        <h2>Reset Password</h2>
        
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step" id="step-indicator-1">
                <div class="step-number">1</div>
                <span>Email</span>
            </div>
            <div class="step-divider"></div>
            <div class="step" id="step-indicator-2">
                <div class="step-number">2</div>
                <span>OTP</span>
            </div>
            <div class="step-divider"></div>
            <div class="step" id="step-indicator-3">
                <div class="step-number">3</div>
                <span>Password</span>
            </div>
        </div>

        <!-- Step 1: Email Input -->
        <div class="form-step active" id="step-1">
            <p>Enter your email address to receive OTP</p>
            <form id="emailForm" onsubmit="handleEmailSubmit(event)">
                <div class="form-group" style="margin-top: 1.5rem;">
                    <label>Email Address</label>
                    <input type="email" id="resetEmail" name="email" placeholder="user@example.com" required autocomplete="email">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%">Send OTP</button>
            </form>
        </div>

        <!-- Step 2: OTP Verification -->
        <div class="form-step" id="step-2">
            <p>Enter the 6-digit OTP sent to your email</p>
            <form id="otpForm" onsubmit="handleOtpSubmit(event)">
                <div class="form-group" style="margin-top: 1.5rem;">
                    <label>Enter OTP</label>
                    <input type="text" id="otpInput" name="otp" placeholder="000000" maxlength="6" pattern="[0-9]{6}" class="otp-input" required autocomplete="off">
                </div>
                
                <div class="timer" id="timer">
                    Time remaining: <span id="countdown">5:00</span>
                </div>
                
                <div style="text-align: center; margin-top: 0.5rem; font-size: 0.9rem;">
                    Didn't receive OTP? <span class="resend-link disabled" id="resendLink">Resend OTP</span>
                </div>
                
                <div id="debugBox" class="debug-box" style="display: none;">
                    <strong>🔧 Debug Mode (Localhost)</strong>
                    <div class="debug-otp" id="debugOtp">------</div>
                    <button type="button" class="copy-btn" onclick="copyOTP()">📋 Copy OTP</button>
                    <div class="debug-info" id="debugInfo">Click the button above to copy</div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;">Verify OTP</button>
                <button type="button" class="btn" style="width: 100%; margin-top: 0.5rem;" onclick="goToStep(1)">← Back</button>
            </form>
        </div>

        <!-- Step 3: Password Reset -->
        <div class="form-step" id="step-3">
            <p>Create a new password for your account</p>
            <form id="resetPasswordForm" onsubmit="handlePasswordReset(event)">
                <div class="form-group" style="margin-top: 1.5rem;">
                    <label>New Password</label>
                    <input type="password" id="newPassword" name="password" placeholder="Enter new password" minlength="6" required autocomplete="new-password">
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" id="confirmPassword" placeholder="Confirm new password" minlength="6" required autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%">Reset Password & Login</button>
            </form>
        </div>

        <p style="margin-top: 1rem; font-size: 0.9rem; color: #666;">
            Remember your password? <a href="login.php" style="color: var(--primary)">Back to Login</a>
        </p>
        <p style="margin-top: 0.5rem; font-size: 0.9rem;">
            <a href="index.php" style="color: var(--primary)">← Back to Home</a>
        </p>
    </div>

    <script>
        let currentStep = 1;
        let userEmail = '';
        let countdownTimer = null;
        let timeRemaining = 30; // 30 seconds for quick testing

        // Update step indicators
        function updateStepIndicators() {
            for (let i = 1; i <= 3; i++) {
                const indicator = document.getElementById(`step-indicator-${i}`);
                indicator.classList.remove('active', 'completed');
                
                if (i < currentStep) {
                    indicator.classList.add('completed');
                } else if (i === currentStep) {
                    indicator.classList.add('active');
                }
            }
        }

        // Navigate to specific step
        function goToStep(step) {
            // Hide all steps
            document.querySelectorAll('.form-step').forEach(el => {
                el.classList.remove('active');
            });
            
            // Show current step
            document.getElementById(`step-${step}`).classList.add('active');
            currentStep = step;
            updateStepIndicators();
            
            // Stop timer if going back
            if (step !== 2 && countdownTimer) {
                clearInterval(countdownTimer);
            }
        }

        // Start countdown timer
        function startCountdown() {
            timeRemaining = 30; // Reset to 30 seconds
            const countdownEl = document.getElementById('countdown');
            const timerEl = document.getElementById('timer');
            const resendLink = document.getElementById('resendLink');
            
            resendLink.classList.add('disabled');
            timerEl.classList.remove('expired');
            
            if (countdownTimer) {
                clearInterval(countdownTimer);
            }
            
            countdownTimer = setInterval(() => {
                timeRemaining--;
                
                const minutes = Math.floor(timeRemaining / 60);
                const seconds = timeRemaining % 60;
                countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                
                if (timeRemaining <= 0) {
                    clearInterval(countdownTimer);
                    timerEl.classList.add('expired');
                    countdownEl.textContent = 'Expired';
                    resendLink.classList.remove('disabled');
                }
            }, 1000);
        }

        // Handle Email Submit (Step 1)
        async function handleEmailSubmit(event) {
            event.preventDefault();
            
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.textContent;
            
            userEmail = document.getElementById('resetEmail').value.trim();
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending OTP...';
            
            const formData = new FormData();
            formData.append('action', 'request_reset');
            formData.append('email', userEmail);
 
            console.log('Requesting OTP for:', userEmail);
 
            fetch('forgot_password.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(async data => {
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
                
                console.log('Response:', data);
                
                if (data.status === 'success') {
                    // Show appropriate message
                    let message = data.message;
                    
                    // Only show debug box if email failed
                    if (data.debug_otp && data.email_error) {
                        message += '\n\nEmail Error: ' + data.email_error;
                        message += '\n\nPlease use the debug OTP shown below to continue.';
                        
                        // Show debug OTP only when email fails
                        document.getElementById('debugOtp').textContent = data.debug_otp;
                        document.getElementById('debugBox').style.display = 'block';
                        
                        const debugInfo = document.getElementById('debugInfo');
                        debugInfo.innerHTML = '⚠️ Email delivery failed. Use this OTP to continue.';
                        debugInfo.style.color = '#f44336';
                    } else {
                        // Email sent successfully - hide debug box
                        document.getElementById('debugBox').style.display = 'none';
                    }
                    
                    await alert(message);
                    
                    // Move to step 2
                    goToStep(2);
                    startCountdown();
                } else {
                    await alert(data.message || 'Failed to send OTP');
                }
            })
            .catch(async err => {
                console.error('Error:', err);
                await alert('Failed to send OTP. Please try again.');
            });
        }

        // Handle OTP Submit (Step 2)
        async function handleOtpSubmit(event) {
            event.preventDefault();
            
            const otp = document.getElementById('otpInput').value.trim();
            
            if (otp.length !== 6) {
                await alert('Please enter a 6-digit OTP');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'verify_otp');
            formData.append('email', userEmail);
            formData.append('otp', otp);

            console.log('Verifying OTP:', otp);

            fetch('forgot_password.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(async data => {
                console.log('Response:', data);
                
                if (data.status === 'success') {
                    await alert(data.message);
                    clearInterval(countdownTimer);
                    goToStep(3);
                } else {
                    await alert(data.message || 'Invalid OTP');
                }
            })
            .catch(async err => {
                console.error('Error:', err);
                await alert('Failed to verify OTP. Please try again.');
            });
        }

        // Handle Password Reset (Step 3)
        async function handlePasswordReset(event) {
            event.preventDefault();
            
            const password = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (password !== confirmPassword) {
                await alert('Passwords do not match!');
                return;
            }

            if (password.length < 6) {
                await alert('Password must be at least 6 characters long');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'reset_password');
            formData.append('email', userEmail);
            formData.append('password', password);

            console.log('Resetting password...');

            fetch('forgot_password.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(async data => {
                console.log('Response:', data);
                
                if (data.status === 'success') {
                    await alert(data.message);
                    
                    // Auto-login after successful password reset
                    autoLogin();
                } else {
                    await alert(data.message || 'Failed to reset password');
                }
            })
            .catch(async err => {
                console.error('Error:', err);
                await alert('Failed to reset password. Please try again.');
            });
        }

        // Auto-login after password reset
        async function autoLogin() {
            const formData = new FormData();
            formData.append('action', 'auto_login');
            formData.append('email', userEmail);

            console.log('Auto-logging in...');

            fetch('forgot_password.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(async data => {
                console.log('Auto-login response:', data);
                
                if (data.status === 'success') {
                    // Store user session
                    sessionStorage.setItem('currentUser', JSON.stringify(data.user));
                    
                    // Redirect to home page
                    await alert('Login successful! Redirecting to home page...');
                    window.location.href = 'index.php';
                } else {
                    await alert('Password reset successful, but auto-login failed. Please login manually.');
                    window.location.href = 'login.php';
                }
            })
            .catch(async err => {
                console.error('Auto-login error:', err);
                await alert('Password reset successful, but auto-login failed. Please login manually.');
                window.location.href = 'login.php';
            });
        }

        // Copy OTP to clipboard
        function copyOTP() {
            const otp = document.getElementById('debugOtp').textContent;
            const debugInfo = document.getElementById('debugInfo');
            
            navigator.clipboard.writeText(otp).then(() => {
                debugInfo.innerHTML = '✅ OTP copied to clipboard!';
                debugInfo.style.color = '#4CAF50';
                
                // Reset message after 2 seconds
                setTimeout(() => {
                    debugInfo.innerHTML = 'Click the button above to copy';
                    debugInfo.style.color = '#666';
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy:', err);
                debugInfo.innerHTML = '❌ Failed to copy. Please select and copy manually.';
                debugInfo.style.color = '#f44336';
            });
        }

        // Resend OTP
        document.getElementById('resendLink').addEventListener('click', async function() {
            if (this.classList.contains('disabled')) {
                return;
            }
            
            const resendBtn = this;
            const originalText = resendBtn.textContent;
            
            // Disable immediately
            resendBtn.classList.add('disabled');
            resendBtn.textContent = 'Sending...';
            
            // Resend OTP by calling handleEmailSubmit logic
            const formData = new FormData();
            formData.append('action', 'request_reset');
            formData.append('email', userEmail);
 
            console.log('Resending OTP for:', userEmail);
 
            fetch('forgot_password.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(async data => {
                console.log('Response:', data);
                
                if (data.status === 'success') {
                    // Show appropriate message
                    let message = data.message || 'New OTP sent to your email!';
                    
                    // Only show debug box if email failed
                    if (data.debug_otp && data.email_error) {
                        message += '\n\nEmail Error: ' + data.email_error;
                        message += '\n\nPlease use the debug OTP shown below.';
                        
                        document.getElementById('debugOtp').textContent = data.debug_otp;
                        document.getElementById('debugBox').style.display = 'block';
                        
                        const debugInfo = document.getElementById('debugInfo');
                        debugInfo.innerHTML = '⚠️ Email delivery failed. Use this OTP to continue.';
                        debugInfo.style.color = '#f44336';
                    } else {
                        // Email sent successfully - hide debug box
                        document.getElementById('debugBox').style.display = 'none';
                    }
                    
                    await alert(message);
                    
                    // Clear OTP input and restart timer
                    document.getElementById('otpInput').value = '';
                    resendBtn.textContent = originalText;
                    startCountdown();
                } else {
                    resendBtn.classList.remove('disabled');
                    resendBtn.textContent = originalText;
                    await alert(data.message || 'Failed to resend OTP');
                }
            })
            .catch(async err => {
                resendBtn.classList.remove('disabled');
                resendBtn.textContent = originalText;
                console.error('Error:', err);
                await alert('Failed to resend OTP. Please try again.');
            });
        });

        // Initialize
        updateStepIndicators();
    </script>
</body>
</html>
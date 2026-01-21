<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MedConnect</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
    <div class="auth-container fade-in" style="margin-top: 5rem;">
        <div style="margin-bottom: 2rem; color: var(--primary);">
            <i class="ph ph-heartbeat" style="font-size: 3rem;"></i>
        </div>
        <h2>Welcome Back</h2>
        <p>Please log in to continue</p>
        
        <!-- Google Sign-In Container -->
        <div id="google-btn-container"></div>
        
        <div class="auth-divider">OR LOGIN WITH EMAIL</div>

        <form id="loginForm" onsubmit="handleLogin(event)">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" id="loginEmail" name="email" placeholder="user@example.com" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required>
            </div>
            
            <div style="text-align: right; margin-bottom: 1rem;">
                <a href="forgot_password_page.php" style="font-size: 0.9rem; color: #666;">Forgot Password?</a>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%">Login</button>
        </form>

        <p style="margin-top: 1rem; font-size: 0.9rem; color: #666;">
            Don't have an account? <a href="signup.php" style="color: var(--primary)">Sign Up</a>
        </p>
        <p style="margin-top: 0.5rem; font-size: 0.9rem;">
            <a href="index.php" style="color: var(--primary)">← Back to Home</a>
        </p>
    </div>

    <script>
        // Initialize Google Sign-In
        window.onload = function() {
            if (window.google) {
                google.accounts.id.initialize({
                    client_id: "823874360352-ltjnmvdgru8nnhl3h9r5766o7pg57nrf.apps.googleusercontent.com",
                    callback: handleGoogleLogin
                });

                google.accounts.id.renderButton(
                    document.getElementById("google-btn-container"),
                    { theme: "outline", size: "large", width: "100%" }
                );
            }
        };

        // Handle Google Login
        function handleGoogleLogin(response) {
            fetch('auth_google.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ credential: response.credential, role: 'patient' })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.is_new_user) {
                        alert("Welcome to MedConnect! Your account has been created.");
                    }
                    // Store user session
                    sessionStorage.setItem('currentUser', JSON.stringify(data.user));
                    // Redirect to home page
                    window.location.href = 'index.php';
                } else {
                    alert("Google Login Failed: " + data.message);
                }
            })
            .catch(err => {
                console.error("Error:", err);
                alert("Google Login Error: " + err.message);
            });
        }

        // Handle Email/Password Login
        function handleLogin(event) {
            event.preventDefault();
            
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;

            if (!email || !password) {
                alert("Please enter both email and password");
                return;
            }

            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('email', email);
            formData.append('password', password);

            console.log("Attempting login with:", email); // Debug log

            fetch('auth.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                console.log("Response data:", data);
                if (data.status === 'success') {
                    sessionStorage.setItem('currentUser', JSON.stringify(data.user));
                    
                    // Redirect based on user role
                    if (data.user.role === 'admin') {
                        window.location.href = 'admin_dashboard.php';
                    } else if (data.user.role === 'doctor') {
                        // For doctors, we might want to check if they are already approved
                        window.location.href = 'index.php';
                    } else if (data.user.role === 'pharmacy') {
                        window.location.href = 'pharmacy_dashboard_enhanced.php';
                    } else {
                        window.location.href = 'index.php';
                    }
                } else if (data.status === 'verification_required') {
                    alert(data.message);
                    window.location.href = 'signup.php?step=2&email=' + encodeURIComponent(data.email);
                } else if (data.status === 'onboarding_required') {
                    alert(data.message);
                    // Use a temporary session storage to pass user info to signup.php for onboarding
                    sessionStorage.setItem('tempUser', JSON.stringify(data.user));
                    window.location.href = 'signup.php?step=3';
                } else if (data.status === 'pending') {
                    alert(data.message);
                } else {
                    alert("Login Failed: " + (data.message || "Unknown error"));
                }
            })
            .catch(err => {
                console.error("Login error:", err);
                alert('Login failed. Please check the console for details.');
            });
        }
    </script>
</body>
</html>

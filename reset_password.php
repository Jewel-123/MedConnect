<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - MedConnect</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body>
    <?php
    // Get token from URL
    $token = $_GET['token'] ?? '';
    if (empty($token)) {
        echo '<div class="auth-container fade-in" style="margin-top: 5rem;">
                <h2>Invalid Link</h2>
                <p>This password reset link is invalid or has expired.</p>
                <p style="margin-top: 1rem;"><a href="login.php" class="btn btn-primary">Back to Login</a></p>
              </div>';
        exit;
    }
    ?>
    
    <div class="auth-container fade-in" style="margin-top: 5rem;">
        <div style="margin-bottom: 2rem; color: var(--primary);">
            <i class="ph ph-lock-open" style="font-size: 3rem;"></i>
        </div>
        <h2>Reset Password</h2>
        <p>Create a new password for your account</p>

        <form id="resetPasswordForm" onsubmit="handleResetPassword(event)">
            <input type="hidden" id="resetToken" value="<?php echo htmlspecialchars($token); ?>">
            
            <div class="form-group" style="margin-top: 1.5rem;">
                <label>New Password</label>
                <input type="password" id="newPassword" name="password" placeholder="Enter new password" minlength="6" required>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" id="confirmPassword" placeholder="Confirm new password" minlength="6" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%">Update Password</button>
        </form>

        <p style="margin-top: 1rem; font-size: 0.9rem;">
            <a href="login.php" style="color: var(--primary)">← Back to Login</a>
        </p>
    </div>

    <script>
        function handleResetPassword(event) {
            event.preventDefault();
            
            const token = document.getElementById('resetToken').value;
            const password = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (!password || !confirmPassword) {
                alert("Please fill in all fields");
                return;
            }

            if (password !== confirmPassword) {
                alert("Passwords do not match!");
                return;
            }

            if (password.length < 6) {
                alert("Password must be at least 6 characters long");
                return;
            }

            const formData = new FormData();
            formData.append('action', 'reset_password');
            formData.append('token', token);
            formData.append('password', password);

            console.log("Submitting password reset...");

            fetch('forgot_password.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                console.log("Response:", data);
                alert(data.message);
                
                if (data.status === 'success') {
                    // Redirect to login page
                    window.location.href = 'login.php';
                }
            })
            .catch(err => {
                console.error("Error:", err);
                alert('Failed to reset password. Please try again.');
            });
        }
    </script>
</body>
</html>
<?php
/**
 * Web-based Login Fix - Access via browser
 */
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Fix - MedConnect</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 { color: #667eea; margin-bottom: 10px; }
        .box { padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid; }
        .success { background: #d1fae5; border-left-color: #10b981; color: #065f46; }
        .error { background: #fee2e2; border-left-color: #ef4444; color: #991b1b; }
        .info { background: #f0f9ff; border-left-color: #0284c7; color: #0c4a6e; }
        .warning { background: #fef3c7; border-left-color: #f59e0b; color: #78350f; }
        .credentials { 
            background: #1e293b; 
            color: white; 
            padding: 20px; 
            border-radius: 8px; 
            margin: 15px 0; 
            font-family: 'Courier New', monospace; 
            font-size: 16px;
        }
        .credentials div { margin: 8px 0; }
        .btn { 
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 10px 10px 10px 0;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }
        .btn:hover { opacity: 0.9; }
        pre { background: #f1f5f9; padding: 10px; border-radius: 6px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 MedConnect Login Fix</h1>
        <p style="color: #64748b; margin-bottom: 20px;">Database diagnostic and account creation</p>

        <?php
        // Check connection
        if ($conn->connect_error) {
            echo '<div class="box error">';
            echo '<strong>❌ Database Connection Failed</strong><br>';
            echo 'Error: ' . htmlspecialchars($conn->connect_error) . '<br><br>';
            echo '<strong>Solution:</strong><br>';
            echo '1. Open XAMPP Control Panel<br>';
            echo '2. Click "Start" next to MySQL<br>';
            echo '3. Refresh this page';
            echo '</div>';
            exit;
        }

        echo '<div class="box success">';
        echo '✅ Database connected successfully';
        echo '</div>';

        // Check users table
        $result = $conn->query("SHOW TABLES LIKE 'users'");
        if ($result->num_rows == 0) {
            echo '<div class="box error">';
            echo '<strong>❌ Users table does not exist</strong><br><br>';
            echo '<strong>Solution:</strong><br>';
            echo '1. Open phpMyAdmin: <a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a><br>';
            echo '2. Select "medconnect" database<br>';
            echo '3. Import: medconnect.sql or consolidated_database_setup.sql';
            echo '</div>';
            exit;
        }

        echo '<div class="box success">';
        echo '✅ Users table exists';
        echo '</div>';

        // Count users
        $count = $conn->query("SELECT COUNT(*) as total FROM users");
        $total = $count->fetch_assoc()['total'];

        echo '<div class="box info">';
        echo "📊 Total users in database: <strong>$total</strong>";
        echo '</div>';

        // Create test patient if needed
        $testCreated = false;
        $testEmail = "patient@test.com";
        
        // Check if test patient exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $testEmail);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;

        if (!$exists) {
            echo '<div class="box warning">';
            echo '⚠️ Test patient account does not exist. Creating now...';
            echo '</div>';

            $testPassword = password_hash("password", PASSWORD_DEFAULT);
            $testName = "Test Patient";
            
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, status, is_verified) VALUES (?, ?, ?, 'patient', 'approved', 1)");
            $stmt->bind_param("sss", $testName, $testEmail, $testPassword);
            
            if ($stmt->execute()) {
                $userId = $stmt->insert_id;
                
                // Create patient profile
                $stmt2 = $conn->prepare("INSERT INTO patient_profiles (user_id, gender) VALUES (?, 'other')");
                $stmt2->bind_param("i", $userId);
                $stmt2->execute();
                
                $testCreated = true;
                
                echo '<div class="box success">';
                echo '✅ Test patient account created successfully!';
                echo '</div>';
            } else {
                echo '<div class="box error">';
                echo '❌ Failed to create test patient: ' . htmlspecialchars($conn->error);
                echo '</div>';
            }
        }

        // Show credentials
        echo '<h2 style="margin: 30px 0 15px; color: #1e293b;">🔑 Login Credentials</h2>';
        echo '<div class="credentials">';
        echo '<div><strong>Email:</strong> patient@test.com</div>';
        echo '<div><strong>Password:</strong> password</div>';
        echo '</div>';

        if ($testCreated) {
            echo '<div class="box success">';
            echo '<strong>✅ Ready to login!</strong><br>';
            echo 'Your test account has been created. Click the button below to login.';
            echo '</div>';
        } else {
            echo '<div class="box info">';
            echo '<strong>ℹ️ Account already exists</strong><br>';
            echo 'Use the credentials above to login.';
            echo '</div>';
        }

        // List all users
        echo '<h2 style="margin: 30px 0 15px; color: #1e293b;">👥 All Users</h2>';
        $users = $conn->query("SELECT id, full_name, email, role, status, is_verified FROM users ORDER BY id DESC LIMIT 10");
        
        if ($users->num_rows > 0) {
            echo '<table style="width: 100%; border-collapse: collapse; margin: 15px 0;">';
            echo '<tr style="background: #f1f5f9; text-align: left;">';
            echo '<th style="padding: 10px; border-bottom: 2px solid #cbd5e1;">Name</th>';
            echo '<th style="padding: 10px; border-bottom: 2px solid #cbd5e1;">Email</th>';
            echo '<th style="padding: 10px; border-bottom: 2px solid #cbd5e1;">Role</th>';
            echo '<th style="padding: 10px; border-bottom: 2px solid #cbd5e1;">Status</th>';
            echo '</tr>';
            
            while ($user = $users->fetch_assoc()) {
                $verified = $user['is_verified'] ? '✅' : '❌';
                echo '<tr style="border-bottom: 1px solid #e2e8f0;">';
                echo '<td style="padding: 10px;">' . $verified . ' ' . htmlspecialchars($user['full_name']) . '</td>';
                echo '<td style="padding: 10px;">' . htmlspecialchars($user['email']) . '</td>';
                echo '<td style="padding: 10px;">' . htmlspecialchars($user['role']) . '</td>';
                echo '<td style="padding: 10px;">' . htmlspecialchars($user['status']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }

        $conn->close();
        ?>

        <div style="margin-top: 30px; text-align: center;">
            <a href="login.php" class="btn">🚀 Go to Login Page</a>
            <a href="signup.php" class="btn" style="background: #64748b;">Create New Account</a>
        </div>

        <div style="margin-top: 30px; padding: 20px; background: #f8fafc; border-radius: 8px; font-size: 14px; color: #64748b;">
            <strong>💡 Tips:</strong>
            <ul style="margin: 10px 0 0 20px;">
                <li>If login still fails, check browser console (F12) for errors</li>
                <li>Make sure XAMPP MySQL is running</li>
                <li>Google login is disabled - use email/password only</li>
            </ul>
        </div>
    </div>
</body>
</html>
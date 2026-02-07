<!DOCTYPE html>
<html>
<head>
    <title>MedConnect - Setup Complete</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #10b981; }
        .success { color: #10b981; font-size: 24px; }
        .error { color: #ef4444; }
        .info { background: #e0f2fe; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .button {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-size: 18px;
            margin-top: 20px;
        }
        .button:hover { background: #059669; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎉 MedConnect Setup</h1>
        
        <?php
        $conn = @new mysqli("localhost", "root", "", "medconnect");
        
        if ($conn->connect_error) {
            echo '<p class="error">❌ Database not found. Please run FORCE_DELETE_ALL.bat</p>';
        } else {
            $result = @$conn->query("SELECT * FROM users WHERE email = 'admin@medconnect.com'");
            
            if ($result && $result->num_rows > 0) {
                $admin = $result->fetch_assoc();
                echo '<p class="success">✅ Setup Complete!</p>';
                echo '<div class="info">';
                echo '<strong>Admin Account Created:</strong><br>';
                echo 'Email: admin@medconnect.com<br>';
                echo 'Password: admin123<br>';
                echo 'Role: ' . $admin['role'] . '<br>';
                echo 'Status: ' . $admin['status'];
                echo '</div>';
                echo '<a href="login.php" class="button">GO TO LOGIN PAGE</a>';
            } else {
                echo '<p class="error">❌ Admin not created yet. Run FORCE_DELETE_ALL.bat</p>';
            }
            $conn->close();
        }
        ?>
    </div>
</body>
</html>
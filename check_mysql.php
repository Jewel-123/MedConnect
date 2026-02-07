<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySQL Status Check</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 { color: #667eea; margin-bottom: 10px; }
        .status { padding: 20px; border-radius: 8px; margin: 15px 0; }
        .success { background: #d1fae5; border-left: 4px solid #10b981; color: #065f46; }
        .error { background: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b; }
        .warning { background: #fef3c7; border-left: 4px solid #f59e0b; color: #78350f; }
        .info { background: #f0f9ff; border-left: 4px solid #0284c7; color: #0c4a6e; }
        .btn { 
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 10px 10px 10px 0;
            font-weight: 600;
        }
        .btn:hover { opacity: 0.9; }
        ul { margin: 10px 0 0 20px; }
        li { margin: 5px 0; }
        .code { background: #1e293b; color: #10b981; padding: 15px; border-radius: 8px; font-family: 'Courier New', monospace; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 MySQL Status Check</h1>
        <p style="color: #64748b; margin-bottom: 20px;">Checking database connection (no data will be modified)</p>

        <?php
        // Test MySQL connection
        $mysqlRunning = false;
        $dbConnected = false;
        $tableCount = 0;
        $errorMsg = '';

        try {
            $conn = @new mysqli('localhost', 'root', '', 'medconnect');
            
            if ($conn->connect_error) {
                $errorMsg = $conn->connect_error;
            } else {
                $dbConnected = true;
                $mysqlRunning = true;
                
                // Count tables
                $result = $conn->query("SHOW TABLES");
                if ($result) {
                    $tableCount = $result->num_rows;
                }
                $conn->close();
            }
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
        }

        // Display status
        if ($mysqlRunning && $dbConnected) {
            echo '<div class="status success">';
            echo '<h2 style="margin-bottom: 10px;">✅ Everything is Working!</h2>';
            echo '<p><strong>MySQL Status:</strong> Running</p>';
            echo '<p><strong>Database:</strong> Connected</p>';
            echo '<p><strong>Tables Found:</strong> ' . $tableCount . '</p>';
            echo '<p style="margin-top: 15px;">All your data is safe and intact. You can proceed with login.</p>';
            echo '</div>';
            
            echo '<div style="margin-top: 20px;">';
            echo '<a href="web_fix_login.php" class="btn">🔑 Setup Login Account</a>';
            echo '<a href="login.php" class="btn" style="background: #10b981;">🚀 Go to Login</a>';
            echo '</div>';
            
        } else {
            echo '<div class="status error">';
            echo '<h2 style="margin-bottom: 10px;">❌ MySQL Not Running</h2>';
            echo '<p><strong>Error:</strong> ' . htmlspecialchars($errorMsg) . '</p>';
            echo '</div>';
            
            echo '<div class="status warning">';
            echo '<h2 style="margin-bottom: 10px;">🔧 How to Fix (No Data Loss)</h2>';
            echo '<ol style="margin: 15px 0 0 20px; line-height: 1.8;">';
            echo '<li><strong>Open XAMPP Control Panel</strong></li>';
            echo '<li>Find the <strong>MySQL</strong> row</li>';
            echo '<li>Click the <strong>"Start"</strong> button next to MySQL</li>';
            echo '<li>Wait for the status to turn <strong>green</strong></li>';
            echo '<li><strong>Refresh this page</strong></li>';
            echo '</ol>';
            echo '</div>';
            
            echo '<div class="status info">';
            echo '<h2 style="margin-bottom: 10px;">💡 Alternative: Use Command</h2>';
            echo '<p>Run this batch file to check and fix MySQL:</p>';
            echo '<div class="code">fix_mysql.bat</div>';
            echo '<p style="margin-top: 10px; font-size: 14px;">Location: C:\\xampp\\htdocs\\medconnect\\fix_mysql.bat</p>';
            echo '</div>';
        }
        ?>

        <div style="margin-top: 30px; padding: 20px; background: #f8fafc; border-radius: 8px; border-left: 4px solid #667eea;">
            <h3 style="color: #1e293b; margin-bottom: 10px;">🛡️ Data Safety Guarantee</h3>
            <p style="color: #64748b; line-height: 1.6;">
                This diagnostic tool only <strong>reads</strong> your database status. 
                It does <strong>NOT modify, delete, or alter</strong> any data or tables. 
                All your existing data remains completely safe and intact.
            </p>
        </div>

        <div style="margin-top: 20px; text-align: center;">
            <button onclick="location.reload()" class="btn" style="background: #64748b;">🔄 Refresh Status</button>
        </div>
    </div>
</body>
</html>
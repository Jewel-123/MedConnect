<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Test Diagnostic</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 20px; }
        .section { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; }
        .section h2 { color: #667eea; font-size: 18px; margin-bottom: 10px; }
        .info { padding: 10px; background: white; border-left: 4px solid #667eea; margin: 10px 0; }
        .success { border-left-color: #10b981; background: #d1fae5; }
        .error { border-left-color: #ef4444; background: #fee2e2; }
        .warning { border-left-color: #f59e0b; background: #fef3c7; }
        pre { background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 12px; }
        button { background: #667eea; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; margin: 5px; }
        button:hover { background: #5568d3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Payment Test Mode Diagnostic</h1>
        
        <div class="section">
            <h2>Configuration Status</h2>
            <?php
            require_once 'razorpay_config.php';
            
            $testMode = defined('PAYMENT_TEST_MODE') && PAYMENT_TEST_MODE === true;
            $class = $testMode ? 'success' : 'warning';
            ?>
            <div class="info <?php echo $class; ?>">
                <strong>Test Mode:</strong> <?php echo $testMode ? '✅ ENABLED' : '❌ DISABLED'; ?>
            </div>
            <div class="info">
                <strong>Razorpay Key ID:</strong> <?php echo RAZORPAY_KEY_ID; ?>
            </div>
        </div>
        
        <div class="section">
            <h2>Recent Transactions</h2>
            <?php
            require_once 'db.php';
            session_start();
            
            if (isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
                $result = $conn->query("
                    SELECT * FROM payment_transactions 
                    WHERE user_id = $userId 
                    ORDER BY created_at DESC 
                    LIMIT 5
                ");
                
                if ($result->num_rows > 0) {
                    echo '<table style="width: 100%; border-collapse: collapse;">';
                    echo '<tr style="background: #667eea; color: white;">';
                    echo '<th style="padding: 10px; text-align: left;">ID</th>';
                    echo '<th style="padding: 10px; text-align: left;">Order ID</th>';
                    echo '<th style="padding: 10px; text-align: left;">Amount</th>';
                    echo '<th style="padding: 10px; text-align: left;">Status</th>';
                    echo '<th style="padding: 10px; text-align: left;">Created</th>';
                    echo '</tr>';
                    
                    while ($row = $result->fetch_assoc()) {
                        $statusClass = $row['status'] === 'completed' ? 'success' : ($row['status'] === 'failed' ? 'error' : 'warning');
                        echo '<tr style="border-bottom: 1px solid #e2e8f0;">';
                        echo '<td style="padding: 10px;">' . $row['id'] . '</td>';
                        echo '<td style="padding: 10px; font-family: monospace; font-size: 11px;">' . htmlspecialchars($row['razorpay_order_id']) . '</td>';
                        echo '<td style="padding: 10px;">₹' . number_format($row['amount'], 2) . '</td>';
                        echo '<td style="padding: 10px;"><span class="info ' . $statusClass . '" style="display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px;">' . strtoupper($row['status']) . '</span></td>';
                        echo '<td style="padding: 10px; font-size: 12px;">' . date('Y-m-d H:i', strtotime($row['created_at'])) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<div class="info warning">No transactions found for your account</div>';
                }
            } else {
                echo '<div class="info error">Please login to view transactions</div>';
            }
            ?>
        </div>
        
        <div class="section">
            <h2>Recent Appointments</h2>
            <?php
            if (isset($_SESSION['user_id'])) {
                $result = $conn->query("
                    SELECT a.*, u.full_name as doctor_name 
                    FROM appointments a
                    JOIN users u ON a.doctor_id = u.id
                    WHERE a.patient_id = $userId 
                    ORDER BY a.created_at DESC 
                    LIMIT 5
                ");
                
                if ($result->num_rows > 0) {
                    echo '<table style="width: 100%; border-collapse: collapse;">';
                    echo '<tr style="background: #667eea; color: white;">';
                    echo '<th style="padding: 10px; text-align: left;">ID</th>';
                    echo '<th style="padding: 10px; text-align: left;">Doctor</th>';
                    echo '<th style="padding: 10px; text-align: left;">Date/Time</th>';
                    echo '<th style="padding: 10px; text-align: left;">Payment</th>';
                    echo '<th style="padding: 10px; text-align: left;">Status</th>';
                    echo '</tr>';
                    
                    while ($row = $result->fetch_assoc()) {
                        $paymentClass = $row['payment_status'] === 'paid' ? 'success' : 'warning';
                        echo '<tr style="border-bottom: 1px solid #e2e8f0;">';
                        echo '<td style="padding: 10px;">' . $row['id'] . '</td>';
                        echo '<td style="padding: 10px;">Dr. ' . htmlspecialchars($row['doctor_name']) . '</td>';
                        echo '<td style="padding: 10px;">' . $row['scheduled_date'] . ' ' . substr($row['scheduled_time'], 0, 5) . '</td>';
                        echo '<td style="padding: 10px;"><span class="info ' . $paymentClass . '" style="display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px;">' . strtoupper($row['payment_status']) . '</span></td>';
                        echo '<td style="padding: 10px;">' . strtoupper($row['status']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<div class="info warning">No appointments found</div>';
                }
            }
            ?>
        </div>
        
        <div class="section">
            <h2>Error Log (Last 50 lines)</h2>
            <button onclick="location.reload()">🔄 Refresh</button>
            <pre><?php
            $logFile = ini_get('error_log');
            if (!$logFile || $logFile === 'syslog') {
                // Try common XAMPP location
                $logFile = 'C:/xampp/php/logs/php_error_log';
                if (!file_exists($logFile)) {
                    $logFile = 'C:/xampp/apache/logs/error.log';
                }
            }
            
            if (file_exists($logFile)) {
                $lines = file($logFile);
                $paymentLines = array_filter($lines, function($line) {
                    return stripos($line, 'PAYMENT') !== false || 
                           stripos($line, 'TEST MODE') !== false ||
                           stripos($line, 'razorpay') !== false;
                });
                
                $recent = array_slice($paymentLines, -50);
                if (count($recent) > 0) {
                    echo htmlspecialchars(implode('', $recent));
                } else {
                    echo "No payment-related log entries found.\nLog file: $logFile";
                }
            } else {
                echo "Error log file not found.\nSearched: $logFile\n\nTo enable logging, check your php.ini file.";
            }
            ?></pre>
        </div>
        
        <div class="section">
            <h2>Actions</h2>
            <button onclick="window.location.href='appointment_booking.php'">📅 Book New Appointment</button>
            <button onclick="window.location.href='index.php'">🏠 Dashboard</button>
        </div>
    </div>
</body>
</html>
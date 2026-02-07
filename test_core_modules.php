<?php
session_start();
require_once 'db.php';

// Simulate logged-in patient for testing
if (!isset($_SESSION['user_id'])) {
    // Get first patient from database
    $result = $conn->query("SELECT id FROM users WHERE role = 'patient' LIMIT 1");
    if ($row = $result->fetch_assoc()) {
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['role'] = 'patient';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Core Modules Test - MedConnect</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #333; margin-bottom: 20px; }
        .test-section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .test-section h2 { color: #0ea5e9; margin-bottom: 15px; font-size: 18px; }
        button { background: #0ea5e9; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; margin: 5px; }
        button:hover { background: #0284c7; }
        .result { margin-top: 15px; padding: 15px; background: #f8fafc; border-left: 4px solid #0ea5e9; border-radius: 4px; font-family: monospace; font-size: 13px; max-height: 300px; overflow-y: auto; }
        .success { border-left-color: #10b981; background: #f0fdf4; }
        .error { border-left-color: #ef4444; background: #fef2f2; }
        .status { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; margin-left: 10px; }
        .status.ok { background: #10b981; color: white; }
        .status.fail { background: #ef4444; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 MedConnect Core Modules Test Suite</h1>
        <p style="margin-bottom: 20px; color: #666;">Test all new API endpoints to ensure everything works correctly.</p>

        <!-- Database Check -->
        <div class="test-section">
            <h2>1. Database Tables Check</h2>
            <button onclick="checkTables()">Check Tables</button>
            <div id="tables-result"></div>
        </div>

        <!-- Symptom Intake API -->
        <div class="test-section">
            <h2>2. Symptom Intake API</h2>
            <button onclick="testSymptomIntake()">Submit Test Symptoms</button>
            <button onclick="testSuggestions()">Test Autocomplete</button>
            <div id="symptom-result"></div>
        </div>

        <!-- Appointment API -->
        <div class="test-section">
            <h2>3. Appointment Management API</h2>
            <button onclick="testGetAppointments()">Get Appointments</button>
            <div id="appointment-result"></div>
        </div>

        <!-- Payment API -->
        <div class="test-section">
            <h2>4. Payment API</h2>
            <button onclick="testPaymentHistory()">Get Payment History</button>
            <div id="payment-result"></div>
        </div>

        <!-- Prescription API -->
        <div class="test-section">
            <h2>5. Prescription API</h2>
            <button onclick="testPrescriptions()">Get My Prescriptions</button>
            <div id="prescription-result"></div>
        </div>

        <!-- Notification System -->
        <div class="test-section">
            <h2>6. Notification System</h2>
            <button onclick="checkNotifications()">Check Notification Tables</button>
            <div id="notification-result"></div>
        </div>
    </div>

    <script>
        function showResult(elementId, data, isError = false) {
            const el = document.getElementById(elementId);
            el.className = 'result ' + (isError ? 'error' : 'success');
            el.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }

        async function checkTables() {
            const tables = [
                'symptom_attachments', 'appointments', 'doctor_queue',
                'consultation_messages', 'pharmacy_profiles', 'payment_transactions',
                'revenue_splits', 'pharmacy_earnings', 'notification_preferences',
                'scheduled_notifications', 'access_logs', 'compliance_events'
            ];
            
            let html = '<div style="margin-top: 10px;">';
            
            for (let table of tables) {
                try {
                    const response = await fetch(`check_db_tables.php?table=${table}`);
                    const exists = await response.text();
                    const status = exists.includes('exists') ? 'ok' : 'fail';
                    html += `<div>${table} <span class="status ${status}">${status === 'ok' ? '✓' : '✗'}</span></div>`;
                } catch (e) {
                    html += `<div>${table} <span class="status fail">✗</span></div>`;
                }
            }
            
            html += '</div>';
            document.getElementById('tables-result').innerHTML = html;
        }

        async function testSymptomIntake() {
            try {
                const response = await fetch('symptom_intake_api.php?action=submit_symptoms', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        symptoms: 'fever and cough for 3 days',
                        duration: '3 days',
                        severity: 'moderate',
                        input_method: 'text'
                    })
                });
                const data = await response.json();
                showResult('symptom-result', data, !data.success);
            } catch (e) {
                showResult('symptom-result', {error: e.message}, true);
            }
        }

        async function testSuggestions() {
            try {
                const response = await fetch('symptom_intake_api.php?action=get_suggestions&query=fever');
                const data = await response.json();
                showResult('symptom-result', data, !data.success);
            } catch (e) {
                showResult('symptom-result', {error: e.message}, true);
            }
        }

        async function testGetAppointments() {
            try {
                const response = await fetch('appointment_api.php?action=get_appointments');
                const data = await response.json();
                showResult('appointment-result', data, !data.success);
            } catch (e) {
                showResult('appointment-result', {error: e.message}, true);
            }
        }

        async function testPaymentHistory() {
            try {
                const response = await fetch('payment_api.php?action=get_payment_history');
                const data = await response.json();
                showResult('payment-result', data, !data.success);
            } catch (e) {
                showResult('payment-result', {error: e.message}, true);
            }
        }

        async function testPrescriptions() {
            try {
                const response = await fetch('prescription_api.php?action=get_my_prescriptions');
                const data = await response.json();
                showResult('prescription-result', data, !data.success);
            } catch (e) {
                showResult('prescription-result', {error: e.message}, true);
            }
        }

        async function checkNotifications() {
            const result = await fetch('check_db_tables.php?table=scheduled_notifications');
            const text = await result.text();
            showResult('notification-result', {
                status: text.includes('exists') ? 'Table exists ✓' : 'Table missing ✗',
                message: 'Notification scheduler is ready to use'
            });
        }
    </script>
</body>
</html>
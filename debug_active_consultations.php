<?php
session_start();
require_once 'db.php';

// Check if logged in as doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    die("Please log in as a doctor first");
}

$doctor_id = $_SESSION['user_id'];

echo "<h2>Debugging Active Consultations for Doctor ID: $doctor_id</h2>";

// Check all consultations for this doctor
echo "<h3>All Consultations for this Doctor:</h3>";
$all = $conn->query("
    SELECT id, patient_id, status, doctor_id, created_at, assigned_at, updated_at
    FROM consultations
    WHERE doctor_id = $doctor_id
    ORDER BY updated_at DESC
    LIMIT 10
");

if ($all->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Patient ID</th><th>Status</th><th>Doctor ID</th><th>Created</th><th>Assigned</th><th>Updated</th></tr>";
    while ($row = $all->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['patient_id']}</td>";
        echo "<td><strong>{$row['status']}</strong></td>";
        echo "<td>{$row['doctor_id']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "<td>{$row['assigned_at']}</td>";
        echo "<td>{$row['updated_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No consultations found for this doctor</p>";
}

// Check what the active consultations query returns
echo "<h3>Active Consultations Query Result:</h3>";
$active = $conn->query("
    SELECT c.*, u.full_name as patient_name, u.email as patient_email,
           (CASE 
               WHEN c.severity = 'high' OR c.urgency_score >= 75 THEN 'emergency'
               WHEN c.severity = 'medium' OR c.urgency_score >= 50 THEN 'priority'
               ELSE 'routine' 
           END) as urgency_level,
           'consultation' as type
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    WHERE c.doctor_id = $doctor_id 
      AND c.status IN ('accepted', 'scheduled', 'waiting', 'in_progress', 'paused')
    ORDER BY c.updated_at DESC
");

if ($active->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Patient</th><th>Status</th><th>Symptoms</th><th>Updated</th></tr>";
    while ($row = $active->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['patient_name']}</td>";
        echo "<td><strong>{$row['status']}</strong></td>";
        echo "<td>" . substr($row['symptoms'], 0, 50) . "...</td>";
        echo "<td>{$row['updated_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'><strong>No active consultations found!</strong></p>";
    echo "<p>Possible reasons:</p>";
    echo "<ul>";
    echo "<li>No consultations with doctor_id = $doctor_id</li>";
    echo "<li>Status is not in ('accepted', 'scheduled', 'waiting', 'in_progress', 'paused')</li>";
    echo "<li>Patient user record doesn't exist (JOIN failed)</li>";
    echo "</ul>";
}

// Check pending consultations
echo "<h3>Pending Consultation Requests:</h3>";
$pending = $conn->query("
    SELECT id, patient_id, status, doctor_id, symptoms, created_at
    FROM consultations
    WHERE (status = 'pending' AND doctor_id IS NULL) 
       OR (status = 'assigned' AND doctor_id = $doctor_id)
    ORDER BY created_at DESC
    LIMIT 5
");

if ($pending->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Patient ID</th><th>Status</th><th>Doctor ID</th><th>Symptoms</th></tr>";
    while ($row = $pending->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['patient_id']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>" . ($row['doctor_id'] ?? 'NULL') . "</td>";
        echo "<td>" . substr($row['symptoms'], 0, 50) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No pending requests</p>";
}

// Check appointments
echo "<h3>Confirmed Appointments:</h3>";
$appointments = $conn->query("
    SELECT id, patient_id, status, payment_status, scheduled_date, scheduled_time
    FROM appointments
    WHERE doctor_id = $doctor_id
      AND status = 'confirmed'
      AND payment_status = 'paid'
    ORDER BY scheduled_date DESC
    LIMIT 5
");

if ($appointments->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Patient ID</th><th>Status</th><th>Payment</th><th>Date</th><th>Time</th></tr>";
    while ($row = $appointments->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['patient_id']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>{$row['payment_status']}</td>";
        echo "<td>{$row['scheduled_date']}</td>";
        echo "<td>{$row['scheduled_time']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No confirmed appointments</p>";
}

echo "<hr>";
echo "<p><a href='doctor_dashboard.php'>Back to Dashboard</a></p>";
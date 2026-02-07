<?php
require_once 'db.php';
session_start();

$doctor_id = $_SESSION['user_id'] ?? 1; // Default to 1 for testing if session is missing

echo "<h2>Diagnostic Report for Doctor ID: $doctor_id</h2>";

// 1. Check Consultations Table
echo "<h3>Consultations (Pending/Unassigned/Paid Check)</h3>";
$query = "SELECT id, patient_id, doctor_id, status, payment_status, created_at FROM consultations";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Patient</th><th>Doctor</th><th>Status</th><th>Payment</th><th>Created</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $color = ($row['payment_status'] !== 'paid') ? "style='color:red;'" : "";
        echo "<tr $color>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['patient_id']}</td>";
        echo "<td>" . ($row['doctor_id'] ?: 'NULL') . "</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>{$row['payment_status']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No consultations found.<br>";
}

// 2. Check Appointments Table
echo "<h3>Appointments (For this Doctor)</h3>";
$query = "SELECT id, patient_id, doctor_id, status, payment_status, scheduled_date, scheduled_time FROM appointments WHERE doctor_id = $doctor_id";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Patient</th><th>Doctor</th><th>Status</th><th>Payment</th><th>Schedule</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $color = ($row['payment_status'] !== 'paid') ? "style='color:red;'" : "";
        echo "<tr $color>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['patient_id']}</td>";
        echo "<td>{$row['doctor_id']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>{$row['payment_status']}</td>";
        echo "<td>{$row['scheduled_date']} {$row['scheduled_time']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No appointments found for this doctor.<br>";
}

// 3. Check Users Table (to see if current session user is actually a doctor)
echo "<h3>Session/User Check</h3>";
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $u = $conn->query("SELECT id, role, status FROM users WHERE id = $uid")->fetch_assoc();
    echo "Logged in User: ID $uid, Role: {$u['role']}, Status: {$u['status']}<br>";
} else {
    echo "Not logged in via PHP session.<br>";
}

$conn->close();
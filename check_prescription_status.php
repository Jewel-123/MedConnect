<?php
session_start();
require_once 'db.php';

// Get all prescriptions with their status
$result = $conn->query("
    SELECT id, prescription_number, status, patient_id, doctor_id, created_at
    FROM prescriptions_v2
    ORDER BY id DESC
    LIMIT 10
");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Prescription Status Check</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #667eea; color: white; }
        .finalized { background: #d1fae5; }
        .draft { background: #fee2e2; }
    </style>
</head>
<body>
    <h1>Prescription Status Diagnostic</h1>
    <table>
        <tr>
            <th>ID</th>
            <th>Prescription Number</th>
            <th>Status</th>
            <th>Patient ID</th>
            <th>Doctor ID</th>
            <th>Created At</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr class="<?php echo $row['status']; ?>">
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['prescription_number']); ?></td>
            <td><strong><?php echo $row['status']; ?></strong></td>
            <td><?php echo $row['patient_id']; ?></td>
            <td><?php echo $row['doctor_id']; ?></td>
            <td><?php echo $row['created_at']; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    
    <h2>Button Logic</h2>
    <p>The "Order Medicine" button will ONLY show if:</p>
    <ul>
        <li>Prescription status = <strong>"finalized"</strong></li>
        <li>If status is anything else (draft, sent_to_pharmacy, etc.), button won't show</li>
    </ul>
    
    <h2>Fix</h2>
    <p>If your prescription is NOT "finalized", you need to finalize it first!</p>
    <p><a href="patient_prescriptions.php">Back to Prescriptions</a></p>
</body>
</html>

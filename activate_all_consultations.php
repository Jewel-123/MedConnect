<?php
/**
 * Consultation Activator
 * Sets recent consultations to 'paid' status so they appear on doctor dashboards
 */

require_once 'db.php';

echo "<h2>Activating Consultations...</h2>";

// Update all pending consultations to 'paid'
$update = $conn->query("
    UPDATE consultations 
    SET payment_status = 'paid', 
        updated_at = NOW() 
    WHERE payment_status = 'pending'
");

$affected = $conn->affected_rows;
echo "<p style='color: green;'><strong>✓ Updated {$affected} consultation(s) to 'paid' status!</strong></p>";

// Also ensure they are assigned if unassigned (optional, based on requirement)
// For now, we only touch payment status as the API now handles 'pending' and 'assigned' statuses.

echo "<p>Next: Check the <a href='doctor_dashboard.php'>Doctor Dashboard</a>.</p>";

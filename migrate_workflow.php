<?php
require_once 'db.php';

// 1. Update consultation_sessions for timer tracking
$sql1 = "ALTER TABLE consultation_sessions 
        ADD COLUMN IF NOT EXISTS accumulated_seconds INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS last_resume_at DATETIME NULL";
if ($conn->query($sql1)) {
    echo "Consultation sessions table updated.\n";
} else {
    echo "Error updating consultation_sessions: " . $conn->error . "\n";
}

// 2. Ensure appointments has 'completed' status
$sql2 = "ALTER TABLE appointments MODIFY COLUMN status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending'";
if ($conn->query($sql2)) {
    echo "Appointments status enum updated.\n";
} else {
    echo "Error updating appointments status: " . $conn->error . "\n";
}

// 3. Ensure consultations has 'accepted' status if not present (Not highly likely to be missing but good to check)
// consultations status is already ENUM('pending', 'assigned', 'accepted', 'in_progress', 'paused', 'completed', 'cancelled')
// No change needed there usually.

echo "Migration complete.\n";
?>

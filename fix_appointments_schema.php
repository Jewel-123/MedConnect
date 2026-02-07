<?php
include 'db.php';

echo "--- MedConnect Appointment Schema Fix ---\n";

$cols = [
    "scheduled_date" => "DATE DEFAULT NULL",
    "scheduled_time" => "TIME DEFAULT NULL",
    "status" => "ENUM('booked', 'pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'booked'",
    "payment_status" => "ENUM('pending', 'paid', 'refunded') DEFAULT 'pending'",
    "consultation_fee" => "DECIMAL(10, 2) DEFAULT 0.00",
    "notes" => "TEXT DEFAULT NULL",
    "cancellation_reason" => "TEXT DEFAULT NULL"
];

foreach ($cols as $col => $def) {
    $check = $conn->query("SHOW COLUMNS FROM appointments LIKE '$col'");
    if ($check && $check->num_rows == 0) {
        if ($conn->query("ALTER TABLE appointments ADD COLUMN $col $def")) {
            echo "  ✓ Added $col to appointments\n";
        } else {
            echo "  ! Error adding $col: " . $conn->error . "\n";
        }
    } else {
        echo "  - Column $col already exists in appointments\n";
        // Ensure status reflects the enum if it exists but might be different
        if ($col === 'status' || $col === 'payment_status') {
             $conn->query("ALTER TABLE appointments MODIFY COLUMN $col $def");
             echo "  - Updated $col definition\n";
        }
    }
}

echo "--- Fix Completed ---\n";
$conn->close();
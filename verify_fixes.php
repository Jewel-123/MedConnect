<?php
include 'db.php';

echo "--- MedConnect Verification ---\n";

// 1. Check Admin Account
echo "Verifying Admin Account...\n";
$stmt = $conn->prepare("SELECT id, full_name, role, status FROM users WHERE email = ?");
$email = 'admin@medconnect.com';
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
if ($u = $res->fetch_assoc()) {
    echo "  ✓ Admin found: " . $u['full_name'] . " (Role: " . $u['role'] . ", Status: " . $u['status'] . ")\n";
} else {
    echo "  ! Admin NOT found.\n";
}

// 2. Check doctor_profiles columns
echo "Verifying doctor_profiles columns...\n";
$res = $conn->query("DESCRIBE doctor_profiles");
$found_exp = false;
$found_lang = false;
while ($row = $res->fetch_assoc()) {
    if ($row['Field'] == 'years_experience') $found_exp = true;
    if ($row['Field'] == 'languages_spoken') $found_lang = true;
}
echo "  " . ($found_exp ? "✓" : "!") . " years_experience column\n";
echo "  " . ($found_lang ? "✓" : "!") . " languages_spoken column\n";

// 3. Check clinic_profiles table
echo "Verifying clinic_profiles table...\n";
$res = $conn->query("SHOW TABLES LIKE 'clinic_profiles'");
if ($res && $res->num_rows > 0) {
    echo "  ✓ clinic_profiles table exists\n";
} else {
    echo "  ! clinic_profiles table MISSING\n";
}

echo "--- Verification Completed ---\n";
$conn->close();
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db.php';

echo "=== TESTING ADMIN APPROVAL LOGIC ===\n\n";

try {
    $testEmail = 'test_doctor_' . time() . '@example.com';
    $password = 'password123';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // 1. Simulate Signup
    echo "Step 1: Simulating Doctor Signup...\n";
    $conn->query("INSERT INTO users (full_name, email, password, role, status, is_verified) 
                  VALUES ('Test Doctor', '$testEmail', '$hash', 'doctor', 'pending_onboarding', 1)");
    $userId = $conn->insert_id;

    // 2. Simulate Onboarding Completion
    echo "Step 2: Completing Onboarding (Doctor)...\n";
    $_POST['action'] = 'complete_onboarding';
    $_POST['user_id'] = $userId;
    $_POST['role'] = 'doctor';
    $_POST['license'] = 'DOC-TEST-123';
    $_POST['specialization'] = 'Cardiology';
    $_POST['experience'] = 5;
    $_POST['fees'] = 100;
    $_POST['languages'] = 'English';

    ob_start();
    include 'auth.php';
    $output = ob_get_clean();
    echo "Output: $output\n";

    // 3. Verify Status
    echo "Step 3: Verifying User Status...\n";
    $res = $conn->query("SELECT status FROM users WHERE id = $userId");
    if ($res && $row = $res->fetch_assoc()) {
        $status = $row['status'];
        echo "Current Status: $status\n";

        if ($status === 'pending') {
            echo "✓ SUCCESS: Doctor status is 'pending' and needs approval.\n";
        } else {
            echo "✗ FAILURE: Doctor status is '$status', should be 'pending'.\n";
        }
    } else {
        echo "✗ FAILURE: Could not find user status.\n";
    }

    // 4. Test Login Attempt
    echo "\nStep 4: Testing Login Attempt (Should be pending)...\n";
    unset($_POST);
    $_POST['action'] = 'login';
    $_POST['email'] = $testEmail;
    $_POST['password'] = $password;

    ob_start();
    include 'auth.php';
    $loginOutput = ob_get_clean();
    echo "Login Response: $loginOutput\n";

    if (strpos($loginOutput, '"status":"pending"') !== false) {
        echo "✓ SUCCESS: Login blocked for pending doctor.\n";
    } else {
        echo "✗ FAILURE: Login was not blocked correctly.\n";
    }

    // Cleanup
    $conn->query("DELETE FROM users WHERE id = $userId");
    $conn->query("DELETE FROM doctor_profiles WHERE user_id = $userId");
    echo "\nTest Completed Successfully.\n";

} catch (Exception $e) {
    echo "\n✗ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    if (isset($userId)) {
        $conn->query("DELETE FROM users WHERE id = $userId");
    }
} catch (Error $e) {
    echo "\n✗ CRITICAL PHP ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    if (isset($userId)) {
        $conn->query("DELETE FROM users WHERE id = $userId");
    }
}
?>

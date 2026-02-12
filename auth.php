<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
session_start();
include 'db.php';
include 'email_config.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

/**
 * Helper function to send OTP email
 */
function sendOTPEmail($email, $otp, $name = 'User') {
    try {
        $mailConfig = getEmailConfig();
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host       = $mailConfig['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailConfig['username'];
        $mail->Password   = $mailConfig['password'];
        $mail->SMTPSecure = $mailConfig['encryption'];
        $mail->Port       = $mailConfig['port'];

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Recipients
        $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
        $mail->addAddress($email, $name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify your MedConnect Account';
        $mail->Body    = "
            <div style='font-family: Outfit, sans-serif; padding: 20px; border: 1px solid #eee; border-radius: 12px; max-width: 500px; color: #1e293b;'>
                <h2 style='color: #0284c7;'>MedConnect Verification</h2>
                <p>Hello <strong>$name</strong>,</p>
                <p>Thank you for joining MedConnect. Please use the following 6-digit code to verify your account:</p>
                <div style='background: #f0f7ff; padding: 15px; border-radius: 8px; text-align: center; font-size: 2rem; font-weight: 700; color: #0284c7; letter-spacing: 5px; margin: 20px 0;'>
                    $otp
                </div>
                <p>This code will expire in 10 minutes.</p>
                <p style='font-size: 0.85rem; color: #64748b; margin-top: 20px;'>If you did not request this, please ignore this email.</p>
            </div>
        ";

        return $mail->send();
    } catch (Exception $e) {
        error_log("PHPMailer error: " . $e->getMessage());
        return false;
    }
}

$action = $_POST['action'] ?? '';

if ($action == 'signup') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    // Validate required fields
    if (empty($name) || empty($email) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "Name, email and password are required"]);
        exit;
    }

    // Validate name (at least 2 characters, letters and spaces only)
    if (!preg_match('/^[a-zA-Z\s]{2,}$/', $name)) {
        echo json_encode(["status" => "error", "message" => "Name must be at least 2 characters and contain only letters and spaces"]);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Please enter a valid email address"]);
        exit;
    }

    // Validate phone number if provided (exactly 10 digits)
    if (!empty($phone) && !preg_match('/^\d{10}$/', $phone)) {
        echo json_encode(["status" => "error", "message" => "Phone number must be exactly 10 digits"]);
        exit;
    }

    // Validate password strength (min 8 chars, uppercase, lowercase, number, special char)
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/', $password)) {
        echo json_encode(["status" => "error", "message" => "Password must be at least 8 characters with uppercase, lowercase, number, and special character"]);
        exit;
    }

    // Check if email exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Email already registered"]);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, phone, status, is_verified) VALUES (?, ?, ?, ?, 'pending_onboarding', 1)");
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Database error during account creation: " . $conn->error]);
        exit;
    }
    $stmt->bind_param("ssss", $name, $email, $hashedPassword, $phone);

    if ($stmt->execute()) {
        $userId = $stmt->insert_id;
        echo json_encode([
            "status" => "success",
            "message" => "Account created successfully!",
            "id" => $userId,
            "email" => $email
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Signup failed: " . $conn->error]);
    }

} elseif ($action == 'verify_otp') {
    $email = trim($_POST['email'] ?? '');
    $otp = trim($_POST['otp'] ?? '');

    $stmt = $conn->prepare("SELECT id FROM password_resets WHERE email = ? AND otp = ? AND type = 'verify' AND expires_at > NOW() AND is_verified = 0");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $conn->query("UPDATE password_resets SET is_verified = 1 WHERE email = '$email' AND otp = '$otp'");
        $conn->query("UPDATE users SET is_verified = 1, status = 'pending_onboarding' WHERE email = '$email'");
        
        // Get user info for session
        $userQuery = $conn->query("SELECT id, full_name, role, email FROM users WHERE email = '$email'");
        $user = $userQuery->fetch_assoc();

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];

        echo json_encode([
            "status" => "success",
            "message" => "Email verified! Please complete your profile.",
            "user" => $user
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid or expired OTP"]);
    }

} elseif ($action == 'resend_otp') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        echo json_encode(["status" => "error", "message" => "Email is required"]);
        exit;
    }

    // Get user name if exists
    $nameCheck = $conn->prepare("SELECT full_name FROM users WHERE email = ?");
    $nameCheck->bind_param("s", $email);
    $nameCheck->execute();
    $nameRes = $nameCheck->get_result();
    $userName = ($nameRes->num_rows > 0) ? $nameRes->fetch_assoc()['full_name'] : 'User';

    $otp = sprintf("%06d", mt_rand(0, 999999));
    $conn->query("DELETE FROM password_resets WHERE email = '$email' AND type = 'verify'");
    $stmtOtp = $conn->prepare("INSERT INTO password_resets (email, otp, type, expires_at) VALUES (?, ?, 'verify', DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
    $stmtOtp->bind_param("ss", $email, $otp);

    if ($stmtOtp->execute() && sendOTPEmail($email, $otp, $userName)) {
        echo json_encode(["status" => "success", "message" => "New code sent!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to resend code. Please check your email configuration."]);
    }

} elseif ($action == 'complete_onboarding') {
    $userId = $_POST['user_id'] ?? 0;
    $role = $_POST['role'] ?? '';

    if (!$userId || !$role) {
        echo json_encode(["status" => "error", "message" => "Missing required data"]);
        exit;
    }

    $conn->query("UPDATE users SET role = '$role' WHERE id = $userId");

    $success = false;
    if ($role == 'patient') {
        $dob = $_POST['dob'] ?? null;
        $gender = $_POST['gender'] ?? null;
        $history = $_POST['medical_history'] ?? '';
        
        // Validate date of birth
        if (empty($dob)) {
            echo json_encode(["status" => "error", "message" => "Date of birth is required"]);
            exit;
        }
        
        // Validate gender
        if (empty($gender) || !in_array($gender, ['male', 'female', 'other'])) {
            echo json_encode(["status" => "error", "message" => "Please select a valid gender"]);
            exit;
        }
        
        $stmt = $conn->prepare("INSERT INTO patient_profiles (user_id, date_of_birth, gender, medical_history_summary) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE date_of_birth=?, gender=?, medical_history_summary=?");
        $stmt->bind_param("issssss", $userId, $dob, $gender, $history, $dob, $gender, $history);
        if ($stmt->execute()) {
            $conn->query("UPDATE users SET status = 'approved' WHERE id = $userId");
            $success = true;
        }
    } elseif ($role == 'doctor') {
        $license = trim($_POST['license'] ?? '');
        $specialization = trim($_POST['specialization'] ?? '');
        $experience = $_POST['experience'] ?? 0;
        $fees = $_POST['fees'] ?? 0;
        $langs = trim($_POST['languages'] ?? '');
        
        // Validate required fields
        if (empty($license)) {
            echo json_encode(["status" => "error", "message" => "Medical license number is required"]);
            exit;
        }
        if (empty($specialization)) {
            echo json_encode(["status" => "error", "message" => "Specialization is required"]);
            exit;
        }
        if (!is_numeric($experience) || $experience < 0) {
            echo json_encode(["status" => "error", "message" => "Experience must be a valid number (0 or greater)"]);
            exit;
        }
        if (!is_numeric($fees) || $fees < 0) {
            echo json_encode(["status" => "error", "message" => "Consultation fee must be a valid number (0 or greater)"]);
            exit;
        }
        if (empty($langs)) {
            echo json_encode(["status" => "error", "message" => "Languages spoken is required"]);
            exit;
        }
        
        $stmt = $conn->prepare("INSERT INTO doctor_profiles (user_id, license_number, specialization, years_experience, consultation_fee, languages_spoken) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE license_number=?, specialization=?, years_experience=?, consultation_fee=?, languages_spoken=?");
        $stmt->bind_param("issidssssis", $userId, $license, $specialization, $experience, $fees, $langs, $license, $specialization, $experience, $fees, $langs);
        if ($stmt->execute()) {
            $conn->query("UPDATE users SET status = 'pending' WHERE id = $userId");
            $success = true;
        }
    } elseif ($role == 'clinic' || $role == 'hospital') {
        $name = trim($_POST['org_name'] ?? '');
        $reg = trim($_POST['reg_number'] ?? '');
        $depts = trim($_POST['departments'] ?? '');
        $addr = trim($_POST['address'] ?? '');
        
        // Validate required fields
        if (empty($name)) {
            echo json_encode(["status" => "error", "message" => ($role == 'hospital' ? "Hospital" : "Clinic") . " name is required"]);
            exit;
        }
        if (empty($reg)) {
            echo json_encode(["status" => "error", "message" => "Registration number is required"]);
            exit;
        }
        if (empty($addr)) {
            echo json_encode(["status" => "error", "message" => "Address is required"]);
            exit;
        }
        
        if ($role == 'hospital') {
            $stmt = $conn->prepare("INSERT INTO hospital_profiles (user_id, hospital_name, address, registration_number) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE hospital_name=?, address=?, registration_number=?");
            if (!$stmt) {
                echo json_encode(["status" => "error", "message" => "Database error during hospital onboarding: " . $conn->error]);
                exit;
            }
            $stmt->bind_param("issssss", $userId, $name, $addr, $reg, $name, $addr, $reg);
        } else {
            if (empty($depts)) {
                echo json_encode(["status" => "error", "message" => "Departments information is required"]);
                exit;
            }
            $stmt = $conn->prepare("INSERT INTO clinic_profiles (user_id, clinic_name, registration_number, departments, address) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE clinic_name=?, registration_number=?, departments=?, address=?");
            if (!$stmt) {
                echo json_encode(["status" => "error", "message" => "Database error during clinic onboarding: " . $conn->error]);
                exit;
            }
            $stmt->bind_param("issssssss", $userId, $name, $reg, $depts, $addr, $name, $reg, $depts, $addr);
        }
        if ($stmt->execute()) {
            $conn->query("UPDATE users SET status = 'pending' WHERE id = $userId");
            $success = true;
        }
    } elseif ($role == 'pharmacy') {
        $name = trim($_POST['pharmacy_name'] ?? '');
        $lic = trim($_POST['license'] ?? '');
        $hours = trim($_POST['hours'] ?? '');
        $delivery = $_POST['delivery'] ?? '';
        $addr = trim($_POST['address'] ?? '');
        
        // Validate required fields
        if (empty($name)) {
            echo json_encode(["status" => "error", "message" => "Pharmacy name is required"]);
            exit;
        }
        if (empty($lic)) {
            echo json_encode(["status" => "error", "message" => "License details are required"]);
            exit;
        }
        if (empty($hours)) {
            echo json_encode(["status" => "error", "message" => "Operating hours are required"]);
            exit;
        }
        if (empty($delivery) || !in_array($delivery, ['pickup', 'delivery', 'both'])) {
            echo json_encode(["status" => "error", "message" => "Please select a valid delivery option"]);
            exit;
        }
        if (empty($addr)) {
            echo json_encode(["status" => "error", "message" => "Address is required"]);
            exit;
        }
        
        $stmt = $conn->prepare("INSERT INTO pharmacy_profiles (user_id, pharmacy_name, license_number, operating_hours, delivery_options, address) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE pharmacy_name=?, license_number=?, operating_hours=?, delivery_options=?, address=?");
        $stmt->bind_param("issssssssss", $userId, $name, $lic, $hours, $delivery, $addr, $name, $lic, $hours, $delivery, $addr);
        if ($stmt->execute()) {
            $conn->query("UPDATE users SET status = 'pending' WHERE id = $userId");
            $success = true;
        }
    }

    if ($success) {
        echo json_encode(["status" => "success", "message" => "Onboarding completed!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Onboarding failed: " . $conn->error]);
    }

} elseif ($action == 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Only require verification for new accounts that aren't approved yet
            if (!$user['is_verified'] && $user['status'] !== 'approved') {
                echo json_encode(["status" => "verification_required", "message" => "Please verify your email first", "email" => $email]);
            } elseif ($user['status'] == 'pending_onboarding') {
                echo json_encode(["status" => "onboarding_required", "message" => "Please complete your profile", "user" => $user]);
            } elseif ($user['status'] == 'pending') {
                if ($user['role'] == 'patient') {
                    // Auto-approve patients if they somehow ended up in pending status
                    $conn->query("UPDATE users SET status = 'approved' WHERE id = " . $user['id']);
                    $user['status'] = 'approved';
                    
                    // Proceed with login
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];

                    echo json_encode([
                        "status" => "success",
                        "user" => [
                            "id" => $user['id'],
                            "name" => $user['full_name'],
                            "role" => $user['role'],
                            "email" => $user['email']
                        ]
                    ]);
                } else {
                    echo json_encode(["status" => "pending", "message" => "Your account is awaiting admin approval"]);
                }
            } elseif ($user['status'] == 'rejected') {
                echo json_encode(["status" => "error", "message" => "Your account application was rejected"]);
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];

                echo json_encode([
                    "status" => "success",
                    "user" => [
                        "id" => $user['id'],
                        "name" => $user['full_name'],
                        "role" => $user['role'],
                        "email" => $user['email']
                    ]
                ]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid password"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Email not found"]);
    }
}
$conn->close();
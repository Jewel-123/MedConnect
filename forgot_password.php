<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit;
}

include 'db.php';
include 'email_config.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$action = $_POST['action'] ?? '';

// ============================================
// ACTION 1: Request Password Reset (Send OTP)
// ============================================
if ($action == 'request_reset') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        echo json_encode(["status" => "error", "message" => "Email is required"]);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Invalid email format"]);
        exit;
    }

    // Check if email exists in database
    $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode(["status" => "error", "message" => "Email not found in our system"]);
        exit;
    }

    $user = $result->fetch_assoc();
    
    // Check if an unverified OTP already exists for this email and was sent recently
    $checkRecent = $conn->prepare("SELECT otp, expires_at FROM password_resets WHERE email = ? AND is_verified = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 2 MINUTE) ORDER BY created_at DESC LIMIT 1");
    $checkRecent->bind_param("s", $email);
    $checkRecent->execute();
    $recentRes = $checkRecent->get_result();
    
    $otp = "";
    if ($recentRes->num_rows > 0) {
        // Reuse existing OTP if requested within 2 minutes to prevent issues with multiple clicks
        $row = $recentRes->fetch_assoc();
        $otp = $row['otp'];
        $expires = $row['expires_at'];
        file_put_contents("reset_log.txt", date("Y-m-d H:i:s") . " - Reusing recent OTP for $email: $otp" . PHP_EOL, FILE_APPEND);
    } else {
        // Generate new 6-digit OTP
        $otp = sprintf("%06d", mt_rand(0, 999999));
        
        // Delete any existing password reset requests for this email before creating new one
        $conn->query("DELETE FROM password_resets WHERE email = '$email'");

        // Save OTP to database using MySQL NOW() for consistency
        $stmt = $conn->prepare("INSERT INTO password_resets (email, otp, expires_at, is_verified) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE), 0)");
        
        if (!$stmt) {
            echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
            exit;
        }

        $stmt->bind_param("ss", $email, $otp);
        if (!$stmt->execute()) {
            echo json_encode(["status" => "error", "message" => "Failed to save OTP"]);
            exit;
        }
    }

    // Fetch the generated/current expiry for logging
    $expiryQuery = $conn->query("SELECT expires_at FROM password_resets WHERE email = '$email' ORDER BY created_at DESC LIMIT 1");
    $expires = $expiryQuery->fetch_assoc()['expires_at'] ?? 'unknown';
        // Attempt to send email using PHPMailer
        $mailSent = false;
        $mailError = '';
        
        try {
            // Check if email is configured
            if (!isEmailConfigured()) {
                throw new Exception('Email not configured. Please update email_config.php with your SMTP credentials.');
            }
            
            $config = getEmailConfig();
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure = $config['encryption'];
            $mail->Port = $config['port'];
            
            // Fix for SSL certificate verification on localhost/XAMPP
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Recipients
            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($email, $user['full_name']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset OTP - MedConnect';
            $mail->Body = '
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                        .otp-box { background: white; border: 2px dashed #667eea; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
                        .otp { font-size: 32px; font-weight: bold; color: #667eea; letter-spacing: 8px; }
                        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1>🔐 Password Reset Request</h1>
                        </div>
                        <div class="content">
                            <p>Hello <strong>' . htmlspecialchars($user['full_name']) . '</strong>,</p>
                            <p>We received a request to reset your password for your MedConnect account.</p>
                            <p>Your One-Time Password (OTP) is:</p>
                            <div class="otp-box">
                                <div class="otp">' . $otp . '</div>
                            </div>
                            <p><strong>⏰ This OTP will expire in 5 minutes.</strong></p>
                            <p>If you did not request this password reset, please ignore this email. Your password will remain unchanged.</p>
                            <p>For security reasons, never share this OTP with anyone.</p>
                        </div>
                        <div class="footer">
                            <p>This is an automated email from MedConnect. Please do not reply to this email.</p>
                        </div>
                    </div>
                </body>
                </html>
            ';
            $mail->AltBody = "Hello " . $user['full_name'] . ",\n\n" .
                            "Your OTP for password reset is: " . $otp . "\n\n" .
                            "This OTP will expire in 5 minutes.\n\n" .
                            "If you did not request this, please ignore this email.\n\n" .
                            "Best regards,\nMedConnect Team";
            
            $mail->send();
            $mailSent = true;
            
        } catch (Exception $e) {
            $mailError = $e->getMessage();
            // Log the error
            $logMsg = date("Y-m-d H:i:s") . " - Email Error for $email: " . $mailError . PHP_EOL;
            file_put_contents("reset_log.txt", $logMsg, FILE_APPEND);
        }

        // Log OTP (for debugging and when email fails)
        $logMsg = date("Y-m-d H:i:s") . " - OTP for $email: $otp (Expires: $expires, Mail Sent: " . ($mailSent ? 'Yes' : 'No');
        if (!$mailSent && !empty($mailError)) {
            $logMsg .= ", Error: " . $mailError;
        }
        $logMsg .= ")" . PHP_EOL;
        file_put_contents("reset_log.txt", $logMsg, FILE_APPEND);

        $response = [
            "status" => "success", 
            "message" => $mailSent ? "OTP has been sent to your email address. Please check your inbox." : "OTP generated but email delivery failed. Check console for OTP.",
            "email" => $email
        ];
        
        // Only include debug OTP if email failed (for debugging)
        if (!$mailSent) {
            $response["debug_otp"] = $otp;
            $response["email_error"] = $mailError;
        }
        

        echo json_encode($response);

// ============================================
// ACTION 2: Verify OTP
// ============================================
} elseif ($action == 'verify_otp') {
    $email = trim($_POST['email'] ?? '');
    $otp = trim($_POST['otp'] ?? '');

    if (empty($email) || empty($otp)) {
        file_put_contents("reset_log.txt", date("Y-m-d H:i:s") . " - Verify Error: Missing email ($email) or OTP ($otp)" . PHP_EOL, FILE_APPEND);
        echo json_encode(["status" => "error", "message" => "Email and OTP are required"]);
        exit;
    }

    // DEBUG LOG
    file_put_contents("reset_log.txt", date("Y-m-d H:i:s") . " - Attempting OTP Verify: Email=$email, OTP=$otp" . PHP_EOL, FILE_APPEND);

    // Verify OTP
    $stmt = $conn->prepare("SELECT id FROM password_resets WHERE email = ? AND otp = ? AND expires_at > NOW() AND is_verified = 0 ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $resetId = $row['id'];
        
        file_put_contents("reset_log.txt", date("Y-m-d H:i:s") . " - OTP Verified Successfully for $email" . PHP_EOL, FILE_APPEND);
        
        // Mark OTP as verified
        $update = $conn->prepare("UPDATE password_resets SET is_verified = 1 WHERE id = ?");
        $update->bind_param("i", $resetId);
        $update->execute();

        echo json_encode([
            "status" => "success", 
            "message" => "OTP verified successfully"
        ]);
    } else {
        // Check if OTP exists but is expired OR mismatched
        $checkAll = $conn->prepare("SELECT otp, expires_at, is_verified FROM password_resets WHERE email = ? ORDER BY created_at DESC LIMIT 1");
        $checkAll->bind_param("s", $email);
        $checkAll->execute();
        $allRes = $checkAll->get_result();
        
        if ($allRes->num_rows > 0) {
            $row = $allRes->fetch_assoc();
            $db_otp = $row['otp'];
            $db_expires = $row['expires_at'];
            $db_verified = $row['is_verified'];
            
            file_put_contents("reset_log.txt", date("Y-m-d H:i:s") . " - Verify Failed for $email: Input OTP=$otp, DB OTP=$db_otp, DB Expires=$db_expires, Verified=$db_verified, Current Time=" . date("Y-m-d H:i:s") . PHP_EOL, FILE_APPEND);
            
            if ($db_otp !== $otp) {
                echo json_encode(["status" => "error", "message" => "Invalid OTP. Please try again."]);
            } elseif ($db_expires <= date("Y-m-d H:i:s")) {
                echo json_encode(["status" => "error", "message" => "OTP has expired. Please request a new one."]);
            } else {
                echo json_encode(["status" => "error", "message" => "This OTP has already been verified or is invalid."]);
            }
        } else {
            file_put_contents("reset_log.txt", date("Y-m-d H:i:s") . " - Verify Failed for $email: No record found in database for this email." . PHP_EOL, FILE_APPEND);
            echo json_encode(["status" => "error", "message" => "Invalid OTP or session expired. Please try again."]);
        }
    }

// ============================================
// ACTION 3: Reset Password (After OTP Verification)
// ============================================
} elseif ($action == 'reset_password') {
    $email = trim($_POST['email'] ?? '');
    $newPassword = $_POST['password'] ?? '';

    if (empty($email) || empty($newPassword)) {
        echo json_encode(["status" => "error", "message" => "Email and password are required"]);
        exit;
    }

    // Verify that OTP was verified for this email
    $stmt = $conn->prepare("SELECT id FROM password_resets WHERE email = ? AND is_verified = 1 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo json_encode(["status" => "error", "message" => "OTP not verified or expired. Please start over."]);
        exit;
    }

    // Update user password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $update->bind_param("ss", $hashedPassword, $email);
    
    if ($update->execute()) {
        // Delete the password reset record
        $conn->query("DELETE FROM password_resets WHERE email = '$email'");
        
        echo json_encode([
            "status" => "success", 
            "message" => "Password updated successfully"
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update password"]);
    }

// ============================================
// ACTION 4: Auto Login After Password Reset
// ============================================
} elseif ($action == 'auto_login') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        echo json_encode(["status" => "error", "message" => "Email is required"]);
        exit;
    }

    // Get user details
    $stmt = $conn->prepare("SELECT id, full_name, email, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Create session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];

        echo json_encode([
            "status" => "success",
            "message" => "Login successful",
            "user" => [
                "id" => $user['id'],
                "name" => $user['full_name'],
                "email" => $user['email'],
                "role" => $user['role']
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "User not found"]);
    }

} else {
    echo json_encode(["status" => "error", "message" => "Invalid action"]);
}

$conn->close();
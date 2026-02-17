<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
session_start();
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['credential'])) {
    $jwt = $data['credential'];
    
    // Verify JWT (In production, use a library like google-auth-library-php)
    // For this simple implementation without composer, we will decode the payload manually
    // WARNING: This skips signature verification. ONLY FOR DEMO/DEV.
    
    $parts = explode('.', $jwt);
    if (count($parts) != 3) {
        echo json_encode(["status" => "error", "message" => "Invalid Token"]);
        exit;
    }
    
    // Base64Url Decode function
    if (!function_exists('base64UrlDecode')) {
        function base64UrlDecode($data) {
            $urlSafeData = str_replace(array('-', '_'), array('+', '/'), $data);
            return base64_decode($urlSafeData);
        }
    }
    
    $payload = json_decode(base64UrlDecode($parts[1]), true);
    
    if (!$payload) {
        echo json_encode(["status" => "error", "message" => "Invalid Payload"]);
        exit;
    }

    $google_id = $payload['sub'];
    $email = $payload['email'];
    $name = $payload['name'] ?? 'Google User'; 
    
    // 1. Check if user exists by email
    $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // User exists, update Google ID if missing
        $user = $result->fetch_assoc();
        
        // Link Google ID if not already linked
        if (empty($user['google_id'])) {
            $update = $conn->prepare("UPDATE users SET google_id = ?, is_verified = 1 WHERE id = ?");
            if ($update) {
                $update->bind_param("si", $google_id, $user['id']);
                $update->execute();
            }
        }

        // Always treat as patient for this flow as per requirement
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = 'patient'; 
        $_SESSION['full_name'] = $user['full_name'];

        echo json_encode([
            "status" => "success",
            "message" => "Login successful",
            "role" => "patient",
            "user" => [
                "id" => $user['id'],
                "name" => $user['full_name'],
                "role" => 'patient',
                "email" => $user['email']
            ]
        ]);
    } else {
        // New User - Auto register as Patient
        $role = 'patient';
        $status = 'approved'; 
        $is_verified = 1;
        $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);

        // Insert new user
        $insert = $conn->prepare("INSERT INTO users (full_name, email, password, google_id, role, status, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($insert) {
            $insert->bind_param("ssssssi", $name, $email, $password, $google_id, $role, $status, $is_verified);
            if ($insert->execute()) {
                $new_user_id = $conn->insert_id;

                // Automatically create patient profile
                $profile_insert = $conn->prepare("INSERT INTO patient_profiles (user_id) VALUES (?)");
                if ($profile_insert) {
                    $profile_insert->bind_param("i", $new_user_id);
                    $profile_insert->execute();
                }

                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'patient';
                $_SESSION['full_name'] = $name;

                echo json_encode([
                    "status" => "success",
                    "message" => "Registration successful",
                    "role" => "patient",
                    "user" => [
                        "id" => $new_user_id,
                        "name" => $name,
                        "role" => 'patient',
                        "email" => $email
                    ],
                    "redirect" => "patient_dashboard.php"
                ]);
            } else {
                echo json_encode(["status" => "error", "message" => "Registration failed: " . $conn->error]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
        }
    }
} else {
    echo json_encode(["status" => "error", "message" => "No credential provided"]);
}

$conn->close();
?>
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
    function base64UrlDecode($data) {
        $urlSafeData = str_replace(array('-', '_'), array('+', '/'), $data);
        return base64_decode($urlSafeData);
    }
    
    $payload = json_decode(base64UrlDecode($parts[1]), true);
    
    if (!$payload) {
        echo json_encode(["status" => "error", "message" => "Invalid Payload"]);
        exit;
    }

    $google_id = $payload['sub'];
    $email = $payload['email'];
    $name = $payload['name'];
    $picture = $payload['picture'] ?? '';
    
    // Check if user exists
    $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // User exists, update Google ID if missing
        $user = $result->fetch_assoc();
        if (empty($user['google_id']) || !$user['is_verified']) {
            $update = $conn->prepare("UPDATE users SET google_id = ?, is_verified = 1 WHERE id = ?");
            if ($update) {
                $update->bind_param("si", $google_id, $user['id']);
                $update->execute();
            }
        }
        
        if ($user['status'] == 'pending') {
            if ($user['role'] == 'patient') {
                $conn->query("UPDATE users SET status = 'approved' WHERE id = " . $user['id']);
                $user['status'] = 'approved';
            } else {
                echo json_encode(["status" => "pending", "message" => "Your account is awaiting admin approval"]);
                exit;
            }
        } elseif ($user['status'] == 'rejected') {
            echo json_encode(["status" => "error", "message" => "Your account application was rejected"]);
            exit;
        } elseif ($user['status'] == 'pending_onboarding') {
            // Fix: Allow patients to login even if status is pending_onboarding (auto-approve like new users)
            if ($user['role'] == 'patient') {
                $conn->query("UPDATE users SET status = 'approved' WHERE id = " . $user['id']);
                $user['status'] = 'approved';
            } else {
                echo json_encode(["status" => "onboarding_required", "message" => "Please complete your profile", "user" => $user]);
                exit;
            }
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];

        echo json_encode([
            "status" => "success",
            "message" => "Login successful",
            "user" => [
                "id" => $user['id'],
                "name" => $user['full_name'],
                "role" => $user['role'],
                "email" => $user['email']
            ]
        ]);
    } else {
        // New User - Auto register as Patient (default)
        // Or prompt? For better UX, let's assume 'patient' or return a specific code to frontend to ask for role.
        // For simplicity: Default to 'patient'
        // Default to 'patient' or use provided role
        $role = $data['role'] ?? 'patient';
        // Validate role
        if (!in_array($role, ['patient', 'doctor', 'pharmacy', 'admin'])) {
            $role = 'patient';
        }
        // Generate random password
        $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        
        // Determine status based on role
        $status = ($role === 'patient') ? 'approved' : 'pending';
        
        $insert = $conn->prepare("INSERT INTO users (full_name, email, password, role, google_id, is_verified, status) VALUES (?, ?, ?, ?, ?, 1, ?)");
        $insert->bind_param("ssssss", $name, $email, $password, $role, $google_id, $status);
        
        if ($insert->execute()) {
             $new_id = $conn->insert_id;
             
             if ($status === 'pending') {
                 echo json_encode([
                     "status" => "pending",
                     "message" => "Account created, but awaiting admin approval."
                 ]);
                 exit;
             }

             $_SESSION['user_id'] = $new_id;
             $_SESSION['role'] = $role;
             $_SESSION['email'] = $email;

             echo json_encode([
                "status" => "success",
                "message" => "Account created successfully",
                "is_new_user" => true,
                "user" => [
                    "id" => $new_id,
                    "name" => $name,
                    "role" => $role,
                    "email" => $email
                ]
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Registration failed"]);
        }
    }

} else {
    echo json_encode(["status" => "error", "message" => "No credential provided"]);
}

$conn->close();
?>

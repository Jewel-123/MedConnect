<?php
/**
 * Admin Actions Handler
 * Handles admin operations like approving/rejecting applications, search, and notifications.
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
require_once 'db.php';

session_start();

$action = $_POST['action'] ?? '';

if ($action === 'update_status') {
    $userId = $_POST['user_id'] ?? 0;
    $status = $_POST['status'] ?? '';

    // Validate inputs
    if (empty($userId) || empty($status)) {
        echo json_encode(["status" => "error", "message" => "Missing required fields"]);
        exit;
    }

    // Validate status value
    if (!in_array($status, ['approved', 'rejected', 'pending'])) {
        echo json_encode(["status" => "error", "message" => "Invalid status value"]);
        exit;
    }

    // Update user status
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $userId);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "User status updated successfully"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to update status: " . $stmt->error
        ]);
    }
    $stmt->close();

} elseif ($action === 'search') {
    $term = $_POST['query'] ?? '';
    if (strlen($term) < 2) {
        echo json_encode(["status" => "success", "data" => []]);
        exit;
    }
    
    $term = "%$term%";
    $results = [];

    // Search Users
    $userStmt = $conn->prepare("SELECT id, full_name as title, role as type, 'user' as category FROM users WHERE (full_name LIKE ? OR email LIKE ?) AND status != 'rejected' AND role IS NOT NULL AND role != '' LIMIT 5");
    $userStmt->bind_param("ss", $term, $term);
    $userStmt->execute();
    $res = $userStmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $results[] = $row;
    }
    $userStmt->close();

    // Search Consultations (if table exists)
    $checkCons = $conn->query("SHOW TABLES LIKE 'consultations'");
    if ($checkCons && $checkCons->num_rows > 0) {
        // Search by symptoms or ID
        $consStmt = $conn->prepare("SELECT id, symptoms as title, 'consultation' as type, 'consultation' as category FROM consultations WHERE symptoms LIKE ? LIMIT 3");
        $consStmt->bind_param("s", $term);
        $consStmt->execute();
        $res = $consStmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $row['title'] = "Consultation #" . $row['id'] . ": " . substr($row['title'], 0, 30);
            $results[] = $row;
        }
        $consStmt->close();
    }

    echo json_encode(["status" => "success", "data" => $results]);

} elseif ($action === 'get_notifications') {
    // Fetch pending tasks
    $notifs = [];
    
    // Pending Doctors
    $pendingDocs = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='doctor' AND status='pending'")->fetch_assoc()['c'];
    if ($pendingDocs > 0) {
        $notifs[] = [
            "id" => "doc_pending",
            "title" => "$pendingDocs Doctor Application(s)",
            "time" => "Action Required",
            "priority" => "high",
            "link" => "?view=doctors"
        ];
    }
    
    // Pending Health Partners (Pharmacy, Clinic, Hospital)
    $pendingPartners = $conn->query("SELECT COUNT(*) as c FROM users WHERE role IN ('pharmacy', 'clinic', 'hospital') AND status='pending'")->fetch_assoc()['c'];
    if ($pendingPartners > 0) {
        $notifs[] = [
            "id" => "partner_pending",
            "title" => "$pendingPartners Health Partner Application(s)",
            "time" => "Action Required",
            "priority" => "medium",
            "link" => "?view=partners"
        ];
    }

    echo json_encode(["status" => "success", "data" => $notifs]);

} elseif ($action === 'mark_all_read') {
    $_SESSION['notifs_read_at'] = date('Y-m-d H:i:s');
    echo json_encode(["status" => "success"]);

} elseif ($action === 'update_user') {
    $userId = $_POST['user_id'] ?? 0;
    $fullName = $_POST['full_name'] ?? '';
    // $email = $_POST['email'] ?? ''; 
    $role = $_POST['role'] ?? '';
    $status = $_POST['status'] ?? '';
    
    if (empty($userId) || empty($fullName) || empty($role) || empty($status)) {
        echo json_encode(["status" => "error", "message" => "Missing required fields"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET full_name = ?, role = ?, status = ? WHERE id = ?");
    $stmt->bind_param("sssi", $fullName, $role, $status, $userId);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "User updated successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update: " . $stmt->error]);
    }
    $stmt->close();

} elseif ($action === 'delete_user') {
    $userId = $_POST['user_id'] ?? 0;
    
    // Check for Consultations
    $check = $conn->query("SELECT * FROM consultations WHERE patient_id = $userId AND status IN ('pending', 'assigned')");
    if ($check && $check->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Cannot delete user with active consultations."]);
        exit;
    }

    // Soft delete -> Set status to rejected
    $stmt = $conn->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "User deactivated successfully"]);
    } else {
         echo json_encode(["status" => "error", "message" => "Failed to deactivate: " . $stmt->error]);
    }
    $stmt->close();

} else {
    echo json_encode(["status" => "error", "message" => "Invalid action"]);
}

$conn->close();
?>

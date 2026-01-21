<?php
/**
 * Admin Actions Handler
 * Handles admin operations like approving/rejecting applications
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
} else {
    echo json_encode(["status" => "error", "message" => "Invalid action"]);
}

$conn->close();
?>

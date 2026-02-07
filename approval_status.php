<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Pending - MedConnect</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body>
    <?php
    session_start();
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }

    require_once 'db.php';
    
    // Fetch user status
    $stmt = $conn->prepare("SELECT full_name, email, role, status FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        header('Location: login.php');
        exit();
    }
    
    // If approved, redirect to appropriate dashboard
    if ($user['status'] === 'approved') {
        header('Location: index.php'); // Will be updated to role-specific dashboard later
        exit();
    }
    ?>

    <div class="auth-container fade-in" style="margin-top: 5rem; text-align: center;">
        <div style="margin-bottom: 2rem;">
            <?php if ($user['status'] === 'pending'): ?>
                <i class="ph ph-clock" style="font-size: 4rem; color: #f59e0b;"></i>
            <?php else: ?>
                <i class="ph ph-x-circle" style="font-size: 4rem; color: #ef4444;"></i>
            <?php endif; ?>
        </div>

        <?php if ($user['status'] === 'pending'): ?>
            <h2>Application Under Review</h2>
            <p style="color: #666; margin: 1rem 0;">
                Thank you for applying, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>!
            </p>
            <p style="color: #666; margin: 1rem 0;">
                Your application as a <strong><?php echo ucfirst($user['role']); ?></strong> is currently being reviewed by our admin team.
            </p>
            <p style="color: #666; margin: 1rem 0;">
                You will receive an email notification once your application has been processed.
            </p>
            <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 1rem; margin: 2rem 0;">
                <p style="margin: 0; color: #92400e;">
                    <i class="ph ph-info"></i> Please check back later or wait for our email notification.
                </p>
            </div>
        <?php elseif ($user['status'] === 'rejected'): ?>
            <h2>Application Not Approved</h2>
            <p style="color: #666; margin: 1rem 0;">
                We're sorry, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>.
            </p>
            <p style="color: #666; margin: 1rem 0;">
                Your application as a <strong><?php echo ucfirst($user['role']); ?></strong> was not approved at this time.
            </p>
            <p style="color: #666; margin: 1rem 0;">
                If you believe this is an error, please contact our support team.
            </p>
        <?php endif; ?>

        <div style="margin-top: 2rem;">
            <a href="logout.php" class="btn btn-primary">Logout</a>
        </div>

        <p style="margin-top: 1rem; font-size: 0.9rem;">
            <a href="index.php" style="color: var(--primary)">← Back to Home</a>
        </p>
    </div>
</body>
</html>
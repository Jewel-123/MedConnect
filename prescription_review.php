<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$prescriptionId = intval($_GET['prescription_id'] ?? 0);
$userId = $_SESSION['user_id'];

if (!$prescriptionId) {
    die('Invalid prescription ID');
}

// Get prescription order details
$stmt = $conn->prepare("
    SELECT po.*, p.prescription_number,
           u.full_name as pharmacy_name,
           pp.pharmacy_name as pharmacy_business_name
    FROM prescription_orders po
    JOIN prescriptions_v2 p ON po.prescription_id = p.id
    JOIN users u ON po.pharmacy_id = u.id
    LEFT JOIN pharmacy_profiles pp ON u.id = pp.user_id
    WHERE po.prescription_id = ? AND po.patient_id = ? AND po.order_status = 'completed'
");
$stmt->bind_param("ii", $prescriptionId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die('Prescription order not found or not completed');
}

// Check if review already submitted
$stmt = $conn->prepare("
    SELECT id FROM prescription_reviews 
    WHERE prescription_id = ? AND patient_id = ?
");
$stmt->bind_param("ii", $prescriptionId, $userId);
$stmt->execute();
$existingReview = $stmt->get_result()->fetch_assoc();

if ($existingReview) {
    die('You have already submitted a review for this prescription');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Review - Order #<?php echo htmlspecialchars($order['order_number']); ?></title>
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.0.3/src/regular/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f1f5f9; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header h1 { font-size: 28px; margin-bottom: 5px; }
        .header p { opacity: 0.9; font-size: 14px; }
        
        .container { max-width: 700px; margin: 30px auto; padding: 0 20px; }
        .back-btn { display: inline-flex; align-items: center; gap: 8px; background: white; color: #667eea; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; margin-bottom: 20px; transition: all 0.3s; }
        .back-btn:hover { background: #f8f9ff; transform: translateX(-5px); }
        
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .card-header { font-size: 20px; font-weight: 700; color: #1e293b; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f1f5f9; }
        
        .order-info { background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .order-info p { margin: 5px 0; color: #64748b; }
        .order-info strong { color: #1e293b; }
        
        .rating-section { margin: 20px 0; }
        .rating-label { font-weight: 600; color: #1e293b; margin-bottom: 10px; display: block; }
        .stars { display: flex; gap: 10px; margin: 10px 0; }
        .star { font-size: 40px; cursor: pointer; color: #e2e8f0; transition: all 0.2s; }
        .star:hover, .star.active { color: #fbbf24; }
        
        .form-group { margin: 20px 0; }
        .form-group label { display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; }
        .form-group textarea { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-size: 14px; resize: vertical; min-height: 120px; }
        .form-group textarea:focus { outline: none; border-color: #667eea; }
        
        .checkbox-group { display: flex; align-items: center; gap: 10px; margin: 15px 0; }
        .checkbox-group input[type="checkbox"] { width: 20px; height: 20px; cursor: pointer; }
        .checkbox-group label { cursor: pointer; color: #475569; }
        
        .btn { padding: 15px 30px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 16px; transition: all 0.3s; display: inline-flex; align-items: center; gap: 10px; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; width: 100%; justify-content: center; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
        
        .success-message { background: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin: 20px 0; display: none; }
        .error-message { background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; margin: 20px 0; display: none; }
    </style>
</head>
<body>
    <div class="header">
        <h1>⭐ Submit Review</h1>
        <p>Share your experience with the pharmacy service</p>
    </div>
    
    <div class="container">
        <a href="patient_prescriptions.php" class="back-btn">
            <i class="ph ph-arrow-left"></i> Back to Prescriptions
        </a>
        
        <div class="card">
            <div class="card-header">Order Information</div>
            
            <div class="order-info">
                <p><strong>Order Number:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                <p><strong>Pharmacy:</strong> <?php echo htmlspecialchars($order['pharmacy_business_name'] ?? $order['pharmacy_name']); ?></p>
                <p><strong>Amount Paid:</strong> ₹<?php echo number_format($order['total_amount'], 2); ?></p>
            </div>
        </div>
        
        <div id="successMessage" class="success-message"></div>
        <div id="errorMessage" class="error-message"></div>
        
        <form id="reviewForm" class="card">
            <div class="card-header">Your Review</div>
            
            <div class="rating-section">
                <label class="rating-label">Overall Rating *</label>
                <div class="stars" id="overallRating">
                    <span class="star" data-rating="1">★</span>
                    <span class="star" data-rating="2">★</span>
                    <span class="star" data-rating="3">★</span>
                    <span class="star" data-rating="4">★</span>
                    <span class="star" data-rating="5">★</span>
                </div>
                <input type="hidden" id="ratingValue" name="rating" required>
            </div>
            
            <div class="rating-section">
                <label class="rating-label">Service Quality</label>
                <div class="stars" id="serviceRating">
                    <span class="star" data-rating="1">★</span>
                    <span class="star" data-rating="2">★</span>
                    <span class="star" data-rating="3">★</span>
                    <span class="star" data-rating="4">★</span>
                    <span class="star" data-rating="5">★</span>
                </div>
                <input type="hidden" id="serviceValue" name="service_quality">
            </div>
            
            <div class="rating-section">
                <label class="rating-label">Delivery Speed</label>
                <div class="stars" id="deliveryRating">
                    <span class="star" data-rating="1">★</span>
                    <span class="star" data-rating="2">★</span>
                    <span class="star" data-rating="3">★</span>
                    <span class="star" data-rating="4">★</span>
                    <span class="star" data-rating="5">★</span>
                </div>
                <input type="hidden" id="deliveryValue" name="delivery_speed">
            </div>
            
            <div class="rating-section">
                <label class="rating-label">Medicine Quality</label>
                <div class="stars" id="medicineRating">
                    <span class="star" data-rating="1">★</span>
                    <span class="star" data-rating="2">★</span>
                    <span class="star" data-rating="3">★</span>
                    <span class="star" data-rating="4">★</span>
                    <span class="star" data-rating="5">★</span>
                </div>
                <input type="hidden" id="medicineValue" name="medicine_quality">
            </div>
            
            <div class="form-group">
                <label for="reviewText">Your Experience (Optional)</label>
                <textarea id="reviewText" name="review_text" placeholder="Tell us about your experience with the pharmacy service..."></textarea>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="wouldRecommend" name="would_recommend" checked>
                <label for="wouldRecommend">I would recommend this pharmacy to others</label>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="ph ph-paper-plane"></i>
                Submit Review
            </button>
        </form>
    </div>
    
    <script>
        const prescriptionId = <?php echo $prescriptionId; ?>;
        const orderId = <?php echo $order['id']; ?>;
        const pharmacyId = <?php echo $order['pharmacy_id']; ?>;
        
        // Rating functionality
        function setupRating(containerId, inputId) {
            const container = document.getElementById(containerId);
            const input = document.getElementById(inputId);
            const stars = container.querySelectorAll('.star');
            
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = this.getAttribute('data-rating');
                    input.value = rating;
                    
                    stars.forEach(s => {
                        if (s.getAttribute('data-rating') <= rating) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });
                });
                
                star.addEventListener('mouseenter', function() {
                    const rating = this.getAttribute('data-rating');
                    stars.forEach(s => {
                        if (s.getAttribute('data-rating') <= rating) {
                            s.style.color = '#fbbf24';
                        } else {
                            s.style.color = '#e2e8f0';
                        }
                    });
                });
            });
            
            container.addEventListener('mouseleave', function() {
                const currentRating = input.value;
                stars.forEach(s => {
                    if (currentRating && s.getAttribute('data-rating') <= currentRating) {
                        s.style.color = '#fbbf24';
                    } else {
                        s.style.color = '#e2e8f0';
                    }
                });
            });
        }
        
        setupRating('overallRating', 'ratingValue');
        setupRating('serviceRating', 'serviceValue');
        setupRating('deliveryRating', 'deliveryValue');
        setupRating('medicineRating', 'medicineValue');
        
        // Form submission
        document.getElementById('reviewForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const rating = document.getElementById('ratingValue').value;
            if (!rating) {
                showError('Please provide an overall rating');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'submit_review');
            formData.append('prescription_id', prescriptionId);
            formData.append('prescription_order_id', orderId);
            formData.append('pharmacy_id', pharmacyId);
            formData.append('rating', rating);
            formData.append('service_quality', document.getElementById('serviceValue').value || null);
            formData.append('delivery_speed', document.getElementById('deliveryValue').value || null);
            formData.append('medicine_quality', document.getElementById('medicineValue').value || null);
            formData.append('review_text', document.getElementById('reviewText').value);
            formData.append('would_recommend', document.getElementById('wouldRecommend').checked ? 1 : 0);
            
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="ph ph-spinner"></i> Submitting...';
            
            try {
                const response = await fetch('prescription_review_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess('Thank you for your review! Redirecting...');
                    setTimeout(() => {
                        window.location.href = 'patient_prescriptions.php';
                    }, 2000);
                } else {
                    showError(data.error || 'Failed to submit review');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="ph ph-paper-plane"></i> Submit Review';
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Network error. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="ph ph-paper-plane"></i> Submit Review';
            }
        });
        
        function showSuccess(message) {
            const el = document.getElementById('successMessage');
            el.textContent = message;
            el.style.display = 'block';
            window.scrollTo(0, 0);
        }
        
        function showError(message) {
            const el = document.getElementById('errorMessage');
            el.textContent = message;
            el.style.display = 'block';
            window.scrollTo(0, 0);
        }
    </script>
</body>
</html>

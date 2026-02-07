<?php
session_start();
require_once 'db.php';
require_once 'razorpay_config.php';

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
    SELECT po.*, p.prescription_number, p.diagnosis,
           u.full_name as pharmacy_name,
           pp.pharmacy_name as pharmacy_business_name,
           pat.full_name as patient_name,
           pat.email as patient_email,
           pat.phone as patient_phone
    FROM prescription_orders po
    JOIN prescriptions_v2 p ON po.prescription_id = p.id
    JOIN users u ON po.pharmacy_id = u.id
    LEFT JOIN pharmacy_profiles pp ON u.id = pp.user_id
    JOIN users pat ON po.patient_id = pat.id
    WHERE po.prescription_id = ? AND po.patient_id = ?
");
$stmt->bind_param("ii", $prescriptionId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die('Prescription order not found or not ready for payment');
}

// Get prescription items with prices
$items = $conn->query("
    SELECT pi.*, inv.unit_price
    FROM prescription_items_v2 pi
    LEFT JOIN pharmacy_inventory inv ON 
        inv.medicine_name = pi.medicine_name AND 
        inv.pharmacy_id = {$order['pharmacy_id']}
    WHERE pi.prescription_id = $prescriptionId
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Prescription #<?php echo htmlspecialchars($order['prescription_number']); ?></title>
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.0.3/src/regular/style.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f1f5f9; }
        
        .header { background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); color: white; padding: 20px 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header h1 { font-size: 28px; margin-bottom: 5px; }
        .header p { opacity: 0.9; font-size: 14px; }
        
        .container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        .back-btn { display: inline-flex; align-items: center; gap: 8px; background: white; color: #0d9488; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; margin-bottom: 20px; transition: all 0.3s; }
        .back-btn:hover { background: #f0fdfa; transform: translateX(-5px); }
        
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .card-header { font-size: 20px; font-weight: 700; color: #1e293b; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f1f5f9; }
        
        .order-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .info-item { padding: 12px; background: #f8fafc; border-radius: 8px; }
        .info-label { font-size: 12px; color: #64748b; margin-bottom: 5px; }
        .info-value { font-size: 16px; font-weight: 600; color: #1e293b; }
        
        .items-list { margin: 20px 0; }
        .item { display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #e2e8f0; }
        .item:last-child { border-bottom: none; }
        .item-name { font-weight: 600; color: #1e293b; }
        .item-details { color: #64748b; font-size: 14px; margin-top: 5px; }
        .item-price { font-weight: 700; color: #10b981; font-size: 18px; }
        
        .total-section { background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .total-row { display: flex; justify-content: space-between; padding: 10px 0; }
        .total-row.grand { border-top: 2px solid #e2e8f0; margin-top: 10px; padding-top: 15px; font-size: 24px; font-weight: 700; color: #1e293b; }
        
        .btn { padding: 15px 30px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 16px; transition: all 0.3s; display: inline-flex; align-items: center; gap: 10px; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(13, 148, 136, 0.4); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
        
        .payment-methods { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0; }
        .payment-method { padding: 15px; border: 2px solid #e2e8f0; border-radius: 8px; text-align: center; cursor: pointer; transition: all 0.3s; }
        .payment-method:hover { border-color: #0d9488; background: #f0fdfa; }
        .payment-method.active { border-color: #0d9488; background: #f0fdfa; }
        
        .success-message { background: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin: 20px 0; display: none; }
        .error-message { background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; margin: 20px 0; display: none; }
    </style>
</head>
<body>
    <div class="header">
        <h1>💳 Payment</h1>
        <p>Complete your prescription order payment</p>
    </div>
    
    <div class="container">
        <a href="patient_prescriptions.php" class="back-btn">
            <i class="ph ph-arrow-left"></i> Back to Prescriptions
        </a>
        
        <div class="card">
            <div class="card-header">Order Details</div>
            
            <div class="order-info">
                <div class="info-item">
                    <div class="info-label">Order Number</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['order_number']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Pharmacy</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['pharmacy_business_name'] ?? $order['pharmacy_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Prescription #</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['prescription_number']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Fulfillment</div>
                    <div class="info-value"><?php echo ucfirst($order['fulfillment_type']); ?></div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Medicines</div>
            
            <div class="items-list">
                <?php foreach ($items as $item): ?>
                <div class="item">
                    <div>
                        <div class="item-name"><?php echo htmlspecialchars($item['medicine_name']); ?></div>
                        <div class="item-details">
                            <?php echo htmlspecialchars($item['dosage']); ?> • 
                            <?php echo htmlspecialchars($item['frequency']); ?> • 
                            <?php echo htmlspecialchars($item['duration']); ?>
                        </div>
                        <?php if ($item['instructions']): ?>
                        <div class="item-details"><em><?php echo htmlspecialchars($item['instructions']); ?></em></div>
                        <?php endif; ?>
                    </div>
                    <div class="item-price">
                        ₹<?php echo number_format($item['unit_price'] ?? 0, 2); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="total-section">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
                <div class="total-row">
                    <span>Delivery Charges</span>
                    <span>₹0.00</span>
                </div>
                <div class="total-row grand">
                    <span>Total Amount</span>
                    <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>
        </div>
        
        <div id="successMessage" class="success-message"></div>
        <div id="errorMessage" class="error-message"></div>
        
        <div class="card">
            <div class="card-header">Payment Method</div>
            
            <div class="payment-methods">
                <div class="payment-method active" data-method="razorpay">
                    <i class="ph ph-credit-card" style="font-size: 32px; color: #667eea;"></i>
                    <div style="margin-top: 10px; font-weight: 600;">Razorpay</div>
                    <div style="font-size: 12px; color: #64748b;">Card/UPI/Netbanking</div>
                </div>
            </div>
            
            <button id="payButton" class="btn btn-primary" style="width: 100%; margin-top: 20px;">
                <i class="ph ph-lock"></i>
                Pay ₹<?php echo number_format($order['total_amount'], 2); ?>
            </button>
        </div>
    </div>
    
    <script>
        const orderData = <?php echo json_encode($order); ?>;
        const prescriptionId = <?php echo $prescriptionId; ?>;
        
        document.getElementById('payButton').addEventListener('click', initiatePayment);
        
        async function initiatePayment() {
            const payButton = document.getElementById('payButton');
            payButton.disabled = true;
            payButton.innerHTML = '<i class="ph ph-spinner"></i> Processing...';
            
            try {
                // Create Razorpay order
                const response = await fetch('razorpay_order_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create_prescription_order',
                        prescription_id: prescriptionId,
                        amount: orderData.total_amount
                    })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.error || 'Failed to create payment order');
                }
                
                // Open Razorpay checkout
                const options = {
                    key: '<?php echo RAZORPAY_KEY_ID; ?>',
                    amount: data.order.amount,
                    currency: 'INR',
                    name: 'MedConnect',
                    description: 'Prescription Order #' + orderData.order_number,
                    order_id: data.order.id,
                    prefill: {
                        name: '<?php echo htmlspecialchars($order['patient_name']); ?>',
                        email: '<?php echo htmlspecialchars($order['patient_email']); ?>',
                        contact: '<?php echo htmlspecialchars($order['patient_phone']); ?>'
                    },
                    theme: {
                        color: '#0d9488'
                    },
                    handler: function(response) {
                        verifyPayment(response);
                    },
                    modal: {
                        ondismiss: function() {
                            payButton.disabled = false;
                            payButton.innerHTML = '<i class="ph ph-lock"></i> Pay ₹<?php echo number_format($order['total_amount'], 2); ?>';
                        }
                    }
                };
                
                const rzp = new Razorpay(options);
                rzp.open();
                
            } catch (error) {
                console.error('Payment error:', error);
                showError(error.message);
                payButton.disabled = false;
                payButton.innerHTML = '<i class="ph ph-lock"></i> Pay ₹<?php echo number_format($order['total_amount'], 2); ?>';
            }
        }
        
        async function verifyPayment(response) {
            try {
                const verifyResponse = await fetch('razorpay_webhook.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'verify_prescription_payment',
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_signature: response.razorpay_signature,
                        prescription_id: prescriptionId
                    })
                });
                
                const data = await verifyResponse.json();
                
                if (data.success) {
                    showSuccess('Payment successful! Redirecting...');
                    setTimeout(() => {
                        window.location.href = 'prescription_review.php?prescription_id=' + prescriptionId;
                    }, 2000);
                } else {
                    showError('Payment verification failed: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Verification error:', error);
                showError('Payment verification failed. Please contact support.');
            }
        }
        
        function showSuccess(message) {
            const el = document.getElementById('successMessage');
            el.textContent = message;
            el.style.display = 'block';
            setTimeout(() => el.style.display = 'none', 5000);
        }
        
        function showError(message) {
            const el = document.getElementById('errorMessage');
            el.textContent = message;
            el.style.display = 'block';
            setTimeout(() => el.style.display = 'none', 5000);
        }
    </script>
</body>
</html>
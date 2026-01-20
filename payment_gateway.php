<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$transactionId = $_GET['txn'] ??0;
$paymentType = $_GET['type'] ?? 'consultation'; // or 'medication'

// Get transaction details if ID provided
$transaction = null;
if ($transactionId) {
    $stmt = $conn->prepare("SELECT * FROM payment_transactions WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $transactionId, $userId);
    $stmt->execute();
    $transaction = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Gateway - MedConnect</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .payment-container { background: white; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 500px; width: 100%; overflow: hidden; }
        .payment-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .payment-header h1 { font-size: 24px; margin-bottom: 10px; }
        .payment-header p { opacity: 0.9; font-size: 14px; }
        .payment-body { padding: 30px; }
        .payment-summary { background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 25px; }
        .summary-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e2e8f0; }
        .summary-row:last-child { border-bottom: none; font-weight: 600; font-size: 18px; }
        .payment-methods { margin-bottom: 25px; }
        .method-title { font-weight: 600; margin-bottom: 15px; color: #1e293b; }
        .method-options { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .method-option { border: 2px solid #e2e8f0; padding: 15px; border-radius: 12px; cursor: pointer; text-align: center; transition: all 0.3s; }
        .method-option:hover { border-color: #667eea; background: #f8f9ff; }
        .method-option.selected { border-color: #667eea; background: #f8f9ff; }
        .method-icon { font-size: 32px; margin-bottom: 8px; }
        .method-name { font-size: 14px; font-weight: 500; color: #475569; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #475569; }
        .form-group input { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .card-row { display: grid; grid-template-columns: 2fr 1fr; gap: 15px; }
        .btn-pay { width: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 16px; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-pay:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4); }
        .secure-badge { text-align: center; margin-top: 20px; color: #64748b; font-size: 13px; }
        .secure-badge svg { vertical-align: middle; margin-right: 5px; }
        .success-animation { display: none; text-align: center; padding: 40px; }
        .success-animation.active { display: block; }
        .success-icon { font-size: 64px; color: #10b981; animation: scaleIn 0.5s ease; }
        @keyframes scaleIn { from { transform: scale(0); } to { transform: scale(1); } }
        .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #667eea; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 20px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <h1>💳 Secure Payment</h1>
            <p>Complete your payment securely</p>
        </div>
        
        <div class="payment-body">
            <div id="paymentForm">
                <div class="payment-summary">
                    <div class="summary-row">
                        <span>Payment Type:</span>
                        <span><?php echo ucfirst($paymentType); ?></span>
                    </div>
                    <?php if ($transaction): ?>
                    <div class="summary-row">
                        <span>Transaction ID:</span>
                        <span><?php echo $transaction['transaction_number']; ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Amount to Pay:</span>
                        <span style="color: #10b981; font-size: 24px;">₹<?php echo number_format($transaction['amount'], 2); ?></span>
                    </div>
                    <?php else: ?>
                    <div class="summary-row">
                        <span>Amount to Pay:</span>
                        <span style="color: #10b981; font-size: 24px;" id="displayAmount">₹0.00</span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="payment-methods">
                    <div class="method-title">Select Payment Method</div>
                    <div class="method-options">
                        <div class="method-option selected" onclick="selectMethod('card')">
                            <div class="method-icon">💳</div>
                            <div class="method-name">Card</div>
                        </div>
                        <div class="method-option" onclick="selectMethod('upi')">
                            <div class="method-icon">📱</div>
                            <div class="method-name">UPI</div>
                        </div>
                        <div class="method-option" onclick="selectMethod('netbanking')">
                            <div class="method-icon">🏦</div>
                            <div class="method-name">Net Banking</div>
                        </div>
                        <div class="method-option" onclick="selectMethod('wallet')">
                            <div class="method-icon">👛</div>
                            <div class="method-name">Wallet</div>
                        </div>
                    </div>
                </div>
                
                <div id="cardDetails">
                    <div class="form-group">
                        <label>Card Number</label>
                        <input type="text" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19">
                    </div>
                    <div class="form-group">
                        <label>Cardholder Name</label>
                        <input type="text" id="cardName" placeholder="JOHN DOE">
                    </div>
                    <div class="card-row">
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="text" id="cardExpiry" placeholder="MM/YY" maxlength="5">
                        </div>
                        <div class="form-group">
                            <label>CVV</label>
                            <input type="password" id="cardCVV" placeholder="123" maxlength="3">
                        </div>
                    </div>
                </div>
                
                <div id="upiDetails" style="display: none;">
                    <div class="form-group">
                        <label>UPI ID</label>
                        <input type="text" id="upiId" placeholder="yourname@upi">
                    </div>
                </div>
                
                <div id="netbankingDetails" style="display: none;">
                    <div class="form-group">
                        <label>Select Bank</label>
                        <select style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px;">
                            <option>State Bank of India</option>
                            <option>HDFC Bank</option>
                            <option>ICICI Bank</option>
                            <option>Axis Bank</option>
                            <option>Other</option>
                        </select>
                    </div>
                </div>
                
                <button class="btn-pay" onclick="processPayment()">
                    Pay ₹<?php echo $transaction ? number_format($transaction['amount'], 2) : '0.00'; ?>
                </button>
                
                <div class="secure-badge">
                    🔒 Your payment is secured with 256-bit encryption
                </div>
            </div>
            
            <div id="processingState" style="display: none; text-align: center; padding: 40px;">
                <div class="spinner"></div>
                <p style="color: #64748b; margin-top: 20px;">Processing your payment...</p>
            </div>
            
            <div id="successState" class="success-animation">
                <div class="success-icon">✅</div>
                <h2 style="color: #1e293b; margin: 20px 0;">Payment Successful!</h2>
                <p style="color: #64748b;">Your transaction has been completed</p>
                <button class="btn-pay" onclick="window.location.href='index.php'" style="margin-top: 20px;">
                    Back to Dashboard
                </button>
            </div>
        </div>
    </div>

    <script>
        let selectedMethod = 'card';
        const transactionId = <?php echo $transactionId ?: 0; ?>;
        
        function selectMethod(method) {
            selectedMethod = method;
            
            // Update UI
            document.querySelectorAll('.method-option').forEach(opt => opt.classList.remove('selected'));
            event.currentTarget.classList.add('selected');
            
            // Show/hide payment details
            document.getElementById('cardDetails').style.display = method === 'card' ? 'block' : 'none';
            document.getElementById('upiDetails').style.display = method === 'upi' ? 'block' : 'none';
            document.getElementById('netbankingDetails').style.display = method === 'netbanking' ? 'block' : 'none';
        }
        
        // Format card number
        document.getElementById('cardNumber')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            let formatted = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formatted;
        });
        
        // Format expiry
        document.getElementById('cardExpiry')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });
        
        async function processPayment() {
            // Show processing
            document.getElementById('paymentForm').style.display = 'none';
            document.getElementById('processingState').style.display = 'block';
            
            // Simulate payment processing (2 seconds)
            setTimeout(async () => {
                try {
                    // Process payment via API
                    const formData = new FormData();
                    formData.append('transaction_id', transactionId);
                    formData.append('gateway_txn_id', 'SIM' + Date.now());
                    formData.append('status', 'success'); // For simulation, always success
                    
                    const response = await fetch('payment_api.php?action=process_payment', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    // Show success
                    document.getElementById('processingState').style.display = 'none';
                    document.getElementById('successState').classList.add('active');
                    
                } catch (error) {
                    alert('Payment failed. Please try again.');
                    document.getElementById('processingState').style.display = 'none';
                    document.getElementById('paymentForm').style.display = 'block';
                }
            }, 2000);
        }
    </script>
</body>
</html>

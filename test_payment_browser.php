<!DOCTYPE html>
<html>
<head>
    <title>Payment Test - No Cache</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .result { margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; }
        .error { background: #f8d7da; border-color: #f5c6cb; }
        button { padding: 10px 20px; font-size: 16px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Payment API Test (No Cache)</h1>
    <p>Testing payment API with cache bypass: <strong><?php echo time(); ?></strong></p>
    
    <button onclick="testPayment()">Test Payment API</button>
    
    <div id="result"></div>

    <script>
    function testPayment() {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<p>Testing...</p>';
        
        const data = {
            transaction_type: 'consultation_fee',
            related_id: 18,
            amount: 200,
            payment_method: 'card'
        };
        
        console.log('Sending payment request...', data);
        
        fetch('payment_api.php?action=initiate_payment&_=' + Date.now(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.text(); // Get raw text first
        })
        .then(text => {
            console.log('Raw response:', text);
            console.log('Response length:', text.length);
            
            // Try to parse JSON
            try {
                const data = JSON.parse(text);
                console.log('Parsed JSON:', data);
                resultDiv.innerHTML = `
                    <div class="result success">
                        <h3>✅ SUCCESS - Valid JSON</h3>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            } catch (e) {
                console.error('JSON parse error:', e);
                resultDiv.innerHTML = `
                    <div class="result error">
                        <h3>❌ JSON Parse Error: ${e.message}</h3>
                        <h4>Raw Response:</h4>
                        <pre>${text}</pre>
                        <h4>First 100 chars (hex):</h4>
                        <pre>${Array.from(text.substring(0, 100)).map(c => c.charCodeAt(0).toString(16).padStart(2, '0')).join(' ')}</pre>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            resultDiv.innerHTML = `
                <div class="result error">
                    <h3>❌ Fetch Error</h3>
                    <pre>${error.message}</pre>
                </div>
            `;
        });
    }
    </script>
</body>
</html>
<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$patientId = $_SESSION['user_id'];

// --- BACKEND API INTEGRATION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Get raw POST input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (isset($data['symptoms']) && !empty($data['symptoms'])) {
        $symptoms = $data['symptoms'];
        
        // Prepare data for Flask API
        $payload = json_encode(['symptoms' => $symptoms]);
        
        // Initialize cURL
        $ch = curl_init('http://127.0.0.1:5000/predict');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ));
        
        // Execute request
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            echo json_encode(['error' => 'API Connection Error: ' . curl_error($ch)]);
        } elseif ($httpCode !== 200) {
            echo json_encode(['error' => 'API Error: HTTP ' . $httpCode]);
        } else {
            // Decode Flask response
            $response = json_decode($result, true);
            
            // Extract ONLY recommended_doctor
            if (isset($response['recommended_doctor'])) {
                echo json_encode(['success' => true, 'recommended_doctor' => $response['recommended_doctor']]);
            } else {
                echo json_encode(['error' => 'Invalid response from AI model']);
            }
        }
        
        curl_close($ch);
    } else {
        echo json_encode(['error' => 'No symptoms provided']);
    }
    exit; // Stop executing to prevent HTML rendering
}
// --- END BACKEND API INTEGRATION ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Symptom Checker - MedConnect</title>
    <style>
        :root {
            --primary: #0d9488;
            --primary-dark: #0f766e;
            --primary-light: #5eead4;
            --primary-gradient: linear-gradient(135deg, #0d9488 0%, #2dd4bf 100%);
            --secondary: #f0fdfa;
            --accent: #f43f5e;
            --bg-body: #f3f4f6;
            --surface: #ffffff;
            --text-dark: #111827;
            --text-muted: #4b5563;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg-body); min-height: 100vh; padding: 20px; color: var(--text-dark); }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 20px; border: 1px solid rgba(0,0,0,0.05); }
        .header { background: var(--primary-gradient); color: white; padding: 40px 30px; text-align: center; }
        .header h1 { font-size: 32px; margin-bottom: 10px; font-weight: 700; letter-spacing: -0.02em; }
        .header p { opacity: 0.9; font-weight: 500; }
        .content { padding: 40px; }
        .step { display: none; }
        .step.active { display: block; }
        .step-indicator { display: flex; justify-content: center; gap: 15px; margin-bottom: 40px; }
        .step-dot { width: 12px; height: 12px; border-radius: 50%; background: #e2e8f0; transition: all 0.3s; }
        .step-dot.active { background: var(--primary); transform: scale(1.5); }
        .form-group { margin-bottom: 30px; }
        .form-group label { display: block; margin-bottom: 12px; font-weight: 600; color: var(--text-dark); font-size: 16px; }
        .form-group textarea { width: 100%; padding: 18px; border: 2px solid #e5e7eb; border-radius: 16px; font-family: inherit; font-size: 15px; resize: vertical; min-height: 140px; transition: border-color 0.2s; background: #f9fafb; }
        .form-group textarea:focus { outline: none; border-color: var(--primary); background: #fff; }
        .form-group input, .form-group select { width: 100%; padding: 15px; border: 2px solid #e5e7eb; border-radius: 16px; font-size: 15px; background: #f9fafb; transition: border-color 0.2s; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--primary); background: #fff; }
        .voice-input { display: flex; gap: 15px; align-items: flex-start; }
        .voice-input textarea { flex: 1; margin-bottom: 0; }
        .voice-btn { background: var(--accent); color: white; border: none; padding: 15px 25px; border-radius: 16px; cursor: pointer; font-size: 16px; font-weight: 600; transition: all 0.3s; white-space: nowrap; box-shadow: 0 4px 12px rgba(244, 63, 94, 0.2); }
        .voice-btn:hover { background: #e11d48; transform: translateY(-2px); }
        .voice-btn.recording { background: #10b981; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); } 50% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); } }
        .severity-options { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        .severity-option { border: 2px solid #e5e7eb; padding: 25px 20px; border-radius: 16px; text-align: center; cursor: pointer; transition: all 0.3s; background: white; }
        .severity-option:hover { border-color: var(--primary-light); background: var(--secondary); transform: translateY(-3px); }
        .severity-option.selected { border-color: var(--primary); background: var(--secondary); box-shadow: 0 8px 20px rgba(13, 148, 136, 0.1); }
        .severity-icon { font-size: 32px; margin-bottom: 8px; }
        .severity-label { font-weight: 600; color: #1e293b; }
        .btn { padding: 14px 30px; border: none; border-radius: 50px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-primary { background: var(--primary-gradient); color: white; box-shadow: 0 4px 15px rgba(13, 148, 136, 0.2); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(13, 148, 136, 0.3); }
        .btn-secondary { background: var(--secondary); color: var(--primary-dark); }
        .btn-secondary:hover { background: #ccfbf1; }
        .actions { display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; }
        
        .analysis-result { background: #f0fdfa; border: 2px solid #0d9488; padding: 30px; border-radius: 20px; margin-top: 30px; text-align: center; }
        .analysis-result h2 { color: #0f766e; margin-bottom: 10px; font-size: 20px; }
        .doctor-display { font-size: 28px; font-weight: 700; color: #111827; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1>🏥 Smart Symptom Checker</h1>
                <p>Tell us what you're experiencing - We'll help you find the right care</p>
            </div>
            
            <div class="content">
                <div class="step-indicator">
                    <div class="step-dot active"></div>
                    <div class="step-dot"></div>
                    <div class="step-dot"></div>
                </div>
                
                <!-- Step 1: Symptoms -->
                <div class="step active" id="step1">
                    <h2 style="margin-bottom: 20px; color: #1e293b;">Describe Your Symptoms</h2>
                    
                    <div class="form-group">
                        <label>What symptoms are you experiencing?</label>
                        <div class="voice-input">
                            <textarea id="symptomsText" placeholder="Type or use voice to describe your symptoms..." required></textarea>
                            <button class="voice-btn" onclick="toggleVoice()">
                                🎤 Voice
                            </button>
                        </div>
                    </div>
                    
                    <div class="actions">
                        <button class="btn btn-primary" onclick="nextStep()">Next →</button>
                    </div>
                </div>
                
                <!-- Step 2: Severity -->
                <div class="step" id="step2">
                    <h2 style="margin-bottom: 20px; color: #1e293b;">How severe are your symptoms?</h2>
                    
                    <div class="severity-options">
                        <div class="severity-option" onclick="selectSeverity('mild')">
                            <div class="severity-icon">😊</div>
                            <div class="severity-label">Mild</div>
                            <p style="font-size: 13px; color: #64748b; margin-top: 5px;">Minor discomfort</p>
                        </div>
                        <div class="severity-option" onclick="selectSeverity('moderate')">
                            <div class="severity-icon">😐</div>
                            <div class="severity-label">Moderate</div>
                            <p style="font-size: 13px; color: #64748b; margin-top: 5px;">Noticeable impact</p>
                        </div>
                        <div class="severity-option" onclick="selectSeverity('severe')">
                            <div class="severity-icon">😣</div>
                            <div class="severity-label">Severe</div>
                            <p style="font-size: 13px; color: #64748b; margin-top: 5px;">Significant pain</p>
                        </div>
                    </div>
                    
                    <div class="actions">
                        <button class="btn btn-secondary" onclick="prevStep()">← Back</button>
                        <button class="btn btn-primary" onclick="nextStep()">Next →</button>
                    </div>
                </div>
                
                <!-- Step 3: Confirmation -->
                <div class="step" id="step3">
                    <h2 style="margin-bottom: 20px; color: #1e293b;">Ready to Analyze?</h2>
                    <p style="margin-bottom: 20px; color: #4b5563;">Click Submit to get your recommended specialist.</p>
                    
                    <div class="actions">
                        <button class="btn btn-secondary" onclick="prevStep()">← Back</button>
                        <button class="btn btn-primary" onclick="submitSymptoms()">Submit & Get Analysis</button>
                    </div>
                </div>
                
                <div id="analysisResult"></div>
            </div>
        </div>
    </div>

    <script>
        let currentStep = 1;
        let selectedSeverity = 'moderate';
        let recognition = null;
        let isRecording = false;

        // Voice recognition setup
        if ('webkitSpeechRecognition' in window) {
            recognition = new webkitSpeechRecognition();
            recognition.continuous = false;
            recognition.interimResults = false;
            
            recognition.onresult = function(event) {
                const transcript = event.results[0][0].transcript;
                const textarea = document.getElementById('symptomsText');
                textarea.value = (textarea.value + ' ' + transcript).trim();
            };
            
            recognition.onend = function() {
                isRecording = false;
                const btn = document.querySelector('.voice-btn');
                btn.classList.remove('recording');
                btn.textContent = '🎤 Voice';
            };
        }

        function toggleVoice() {
            if (!recognition) {
                alert('Voice recognition not supported in this browser');
                return;
            }
            
            const btn = document.querySelector('.voice-btn');
            
            if (isRecording) {
                recognition.stop();
            } else {
                recognition.start();
                btn.classList.add('recording');
                btn.textContent = '⏹️ Stop';
                isRecording = true;
            }
        }

        function selectSeverity(severity) {
            selectedSeverity = severity;
            document.querySelectorAll('.severity-option').forEach(opt => opt.classList.remove('selected'));
            event.currentTarget.classList.add('selected');
        }

        function nextStep() {
            if (currentStep === 1) {
                const symptoms = document.getElementById('symptomsText').value;
                if (!symptoms.trim()) {
                    alert('Please describe your symptoms');
                    return;
                }
            }
            
            document.getElementById('step' + currentStep).classList.remove('active');
            currentStep++;
            document.getElementById('step' + currentStep).classList.add('active');
            
            document.querySelectorAll('.step-dot').forEach((dot, idx) => {
                dot.classList.toggle('active', idx < currentStep);
            });
        }

        function prevStep() {
            document.getElementById('step' + currentStep).classList.remove('active');
            currentStep--;
            document.getElementById('step' + currentStep).classList.add('active');
            
            document.querySelectorAll('.step-dot').forEach((dot, idx) => {
                dot.classList.toggle('active', idx < currentStep);
            });
        }

        async function submitSymptoms() {
            const symptoms = document.getElementById('symptomsText').value;
            const resultDiv = document.getElementById('analysisResult');
            
            // Show loading
            resultDiv.innerHTML = '<p style="text-align:center; color:#6b7280;">Analyzing symptoms with AI...</p>';
            
            try {
                const response = await fetch('symptom_checker.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        symptoms: symptoms
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAnalysis(data.recommended_doctor);
                } else {
                    resultDiv.innerHTML = `<p style="color:red; text-align:center;">Error: ${data.error || 'Unknown error'}</p>`;
                }
            } catch (error) {
                console.error('Error:', error);
                resultDiv.innerHTML = '<p style="color:red; text-align:center;">Failed to connect to analysis server.</p>';
            }
        }

        function showAnalysis(doctor) {
            // Capitalize doctor name
            const doctorFormatted = doctor.charAt(0).toUpperCase() + doctor.slice(1);
            
            const html = `
                <div class="analysis-result">
                    <h2>Recommended Specialist</h2>
                    <div class="doctor-display">${doctorFormatted}</div>
                    <button class="btn btn-secondary" onclick="window.location.href='appointment_booking.php?specialty=${encodeURIComponent(doctorFormatted)}'">
                        Book Appointment
                    </button>
                    <button class="btn btn-secondary" onclick="location.reload()" style="margin-left:10px;">
                        Check Again
                    </button>
                </div>
            `;
            
            // Hide steps and show result
            document.querySelectorAll('.step').forEach(el => el.style.display = 'none');
            document.querySelector('.step-indicator').style.display = 'none';
            document.getElementById('analysisResult').innerHTML = html;
        }
    </script>
</body>
</html>

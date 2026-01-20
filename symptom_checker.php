<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$patientId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Symptom Checker - MedConnect</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); overflow: hidden; margin-bottom: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content { padding: 30px; }
        .step { display: none; }
        .step.active { display: block; }
        .step-indicator { display: flex; justify-content: center; gap: 15px; margin-bottom: 30px; }
        .step-dot { width: 12px; height: 12px; border-radius: 50%; background: #e2e8f0; transition: all 0.3s; }
        .step-dot.active { background: #667eea; transform: scale(1.5); }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 10px; font-weight: 600; color: #1e293b; font-size: 16px; }
        .form-group textarea { width: 100%; padding: 15px; border: 2px solid #e2e8f0; border-radius: 12px; font-family: inherit; font-size: 15px; resize: vertical; min-height: 120px; }
        .form-group textarea:focus { outline: none; border-color: #667eea; }
        .form-group input, .form-group select { width: 100%; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 15px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #667eea; }
        .voice-input { display: flex; gap: 15px; align-items: center; }
        .voice-input textarea { flex: 1; }
        .voice-btn { background: #ef4444; color: white; border: none; padding: 15px 25px; border-radius: 12px; cursor: pointer; font-size: 16px; font-weight: 600; transition: all 0.3s; white-space: nowrap; }
        .voice-btn:hover { background: #dc2626; }
        .voice-btn.recording { background: #10b981; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
        .severity-options { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .severity-option { border: 2px solid #e2e8f0; padding: 20px; border-radius: 12px; text-align: center; cursor: pointer; transition: all 0.3s; }
        .severity-option:hover { border-color: #667eea; background: #f8f9ff; }
        .severity-option.selected { border-color: #667eea; background: #f8f9ff; }
        .severity-icon { font-size: 32px; margin-bottom: 8px; }
        .severity-label { font-weight: 600; color: #1e293b; }
        .file-upload { border: 2px dashed #cbd5e1; padding: 30px; border-radius: 12px; text-align: center; cursor: pointer; transition: all 0.3s; }
        .file-upload:hover { border-color: #667eea; background: #f8f9ff; }
        .file-upload input { display: none; }
        .file-list { margin-top: 15px; }
        .file-item { background: #f8fafc; padding: 12px; border-radius: 8px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; }
        .btn { padding: 14px 30px; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4); }
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .actions { display: flex; gap: 15px; justify-content: flex-end; margin-top: 25px; }
        .suggestions { margin-top: 10px; }
        .suggestion-item { padding: 10px 15px; background: #f8fafc; border-radius: 8px; margin-bottom: 8px; cursor: pointer; transition: all 0.2s; border-left: 3px solid #94a3b8; }
        .suggestion-item:hover { background: #e2e8f0; border-left-color: #667eea; }
        .analysis-result { background: #f0fdfa; border: 2px solid #10b981; padding: 20px; border-radius: 12px; margin-top: 20px; }
        .urgency-badge { display: inline-block; padding: 6px 16px; border-radius: 20px; font-weight: 600; font-size: 14px; }
        .urgency-emergency { background: #fee2e2; color: #991b1b; }
        .urgency-urgent { background: #fed7aa; color: #92400e; }
        .urgency-priority { background: #fef3c7; color: #78350f; }
        .urgency-routine { background: #d1fae5; color: #065f46; }
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
                    <div class="step-dot"></div>
                </div>
                
                <!-- Step 1: Symptoms -->
                <div class="step active" id="step1">
                    <h2 style="margin-bottom: 20px; color: #1e293b;">Describe Your Symptoms</h2>
                    
                    <div class="form-group">
                        <label>What symptoms are you experiencing?</label>
                        <div class="voice-input">
                            <textarea id="symptomsText" placeholder="Type or use voice to describe your symptoms..." oninput="getSuggestions()"></textarea>
                            <button class="voice-btn" onclick="toggleVoice()">
                                🎤 Voice
                            </button>
                        </div>
                        <div class="suggestions" id="suggestions"></div>
                    </div>
                    
                    <div class="form-group">
                        <label>How long have you had these symptoms?</label>
                        <input type="text" id="duration" placeholder="e.g., 2 days, 1 week">
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
                
                <!-- Step 3: Additional Info -->
                <div class="step" id="step3">
                    <h2 style="margin-bottom: 20px; color: #1e293b;">Additional Information</h2>
                    
                    <div class="form-group">
                        <label>Age</label>
                        <input type="number" id="age" placeholder="Your age">
                    </div>
                    
                    <div class="form-group">
                        <label>Gender</label>
                        <select id="gender">
                            <option value="">Select gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Existing Medical Conditions (Optional)</label>
                        <textarea id="conditions" placeholder="Diabetes, hypertension, etc."></textarea>
                    </div>
                    
                    <div class="actions">
                        <button class="btn btn-secondary" onclick="prevStep()">← Back</button>
                        <button class="btn btn-primary" onclick="nextStep()">Next →</button>
                    </div>
                </div>
                
                <!-- Step 4: Upload Files -->
                <div class="step" id="step4">
                    <h2 style="margin-bottom: 20px; color: #1e293b;">Upload Medical Reports (Optional)</h2>
                    
                    <div class="file-upload" onclick="document.getElementById('fileInput').click()">
                        <div style="font-size: 48px; margin-bottom: 10px;">📄</div>
                        <p style="color: #64748b; margin-bottom: 5px;">Click to upload medical images or reports</p>
                        <p style="font-size: 13px; color: #94a3b8;">Supported: JPG, PNG, PDF (Max 5MB)</p>
                        <input type="file" id="fileInput" accept="image/*,.pdf" multiple onchange="handleFiles()">
                    </div>
                    
                    <div class="file-list" id="fileList"></div>
                    
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
        let uploadedFiles = [];
        let recognition = null;
        let isRecording = false;
        let consultationId = null;

        // Voice recognition setup
        if ('webkitSpeechRecognition' in window) {
            recognition = new webkitSpeechRecognition();
            recognition.continuous = true;
            recognition.interimResults = true;
            
            recognition.onresult = function(event) {
                let transcript = '';
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    transcript += event.results[i][0].transcript;
                }
                document.getElementById('symptomsText').value = transcript;
                getSuggestions();
            };
        }

        function toggleVoice() {
            if (!recognition) {
                alert('Voice recognition not supported in this browser');
                return;
            }
            
            const btn = event.currentTarget;
            
            if (isRecording) {
                recognition.stop();
                btn.classList.remove('recording');
                btn.textContent = '🎤 Voice';
                isRecording = false;
            } else {
                recognition.start();
                btn.classList.add('recording');
                btn.textContent = '⏹️ Stop';
                isRecording = true;
            }
        }

        async function getSuggestions() {
            const text = document.getElementById('symptomsText').value;
            if (text.length < 2) {
                document.getElementById('suggestions').innerHTML = '';
                return;
            }
            
            const words = text.split(' ');
            const lastWord = words[words.length - 1];
            
            if (lastWord.length < 2) return;
            
            const response = await fetch(`symptom_intake_api.php?action=get_suggestions&query=${lastWord}`);
            const data = await response.json();
            
            if (data.success && data.suggestions.length > 0) {
                const html = data.suggestions.slice(0, 5).map(s => 
                    `<div class="suggestion-item" onclick="addSuggestion('${s.keyword}')">${s.keyword}</div>`
                ).join('');
                document.getElementById('suggestions').innerHTML = html;
            }
        }

        function addSuggestion(keyword) {
            const textarea = document.getElementById('symptomsText');
            const words = textarea.value.split(' ');
            words[words.length - 1] = keyword;
            textarea.value = words.join(' ') + ' ';
            document.getElementById('suggestions').innerHTML = '';
            textarea.focus();
        }

        function selectSeverity(severity) {
            selectedSeverity = severity;
            document.querySelectorAll('.severity-option').forEach(opt => opt.classList.remove('selected'));
            event.currentTarget.classList.add('selected');
        }

        function handleFiles() {
            const files = event.target.files;
            Array.from(files).forEach(file => {
                if (file.size > 5 * 1024 * 1024) {
                    alert(file.name + ' is too large. Max size is 5MB');
                    return;
                }
                uploadedFiles.push(file);
            });
            
            displayFiles();
        }

        function displayFiles() {
            const list = document.getElementById('fileList');
            list.innerHTML = uploadedFiles.map((file, idx) => `
                <div class="file-item">
                    <span>📄 ${file.name} (${(file.size / 1024).toFixed(1)} KB)</span>
                    <button onclick="removeFile(${idx})" style="background: none; border: none; color: #ef4444; cursor: pointer;">✕</button>
                </div>
            `).join('');
        }

        function removeFile(idx) {
            uploadedFiles.splice(idx, 1);
            displayFiles();
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
            const duration = document.getElementById('duration').value;
            const age = document.getElementById('age').value;
            const gender = document.getElementById('gender').value;
            const conditions = document.getElementById('conditions').value;
            
            try {
                // Submit symptoms
                const response = await fetch('symptom_intake_api.php?action=submit_symptoms', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        symptoms, duration, age, gender,
                        existing_conditions: conditions,
                        severity: selectedSeverity,
                        input_method: isRecording ? 'voice' : 'text'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    consultationId = data.consultation_id;
                    
                    // Upload files if any
                    if (uploadedFiles.length > 0) {
                        for (let file of uploadedFiles) {
                            const formData = new FormData();
                            formData.append('file', file);
                            formData.append('consultation_id', consultationId);
                            
                            await fetch('symptom_intake_api.php?action=upload_attachment', {
                                method: 'POST',
                                body: formData
                            });
                        }
                    }
                    
                    // Show analysis result
                    showAnalysis(data.analysis);
                } else {
                    alert(data.error || 'Failed to submit symptoms');
                }
            } catch (error) {
                alert('Error submitting symptoms');
            }
        }

        function showAnalysis(analysis) {
            const urgencyClass = 'urgency-' + analysis.urgency_level;
            
            document.getElementById('analysisResult').innerHTML = `
                <div class="analysis-result">
                    <h3 style="color: #1e293b; margin-bottom: 15px;">📊 Analysis Complete</h3>
                    <div style="margin-bottom: 15px;">
                        <strong>Recommended Specialty:</strong> ${analysis.primary_specialty}
                    </div>
                    <div style="margin-bottom: 15px;">
                        <strong>Urgency Level:</strong> 
                        <span class="urgency-badge ${urgencyClass}">${analysis.urgency_level.toUpperCase()}</span>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <strong>Confidence:</strong> ${analysis.confidence_level}%
                    </div>
                    <p style="color: #64748b; margin-bottom: 20px;">
                        ${analysis.urgency_level === 'emergency' ? 
                            '⚠️ Your symptoms indicate an emergency. Please seek immediate medical attention or call emergency services.' :
                            'We\'re connecting you with the best available doctor for your symptoms.'}
                    </p>
                    <button class="btn btn-primary" onclick="window.location.href='appointment_booking.php'">
                        View in My Consultations
                    </button>
                </div>
            `;
        }
    </script>
</body>
</html>

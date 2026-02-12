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
        .file-upload { border: 2px dashed #cbd5e1; padding: 30px; border-radius: 16px; text-align: center; cursor: pointer; transition: all 0.3s; background: #fff; }
        .file-upload:hover { border-color: var(--primary); background: var(--secondary); }
        .file-upload input { display: none; }
        .file-list { margin-top: 15px; }
        .file-item { background: #f8fafc; padding: 12px; border-radius: 12px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #e5e7eb; }
        .btn { padding: 14px 30px; border: none; border-radius: 50px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-primary { background: var(--primary-gradient); color: white; box-shadow: 0 4px 15px rgba(13, 148, 136, 0.2); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(13, 148, 136, 0.3); }
        .btn-secondary { background: var(--secondary); color: var(--primary-dark); }
        .btn-secondary:hover { background: #ccfbf1; }
        .actions { display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; }
        .suggestions { margin-top: 10px; }
        .suggestion-item { padding: 12px 18px; background: #fff; border-radius: 12px; margin-bottom: 8px; cursor: pointer; transition: all 0.2s; border: 1px solid #e5e7eb; border-left: 4px solid var(--primary-light); }
        .suggestion-item:hover { background: var(--secondary); border-left-color: var(--primary); }
        .analysis-result { background: #fff; border: 2px solid var(--primary); padding: 30px; border-radius: 20px; margin-top: 30px; box-shadow: var(--shadow-lg); }
        .urgency-badge { display: inline-block; padding: 8px 18px; border-radius: 50px; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; }
        .urgency-emergency { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .urgency-urgent { background: #ffedd5; color: #9a3412; border: 1px solid #fed7aa; }
        .urgency-priority { background: #fef9c3; color: #854d0e; border: 1px solid #fef08a; }
        .urgency-routine { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
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

        async function getDetailedAIAnalysis() {
            const symptoms = document.getElementById('symptomsText').value;
            const age = document.getElementById('age').value;
            const gender = document.getElementById('gender').value;
            const conditions = document.getElementById('conditions').value;
            
            if (!consultationId) {
                alert('Please submit symptoms first');
                return;
            }
            
            try {
                const response = await fetch('symptom_intake_api.php?action=get_ai_analysis', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        symptoms,
                        age,
                        gender,
                        existing_conditions: conditions,
                        consultation_id: consultationId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAIAnalysis(data.analysis);
                } else {
                    alert(data.error || 'Failed to get AI analysis');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error getting AI analysis');
            }
        }

        function showAnalysis(analysis) {
            const urgencyClass = 'urgency-' + analysis.urgency_level;
            
            document.getElementById('analysisResult').innerHTML = `
                <div class="analysis-result">
                    <h3 style="color: #1e293b; margin-bottom: 15px;">📊 Initial Analysis Complete</h3>
                    <div style="margin-bottom: 15px;">
                        <strong>Recommended Specialty:</strong> ${analysis.primary_specialty}
                    </div>
                    <div style="margin-bottom: 15px;">
                        <strong>Urgency Level:</strong> 
                        <span class="urgency-badge ${urgencyClass}">${analysis.urgency_level.toUpperCase()}</span>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <strong>Recommended Doctor:</strong> ${analysis.recommended_doctor}
                    </div>
                    <p style="color: #64748b; margin-bottom: 20px;">
                        ${analysis.urgency_level === 'emergency' ? 
                            '⚠️ Your symptoms indicate an emergency. Please seek immediate medical attention or call emergency services.' :
                            'We\'re connecting you with the best available doctor for your symptoms.'}
                    </p>
                    <button class="btn btn-primary" onclick="getDetailedAIAnalysis()" style="margin-right: 10px;">
                        🤖 Get Detailed AI Analysis
                    </button>
                    <button class="btn btn-secondary" onclick="window.location.href='appointment_booking.php'">
                        View in My Consultations
                    </button>
                </div>
            `;
        }
        
        function showAIAnalysis(analysis) {
            let html = '<div class="analysis-result" style="background: #f8fafc; border: 2px solid #667eea; max-width: 900px;">';
            
            // Title
            html += '<h2 style="color: #667eea; margin-bottom: 20px; text-align: center;">🤖 Advanced AI Medical Analysis</h2>';
            
            // Extracted Symptoms
            html += '<div style="margin-bottom: 25px;">';
            html += '<h3 style="color: #1e293b; margin-bottom: 10px; font-size: 18px;">📋 Extracted Symptoms:</h3>';
            html += '<ul style="list-style: none; padding-left: 0;">';
            analysis.extracted_symptoms.forEach(s => {
                html += `<li style="padding: 8px; background: white; margin-bottom: 5px; border-radius: 6px;">• ${s}</li>`;
            });
            html += '</ul></div>';
            
            // Normalized Medical Terms
            if (analysis.normalized_symptoms.length > 0) {
                html += '<div style="margin-bottom: 25px;">';
                html += '<h3 style="color: #1e293b; margin-bottom: 10px; font-size: 18px;">🔄 Normalized Medical Terms:</h3>';
                html += '<ul style="list-style: none; padding-left: 0;">';
                analysis.normalized_symptoms.forEach(s => {
                    html += `<li style="padding: 8px; background: white; margin-bottom: 5px; border-radius: 6px;">• ${s}</li>`;
                });
                html += '</ul></div>';
            }
            
            // Context
            html += '<div style="margin-bottom: 25px;">';
            html += '<h3 style="color: #1e293b; margin-bottom: 10px; font-size: 18px;">👤 Context Considered:</h3>';
            html += '<ul style="list-style: none; padding-left: 0;">';
            analysis.context_considered.forEach(c => {
                html += `<li style="padding: 8px; background: white; margin-bottom: 5px; border-radius: 6px;">• ${c}</li>`;
            });
            html += '</ul></div>';
            
            // Red Flags (CRITICAL)
            if (analysis.urgent_warning_signs.length > 0 && analysis.urgent_warning_signs[0] !== 'None detected') {
                html += '<div style="margin-bottom: 25px; background: #fee2e2; border: 3px solid #dc2626; padding: 20px; border-radius: 12px;">';
                html += '<h3 style="color: #991b1b; margin-bottom: 15px; font-size: 20px;">🚨 URGENT WARNING SIGNS DETECTED</h3>';
                analysis.urgent_warning_signs.forEach(flag => {
                    html += `
                        <div style="background: white; padding: 15px; margin-bottom: 10px; border-radius: 8px; border-left: 4px solid #dc2626;">
                            <div style="font-weight: bold; color: #991b1b; margin-bottom: 5px;">
                                ${flag.symptom.toUpperCase()} - ${flag.urgency}
                            </div>
                            <div style="margin-bottom: 8px; color: #1e293b;">${flag.warning}</div>
                            <div style="background: #fef3c7; padding: 10px; border-radius: 6px; font-weight: 600; color: #78350f;">
                                ⚡ RECOMMENDED ACTION: ${flag.action}
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
            } else {
                html += '<div style="margin-bottom: 25px; background: #d1fae5; border: 2px solid #10b981; padding: 15px; border-radius: 12px;">';
                html += '<h3 style="color: #065f46; margin-bottom: 5px; font-size: 18px;">✅ No Urgent Warning Signs Detected</h3>';
                html += '<p style="color: #047857; margin: 0;">No immediate emergency symptoms identified.</p>';
                html += '</div>';
            }
            
            // Possible Conditions (Differential Analysis)
            html += '<div style="margin-bottom: 25px;">';
            html += '<h3 style="color: #1e293b; margin-bottom: 15px; font-size: 20px;">🔍 Possible Conditions (Ranked by Likelihood):</h3>';
            
            if (analysis.possible_conditions.length > 0) {
                analysis.possible_conditions.forEach((condition, idx) => {
                    const confidenceColor = condition.confidence >= 70 ? '#10b981' : 
                                           condition.confidence >= 50 ? '#f59e0b' : '#6b7280';
                    const bgColor = idx === 0 ? '#f0f9ff' : 'white';
                    
                    html += `
                        <div style="background: ${bgColor}; border: 2px solid ${confidenceColor}; padding: 20px; margin-bottom: 15px; border-radius: 12px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h4 style="color: #1e293b; margin: 0; font-size: 18px;">
                                    ${idx + 1}. ${condition.condition}
                                </h4>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="background: ${confidenceColor}; color: white; padding: 6px 16px; border-radius: 20px; font-weight: 600; font-size: 14px;">
                                        ${condition.confidence}
                                    </span>
                                    <span style="color: #64748b; font-size: 14px;">${condition.likelihood}</span>
                                </div>
                            </div>
                            <div style="color: #64748b; margin-bottom: 12px; font-size: 14px;">
                                <strong>Specialty:</strong> ${condition.specialty}
                            </div>
                            <div style="color: #475569; margin-bottom: 15px; font-size: 14px;">
                                ${condition.description}
                            </div>
                            <div style="margin-bottom: 10px;">
                                <strong style="color: #10b981;">✓ Supporting Symptoms:</strong>
                                <div style="margin-top: 5px; color: #64748b; font-size: 14px;">
                                    ${condition.supporting_symptoms.join(', ')}
                                </div>
                            </div>
                            ${condition.missing_symptoms.length > 0 ? `
                                <div>
                                    <strong style="color: #f59e0b;">⚠ Missing/Unclear Symptoms:</strong>
                                    <div style="margin-top: 5px; color: #64748b; font-size: 14px;">
                                        ${condition.missing_symptoms.join(', ')}
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    `;
                });
            } else {
                html += '<p style="color: #64748b;">Insufficient information to determine likelihood accurately.</p>';
            }
            html += '</div>';
            
            // Clarifying Questions
            if (analysis.clarifying_questions.length > 0) {
                html += '<div style="margin-bottom: 25px; background: #fef3c7; border: 2px solid #f59e0b; padding: 20px; border-radius: 12px;">';
                html += '<h3 style="color: #78350f; margin-bottom: 15px; font-size: 18px;">❓ Clarifying Questions:</h3>';
                html += '<p style="color: #92400e; margin-bottom: 15px; font-size: 14px;">Answering these questions can help improve diagnostic accuracy:</p>';
                html += '<ol style="color: #1e293b; padding-left: 20px;">';
                analysis.clarifying_questions.forEach(q => {
                    html += `<li style="margin-bottom: 10px; font-size: 15px;">${q}</li>`;
                });
                html += '</ol>';
                html += '</div>';
            }
            
            // Safety Notice
            html += '<div style="background: #fee2e2; border: 2px solid #dc2626; padding: 20px; border-radius: 12px; margin-bottom: 20px;">';
            html += '<h3 style="color: #991b1b; margin-bottom: 10px; font-size: 16px;">⚠️ Important Safety Notice</h3>';
            html += `<p style="color: #7f1d1d; margin: 0; font-size: 14px; line-height: 1.6;">${analysis.safety_notice}</p>`;
            html += '</div>';
            
            // Action Buttons
            html += '<div style="text-align: center; margin-top: 25px;">';
            html += '<button class="btn btn-primary" onclick="window.location.href=\'appointment_booking.php\'" style="margin-right: 10px;">View in My Consultations</button>';
            html += '<button class="btn btn-secondary" onclick="window.print()">Print Analysis</button>';
            html += '</div>';
            
            html += '</div>';
            
            document.getElementById('analysisResult').innerHTML = html;
            
            // Scroll to results
            document.getElementById('analysisResult').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    </script>

</body>
</html>

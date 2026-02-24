/**
 * Enhanced Consultation Chat JavaScript
 * Real-time messaging with AJAX polling, message classification, and workflow guidance
 */

const ConsultationChat = {
    consultationId: null,
    userId: null,
    receiverId: null,
    role: null,
    lastMessageId: 0,
    pollingInterval: null,
    pollingDelay: 3000, // 3 seconds

    /**
     * Initialize the chat system
     */
    init(consultationId, userId, receiverId, role) {
        this.consultationId = consultationId;
        this.userId = userId;
        this.receiverId = receiverId;
        this.role = role;

        console.log('[Chat] Initialized:', { consultationId, userId, receiverId, role });

        // Start polling for new messages
        this.startPolling();

        // Load workflow guidance for doctors
        if (this.role === 'doctor') {
            this.loadWorkflowGuidance();
            this.loadClinicalNotes();
        }

        // Set up event listeners
        this.setupEventListeners();
    },

    /**
     * Set up event listeners
     */
    setupEventListeners() {
        // Send message on Enter key
        const messageInput = document.getElementById('messageInputEnhanced');
        if (messageInput) {
            messageInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
        }

        // Auto-save clinical notes for doctors
        if (this.role === 'doctor') {
            const notesTextarea = document.getElementById('clinicalNotesText');
            if (notesTextarea) {
                let saveTimeout;
                notesTextarea.addEventListener('input', () => {
                    clearTimeout(saveTimeout);
                    saveTimeout = setTimeout(() => {
                        this.saveClinicalNotes();
                    }, 2000); // 2 second debounce
                });
            }
        }
    },

    /**
     * Start AJAX polling for new messages
     */
    startPolling() {
        // Initial fetch
        this.fetchMessages();

        // Set up interval
        this.pollingInterval = setInterval(() => {
            this.fetchMessages();
        }, this.pollingDelay);

        console.log('[Chat] Polling started (every ' + this.pollingDelay + 'ms)');
    },

    /**
     * Stop polling
     */
    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
            console.log('[Chat] Polling stopped');
        }
    },

    /**
     * Fetch new messages via AJAX
     */
    async fetchMessages() {
        try {
            const response = await fetch(
                `consultation_chat_api_enhanced.php?action=fetch_messages&consultation_id=${this.consultationId}&last_id=${this.lastMessageId}`
            );
            const data = await response.json();

            if (data.success && data.messages.length > 0) {
                console.log('[Chat] Fetched', data.messages.length, 'new messages');

                data.messages.forEach(msg => {
                    this.appendMessage(msg);

                    // Update workflow guidance if doctor and message is from patient
                    if (this.role === 'doctor' && msg.sender_id != this.userId) {
                        this.updateWorkflowGuidance(msg);
                    }

                    this.lastMessageId = Math.max(this.lastMessageId, msg.id);
                });

                // Scroll to bottom
                this.scrollToBottom();

                // Reload workflow guidance for doctors
                if (this.role === 'doctor') {
                    this.loadWorkflowGuidance();
                }
            }
        } catch (error) {
            console.error('[Chat] Error fetching messages:', error);
        }
    },

    /**
     * Send a message
     */
    async sendMessage() {
        const input = document.getElementById('messageInputEnhanced');
        if (!input) {
            console.error('[Chat] Message input not found');
            return;
        }

        const content = input.value.trim();
        if (!content) return;

        if (!this.receiverId) {
            alert('Cannot send message: Receiver not identified');
            return;
        }

        // Disable input while sending
        input.disabled = true;

        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('consultation_id', this.consultationId);
        formData.append('content', content);
        formData.append('receiver_id', this.receiverId);
        formData.append('type', 'text');

        try {
            const response = await fetch('consultation_chat_api_enhanced.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                // Clear input
                input.value = '';

                // Optimistic UI update - add message immediately
                const newMsg = {
                    id: data.message_id,
                    sender_id: this.userId,
                    message_content: content,
                    created_at: new Date().toISOString(),
                    is_read: false,
                    message_type: 'text'
                };
                this.appendMessage(newMsg);
                this.lastMessageId = Math.max(this.lastMessageId, data.message_id);

                // Show classification badge for doctors (if patient message was classified)
                if (this.role === 'doctor' && data.classification) {
                    console.log('[Chat] Message classified as:', data.classification, '| Stage:', data.workflow_stage);
                }

                this.scrollToBottom();
            } else {
                alert('Failed to send message: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('[Chat] Error sending message:', error);
            alert('Network error: Could not send message');
        } finally {
            input.disabled = false;
            input.focus();
        }
    },

    /**
     * Append a message to the chat UI
     */
    appendMessage(msg) {
        // Check if message already exists (deduplication)
        if (document.getElementById('msg-' + msg.id)) return;

        const container = document.getElementById('chatMessagesContainer');
        if (!container) return;

        const isSent = (msg.sender_id == this.userId);
        const msgDiv = document.createElement('div');
        msgDiv.className = `message ${isSent ? 'sent' : 'received'}`;
        msgDiv.id = 'msg-' + msg.id;

        const time = new Date(msg.created_at).toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        });

        let classificationBadge = '';
        if (this.role === 'doctor' && !isSent && msg.message_classification) {
            const badgeColors = {
                'non_clinical': '#f59e0b',
                'partial_symptom': '#3b82f6',
                'detailed_symptom': '#10b981',
                'follow_up': '#8b5cf6',
                'general': '#6b7280'
            };
            const badgeColor = badgeColors[msg.message_classification] || '#6b7280';
            const badgeText = msg.message_classification.replace('_', ' ').toUpperCase();

            classificationBadge = `
                <div style="margin-top: 6px;">
                    <span style="background: ${badgeColor}; color: white; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 700; text-transform: uppercase;">
                        ${badgeText}
                    </span>
                </div>
            `;
        }

        const readIndicator = isSent
            ? (msg.is_read
                ? '<i class="fas fa-check-double" style="color: var(--primary)"></i>'
                : '<i class="fas fa-check" style="color: var(--text-muted)"></i>')
            : '';

        msgDiv.innerHTML = `
            <div class="bubble">${this.escapeHtml(msg.message_content)}</div>
            ${classificationBadge}
            <div class="msg-meta">
                <span>${time}</span>
                ${readIndicator}
            </div>
        `;

        container.appendChild(msgDiv);
    },

    /**
     * Scroll chat to bottom
     */
    scrollToBottom() {
        const container = document.getElementById('chatMessagesContainer');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    },

    /**
     * Load workflow guidance (Doctor only)
     */
    async loadWorkflowGuidance() {
        if (this.role !== 'doctor') return;

        try {
            const response = await fetch(
                `consultation_chat_api_enhanced.php?action=get_workflow_guidance&consultation_id=${this.consultationId}`
            );
            const data = await response.json();

            if (data.success && data.guidance) {
                this.displayWorkflowGuidance(data.guidance);
            }
        } catch (error) {
            console.error('[Chat] Error loading workflow guidance:', error);
        }
    },

    /**
     * Display workflow guidance in the panel
     */
    displayWorkflowGuidance(guidance) {
        const panel = document.getElementById('workflowGuidancePanel');
        if (!panel) return;

        const stageColors = {
            'greeting': '#f59e0b',
            'chief_complaint': '#3b82f6',
            'hpi': '#10b981',
            'medical_history': '#8b5cf6',
            'assessment': '#059669',
            'plan': '#0ea5e9',
            'closing': '#6366f1'
        };

        const stageColor = stageColors[guidance.current_stage] || '#6b7280';

        let questionsHtml = '';
        if (guidance.suggested_questions && guidance.suggested_questions.length > 0) {
            questionsHtml = guidance.suggested_questions.map(q =>
                `<li style="cursor: pointer; padding: 4px 0;" onclick="ConsultationChat.useQuestion('${this.escapeHtml(q)}')">${q}</li>`
            ).join('');
        }

        panel.innerHTML = `
            <div style="background: ${stageColor}; color: white; padding: 8px 12px; border-radius: 8px; margin-bottom: 12px;">
                <div style="font-size: 10px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.05em;">Current Stage</div>
                <div style="font-size: 14px; font-weight: 700;">${guidance.stage_description}</div>
            </div>
            
            <div style="background: #f1f5f9; padding: 10px; border-radius: 8px; margin-bottom: 12px; border-left: 3px solid ${stageColor};">
                <div style="font-size: 11px; font-weight: 600; color: #475569; margin-bottom: 6px;">💡 GUIDANCE</div>
                <div style="font-size: 12px; color: #64748b;">${guidance.guidance_text}</div>
            </div>
            
            ${questionsHtml ? `
                <div style="margin-bottom: 12px;">
                    <div style="font-size: 11px; font-weight: 700; color: var(--primary-dark); margin-bottom: 6px;">Suggested Follow-Up Questions:</div>
                    <ul style="margin: 0; padding-left: 18px; font-size: 11px; color: var(--text-muted);">
                        ${questionsHtml}
                    </ul>
                </div>
            ` : ''}
            
            ${guidance.example_response ? `
                <div style="background: #fffbeb; padding: 8px; border-radius: 6px; border-left: 3px solid #f59e0b;">
                    <div style="font-size: 10px; font-weight: 600; color: #92400e; margin-bottom: 4px;">Example Response:</div>
                    <div style="font-style: italic; font-size: 11px; color: #92400e;">"${guidance.example_response}"</div>
                </div>
            ` : ''}
        `;
    },

    /**
     * Use a suggested question (click to insert)
     */
    useQuestion(question) {
        const input = document.getElementById('messageInputEnhanced');
        if (input) {
            input.value = question;
            input.focus();
        }
    },

    /**
     * Update workflow guidance based on new message
     */
    updateWorkflowGuidance(msg) {
        if (msg.ai_suggestion && msg.ai_suggestion.suggested_questions) {
            console.log('[Chat] AI Suggestion:', msg.ai_suggestion.suggested_response);
            console.log('[Chat] Suggested Questions:', msg.ai_suggestion.suggested_questions);
        }
    },

    /**
     * Save clinical notes (Doctor only)
     */
    async saveClinicalNotes() {
        if (this.role !== 'doctor') return;

        const textarea = document.getElementById('clinicalNotesText');
        if (!textarea) return;

        const notes = textarea.value;

        const formData = new FormData();
        formData.append('action', 'save_clinical_notes');
        formData.append('consultation_id', this.consultationId);
        formData.append('private_notes', notes);

        try {
            const response = await fetch('consultation_chat_api_enhanced.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                console.log('[Notes] Auto-saved at:', data.autosaved_at);
                // Show save indicator
                const saveIndicator = document.getElementById('saveIndicator');
                if (saveIndicator) {
                    saveIndicator.textContent = '✓ Saved ' + new Date().toLocaleTimeString();
                    saveIndicator.style.color = '#10b981';
                }
            }
        } catch (error) {
            console.error('[Notes] Error saving:', error);
        }
    },

    /**
     * Load clinical notes (Doctor only)
     */
    async loadClinicalNotes() {
        if (this.role !== 'doctor') return;

        try {
            const response = await fetch(
                `consultation_chat_api_enhanced.php?action=get_clinical_notes&consultation_id=${this.consultationId}`
            );
            const data = await response.json();

            if (data.success && data.notes) {
                const textarea = document.getElementById('clinicalNotesText');
                if (textarea && data.notes.private_notes) {
                    textarea.value = data.notes.private_notes;
                }
            }
        } catch (error) {
            console.error('[Notes] Error loading:', error);
        }
    },

    /**
     * Insert SOAP template
     */
    async insertSOAPTemplate() {
        const textarea = document.getElementById('clinicalNotesText');
        if (!textarea) return;

        const template = `Subjective:
- Patient presents with: 
- History of present illness: 

Objective:
- Vitals: 
- Physical Exam: 

Assessment:
- Primary Diagnosis: 
- Differential Diagnosis: 

Plan:
- Medications: 
- Tests: 
- Follow-up: `;

        if (textarea.value.trim() !== '' && !await confirm('This will append the SOAP template. Continue?')) {
            return;
        }

        textarea.value = textarea.value + (textarea.value ? '\n\n' : '') + template;
        textarea.focus();

        // Trigger auto-save
        textarea.dispatchEvent(new Event('input'));
    },

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Export for global use
window.ConsultationChat = ConsultationChat;

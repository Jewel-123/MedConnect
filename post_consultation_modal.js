/**
 * Post-Consultation Modal JavaScript
 * Handles doctor post-consultation workflow
 */

const PostConsultationModal = {
    consultationId: null,
    patientId: null,

    /**
     * Open post-consultation modal
     */
    open(consultationId, patientId) {
        this.consultationId = consultationId;
        this.patientId = patientId;

        // Load consultation data
        this.loadConsultationData();

        // Show modal
        document.getElementById('postConsultationModal').style.display = 'flex';
    },

    /**
     * Close modal
     */
    close() {
        document.getElementById('postConsultationModal').style.display = 'none';
        this.consultationId = null;
        this.patientId = null;
    },

    /**
     * Load consultation data
     */
    async loadConsultationData() {
        try {
            const response = await fetch(`doctor_api.php?action=get_post_consultation_data&consultation_id=${this.consultationId}`);
            const result = await response.json();

            if (result.status === 'success') {
                const consultation = result.consultation;

                // Populate form
                document.getElementById('postConsultPatientName').textContent = consultation.patient_name;
                document.getElementById('postConsultSymptoms').textContent = consultation.symptoms;

                // Pre-fill if data exists
                if (consultation.diagnosis) {
                    document.getElementById('postConsultDiagnosis').value = consultation.diagnosis;
                }
                if (consultation.medical_advice) {
                    document.getElementById('postConsultAdvice').value = consultation.medical_advice;
                }
                if (consultation.follow_up_scheduled) {
                    document.getElementById('postConsultFollowUpDate').value = consultation.follow_up_scheduled;
                }
                if (consultation.follow_up_notes) {
                    document.getElementById('postConsultFollowUpNotes').value = consultation.follow_up_notes;
                }
            }
        } catch (error) {
            console.error('Error loading consultation data:', error);
            alert('Failed to load consultation data');
        }
    },

    /**
     * Save diagnosis and notes
     */
    async saveDiagnosisNotes() {
        const diagnosis = document.getElementById('postConsultDiagnosis').value.trim();
        const medical_advice = document.getElementById('postConsultAdvice').value.trim();

        if (!diagnosis) {
            alert('Diagnosis is required');
            return false;
        }

        try {
            const response = await fetch('doctor_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'save_diagnosis_notes',
                    consultation_id: this.consultationId,
                    diagnosis: diagnosis,
                    medical_advice: medical_advice
                })
            });

            const result = await response.json();

            if (result.status === 'success') {
                return true;
            } else {
                alert('Error: ' + result.message);
                return false;
            }
        } catch (error) {
            console.error('Error saving diagnosis:', error);
            alert('Failed to save diagnosis');
            return false;
        }
    },

    /**
     * Schedule follow-up
     */
    async scheduleFollowUp() {
        const followUpDate = document.getElementById('postConsultFollowUpDate').value;
        const followUpNotes = document.getElementById('postConsultFollowUpNotes').value.trim();

        if (!followUpDate) {
            return true; // Optional, skip if not provided
        }

        try {
            const response = await fetch('doctor_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'schedule_follow_up',
                    consultation_id: this.consultationId,
                    follow_up_date: followUpDate,
                    follow_up_notes: followUpNotes
                })
            });

            const result = await response.json();

            if (result.status === 'success') {
                return true;
            } else {
                alert('Error scheduling follow-up: ' + result.message);
                return false;
            }
        } catch (error) {
            console.error('Error scheduling follow-up:', error);
            return false;
        }
    },

    /**
     * Open prescription modal
     */
    openPrescriptionModal() {
        this.close();
        openPrescriptionModal(this.consultationId, this.patientId);
    },

    /**
     * Complete consultation workflow
     */
    async completeWorkflow() {
        const btn = document.getElementById('postConsultCompleteBtn');
        btn.disabled = true;
        btn.textContent = 'Processing...';

        try {
            // Step 1: Save diagnosis and notes
            const diagnosisSaved = await this.saveDiagnosisNotes();
            if (!diagnosisSaved) {
                btn.disabled = false;
                btn.textContent = 'Complete Consultation';
                return;
            }

            // Step 2: Schedule follow-up (optional)
            await this.scheduleFollowUp();

            // Step 3: Complete consultation
            const formData = new FormData();
            formData.append('action', 'complete_consultation');
            formData.append('consultation_id', this.consultationId);

            const response = await fetch('doctor_api.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === 'success') {
                alert('Consultation completed successfully!');
                this.close();

                // Reload dashboard
                if (typeof loadConsultations === 'function') {
                    loadConsultations();
                }
                if (typeof loadDashboard === 'function') {
                    loadDashboard();
                }
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Error completing consultation:', error);
            alert('Failed to complete consultation');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Complete Consultation';
        }
    }
};

// Export for global use
window.PostConsultationModal = PostConsultationModal;

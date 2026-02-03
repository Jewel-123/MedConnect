# Post-Consultation Workflow - Integration Guide

## Files Created

### Backend APIs
1. **patient_api.php** - Patient-facing API endpoints
   - `get_consultation_summary` - Fetch consultation details
   - `get_prescription_details` - View prescription
   - `create_medicine_order` - Order medicines
   - `submit_feedback` - Rate doctor
   - `get_active_prescriptions` - List prescriptions

2. **doctor_api.php** (Enhanced)
   - `get_post_consultation_data` - Load consultation for post-consultation form
   - `save_diagnosis_notes` - Save diagnosis and medical advice
   - `schedule_follow_up` - Schedule follow-up appointment
   - `complete_consultation` - Enhanced with prescription auto-send and notifications

### Frontend Components
1. **post_consultation_modal.html** - Doctor post-consultation UI
2. **post_consultation_modal.js** - Doctor workflow logic
3. **prescription_viewer_modal.html** - Patient prescription viewer
4. **prescription_viewer.js** - Patient prescription logic

### Database
1. **update_post_consultation_schema.sql** - Schema migration
2. **run_post_consultation_schema.php** - Migration runner

## Integration Steps

### Step 1: Run Database Migration
```bash
cd c:\xampp\htdocs\medconnect
php run_post_consultation_schema.php
```

Or visit: http://localhost/medconnect/run_post_consultation_schema.php

### Step 2: Include Scripts in Doctor Dashboard

Add to `doctor_dashboard.php` (before closing `</body>` tag):

```html
<!-- Post-Consultation Modal -->
<?php include 'post_consultation_modal.html'; ?>

<!-- Post-Consultation Script -->
<script src="post_consultation_modal.js"></script>
```

### Step 3: Add "End Consultation" Button

In `consultation_room.php`, add button to trigger post-consultation:

```html
<button onclick="endConsultationAndRedirect()" class="btn btn-danger">
    End Consultation
</button>

<script>
function endConsultationAndRedirect() {
    const consultationId = <?php echo $consultation_id; ?>;
    const patientId = <?php echo $patient_id; ?>;
    
    // Redirect to doctor dashboard with post-consultation modal
    window.location.href = `doctor_dashboard.php?action=post_consult&consultation_id=${consultationId}&patient_id=${patientId}`;
}
</script>
```

### Step 4: Auto-Open Modal in Doctor Dashboard

Add to `doctor_dashboard_v3.js` (in initialization):

```javascript
// Check if redirected from consultation room
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('action') === 'post_consult') {
    const consultationId = urlParams.get('consultation_id');
    const patientId = urlParams.get('patient_id');
    
    if (consultationId && patientId) {
        // Open post-consultation modal
        PostConsultationModal.open(consultationId, patientId);
    }
}
```

### Step 5: Include Scripts in Patient Dashboard

Add to patient dashboard HTML (in `script.js` template or separate file):

```html
<!-- Prescription Viewer Modal -->
<?php include 'prescription_viewer_modal.html'; ?>

<!-- Prescription Viewer Script -->
<script src="prescription_viewer.js"></script>
```

### Step 6: Update Patient Dashboard Template

Modify the patient dashboard template in `script.js` to include consultation summary section:

```javascript
// Add to patientDashboard template
<div class="dashboard-section">
    <h3>Recent Consultations</h3>
    <div id="recentConsultations"></div>
</div>
```

Add function to load recent consultations:

```javascript
async function loadRecentConsultations() {
    try {
        const response = await fetch('patient_api.php?action=get_active_prescriptions');
        const result = await response.json();
        
        if (result.status === 'success') {
            const container = document.getElementById('recentConsultations');
            container.innerHTML = '';
            
            result.prescriptions.forEach(prescription => {
                const card = document.createElement('div');
                card.className = 'consultation-card';
                card.innerHTML = `
                    <h4>Dr. ${prescription.doctor_name}</h4>
                    <p>Date: ${new Date(prescription.consultation_date).toLocaleDateString()}</p>
                    <p>Diagnosis: ${prescription.diagnosis}</p>
                    <button onclick="PrescriptionViewer.open(${prescription.id})">
                        View Prescription
                    </button>
                `;
                container.appendChild(card);
            });
        }
    } catch (error) {
        console.error('Error loading consultations:', error);
    }
}
```

## Testing Workflow

### Doctor Flow:
1. Login as doctor
2. Accept consultation request
3. Join consultation room
4. Chat with patient
5. Click "End Consultation"
6. Redirected to dashboard with post-consultation modal
7. Fill diagnosis and medical advice
8. Click "Create Prescription" (optional)
9. Schedule follow-up (optional)
10. Click "Complete Consultation"

### Patient Flow:
1. Login as patient
2. View dashboard
3. See completed consultation in "Recent Consultations"
4. Click "View Prescription"
5. See prescription details
6. Click "Order Medicines"
7. Choose delivery or pickup
8. Confirm order

## API Endpoints Reference

### Doctor API
- `GET doctor_api.php?action=get_post_consultation_data&consultation_id=X`
- `POST doctor_api.php` with `action=save_diagnosis_notes`
- `POST doctor_api.php` with `action=schedule_follow_up`
- `POST doctor_api.php` with `action=complete_consultation`

### Patient API
- `GET patient_api.php?action=get_consultation_summary&consultation_id=X`
- `GET patient_api.php?action=get_prescription_details&prescription_id=X`
- `GET patient_api.php?action=get_active_prescriptions`
- `POST patient_api.php` with `action=create_medicine_order`
- `POST patient_api.php` with `action=submit_feedback`

## Database Changes

New columns added to `consultations` table:
- `diagnosis` (TEXT) - Doctor's diagnosis
- `medical_advice` (TEXT) - Medical advice and instructions
- `follow_up_scheduled` (DATE) - Follow-up date
- `follow_up_notes` (TEXT) - Follow-up notes

New column added to `prescriptions_v2` table:
- `auto_sent_to_pharmacy` (BOOLEAN) - Auto-send flag

## Notifications

The system automatically sends notifications for:
1. **Patient** - When consultation is completed
2. **Patient** - When prescription is ready
3. **Pharmacy** - When prescription is sent
4. **Patient** - Follow-up reminder

## Security Features

- Prescriptions are read-only for patients
- Only assigned doctor can modify consultation
- Pharmacy cannot alter prescription content
- Digital signature timestamp on prescription issuance

# Quick Start Guide - Post-Consultation Workflow

## ✅ Database Setup (COMPLETED)

The database schema has been successfully updated with:
- `consultations.diagnosis`
- `consultations.medical_advice`
- `consultations.follow_up_scheduled`
- `consultations.follow_up_notes`
- `prescriptions_v2.auto_sent_to_pharmacy`

## 📋 Integration Checklist

### 1. Doctor Dashboard Integration

**File: `doctor_dashboard.php`**

Add before `</body>`:
```html
<?php include 'post_consultation_modal.html'; ?>
<script src="post_consultation_modal.js"></script>
```

**File: `doctor_dashboard_v3.js`**

Add to initialization (around line 50):
```javascript
// Auto-open post-consultation modal
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('action') === 'post_consult') {
    const consultationId = urlParams.get('consultation_id');
    const patientId = urlParams.get('patient_id');
    if (consultationId && patientId) {
        setTimeout(() => {
            PostConsultationModal.open(consultationId, patientId);
        }, 500);
    }
}
```

### 2. Consultation Room Integration

**File: `consultation_room.php`**

Add "End Consultation" button (for doctor only):
```php
<?php if ($_SESSION['role'] === 'doctor'): ?>
<button onclick="endConsultation()" class="btn btn-danger" style="margin-top: 20px;">
    End Consultation
</button>

<script>
function endConsultation() {
    if (confirm('Are you sure you want to end this consultation?')) {
        window.location.href = 'doctor_dashboard.php?action=post_consult&consultation_id=<?php echo $consultation_id; ?>&patient_id=<?php echo $patient_id; ?>';
    }
}
</script>
<?php endif; ?>
```

### 3. Patient Dashboard Integration

**File: Patient dashboard HTML (in `script.js` or separate file)**

Add before `</body>`:
```html
<?php include 'prescription_viewer_modal.html'; ?>
<script src="prescription_viewer.js"></script>
```

Add to patient dashboard template:
```javascript
// In patientDashboard template
`<div class="dashboard-section">
    <h3>Recent Consultations</h3>
    <div id="recentConsultations">Loading...</div>
</div>`

// Add this function
async function loadRecentConsultations() {
    try {
        const response = await fetch('patient_api.php?action=get_active_prescriptions');
        const result = await response.json();
        
        const container = document.getElementById('recentConsultations');
        if (result.status === 'success' && result.prescriptions.length > 0) {
            container.innerHTML = result.prescriptions.map(p => `
                <div class="consultation-card" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                    <h4 style="margin: 0 0 10px 0;">Dr. ${p.doctor_name}</h4>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>Date:</strong> ${new Date(p.consultation_date).toLocaleDateString()}
                    </p>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>Diagnosis:</strong> ${p.diagnosis}
                    </p>
                    <button onclick="PrescriptionViewer.open(${p.id})" 
                            style="margin-top: 10px; padding: 8px 16px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        View Prescription
                    </button>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p style="color: #999;">No recent consultations</p>';
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('recentConsultations').innerHTML = '<p style="color: #e74c3c;">Error loading consultations</p>';
    }
}

// Call when dashboard loads
loadRecentConsultations();
```

## 🧪 Testing Steps

### Test 1: Doctor Post-Consultation
1. Login as doctor
2. Navigate to active consultation
3. Click "End Consultation"
4. Verify redirect to dashboard
5. Verify post-consultation modal appears
6. Fill diagnosis: "Common Cold"
7. Fill advice: "Rest and drink fluids"
8. Click "Complete Consultation"
9. Verify success message

### Test 2: Patient View
1. Login as patient (same consultation)
2. View dashboard
3. Verify consultation appears in "Recent Consultations"
4. Click "View Prescription"
5. Verify prescription details display

### Test 3: Medicine Order
1. In prescription viewer, click "Order Medicines"
2. Select "Home Delivery"
3. Enter address and contact
4. Click "Confirm Order"
5. Verify order created

## 📁 Files Created

✅ Backend:
- `patient_api.php`
- `doctor_api.php` (enhanced)
- `update_post_consultation_schema.sql`
- `run_post_consultation_schema.php`

✅ Frontend:
- `post_consultation_modal.html`
- `post_consultation_modal.js`
- `prescription_viewer_modal.html`
- `prescription_viewer.js`

✅ Documentation:
- `POST_CONSULTATION_INTEGRATION.md`
- `QUICK_START.md` (this file)

## 🔗 API Endpoints

### Doctor
- `GET doctor_api.php?action=get_post_consultation_data&consultation_id=X`
- `POST doctor_api.php` → `save_diagnosis_notes`
- `POST doctor_api.php` → `schedule_follow_up`
- `POST doctor_api.php` → `complete_consultation`

### Patient
- `GET patient_api.php?action=get_consultation_summary&consultation_id=X`
- `GET patient_api.php?action=get_prescription_details&prescription_id=X`
- `GET patient_api.php?action=get_active_prescriptions`
- `POST patient_api.php` → `create_medicine_order`
- `POST patient_api.php` → `submit_feedback`

## ⚠️ Important Notes

1. **Database**: Schema update already completed ✅
2. **No Data Loss**: All changes are additive, no existing data affected
3. **Backward Compatible**: Existing features continue to work
4. **Security**: All endpoints check user authentication and role
5. **Notifications**: Automatic notifications sent to patient and pharmacy

## 🎯 Next Steps

1. Integrate the 3 code snippets above into your dashboard files
2. Test the workflow using the testing steps
3. Customize styling to match your design
4. Deploy to production when ready

## 💡 Tips

- The post-consultation modal can be styled by editing `post_consultation_modal.html`
- Prescription viewer styles are in `prescription_viewer_modal.html`
- All modals use the same `.modal` class for consistent behavior
- Check browser console for any JavaScript errors during testing

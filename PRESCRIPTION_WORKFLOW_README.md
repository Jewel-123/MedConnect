# Prescription Workflow - Quick Reference

## 🚀 Quick Start

### 1. Run Database Migration
```bash
# Double-click this file:
run_prescription_migration.bat
```

### 2. Get Central Pharmacy ID
```sql
SELECT id FROM users WHERE email = 'central.pharmacy@medconnect.com';
```

### 3. Test Workflow
1. Doctor: Create & finalize prescription
2. Patient: View in dashboard
3. Pharmacy: Accept → Mark Ready → Complete

---

## 📋 Status Lifecycle

```
DRAFT → FINALIZED → SENT_TO_PHARMACY → IN_PROGRESS → READY → COMPLETED
```

| Status | Who Triggers | Patient Sees | Pharmacy Sees |
|--------|-------------|--------------|---------------|
| DRAFT | Doctor | ❌ | ❌ |
| FINALIZED | Doctor | ✅ | ❌ |
| SENT_TO_PHARMACY | System (Auto) | ✅ | ✅ |
| IN_PROGRESS | Pharmacy | ✅ | ✅ |
| READY | Pharmacy | ✅ | ✅ |
| COMPLETED | Pharmacy | ✅ | ✅ |

---

## 🔌 API Endpoints

### Doctor: Finalize Prescription
```javascript
POST prescription_finalize_api.php
{
    action: 'finalize_prescription',
    prescription_id: 123
}
```

### Patient: Get Dashboard
```javascript
GET patient_dashboard_api.php?action=get_dashboard
```

### Pharmacy: Get Pending
```javascript
GET pharmacy_workflow_api.php?action=get_dashboard&tab=pending
```

### Pharmacy: Update Status
```javascript
POST pharmacy_workflow_api.php
{
    action: 'update_status',
    prescription_id: 123,
    new_status: 'in_progress'
}
```

---

## 📁 Files Created

1. **prescription_workflow_schema.sql** - Database migration
2. **prescription_finalize_api.php** - Doctor finalization endpoint
3. **patient_dashboard_api.php** - Patient dashboard data
4. **pharmacy_workflow_api.php** - Pharmacy workflow & status updates
5. **run_prescription_migration.bat** - Migration helper script

## 📝 Files Updated

1. **prescription_api.php** - Filter draft prescriptions
2. **patient_prescriptions.php** - New status badges & UI

---

## ✅ What This Fixes

### Before
- ❌ Prescriptions stuck in consultation room
- ❌ Patient dashboard empty
- ❌ No pharmacy workflow
- ❌ No status tracking

### After
- ✅ Prescriptions auto-sent to pharmacy
- ✅ Patient sees prescriptions immediately
- ✅ Pharmacy has structured workflow
- ✅ Complete status lifecycle tracking
- ✅ Audit trail with timestamps

---

## 🧪 Quick Test

```sql
-- 1. Check schema updated
SHOW COLUMNS FROM prescriptions_v2 LIKE 'status';

-- 2. Verify Central Pharmacy exists
SELECT * FROM users WHERE role = 'pharmacy';

-- 3. Test finalization (replace IDs)
UPDATE prescriptions_v2 
SET status = 'finalized', finalized_at = NOW() 
WHERE id = 1;

-- 4. Check patient can see it
SELECT * FROM prescriptions_v2 
WHERE patient_id = 2 AND status != 'draft';
```

---

## 🔧 Troubleshooting

**Migration fails?**
- Check MySQL is running
- Verify database name is 'medconnect'
- Run manually: `mysql -u root medconnect < prescription_workflow_schema.sql`

**Patient not seeing prescriptions?**
- Verify status != 'draft'
- Check prescription_api.php has filter
- Clear browser cache

**Pharmacy dashboard empty?**
- Verify pharmacy_id is set
- Check prescriptions have status = 'sent_to_pharmacy'
- Verify pharmacy user logged in

---

## 📞 Central Pharmacy Credentials

**Email:** central.pharmacy@medconnect.com  
**Role:** pharmacy  
**Password:** Set during migration (update in SQL file)

---

For complete documentation, see [walkthrough.md](file:///C:/Users/Neenu/.gemini/antigravity/brain/12d76bb8-4c49-4e7d-98e4-121ed3724560/walkthrough.md)

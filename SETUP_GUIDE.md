# Medical AI Assistant - Setup Guide

## Overview

The Medical AI Assistant has been successfully implemented with all core features:

✅ **Database Schema** - 6 tables for medical knowledge base  
✅ **Medical Knowledge Base** - 40+ conditions, 80+ symptom mappings, 20+ red flags  
✅ **AI Engine** - Complete 9-step workflow implementation  
✅ **API Integration** - New endpoint for AI analysis  
✅ **Frontend Enhancement** - Comprehensive UI for displaying results  

---

## Quick Setup (3 Steps)

### Step 1: Initialize Database Schema

Run this command from your project directory:

```bash
C:\xampp\php\php.exe execute_medical_schema.php
```

**OR** manually execute the SQL file in phpMyAdmin:
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Select the `medconnect` database
3. Go to "Import" tab
4. Choose file: `medical_ai_schema.sql`
5. Click "Go"

**Expected Output:** 6 tables created successfully

---

### Step 2: Seed Medical Knowledge Base

Run this command:

```bash
C:\xampp\php\php.exe seed_medical_knowledge.php
```

**Expected Output:**
```
✓ Inserted 40 medical conditions
✓ Inserted 80+ symptom mappings
✓ Inserted 20 red flag symptoms
✓ Inserted 60+ symptom normalizations
✓ Inserted 20+ clarifying questions
```

---

### Step 3: Test the AI Assistant

1. Open your browser and navigate to: `http://localhost/medconnect/symptom_checker.php`
2. Login as a patient
3. Enter symptoms (try: "severe chest pain and shortness of breath")
4. Complete the symptom intake form
5. Click **"Get Detailed AI Analysis"** button

---

## What You'll See

### 🤖 Advanced AI Medical Analysis

The AI assistant will display:

1. **Extracted Symptoms** - Parsed symptoms with onset, duration, severity
2. **Normalized Medical Terms** - Informal language → medical terminology
3. **Context Considered** - Age, gender, existing conditions
4. **🚨 Urgent Warning Signs** - Red-flag symptoms requiring immediate care
5. **🔍 Possible Conditions** - Ranked by confidence (0-100%)
   - High likelihood (70-100%)
   - Medium likelihood (50-69%)
   - Low likelihood (0-49%)
6. **Supporting/Missing Symptoms** - Explains the match for each condition
7. **❓ Clarifying Questions** - Focused questions to improve accuracy
8. **⚠️ Safety Notice** - Mandatory disclaimer

---

## Files Created

### Core Engine
- `medical_ai_engine.php` - Main AI analysis engine (9-step workflow)
- `medical_ai_schema.sql` - Database schema (6 tables)
- `seed_medical_knowledge.php` - Knowledge base population script

### Integration
- `symptom_intake_api.php` - Enhanced with `get_ai_analysis` endpoint
- `symptom_checker.php` - Updated UI with AI analysis display

### Setup Utilities
- `execute_medical_schema.php` - Schema execution helper
- `SETUP_GUIDE.md` - This file

---

## API Usage

### Endpoint: `symptom_intake_api.php?action=get_ai_analysis`

**Method:** POST

**Request Body:**
```json
{
  "symptoms": "chest pain and shortness of breath",
  "age": 45,
  "gender": "male",
  "existing_conditions": "hypertension",
  "consultation_id": 123
}
```

**Response:**
```json
{
  "success": true,
  "analysis": {
    "extracted_symptoms": [...],
    "normalized_symptoms": [...],
    "context_considered": [...],
    "urgent_warning_signs": [...],
    "possible_conditions": [
      {
        "condition": "Myocardial Infarction (Heart Attack)",
        "confidence": "85%",
        "likelihood": "High likelihood",
        "specialty": "Cardiology",
        "supporting_symptoms": [...],
        "missing_symptoms": [...]
      }
    ],
    "clarifying_questions": [...],
    "safety_notice": "..."
  }
}
```

---

## Testing Scenarios

### Test Case 1: Emergency Symptoms
**Input:** "severe chest pain radiating to left arm, shortness of breath, sweating"

**Expected:**
- 🚨 Red flag warning displayed
- Urgency: EMERGENCY
- Top condition: Myocardial Infarction (>80% confidence)
- Recommendation: Seek immediate medical attention

### Test Case 2: Common Condition
**Input:** "headache and fever for 2 days"

**Expected:**
- Multiple conditions (Viral Infection, Sinusitis, Migraine)
- Confidence: 40-70%
- 2-3 clarifying questions
- No red flags

### Test Case 3: Symptom Normalization
**Input:** "my tummy hurts and I feel like throwing up"

**Expected:**
- Normalized: "abdominal pain, nausea"
- Conditions matched based on medical terms
- Professional terminology in output

---

## Troubleshooting

### Database Connection Error
**Issue:** "Uncaught Error: Call to undefined function mysqli_connect()"

**Solution:**
1. Ensure XAMPP MySQL is running
2. Check `db.php` connection settings
3. Verify database name is `medconnect`

### No Conditions Matched
**Issue:** AI returns empty possible_conditions array

**Solution:**
1. Ensure Step 2 (seed script) was run successfully
2. Check that tables have data:
   ```sql
   SELECT COUNT(*) FROM medical_conditions;
   SELECT COUNT(*) FROM condition_symptoms;
   ```

### Frontend Not Showing AI Button
**Issue:** "Get Detailed AI Analysis" button not appearing

**Solution:**
1. Clear browser cache
2. Ensure `symptom_checker.php` was updated correctly
3. Check browser console for JavaScript errors

---

## Expanding the Knowledge Base

To add more conditions/symptoms:

1. **Add Conditions:**
   ```sql
   INSERT INTO medical_conditions 
   (condition_name, description, specialty, severity_level, ...)
   VALUES ('New Condition', 'Description', 'Specialty', 'moderate', ...);
   ```

2. **Add Symptom Mappings:**
   ```sql
   INSERT INTO condition_symptoms 
   (condition_id, symptom_name, likelihood_score, is_primary_symptom, ...)
   VALUES (condition_id, 'symptom name', 75, 1, ...);
   ```

3. **Add Red Flags:**
   ```sql
   INSERT INTO red_flag_symptoms 
   (symptom_keyword, urgency_level, warning_message, recommended_action, ...)
   VALUES ('symptom', 'emergency', 'Warning...', 'Action...', ...);
   ```

---

## Safety & Compliance

⚠️ **Important Reminders:**

- This system provides **pattern recognition**, NOT medical diagnoses
- All outputs include mandatory safety disclaimers
- Never provides treatment dosages or specific medications
- Recommends professional medical care for all serious symptoms
- Logs all analyses for quality improvement and auditing

**Legal Compliance:**
- Ensure HIPAA/GDPR compliance for your deployment
- Review all disclaimers with legal counsel
- Consider liability insurance for medical AI systems

---

## Support

For issues or questions:
1. Check the implementation plan: `implementation_plan.md`
2. Review the task checklist: `task.md`
3. Examine the AI engine code: `medical_ai_engine.php`

---

**Status:** ✅ Implementation Complete - Ready for Testing

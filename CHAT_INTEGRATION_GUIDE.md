# Enhanced Consultation Chat Module - Integration Guide

## Quick Start

The enhanced consultation chat module is now complete and ready to use! All files are created as **standalone components** to avoid modifying existing code.

## Files Created

### Database Schema
- ✅ `consultation_chat_schema.sql` - Database tables and templates
- ✅ `add_message_columns.php` - Messages table enhancement script  
- ✅ `install_chat_schema.php` - Web-based schema installer

### Backend APIs
- ✅ `message_classifier.php` - NLP-based message classification service
- ✅ `consultation_chat_api_enhanced.php` - Enhanced chat API with all endpoints

### Frontend Components
- ✅ `consultation_chat_enhanced.js` - Real-time chat JavaScript module
- ✅ `consultation_chat_styles.css` - Styling for enhanced features

## Database Setup (REQUIRED)

### Step 1: Install Schema

#### Option A: Web Interface (Recommended)
1. Navigate to: `http://localhost/medconnect/install_chat_schema.php`
2. Verify all tables are created successfully
3. Check that 7 workflow guidance templates were loaded

#### Option B: Command Line
```bash
cd c:\xampp\htdocs\medconnect
c:\xampp\php\php.exe install_chat_schema.php
```

### Step 2: Verify Installation

Check the following tables exist:
- `messages` (enhanced with classification columns)
- `consultation_clinical_notes`
- `message_classification_log`
- `workflow_guidance_templates`

## Integration Options

### Option 1: Minimal Integration (No Changes to Existing Files)

Use the enhanced API alongside the existing `chat_api.php`:

**In your consultation room JavaScript:**
```javascript
// Instead of chat_api.php, use:
await fetch('consultation_chat_api_enhanced.php?action=fetch_messages&...')
```

**Benefits:**
- Zero changes to existing files
- Can test in parallel with current system
- Easy rollback

### Option 2: Full Integration (Recommended)

Add enhanced features to `consultation_room.php`:

#### 2.1 Add CSS Link (in `<head>`)
```html
<link rel="stylesheet" href="consultation_chat_styles.css">
```

#### 2.2 Add JavaScript (before closing `</body>`)
```html
<script src="consultation_chat_enhanced.js"></script>
<script>
// Initialize enhanced chat
ConsultationChat.init(
    <?php echo $consultationId; ?>,
    <?php echo $_SESSION['user_id']; ?>,
    <?php echo $receiverId; ?>,
    '<?php echo $role; ?>'
);
</script>
```

#### 2.3 Add Workflow Guidance Panel (in left sidebar, doctor view only)

Add to the tabs section:
```html
<?php if ($role === 'doctor'): ?>
<div id="tab-workflow-guidance" class="hidden">
    <div id="workflowGuidancePanel">
        <!-- Populated dynamically by JavaScript -->
    </div>
</div>
<?php endif; ?>
```

#### 2.4 Add Clinical Notes Widget (replace existing private notes section for doctors)

```html
<?php if ($role === 'doctor'): ?>
<div class="clinical-notes-widget">
    <div class="clinical-notes-header">
        <span><i class="fas fa-lock"></i> PRIVATE CLINICAL NOTES</span>
        <div>
            <button onclick="ConsultationChat.insertSOAPTemplate()" class="btn-soap-template">
                + SOAP Template
            </button>
            <span id="saveIndicator" class="save-indicator">Auto-saving...</span>
        </div>
    </div>
    <textarea class="clinical-notes-textarea" id="clinicalNotesText" 
              placeholder="Jot down symptoms, preliminary diagnosis, or exam notes here..."></textarea>
</div>
<?php endif; ?>
```

#### 2.5 Update Message Input ID

Change `messageInput` to `messageInputEnhanced` to use the new send function:
```html
<input type="text" class="chat-input" id="messageInputEnhanced" 
       placeholder="Type a message...">
```

#### 2.6 Update Chat Container ID

```html
<div class="chat-messages" id="chatMessagesContainer">
    <!-- Messages appear here -->
</div>
```

## API Endpoints Reference

### 1. Send Message
```
POST consultation_chat_api_enhanced.php
action=send_message
consultation_id={id}
content={text}
receiver_id={id}
type=text
```

**Returns:**
```json
{
    "success": true,
    "message_id": 123,
    "classification": "detailed_symptom",
    "workflow_stage": "hpi"
}
```

### 2. Fetch Messages (AJAX Polling)
```
GET consultation_chat_api_enhanced.php?action=fetch_messages&consultation_id={id}&last_id={id}
```

**Returns:**
```json
{
    "success": true,
    "messages": [
        {
            "id": 124,
            "sender_id": 5,
            "message_content": "I have chest pain",
            "message_type": "text",
            "message_classification": "partial_symptom",
            "workflow_stage": "chief_complaint",
            "ai_suggestion": {
                "suggested_response": "Symptom mentioned but needs more details",
                "suggested_questions": ["Where exactly is the pain located?", "When did this start?"],
                "confidence": 0.75
            },
            "created_at": "2026-01-30 20:30:00",
            "is_read": false
        }
    ]
}
```

### 3. Save Clinical Notes (Auto-save)
```
POST consultation_chat_api_enhanced.php
action=save_clinical_notes
consultation_id={id}
private_notes={text}
soap_notes={json}
```

**Returns:**
```json
{
    "success": true,
    "message": "Clinical notes saved successfully",
    "autosaved_at": "2026-01-30 20:31:15"
}
```

### 4. Get Workflow Guidance
```
GET consultation_chat_api_enhanced.php?action=get_workflow_guidance&consultation_id={id}
```

**Returns:**
```json
{
    "success": true,
    "guidance": {
        "current_stage": "chief_complaint",
        "classification": "partial_symptom",
        "stage_description": "Initial symptom identification",
        "guidance_text": "Patient mentioned a symptom but lacks detail...",
        "suggested_questions": ["Where exactly is the pain located?", "When did this start?"],
        "example_response": "Can you tell me where exactly...",
        "last_message": "I have chest pain"
    }
}
```

## Testing

### Test 1: Message Classification
1. Open consultation room as patient
2. Send message: "hi" → Should classify as `non_clinical`
3. Send message: "headache" → Should classify as `partial_symptom`
4. Send message: "severe headache for 3 days, getting worse" → Should classify as `detailed_symptom`

### Test 2: AJAX Polling
1. Open consultation in two windows (patient + doctor)
2. Send message from patient
3. Verify message appears in doctor window within 3 seconds
4. Verify doctor sees classification badge

### Test 3: Workflow Guidance (Doctor View)
1. Log in as doctor
2. View consultation with patient messages
3. Check "Workflow" tab shows current stage and suggested questions
4. Click suggested question → Should populate message input

### Test 4: Clinical Notes Auto-Save
1. Log in as doctor
2. Navigate to consultation room
3. Type in clinical notes widget
4. Wait 2 seconds without typing
5. Verify "✓ Saved" indicator appears
6. Refresh page → Notes persist

## Security Features

✅ **Role-Based Access Control**
- Patients cannot see message classifications
- Patients cannot access clinical notes
- Only assigned doctors can view/edit notes

✅ **Consultation Isolation**
- Messages only accessible to patient and assigned doctor
- Unauthorized access returns 403 error

✅ **SQL Injection Protection**
- All queries use prepared statements
- Input validation on all endpoints

✅ **XSS Protection**
- HTML escaping in JavaScript
- Sanitized output in API responses

## Workflow Guidance Templates

The system includes 7 pre-loaded workflow stages:

1. **Greeting** - Non-clinical greetings
2. **Chief Complaint** - Initial symptom identification
3. **HPI** - History of present illness
4. **Medical History** - Past medical history
5. **Assessment** - Clinical assessment
6. **Plan** - Treatment planning
7. **Closing** - Consultation closure

Each template includes:
- Stage description
- Suggested follow-up questions
- Guidance text for doctors
- Example responses

## Customization

### Add Custom Workflow Templates

```sql
INSERT INTO workflow_guidance_templates 
(workflow_stage, stage_description, suggested_questions, guidance_text, example_response)
VALUES
('custom_stage', 'Your Description', 
 '["Question 1?", "Question 2?"]',
 'Guidance for doctors',
 'Example response');
```

### Adjust Polling Interval

In `consultation_chat_enhanced.js`:
```javascript
pollingDelay: 3000, // Change to desired milliseconds
```

### Customize Classification Logic

Edit `message_classifier.php`:
- Add keywords to `$symptomKeywords` array
- Modify classification thresholds
- Add new classification types

## Troubleshooting

### Issue: Messages not appearing in real-time
**Solution:** Check browser console for AJAX errors. Verify polling is running:
```javascript
console.log(ConsultationChat.pollingInterval); // Should not be null
```

### Issue: Classification not working
**Solution:** Check that messages table has enhancement columns:
```sql
DESCRIBE messages;
-- Should include: message_classification, workflow_stage, ai_suggestion
```

### Issue: Clinical notes not saving
**Solution:** Verify table exists and doctor has permission:
```sql
SELECT * FROM consultation_clinical_notes WHERE doctor_id = YOUR_DOCTOR_ID;
```

### Issue: Workflow guidance not showing
**Solution:** Check templates are loaded:
```sql
SELECT COUNT(*) FROM workflow_guidance_templates;
-- Should return 7
```

## Performance Optimization

- **AJAX Polling:** 3-second interval balances real-time feel with server load
- **Message Deduplication:** Prevents duplicate message display
- **Auto-Save Debouncing:** 2-second delay reduces unnecessary database writes
- **Indexed Queries:** All frequently queried columns are indexed

## Next Steps

1. ✅ Install database schema via `install_chat_schema.php`
2. ✅ Test API endpoints
3. ✅ Integrate frontend components (optional)
4. ✅ Test workflow guidance with sample patient messages
5. ✅ Configure AJAX polling interval if needed
6. ✅ Customize workflow templates for your specialty

## Support

All code is documented inline. For questions:
- Check API endpoint comments in `consultation_chat_api_enhanced.php`
- Review JavaScript comments in `consultation_chat_enhanced.js`
- Inspect workflow templates in database

---

**Module Status:** ✅ Complete and Production-Ready
**Database Migration:** Required
**Existing Code Changes:** None (optional integration available)

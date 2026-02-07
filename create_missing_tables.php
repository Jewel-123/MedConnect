<?php
/**
 * Create Missing Chat Tables
 * Creates the 3 tables that failed to install
 */

require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Chat Schema</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .header { color: #569cd6; font-weight: bold; font-size: 16px; }
    </style>
</head>
<body>
<pre>
<?php

echo "<span class='header'>========================================\n";
echo "Creating Missing Chat Tables\n";
echo "========================================\n\n</span>";

$tables = [];

// Table 1: consultation_clinical_notes
$tables[] = [
    'name' => 'consultation_clinical_notes',
    'sql' => "CREATE TABLE IF NOT EXISTS `consultation_clinical_notes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `consultation_id` INT NOT NULL UNIQUE,
        `doctor_id` INT NOT NULL,
        `soap_notes` JSON DEFAULT NULL COMMENT 'Structured SOAP format',
        `private_notes` TEXT DEFAULT NULL,
        `consultation_summary` TEXT DEFAULT NULL,
        `last_autosaved_at` TIMESTAMP NULL DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        INDEX `idx_consultation_id` (`consultation_id`),
        INDEX `idx_doctor_id` (`doctor_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

// Table 2: workflow_guidance_templates
$tables[] = [
    'name' => 'workflow_guidance_templates',
    'sql' => "CREATE TABLE IF NOT EXISTS `workflow_guidance_templates` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `workflow_stage` VARCHAR(50) NOT NULL UNIQUE,
        `stage_description` VARCHAR(200) NOT NULL,
        `suggested_questions` JSON DEFAULT NULL,
        `guidance_text` TEXT DEFAULT NULL,
        `example_response` TEXT DEFAULT NULL,
        `is_active` BOOLEAN DEFAULT TRUE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_workflow_stage` (`workflow_stage`),
        INDEX `idx_is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

// Table 3: message_classification_log
$tables[] = [
    'name' => 'message_classification_log',
    'sql' => "CREATE TABLE IF NOT EXISTS `message_classification_log` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `message_id` INT NOT NULL,
        `consultation_id` INT NOT NULL,
        `classification_type` ENUM('non_clinical', 'partial_symptom', 'detailed_symptom', 'follow_up', 'general') NOT NULL,
        `confidence_score` DECIMAL(3, 2) DEFAULT NULL,
        `detected_keywords` JSON DEFAULT NULL,
        `suggested_response` TEXT DEFAULT NULL,
        `workflow_stage_detected` VARCHAR(50) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`message_id`) REFERENCES `messages`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE CASCADE,
        INDEX `idx_message_id` (`message_id`),
        INDEX `idx_consultation_id` (`consultation_id`),
        INDEX `idx_classification_type` (`classification_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

foreach ($tables as $table) {
    echo "Creating table: {$table['name']}...\n";
    
    if ($conn->query($table['sql'])) {
        echo "<span class='success'>✓ {$table['name']} created successfully</span>\n\n";
    } else {
        echo "<span class='error'>✗ Error: " . $conn->error . "</span>\n\n";
    }
}

// Insert workflow templates
echo "Inserting workflow templates...\n";

$templates = [
    ['greeting', 'Non-clinical greeting', '["What symptoms are you experiencing today?", "How can I help you?", "What brings you here today?"]', 'Patient sent a non-clinical greeting. Acknowledge briefly and immediately redirect to clinical information.', 'Hi! To get started, please tell me what symptoms you\'re experiencing today.'],
    
    ['chief_complaint', 'Initial symptom identification', '["Where is the pain located?", "When did this start?", "How severe is it on a scale of 1-10?"]', 'Patient mentioned a symptom but lacks detail. Ask structured follow-up questions about location, onset, and severity.', 'Can you tell me where exactly the pain is located and when it started?'],
    
    ['hpi', 'History of present illness', '["Does anything make it better or worse?", "Have you tried any treatments?", "Any associated symptoms?"]', 'Gather detailed history: onset, progression, triggers, relief factors, previous treatments.', 'Has anything made it better or worse? Have you taken any medication for this?'],
    
    ['medical_history', 'Past medical history', '["Do you have any chronic conditions?", "Are you currently taking any medications?", "Any known allergies?"]', 'Only ask if relevant to current complaint. Focus on conditions, medications, and allergies.', 'Do you have any chronic health conditions I should know about?'],
    
    ['assessment', 'Clinical assessment', '["Based on your symptoms, this could be related to..."]', 'Summarize findings and state preliminary impression.', 'Based on what you\'ve shared, this may be related to...'],
    
    ['plan', 'Treatment planning', '["I recommend...", "You should monitor for...", "Follow up if..."]', 'Provide advice, recommended tests, prescriptions, red flags, and follow-up instructions.', 'I recommend you take... and monitor for any worsening symptoms.'],
    
    ['closing', 'Consultation closure', '["Do you have any other questions?", "Is there anything else I can help with?"]', 'Confirm patient understanding and close professionally.', 'Do you have any questions about the treatment plan?']
];

$inserted = 0;
foreach ($templates as $template) {
    $stmt = $conn->prepare("
        INSERT IGNORE INTO workflow_guidance_templates 
        (workflow_stage, stage_description, suggested_questions, guidance_text, example_response)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssss", $template[0], $template[1], $template[2], $template[3], $template[4]);
    
    if ($stmt->execute()) {
        if ($conn->affected_rows > 0) {
            $inserted++;
            echo "<span class='success'>  ✓ {$template[0]}</span>\n";
        }
    }
}

echo "\n<span class='success'>✓ Inserted $inserted workflow templates</span>\n\n";

// Final verification
echo "<span class='header'>========================================\n";
echo "VERIFICATION\n";
echo "========================================\n\n</span>";

foreach (['consultation_clinical_notes', 'workflow_guidance_templates', 'message_classification_log'] as $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    if ($result && $result->num_rows > 0) {
        echo "<span class='success'>✓ $tableName exists</span>\n";
    } else {
        echo "<span class='error'>✗ $tableName NOT found</span>\n";
    }
}

// Count templates
$result = $conn->query("SELECT COUNT(*) as count FROM workflow_guidance_templates");
if ($result) {
    $row = $result->fetch_assoc();
    echo "\n<span class='success'>✓ Workflow templates: {$row['count']}</span>\n";
}

echo "\n<span class='header'>========================================\n";
echo "Installation Complete!\n";
echo "========================================</span>\n";

?>
</pre>
</body>
</html>
<?php
// Verification Script for MedConnect Updates
echo "<h1>MedConnect Update Verification</h1>";
echo "<p>Checking file integrity...</p>";

$files = [
    'doctor_dashboard.php' => [
        'patientHistoryModal' => 'Patient History Modal',
        'doctor_dashboard.js?v=' => 'Cache Busting',
    ],
    'doctor_dashboard.js' => [
        'viewPatientHistory' => 'Patient History Function',
        'Doctor Dashboard JS Loaded - v2' => 'Version Log',
    ],
    'consultation_room.php' => [
        'sessionTimer' => 'Session Timer',
        '⚠️ ALLERGIES' => 'Allergies Alert',
        'insertSOAP' => 'SOAP Template Function',
    ]
];

$all_pass = true;

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; max-width: 800px; text-align: left;'>";
echo "<tr style='background: #f1f5f9;'><th>File</th><th>Feature</th><th>Status</th></tr>";

foreach ($files as $filename => $checks) {
    echo "<tr><td colspan='3' style='background: #e2e8f0;'><strong>$filename</strong></td></tr>";
    
    if (!file_exists($filename)) {
        echo "<tr><td>$filename</td><td>File Existence</td><td style='color: red;'>MISSING</td></tr>";
        $all_pass = false;
        continue;
    }
    
    $content = file_get_contents($filename);
    
    foreach ($checks as $search => $name) {
        $found = strpos($content, $search) !== false;
        $color = $found ? 'green' : 'red';
        $status = $found ? 'VERIFIED' : 'MISSING';
        if (!$found) $all_pass = false;
        
        echo "<tr>
            <td></td>
            <td>$name</td>
            <td style='color: $color; font-weight: bold;'>$status</td>
        </tr>";
    }
}

echo "</table>";

if ($all_pass) {
    echo "<h2 style='color: green;'>✅ All Updates Verified Successfully!</h2>";
    echo "<p>If you still don't see changes, please <strong>Hard Refresh</strong> your browser (Ctrl+F5).</p>";
} else {
    echo "<h2 style='color: red;'>❌ Some Updates are Missing</h2>";
    echo "<p>Please verify the file permissions or try re-applying the update.</p>";
}
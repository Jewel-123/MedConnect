<?php
/**
 * Test Message Classification
 * Tests the message classifier with various input samples
 */

require_once 'message_classifier.php';

header('Content-Type: text/plain; charset=utf-8');

echo "========================================\n";
echo "Message Classification Test Suite\n";
echo "========================================\n\n";

$classifier = new MessageClassifier();

// Test cases
$testCases = [
    // Non-clinical messages
    ['message' => 'hi', 'expected' => 'non_clinical'],
    ['message' => 'hello doctor', 'expected' => 'non_clinical'],
    ['message' => 'thanks', 'expected' => 'non_clinical'],
    ['message' => 'ok', 'expected' => 'non_clinical'],
    
    // Partial symptom messages
    ['message' => 'headache', 'expected' => 'partial_symptom'],
    ['message' => 'I have pain', 'expected' => 'partial_symptom'],
    ['message' => 'fever', 'expected' => 'partial_symptom'],
    ['message' => 'stomach ache', 'expected' => 'partial_symptom'],
    
    // Detailed symptom messages
    ['message' => 'I have severe headache for 3 days getting worse', 'expected' => 'detailed_symptom'],
    ['message' => 'chest pain since yesterday, moderate intensity, constant', 'expected' => 'detailed_symptom'],
    ['message' => 'cough and fever for 5 days, getting worse at night', 'expected' => 'detailed_symptom'],
    ['message' => 'back pain for 2 weeks, severe when bending', 'expected' => 'detailed_symptom'],
    
    // General messages
    ['message' => 'I need help', 'expected' => 'general'],
    ['message' => 'not feeling well', 'expected' => 'general'],
];

$passCount = 0;
$failCount = 0;

foreach ($testCases as $index => $test) {
    $result = $classifier->classify($test['message']);
    $passed = ($result['classification'] === $test['expected']);
    
    if ($passed) {
        $passCount++;
        $status = "✓ PASS";
    } else {
        $failCount++;
        $status = "✗ FAIL";
    }
    
    echo sprintf("Test #%d: %s\n", $index + 1, $status);
    echo "  Message: \"{$test['message']}\"\n";
    echo "  Expected: {$test['expected']}\n";
    echo "  Got: {$result['classification']}\n";
    echo "  Workflow Stage: " . ($result['workflow_stage'] ?? 'none') . "\n";
    echo "  Confidence: " . number_format($result['confidence'], 2) . "\n";
    
    if (!empty($result['detected_keywords'])) {
        echo "  Keywords: " . implode(', ', $result['detected_keywords']) . "\n";
    }
    
    if (!empty($result['suggested_questions'])) {
        echo "  Suggested Questions:\n";
        foreach ($result['suggested_questions'] as $question) {
            echo "    - $question\n";
        }
    }
    
    echo "\n";
}

echo "========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Total Tests: " . count($testCases) . "\n";
echo "Passed: $passCount ✓\n";
echo "Failed: $failCount ✗\n";
echo "Success Rate: " . number_format(($passCount / count($testCases)) * 100, 1) . "%\n";
echo "========================================\n";
?>

<?php
/**
 * Verify Pharmacy Billing System Setup
 * Tests all components and displays comprehensive status
 */

require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>Pharmacy Billing Verification</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
h1 { color: #1e56a0; border-bottom: 3px solid #0d9488; padding-bottom: 10px; }
h2 { color: #0d9488; margin-top: 30px; }
.success { color: #059669; }
.error { color: #dc2626; }
.warning { color: #f59e0b; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #f1f5f9; font-weight: 600; }
.status-box { padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid; }
.status-box.success { background: #ecfdf5; border-color: #059669; }
.status-box.error { background: #fef2f2; border-color: #dc2626; }
.status-box.warning { background: #fffbeb; border-color: #f59e0b; }
</style></head><body><div class='container'>";

echo "<h1>🧪 Pharmacy Billing System Verification</h1>";

$checks = [
    'pass' => 0,
    'fail' => 0,
    'warnings' => 0
];

// ================================================================
// 1. CHECK DATABASE SCHEMA
// ================================================================
echo "<h2>1. Database Schema Verification</h2>";

// Check medicines table
echo "<h3>Medicines Table</h3>";
$result = $conn->query("DESCRIBE medicines");
if ($result && $result->num_rows > 0) {
    echo "<div class='status-box success'>✅ Medicines table exists</div>";
    echo "<table><tr><th>Column</th><th>Type</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
    }
    echo "</table>";
    
    // Check medicine count
    $count = $conn->query("SELECT COUNT(*) as cnt FROM medicines")->fetch_assoc()['cnt'];
    echo "<div class='status-box success'>✅ Total medicines: <strong>$count</strong></div>";
    $checks['pass']++;
} else {
    echo "<div class='status-box error'>❌ Medicines table not found</div>";
    $checks['fail']++;
}

// Check prescription_items_v2 for medicine_id
echo "<h3>Prescription Items Table</h3>";
$result = $conn->query("SHOW COLUMNS FROM prescription_items_v2 LIKE 'medicine_id'");
if ($result && $result->num_rows > 0) {
    echo "<div class='status-box success'>✅ medicine_id column exists in prescription_items_v2</div>";
    
    // Check how many items are linked
    $linked = $conn->query("SELECT COUNT(*) as cnt FROM prescription_items_v2 WHERE medicine_id IS NOT NULL")->fetch_assoc()['cnt'];
    $total = $conn->query("SELECT COUNT(*) as cnt FROM prescription_items_v2")->fetch_assoc()['cnt'];
    
    if ($linked == $total && $total > 0) {
        echo "<div class='status-box success'>✅ All $total prescription items linked to medicines</div>";
        $checks['pass']++;
    } else if ($linked == 0 && $total > 0) {
        echo "<div class='status-box warning'>⚠️ $total prescription items exist but none are linked to medicines</div>";
        $checks['warnings']++;
    } else {
        echo "<div class='status-box success'>✅ $linked / $total prescription items linked to medicines</div>";
        $checks['pass']++;
    }
} else {
    echo "<div class='status-box error'>❌ medicine_id column not found</div>";
    $checks['fail']++;
}

// Check prescriptions_v2 billing columns
echo "<h3>Prescriptions Table</h3>";
$billing_cols = ['total_amount', 'ordered_at', 'verified_at', 'bill_generated_at', 'dispensed_at', 'pharmacist_id'];
$missing = [];
foreach ($billing_cols as $col) {
    $result = $conn->query("SHOW COLUMNS FROM prescriptions_v2 LIKE '$col'");
    if (!$result || $result->num_rows == 0) {
        $missing[] = $col;
    }
}

if (empty($missing)) {
    echo "<div class='status-box success'>✅ All billing columns exist in prescriptions_v2</div>";
    $checks['pass']++;
} else {
    echo "<div class='status-box error'>❌ Missing columns: " . implode(', ', $missing) . "</div>";
    $checks['fail']++;
}

// Check payments table
echo "<h3>Payments Table</h3>";
$result = $conn->query("DESCRIBE payments");
if ($result && $result->num_rows > 0) {
    echo "<div class='status-box success'>✅ Payments table exists</div>";
    $checks['pass']++;
} else {
    echo "<div class='status-box error'>❌ Payments table not found</div>";
    $checks['fail']++;
}

// ================================================================
// 2. CHECK SAMPLE DATA
// ================================================================
echo "<h2>2. Sample Data Verification</h2>";

echo "<h3>Medicine Categories</h3>";
$cats = $conn->query("SELECT category, COUNT(*) as count, CONCAT('₹', FORMAT(AVG(price), 2)) as avg_price 
                      FROM medicines GROUP BY category ORDER BY count DESC LIMIT 10");
if ($cats && $cats->num_rows > 0) {
    echo "<table><tr><th>Category</th><th>Count</th><th>Avg Price</th></tr>";
    while ($row = $cats->fetch_assoc()) {
        echo "<tr><td>{$row['category']}</td><td>{$row['count']}</td><td>{$row['avg_price']}</td></tr>";
    }
    echo "</table>";
}

echo "<h3>Prescription Status Distribution</h3>";
$statuses = $conn->query("SELECT status, COUNT(*) as count FROM prescriptions_v2 GROUP BY status");
if ($statuses && $statuses->num_rows > 0) {
    echo "<table><tr><th>Status</th><th>Count</th></tr>";
    while ($row = $statuses->fetch_assoc()) {
        echo "<tr><td>{$row['status']}</td><td>{$row['count']}</td></tr>";
    }
    echo "</table>";
}

// ================================================================
// 3. TEST PRICING JOINS
// ================================================================
echo "<h2>3. Pricing JOIN Test</h2>";

$test_query = "
    SELECT pi.medicine_name, pi.quantity, m.price, m.stock,
           (m.price * pi.quantity) as line_total
    FROM prescription_items_v2 pi
    LEFT JOIN medicines m ON pi.medicine_id = m.id
    LIMIT 5
";

$test_result = $conn->query($test_query);
if ($test_result && $test_result->num_rows > 0) {
    echo "<div class='status-box success'>✅ JOIN query working correctly</div>";
    echo "<table><tr><th>Medicine</th><th>Quantity</th><th>Price</th><th>Stock</th><th>Line Total</th></tr>";
    while ($row = $test_result->fetch_assoc()) {
        echo "<tr>
            <td>{$row['medicine_name']}</td>
            <td>{$row['quantity']}</td>
            <td>₹" . number_format($row['price'], 2) . "</td>
            <td>{$row['stock']}</td>
            <td>₹" . number_format($row['line_total'], 2) . "</td>
        </tr>";
    }
    echo "</table>";
    $checks['pass']++;
} else {
    echo "<div class='status-box error'>❌ JOIN query failed or returned no results</div>";
    $checks['fail']++;
}

// ================================================================
// SUMMARY
// ================================================================
echo "<h2>📊 Verification Summary</h2>";
echo "<div style='display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin: 20px 0;'>";
echo "<div class='status-box success'><strong>✅ Passed:</strong> {$checks['pass']}</div>";
echo "<div class='status-box error'><strong>❌ Failed:</strong> {$checks['fail']}</div>";
echo "<div class='status-box warning'><strong>⚠️ Warnings:</strong> {$checks['warnings']}</div>";
echo "</div>";

if ($checks['fail'] == 0) {
    echo "<div class='status-box success'>";
    echo "<h3 style='margin:0; color:#059669;'>🎉 All Critical Checks Passed!</h3>";
    echo "<p>Your pharmacy billing system is properly configured and ready for testing.</p>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>Test the complete workflow from order to dispensing</li>";
    echo "<li>Verify stock deduction works correctly</li>";
    echo "<li>Test payment integration</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='status-box error'>";
    echo "<h3 style='margin:0; color:#dc2626;'>⚠️ Action Required</h3>";
    echo "<p>Some components are missing or misconfigured. Please review the failed checks above and run the setup scripts again.</p>";
    echo "</div>";
}

echo "</div></body></html>";
?>

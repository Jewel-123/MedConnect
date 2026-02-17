<?php
require_once 'db.php';
// Delete the test consultation created
$stmt = $conn->prepare("DELETE FROM consultations WHERE symptoms = 'Heart palpitations' AND matched_specialty = 'Cardiologist'");
$stmt->execute();
echo "Test data cleaned up.";
?>

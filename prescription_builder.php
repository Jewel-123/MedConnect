<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    die("Unauthorized access");
}

$consultationId = $_GET['id'] ?? null;
if (!$consultationId) die("Consultation ID required");

// Fetch consultation for patient info
$stmt = $conn->prepare("SELECT c.*, u.full_name FROM consultations c JOIN users u ON c.patient_id = u.id WHERE c.id = ?");
$stmt->bind_param('i', $consultationId);
$stmt->execute();
$consultation = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Prescription Builder | MedConnect</title>
    <style>
        body { font-family: sans-serif; padding: 30px; background: #f1f5f9; }
        .card { background: white; padding: 25px; border-radius: 12px; max-width: 600px; margin: auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        button { padding: 12px 24px; background: #0ea5e9; color: white; border: none; border-radius: 6px; cursor: pointer; width: 100%; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Issue Prescription</h2>
        <p>Patient: <strong><?php echo htmlspecialchars($consultation['full_name']); ?></strong></p>
        <hr>
        <form id="prescriptionForm">
            <input type="hidden" name="consultation_id" value="<?php echo $consultationId; ?>">
            <div class="form-group">
                <label>Diagnosis</label>
                <textarea name="diagnosis" rows="3" required placeholder="Main diagnosis..."></textarea>
            </div>
            <div id="medications">
                <div class="medication-item">
                    <label>Medication 1</label>
                    <input type="text" name="meds[]" placeholder="Medicine name" required>
                    <div style="display: flex; gap: 10px; margin-top: 5px;">
                        <input type="text" name="dosage[]" placeholder="Dosage (e.g. 500mg)" required>
                        <input type="text" name="freq[]" placeholder="Frequency (e.g. 3 times daily)" required>
                    </div>
                </div>
            </div>
            <div class="form-group" style="margin-top: 20px;">
                <label>Notes for Pharmacy</label>
                <textarea name="pharmacy_notes" rows="2"></textarea>
            </div>
            <button type="button" onclick="submitPrescription()">Sign & Finalize Prescription</button>
        </form>
    </div>

    <script>
        async function submitPrescription() {
            const formData = new FormData(document.getElementById('prescriptionForm'));
            const data = Object.fromEntries(formData.entries());
            data.medications = formData.getAll('meds[]');
            data.dosages = formData.getAll('dosage[]');
            data.frequencies = formData.getAll('freq[]');

            try {
                const response = await fetch('doctor_api.php?action=send_prescription', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    alert("Prescription issued and routed to pharmacy!");
                    window.close();
                } else {
                    alert(result.error || "Failed to issue prescription");
                }
            } catch (e) {
                alert("Error sending prescription");
            }
        }
    </script>
</body>
</html>

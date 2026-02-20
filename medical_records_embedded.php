<?php
// medical_records_embedded.php — Same as medical_records.php but without sidebar/header
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) exit;

$patient_id = $_GET['patient_id'] ?? null;
if (!$patient_id) exit;

$role = $_SESSION['role'];
$activeTab = $_GET['tab'] ?? 'records';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root { --primary: #0d9488; --bg: #ffffff; --border: #e2e8f0; --text-main: #0f172a; --text-muted: #64748b; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); margin: 0; color: var(--text-main); }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 0.75rem; background: #f8fafc; text-align: left; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); border-bottom: 1px solid var(--border); }
        td { padding: 0.75rem; border-bottom: 1px solid var(--border); font-size: 0.85rem; }
        .btn { padding: 0.3rem 0.6rem; border-radius: 6px; border: 1px solid var(--border); cursor: pointer; color: var(--text-muted); background: white; font-size: 0.8rem; }
        .btn:hover { border-color: var(--primary); color: var(--primary); }
        .status-badge { padding: 0.2rem 0.5rem; border-radius: 99px; font-size: 0.65rem; font-weight: 700; color: white; }
        .bg-pending { background: #f59e0b; }
        .bg-sent { background: #10b981; }
        .bg-cancelled { background: #64748b; }
    </style>
</head>
<body>
    <div id="content">Loading...</div>

    <script>
        document.addEventListener('DOMContentLoaded', loadData);

        async function loadData() {
            const tableDiv = document.getElementById('content');
            try {
                const endpoint = "<?php echo $activeTab; ?>" === 'records' ? 'medical_records_api.php' : 'reminders_api.php';
                const res = await fetch(`${endpoint}?action=list&patient_id=<?php echo $patient_id; ?>`);
                const data = await res.json();
                
                if (data.status === 'success') {
                    if ("<?php echo $activeTab; ?>" === 'records') renderRecords(data.data);
                    else renderReminders(data.data);
                } else {
                    tableDiv.innerText = data.message;
                }
            } catch (e) {
                tableDiv.innerText = "Error loading data.";
            }
        }

        function renderRecords(records) {
            const div = document.getElementById('content');
            if (records.length === 0) { div.innerHTML = '<p style="text-align:center; padding: 2rem; color: #64748b;">No history available.</p>'; return; }
            let html = '<table><thead><tr><th>Date</th><th>Diagnosis</th><th>Medications</th><th>Link</th></tr></thead><tbody>';
            records.forEach(r => {
                html += `<tr>
                    <td>${r.visit_date}</td>
                    <td>${r.diagnosis}</td>
                    <td>${r.medications || '-'}</td>
                    <td><button class="btn" onclick="window.parent.location.href='medical_records.php?patient_id=<?php echo $patient_id; ?>'">View</button></td>
                </tr>`;
            });
            div.innerHTML = html + '</tbody></table>';
        }

        function renderReminders(items) {
            const div = document.getElementById('content');
            if (items.length === 0) { div.innerHTML = '<p style="text-align:center; padding: 2rem; color: #64748b;">No reminders.</p>'; return; }
            let html = '<table><thead><tr><th>Date</th><th>Type</th><th>Status</th></tr></thead><tbody>';
            items.forEach(r => {
                html += `<tr>
                    <td>${r.reminder_date}</td>
                    <td>${r.reminder_type.replace('_',' ')}</td>
                    <td><span class="status-badge bg-${r.status}">${r.status.toUpperCase()}</span></td>
                </tr>`;
            });
            div.innerHTML = html + '</tbody></table>';
        }
    </script>
</body>
</html>

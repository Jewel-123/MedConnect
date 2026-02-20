<?php
session_start();
require_once 'db.php';

// Security: User must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'patient';
$user_name = $_SESSION['admin_name'] ?? $_SESSION['full_name'] ?? 'User';

// Determine dashboard Home link
$home_link = 'patient_dashboard.php';
if ($role === 'admin') $home_link = 'admin_dashboard.php';
if ($role === 'doctor') $home_link = 'doctor_dashboard.php';
if ($role === 'pharmacy') $home_link = 'pharmacy_dashboard.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records | MedConnect</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <style>
        :root {
            --primary: #0d9488;
            --primary-dark: #0f766e;
            --primary-light: #ccfbf1;
            --secondary: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --surface: #ffffff;
            --bg: #f8fafc;
            --border: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --sidebar-w: 260px;
            --header-h: 64px;
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }

        /* Sidebar */
        aside { width: var(--sidebar-w); background: var(--surface); border-right: 1px solid var(--border); display: flex; flex-direction: column; overflow-y: auto; }
        .logo { height: var(--header-h); display: flex; align-items: center; padding: 0 1.5rem; gap: 0.75rem; color: var(--primary); font-weight: 700; border-bottom: 1px solid var(--border); }
        .nav-menu { padding: 1.5rem 1rem; flex: 1; }
        .nav-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; color: var(--secondary); text-decoration: none; border-radius: var(--radius-md); font-weight: 500; margin-bottom: 0.25rem; }
        .nav-item:hover, .nav-item.active { background: var(--primary-light); color: var(--primary-dark); }
        .nav-item i { font-size: 1.25rem; }

        /* Content Area */
        .wrapper { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        header { height: var(--header-h); background: var(--surface); border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; padding: 0 2rem; }
        main { flex: 1; padding: 2rem; overflow-y: auto; }
        .container { max-width: 1200px; margin: 0 auto; }

        /* UI Elements */
        .card { background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); box-shadow: var(--shadow); overflow: hidden; margin-bottom: 2rem; }
        .card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .card-title { font-size: 1.25rem; font-weight: 700; color: var(--text-main); }
        
        table { width: 100%; border-collapse: collapse; }
        th { padding: 1rem; background: #f1f5f9; text-align: left; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); }
        td { padding: 1rem; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        tr:hover { background: #f8fafc; }

        .btn { padding: 0.6rem 1.2rem; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; transition: 0.2s; font-size: 0.9rem; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text-muted); }
        .btn-outline:hover { background: #f1f5f9; }
        .btn-sm { padding: 0.4rem 0.8rem; font-size: 0.8rem; }

        /* Modal */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; padding: 1rem; }
        .modal { background: white; border-radius: var(--radius-lg); width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .modal-header { padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 1.5rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.5rem; color: var(--text-muted); }
        .form-input, .form-textarea, .form-select { width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 0.95rem; }
        .form-textarea { height: 100px; resize: vertical; }

        .tabs { display: flex; border-bottom: 1px solid var(--border); margin-bottom: 1.5rem; }
        .tab { padding: 0.75rem 1.5rem; cursor: pointer; border-bottom: 2px solid transparent; color: var(--text-muted); font-weight: 600; }
        .tab.active { border-color: var(--primary); color: var(--primary); }

        .status-badge { padding: 0.25rem 0.75rem; border-radius: 99px; font-size: 0.75rem; font-weight: 700; color: white; }
        .bg-pending { background: var(--warning); }
        .bg-sent { background: var(--success); }
        .bg-completed { background: var(--success); }
        .bg-cancelled { background: var(--secondary); }

        @media (max-width: 768px) {
            aside { display: none; }
            main { padding: 1rem; }
        }
    </style>
</head>
<body>

    <aside>
        <div class="logo">
            <i class="ph-fill ph-first-aid-kit" style="font-size: 1.8rem;"></i>
            <span>MedConnect</span>
        </div>
        <div class="nav-menu">
            <a href="<?php echo $home_link; ?>" class="nav-item">
                <i class="ph ph-squares-four"></i>
                <span>Home Dashboard</span>
            </a>
            <div style="margin: 1.5rem 0.5rem 0.5rem; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); font-weight: 700;">Records Module</div>
            <a href="?tab=records" class="nav-item <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'records') ? 'active' : ''; ?>">
                <i class="ph ph-file-medical"></i>
                <span>Medical History</span>
            </a>
            <a href="?tab=reminders" class="nav-item <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'reminders') ? 'active' : ''; ?>">
                <i class="ph ph-bell"></i>
                <span>Reminders</span>
            </a>
        </div>
    </aside>

    <div class="wrapper">
        <header>
            <h2 style="font-size: 1.1rem; font-weight: 700;">Medical Records & Reminder System</h2>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="text-align: right;">
                    <div style="font-size: 0.85rem; font-weight: 700;"><?php echo htmlspecialchars($user_name); ?></div>
                    <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: capitalize;"><?php echo $role; ?></div>
                </div>
                <div style="width: 36px; height: 36px; background: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                    <?php echo strtoupper(substr($user_name,0,1)); ?>
                </div>
            </div>
        </header>

        <main>
            <div class="container">
                <!-- Tab Switching Logic handled by PHP + JS -->
                <?php 
                $activeTab = $_GET['tab'] ?? 'records';
                $patient_id = $_GET['patient_id'] ?? ($role === 'patient' ? $user_id : null);
                ?>

                <!-- Staff/Admin Patient Selector -->
                <?php if ($role !== 'patient'): ?>
                <div class="card" style="padding: 1rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <label style="font-size: 0.9rem; font-weight: 700;">Select Patient:</label>
                    <select id="patientSelect" class="form-select" style="max-width:300px;" onchange="changePatient(this.value)">
                        <option value="">-- Choose Patient --</option>
                        <?php 
                        $res = $conn->query("SELECT id, full_name, email FROM users WHERE role = 'patient' AND status = 'approved' ORDER BY full_name");
                        while($p = $res->fetch_assoc()):
                            $selected = ($patient_id == $p['id']) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo $selected; ?>>
                            <?php echo htmlspecialchars($p['full_name']); ?> (<?php echo htmlspecialchars($p['email']); ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <?php if ($patient_id): ?>
                        <button class="btn btn-primary btn-sm" onclick="openAddModal()" style="margin-left:auto;">
                            <i class="ph ph-plus-circle"></i> Add Medical Record
                        </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (!$patient_id && $role !== 'patient'): ?>
                    <div style="text-align: center; padding: 4rem; color: var(--text-muted);">
                        <i class="ph ph-users" style="font-size: 4rem; opacity: 0.3;"></i>
                        <p style="margin-top: 1rem; font-size: 1.1rem;">Please select a patient to view their records.</p>
                    </div>
                <?php else: ?>
                    
                    <div class="card">
                        <div class="card-header">
                            <span class="card-title"><?php echo ($activeTab === 'records') ? 'Medical History' : 'Upcoming Reminders'; ?></span>
                        </div>
                        <div id="contentTable">
                            <div style="padding: 2rem; text-align: center;">Loading data...</div>
                        </div>
                    </div>

                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div class="modal-overlay" id="recordModal">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modalTitle">Add Medical Record</h3>
                <button onclick="closeModal('recordModal')" style="background:none; border:none; cursor:pointer;"><i class="ph ph-x" style="font-size:1.5rem;"></i></button>
            </div>
            <div class="modal-body">
                <form id="recordForm" onsubmit="saveRecord(event)">
                    <input type="hidden" id="recordId" name="id">
                    <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Diagnosis *</label>
                        <input type="text" name="diagnosis" class="form-input" required placeholder="Main condition or reason for visit">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Visit Date *</label>
                            <input type="date" name="visit_date" class="form-input" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Allergies (if any)</label>
                            <input type="text" name="allergies" class="form-input" placeholder="e.g. Penicillin, Peanuts">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Current Medications</label>
                        <textarea name="medications" class="form-textarea" placeholder="List medications and dosages"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Lab Results / Findings</label>
                        <textarea name="lab_results" class="form-textarea" placeholder="Blood work, BP, Weight, etc."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Clinical Notes</label>
                        <textarea name="notes" class="form-textarea" placeholder="Detailed provider notes..."></textarea>
                    </div>

                    <!-- Toggle Reminder Section -->
                    <div id="reminderToggleSection" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed var(--border);">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 700; font-size: 0.9rem;">
                            <input type="checkbox" id="createReminder" onchange="toggleReminderFields(this.checked)"> Add a reminder for this patient?
                        </label>
                        
                        <div id="reminderFields" style="display: none; margin-top: 1rem; background: #f8fafc; padding: 1rem; border-radius: 8px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label class="form-label">Reminder Type</label>
                                    <select name="reminder_type" class="form-select">
                                        <option value="follow_up">Follow-up Appointment</option>
                                        <option value="medication_refill">Medication Refill</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Reminder Date</label>
                                    <input type="date" name="reminder_date" class="form-input" value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Notify Via</label>
                                <select name="notification_method" class="form-select">
                                    <option value="email">Email</option>
                                    <option value="sms">SMS (Simulation)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Reminder Message</label>
                                <input type="text" name="reminder_message" class="form-input" placeholder="e.g. Please come for your BP follow-up.">
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">Save Record</button>
                        <button type="button" class="btn btn-outline" onclick="closeModal('recordModal')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const patientId = "<?php echo $patient_id; ?>";
        const currentRole = "<?php echo $role; ?>";
        const activeTab = "<?php echo $activeTab; ?>";

        document.addEventListener('DOMContentLoaded', () => {
            if (patientId) {
                loadData();
            }
        });

        function changePatient(val) {
            window.location.href = `?tab=${activeTab}&patient_id=${val}`;
        }

        async function loadData() {
            const tableDiv = document.getElementById('contentTable');
            tableDiv.innerHTML = '<div style="padding: 2rem; text-align: center;">Loading...</div>';

            try {
                if (activeTab === 'records') {
                    const res = await fetch(`medical_records_api.php?action=list&patient_id=${patientId}`);
                    const data = await res.json();
                    if (data.status === 'success') {
                        renderRecords(data.data);
                    } else {
                        tableDiv.innerHTML = `<div style="padding: 2rem; color:red;">${data.message}</div>`;
                    }
                } else {
                    const res = await fetch(`reminders_api.php?action=list&patient_id=${patientId}`);
                    const data = await res.json();
                    if (data.status === 'success') {
                        renderReminders(data.data);
                    } else {
                        tableDiv.innerHTML = `<div style="padding: 2rem; color:red;">${data.message}</div>`;
                    }
                }
            } catch (e) {
                tableDiv.innerHTML = `<div style="padding: 2rem; color:red;">Failed to connect to API.</div>`;
            }
        }

        function renderRecords(records) {
            const tableDiv = document.getElementById('contentTable');
            if (records.length === 0) {
                tableDiv.innerHTML = '<div style="padding: 3rem; text-align: center; color: var(--text-muted);">No medical records found for this patient.</div>';
                return;
            }

            let html = `<table><thead><tr>
                <th>Date</th>
                <th>Diagnosis</th>
                <th>Medications</th>
                <th>Provider</th>
                <th>Actions</th>
            </tr></thead><tbody>`;

            records.forEach(r => {
                html += `<tr>
                    <td><b>${r.visit_date}</b></td>
                    <td>${r.diagnosis}</td>
                    <td style="max-width: 200px; font-size:0.8rem;">${r.medications || '-'}</td>
                    <td>${r.doctor_name || 'Staff'}</td>
                    <td>
                        <button class="btn btn-outline btn-sm" onclick="viewRecord(${r.id})"><i class="ph ph-eye"></i></button>
                        ${currentRole !== 'patient' ? `<button class="btn btn-outline btn-sm" onclick="editRecord(${r.id})"><i class="ph ph-pencil"></i></button>` : ''}
                        ${currentRole === 'admin' ? `<button class="btn btn-outline btn-sm" style="color:red;" onclick="deleteRecord(${r.id})"><i class="ph ph-trash"></i></button>` : ''}
                    </td>
                </tr>`;
            });
            html += '</tbody></table>';
            tableDiv.innerHTML = html;
        }

        function renderReminders(items) {
            const tableDiv = document.getElementById('contentTable');
            if (items.length === 0) {
                tableDiv.innerHTML = '<div style="padding: 3rem; text-align: center; color: var(--text-muted);">No reminders found.</div>';
                return;
            }

            let html = `<table><thead><tr>
                <th>Date</th>
                <th>Type</th>
                <th>Notification</th>
                <th>Linked Diagnosis</th>
                <th>Status</th>
                <th>Actions</th>
            </tr></thead><tbody>`;

            items.forEach(r => {
                const typeLabel = r.reminder_type === 'follow_up' ? 'Follow-up' : (r.reminder_type === 'medication_refill' ? 'Med Refill' : r.reminder_type);
                const status = (r.status || 'pending').toLowerCase();
                const statusText = status.toUpperCase();
                
                html += `<tr>
                    <td><b>${r.reminder_date}</b></td>
                    <td>${typeLabel}</td>
                    <td><i class="ph ph-${r.notification_method === 'email' ? 'envelope' : 'chat-text'}"></i> ${(r.notification_method || 'email').toUpperCase()}</td>
                    <td>${r.diagnosis || '-'}</td>
                    <td><span class="status-badge bg-${status}">${statusText}</span></td>
                    <td>
                        ${status === 'pending' && currentRole !== 'patient' ? `
                            <button class="btn btn-outline btn-sm" onclick="cancelReminder(${r.id})">Cancel</button>
                        ` : (status === 'completed' ? '<span style="color:var(--success); font-size:0.8rem;"><i class="ph ph-check-circle"></i> Done</span>' : '-')}
                    </td>
                </tr>`;
            });
            html += '</tbody></table>';
            tableDiv.innerHTML = html;
        }

        function openAddModal() {
            document.getElementById('modalTitle').innerText = 'Add Medical Record';
            document.getElementById('recordForm').reset();
            document.getElementById('recordId').value = '';
            document.getElementById('reminderToggleSection').style.display = 'block';
            toggleReminderFields(false);
            openModal('recordModal');
        }

        async function editRecord(id) {
            const res = await fetch(`medical_records_api.php?action=get&id=${id}`);
            const data = await res.json();
            if (data.status === 'success') {
                const r = data.data;
                document.getElementById('modalTitle').innerText = 'Edit Medical Record';
                const form = document.getElementById('recordForm');
                document.getElementById('recordId').value = r.id;
                form.diagnosis.value = r.diagnosis;
                form.visit_date.value = r.visit_date;
                form.allergies.value = r.allergies;
                form.medications.value = r.medications;
                form.lab_results.value = r.lab_results;
                form.notes.value = r.notes;
                
                // Don't show reminder toggle on edit for simplicity
                document.getElementById('reminderToggleSection').style.display = 'none';
                
                openModal('recordModal');
            }
        }

        async function viewRecord(id) {
            const res = await fetch(`medical_records_api.php?action=get&id=${id}`);
            const data = await res.json();
            if (data.status === 'success') {
                const r = data.data;
                alert(`Diagnosis: ${r.diagnosis}\nDate: ${r.visit_date}\n\nNotes: ${r.notes || "No notes available."}`);
            }
        }

        async function saveRecord(e) {
            e.preventDefault();
            const form = e.target;
            const btn = form.querySelector('button[type="submit"]');
            const originalBtnText = btn.innerHTML;
            
            try {
                // Pre-validation
                const diagnosis = form.diagnosis.value.trim();
                if (!diagnosis) {
                    alert("Diagnosis is required.");
                    return;
                }

                btn.disabled = true;
                btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Saving...';

                const formData = new FormData(form);
                const id = document.getElementById('recordId').value;
                const action = id ? 'update' : 'create';
                formData.set('action', action); // Use set to ensure only one action field
                
                // Explicitly ensure patient_id is present
                if (!formData.get('patient_id')) {
                    formData.set('patient_id', patientId);
                }

                const res = await fetch('medical_records_api.php', { method: 'POST', body: formData });
                const text = await res.text();
                
                let data;
                try {
                    data = JSON.parse(text);
                } catch (jsonErr) {
                    console.error("Invalid JSON:", text);
                    alert("Server Error: " + text.substring(0, 200));
                    throw new Error("Server returned an invalid response.");
                }
                
                if (data.status === 'success') {
                    // Try adding reminder if requested (only on create)
                    if (action === 'create' && document.getElementById('createReminder').checked) {
                        try {
                            const reminderData = new FormData();
                            reminderData.append('action', 'create');
                            reminderData.append('medical_record_id', data.id);
                            reminderData.append('patient_id', formData.get('patient_id') || patientId);
                            reminderData.append('reminder_type', form.reminder_type.value);
                            reminderData.append('reminder_date', form.reminder_date.value);
                            reminderData.append('notification_method', form.notification_method.value);
                            reminderData.append('message', form.reminder_message.value);
                            
                            const rRes = await fetch('reminders_api.php', { method: 'POST', body: reminderData });
                            const rData = await rRes.json();
                            if (rData.status !== 'success') console.warn("Reminder Error:", rData.message);
                        } catch (remErr) {
                            console.error("Reminder process failed:", remErr);
                        }
                    }
                    
                    closeModal('recordModal');
                    loadData();
                } else {
                    alert(data.message || "Server Error: Failed to save record.");
                }
            } catch (err) {
                console.error("Save Error:", err);
                alert(err.message || "Failed to save record. Please check console for details.");
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalBtnText;
            }
        }

        async function deleteRecord(id) {
            if (!confirm("Are you sure? This action cannot be undone.")) return;
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            const res = await fetch('medical_records_api.php', { method: 'POST', body: formData });
            loadData();
        }

        async function cancelReminder(id) {
            if (!confirm("Cancel this reminder?")) return;
            const formData = new FormData();
            formData.append('action', 'cancel');
            formData.append('id', id);
            await fetch('reminders_api.php', { method: 'POST', body: formData });
            loadData();
        }

        function toggleReminderFields(show) {
            document.getElementById('reminderFields').style.display = show ? 'block' : 'none';
        }

        function openModal(id) { document.getElementById(id).style.display = 'flex'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    </script>
</body>
</html>

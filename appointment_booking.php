<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$patientId = $_SESSION['user_id'];

// Get approved doctors
$doctors = $conn->query("
    SELECT u.id, u.full_name, d.specialization, d.consultation_fee, d.languages_spoken,
           COALESCE(AVG(r.rating), 0) as avg_rating, COUNT(r.id) as review_count
    FROM users u
    INNER JOIN doctor_profiles d ON u.id = d.user_id
    LEFT JOIN doctor_reviews r ON u.id = r.doctor_id
    WHERE u.role = 'doctor' AND u.status = 'approved'
    GROUP BY u.id
    ORDER BY avg_rating DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - MedConnect</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: white; padding: 20px 30px; border-radius: 12px 12px 0 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header h1 { color: #333; font-size: 28px; }
        .header p { color: #666; margin-top: 5px; }
        .content { background: white; padding: 30px; border-radius: 0 0 12px 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); }
        .doctor-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .doctor-card { border: 2px solid #e2e8f0; border-radius: 12px; padding: 20px; transition: all 0.3s; cursor: pointer; }
        .doctor-card:hover { border-color: #667eea; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2); transform: translateY(-2px); }
        .doctor-card.selected { border-color: #667eea; background: #f8f9ff; }
        .doctor-name { font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 5px; }
        .doctor-spec { color: #667eea; font-size: 14px; margin-bottom: 10px; }
        .doctor-info { display: flex; gap: 15px; margin-top: 10px; font-size: 13px; color: #64748b; }
        .rating { color: #f59e0b; }
        .fee { font-weight: 600; color: #10b981; }
        .calendar-section { display: none; margin-top: 30px; padding: 25px; background: #f8fafc; border-radius: 12px; }
        .calendar-section.active { display: block; }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .calendar-header h3 { color: #1e293b; }
        .calendar-nav { display: flex; gap: 10px; }
        .calendar-nav button { background: #667eea; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; margin-top: 15px; }
        .calendar-day { text-align: center; padding: 15px; border-radius: 8px; cursor: pointer; border: 2px solid #e2e8f0; background: white; transition: all 0.2s; }
        .calendar-day:hover { border-color: #667eea; }
        .calendar-day.selected { background: #667eea; color: white; border-color: #667eea; }
        .calendar-day.disabled { opacity: 0.4; cursor: not-allowed; }
        .time-slots { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 20px; }
        .time-slot { padding: 12px; text-align: center; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; background: white; transition: all 0.2s; }
        .time-slot:hover { border-color: #667eea; }
        .time-slot.selected { background: #667eea; color: white; border-color: #667eea; }
        .notes-section { margin-top: 20px; }
        .notes-section textarea { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-family: inherit; resize: vertical; min-height: 80px; }
        .action-buttons { margin-top: 25px; display: flex; gap: 15px; justify-content: flex-end; }
        .btn { padding: 12px 30px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4); }
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d1fae5; border: 2px solid #10b981; color: #065f46; }
        .alert-error { background: #fee2e2; border: 2px solid #ef4444; color: #991b1b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📅 Book an Appointment</h1>
            <p>Select a doctor and choose your preferred date and time</p>
        </div>
        
        <div class="content">
            <div id="alertBox" style="display: none;"></div>
            
            <h2 style="margin-bottom: 20px; color: #1e293b;">Select a Doctor</h2>
            <div class="doctor-grid">
                <?php foreach ($doctors as $doctor): ?>
                <div class="doctor-card" onclick="selectDoctor(<?php echo $doctor['id']; ?>, '<?php echo htmlspecialchars($doctor['full_name']); ?>')">
                    <div class="doctor-name">Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></div>
                    <div class="doctor-spec"><?php echo htmlspecialchars($doctor['specialization']); ?></div>
                    <div class="doctor-info">
                        <span class="rating">⭐ <?php echo number_format($doctor['avg_rating'], 1); ?> (<?php echo $doctor['review_count']; ?>)</span>
                        <span class="fee">₹<?php echo number_format($doctor['consultation_fee']); ?></span>
                    </div>
                    <div style="margin-top: 10px; font-size: 13px; color: #64748b;">
                        🗣️ <?php echo htmlspecialchars($doctor['languages_spoken']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div id="calendarSection" class="calendar-section">
                <div class="calendar-header">
                    <h3>Select Date & Time</h3>
                    <div class="calendar-nav">
                        <button onclick="changeMonth(-1)">← Prev</button>
                        <button onclick="changeMonth(1)">Next →</button>
                    </div>
                </div>
                
                <h4 id="monthYear" style="margin-bottom: 15px; color: #475569;"></h4>
                <div class="calendar-grid" id="calendarGrid"></div>
                
                <div id="timeSlotsSection" style="display: none;">
                    <h4 style="margin-top: 25px; margin-bottom: 15px; color: #475569;">Available Time Slots</h4>
                    <div class="time-slots" id="timeSlots"></div>
                </div>
                
                <div class="notes-section">
                    <h4 style="margin-bottom: 10px; color: #475569;">Additional Notes (Optional)</h4>
                    <textarea id="appointmentNotes" placeholder="Any specific concerns or requirements..."></textarea>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-secondary" onclick="resetForm()">Cancel</button>
                    <button class="btn btn-primary" onclick="confirmAppointment()">Confirm Appointment</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Clear any previous error messages on page load
        window.addEventListener('DOMContentLoaded', () => {
            document.getElementById('alertBox').innerHTML = '';
        });
        
        let selectedDoctor = null;
        let selectedDoctorName = '';
        let selectedDate = null;
        let selectedTime = null;
        let currentMonth = new Date().getMonth();
        let currentYear = new Date().getFullYear();

        function selectDoctor(doctorId, doctorName) {
            selectedDoctor = doctorId;
            selectedDoctorName = doctorName;
            
            // Visual feedback
            document.querySelectorAll('.doctor-card').forEach(card => card.classList.remove('selected'));
            event.currentTarget.classList.add('selected');
            
            // Show calendar
            document.getElementById('calendarSection').classList.add('active');
            generateCalendar();
        }

        function generateCalendar() {
            const grid = document.getElementById('calendarGrid');
            const monthYear = document.getElementById('monthYear');
            
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            monthYear.textContent = `${months[currentMonth]} ${currentYear}`;
            
            grid.innerHTML = '';
            
            // Add day headers
            ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(day => {
                const header = document.createElement('div');
                header.textContent = day;
                header.style.fontWeight = '600';
                header.style.color = '#475569';
                header.style.padding = '10px';
                grid.appendChild(header);
            });
            
            const firstDay = new Date(currentYear, currentMonth, 1).getDay();
            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            // Empty cells for days before month starts
            for (let i = 0; i < firstDay; i++) {
                grid.appendChild(document.createElement('div'));
            }
            
            // Days of the month
            for (let day = 1; day <= daysInMonth; day++) {
                const dateObj = new Date(currentYear, currentMonth, day);
                const dayEl = document.createElement('div');
                dayEl.className = 'calendar-day';
                dayEl.textContent = day;
                
                if (dateObj < today) {
                    dayEl.classList.add('disabled');
                } else {
                    dayEl.onclick = () => selectDate(dateObj);
                }
                
                grid.appendChild(dayEl);
            }
        }

        function selectDate(date) {
            selectedDate = date.toISOString().split('T')[0];
            
            // Visual feedback
            document.querySelectorAll('.calendar-day').forEach(day => day.classList.remove('selected'));
            event.currentTarget.classList.add('selected');
            
            // Show time slots
            generateTimeSlots();
        }

        function generateTimeSlots() {
            const slotsContainer = document.getElementById('timeSlots');
            const section = document.getElementById('timeSlotsSection');
            section.style.display = 'block';
            
            slotsContainer.innerHTML = '';
            
            // Generate time slots (9 AM to 5 PM)
            const slots = [];
            for (let hour = 9; hour < 17; hour++) {
                slots.push(`${hour.toString().padStart(2, '0')}:00:00`);
                slots.push(`${hour.toString().padStart(2, '0')}:30:00`);
            }
            
            slots.forEach(time => {
                const slot = document.createElement('div');
                slot.className = 'time-slot';
                slot.textContent = time.substring(0, 5);
                slot.onclick = () => selectTime(time);
                slotsContainer.appendChild(slot);
            });
        }

        function selectTime(time) {
            selectedTime = time;
            
            // Visual feedback
            document.querySelectorAll('.time-slot').forEach(slot => slot.classList.remove('selected'));
            event.currentTarget.classList.add('selected');
        }

        function changeMonth(direction) {
            currentMonth += direction;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            } else if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            generateCalendar();
            document.getElementById('timeSlotsSection').style.display = 'none';
            selectedDate = null;
            selectedTime = null;
        }

        async function confirmAppointment() {
            if (!selectedDoctor || !selectedDate || !selectedTime) {
                showAlert('Please select doctor, date, and time', 'error');
                return;
            }
            
            const notes = document.getElementById('appointmentNotes').value;
            
            try {
                const response = await fetch('appointment_api.php?action=create_appointment', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        doctor_id: selectedDoctor,
                        scheduled_date: selectedDate,
                        scheduled_time: selectedTime,
                        notes: notes
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(`Appointment booked with Dr. ${selectedDoctorName}! Redirecting to payment...`, 'success');
                    
                    // Redirect to payment gateway after 1 second
                    setTimeout(() => {
                        window.location.href = `payment_gateway.php?type=consultation&related_id=${data.appointment_id}&amount=${data.consultation_fee}`;
                    }, 1000);
                } else {
                    showAlert(data.error || 'Failed to book appointment', 'error');
                }
            } catch (error) {
                showAlert('Error booking appointment', 'error');
            }
        }

        function resetForm() {
            window.location.reload();
        }

        function showAlert(message, type) {
            const alertBox = document.getElementById('alertBox');
            alertBox.style.display = 'block';
            alertBox.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            setTimeout(() => {
                alertBox.innerHTML = '';
                alertBox.style.display = 'none';
            }, 5000);
        }
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply - MedConnect</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body>
    <div class="auth-container fade-in" style="margin-top: 3rem;">
        <div style="margin-bottom: 2rem; color: var(--primary);">
            <i class="ph ph-heartbeat" style="font-size: 3rem;"></i>
        </div>
        <h2>Apply to Join MedConnect</h2>
        <p>Submit your application to join our healthcare network</p>

        <form id="applicationForm" onsubmit="handleApplication(event)">
            <div class="form-group" style="margin-top: 1.5rem;">
                <label>I am applying as a:</label>
                <select id="applicationRole" name="role" onchange="toggleDesignation()" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; background: #fff;" required>
                    <option value="">-- Select Role --</option>
                    <option value="doctor">Doctor</option>
                    <option value="staff">Staff Member</option>
                    <option value="admin">Admin</option>
                    <option value="hospital">Hospital</option>
                </select>
            </div>

            <div class="form-group" id="designationGroup" style="display: none;">
                <label>Designation:</label>
                <select id="designation" name="designation" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; background: #fff;">
                    <option value="">-- Select Designation --</option>
                    <option value="receptionist">Receptionist</option>
                    <option value="nurse">Nurse</option>
                    <option value="lab_staff">Lab Staff</option>
                    <option value="canteen_staff">Canteen Staff</option>
                    <option value="pharmacist">Pharmacist</option>
                </select>
            </div>

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" id="applicationName" name="name" placeholder="Dr. John Doe" required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" id="applicationEmail" name="email" placeholder="doctor@example.com" required>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" id="applicationPassword" name="password" placeholder="Create a password" minlength="6" required>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" id="applicationPasswordConfirm" placeholder="Confirm password" minlength="6" required>
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" id="applicationPhone" name="phone" placeholder="+1 234 567 8900">
            </div>

            <div class="form-group" id="licenseGroup">
                <label>License Number</label>
                <input type="text" id="applicationLicense" name="license" placeholder="Enter your professional license number">
            </div>

            <div class="form-group" id="specializationGroup" style="display: none;">
                <label>Specialization</label>
                <input type="text" id="applicationSpecialization" name="specialization" placeholder="e.g., Cardiology, General Practice">
            </div>

            <div class="form-group" id="hospitalNameGroup" style="display: none;">
                <label>Hospital Name</label>
                <input type="text" id="applicationHospitalName" name="hospital_name" placeholder="Enter hospital name">
            </div>

            <div class="form-group" id="hospitalAddressGroup" style="display: none;">
                <label>Hospital Address</label>
                <textarea id="applicationHospitalAddress" name="hospital_address" placeholder="Enter complete hospital address" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; min-height: 80px;"></textarea>
            </div>

            <div class="form-group" id="departmentGroup" style="display: none;">
                <label>Department</label>
                <input type="text" id="applicationDepartment" name="department" placeholder="e.g., IT, HR, Operations">
            </div>

            <div style="background: #e0f2fe; border: 1px solid #0284c7; border-radius: 8px; padding: 1rem; margin: 1rem 0;">
                <p style="margin: 0; color: #0c4a6e; font-size: 0.9rem;">
                    <i class="ph ph-info"></i> Your application will be reviewed by our admin team. You will be notified via email once approved.
                </p>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%">Submit Application</button>
        </form>

        <p style="margin-top: 1rem; font-size: 0.9rem; color: #666;">
            Already have an account? <a href="login.php" style="color: var(--primary)">Login</a>
        </p>
        <p style="margin-top: 0.5rem; font-size: 0.9rem;">
            <a href="index.php" style="color: var(--primary)">← Back to Home</a>
        </p>
    </div>

    <script>
        function toggleDesignation() {
            const role = document.getElementById('applicationRole').value;
            const designationGroup = document.getElementById('designationGroup');
            const specializationGroup = document.getElementById('specializationGroup');
            const hospitalNameGroup = document.getElementById('hospitalNameGroup');
            const hospitalAddressGroup = document.getElementById('hospitalAddressGroup');
            const departmentGroup = document.getElementById('departmentGroup');
            
            const designation = document.getElementById('designation');
            const specialization = document.getElementById('applicationSpecialization');
            const hospitalName = document.getElementById('applicationHospitalName');
            const hospitalAddress = document.getElementById('applicationHospitalAddress');
            const department = document.getElementById('applicationDepartment');

            // Hide all role-specific fields first
            designationGroup.style.display = 'none';
            designation.required = false;
            specializationGroup.style.display = 'none';
            specialization.required = false;
            hospitalNameGroup.style.display = 'none';
            hospitalName.required = false;
            hospitalAddressGroup.style.display = 'none';
            hospitalAddress.required = false;
            departmentGroup.style.display = 'none';
            department.required = false;

            // Show relevant fields based on role
            if (role === 'staff') {
                designationGroup.style.display = 'block';
                designation.required = true;
            } else if (role === 'doctor') {
                specializationGroup.style.display = 'block';
                specialization.required = true;
            } else if (role === 'hospital') {
                hospitalNameGroup.style.display = 'block';
                hospitalName.required = true;
                hospitalAddressGroup.style.display = 'block';
                hospitalAddress.required = true;
            } else if (role === 'admin') {
                departmentGroup.style.display = 'block';
                department.required = false; // Optional for admin
            }
        }

        function handleApplication(event) {
            event.preventDefault();
            
            const role = document.getElementById('applicationRole').value;
            const name = document.getElementById('applicationName').value;
            const email = document.getElementById('applicationEmail').value;
            const password = document.getElementById('applicationPassword').value;
            const passwordConfirm = document.getElementById('applicationPasswordConfirm').value;
            const phone = document.getElementById('applicationPhone').value;
            const license = document.getElementById('applicationLicense').value;

            // Validate inputs
            if (!role || !name || !email || !password) {
                alert("Please fill in all required fields");
                return;
            }

            if (password !== passwordConfirm) {
                alert("Passwords do not match!");
                return;
            }

            if (password.length < 6) {
                alert("Password must be at least 6 characters long");
                return;
            }

            // Additional validation for staff
            if (role === 'staff') {
                const designation = document.getElementById('designation').value;
                if (!designation) {
                    alert("Please select a designation");
                    return;
                }
            }

            // Additional validation for doctor
            if (role === 'doctor') {
                const specialization = document.getElementById('applicationSpecialization').value;
                if (!specialization) {
                    alert("Please enter your specialization");
                    return;
                }
            }

            // Additional validation for hospital
            if (role === 'hospital') {
                const hospitalName = document.getElementById('applicationHospitalName').value;
                const hospitalAddress = document.getElementById('applicationHospitalAddress').value;
                if (!hospitalName || !hospitalAddress) {
                    alert("Please enter hospital name and address");
                    return;
                }
            }

            const formData = new FormData();
            formData.append('action', 'apply');
            formData.append('role', role);
            formData.append('name', name);
            formData.append('email', email);
            formData.append('password', password);
            formData.append('phone', phone);
            formData.append('license', license);

            if (role === 'staff') {
                formData.append('designation', document.getElementById('designation').value);
            } else if (role === 'doctor') {
                formData.append('specialization', document.getElementById('applicationSpecialization').value);
            } else if (role === 'hospital') {
                formData.append('hospital_name', document.getElementById('applicationHospitalName').value);
                formData.append('hospital_address', document.getElementById('applicationHospitalAddress').value);
            } else if (role === 'admin') {
                const dept = document.getElementById('applicationDepartment').value;
                if (dept) formData.append('department', dept);
            }

            console.log("Submitting application...");

            fetch('auth.php', {
                method: 'POST',
                body: formData
            })
            .then(res => {
                console.log("Response status:", res.status);
                return res.json();
            })
            .then(data => {
                console.log("Response data:", data);
                if (data.status === 'success') {
                    alert("Application submitted successfully! You will be notified once it's reviewed.");
                    window.location.href = 'login.php';
                } else {
                    alert("Application Failed: " + (data.message || "Unknown error"));
                }
            })
            .catch(err => {
                console.error("Application error:", err);
                alert('Application submission failed. Please try again.');
            });
        }
    </script>
</body>
</html>
<?php
session_start();

// Redirect logged-in users to their respective dashboards
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'patient') {
        header("Location: patient_dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'doctor') {
        header("Location: doctor_dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'pharmacy') {
        header("Location: pharmacy_dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedConnect - Expert Medical Consultation</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <?php
    require_once 'db.php';

    // Fetch doctors with their profiles
    $query = "SELECT u.full_name, dp.specialization, dp.years_experience, dp.consultation_fee 
              FROM users u 
              JOIN doctor_profiles dp ON u.id = dp.user_id 
              WHERE u.role = 'doctor' 
              ORDER BY u.full_name ASC";
    $result = $conn->query($query);
    $doctors = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $doctors[] = $row;
        }
    }

    function getDoctorImage($name) {
        $filename = strtolower(str_replace(['Dr. ', 'Dr ', ' '], ['', '', '_'], $name));
        // Handle common typos or variants
        $filename = str_replace('roudrigez', 'rodriguez', $filename);
        
        $extensions = ['jpg', 'jpeg', 'png', 'webp', 'JPG', 'PNG'];
        $basePath = 'assets/img/doctors/';
        
        foreach ($extensions as $ext) {
            $path = $basePath . "dr_" . $filename . "." . $ext;
            if (file_exists($path)) {
                return $path;
            }
        }
        return 'assets/img/doctors/default_doctor.png';
    }
    ?>
</head>

<body>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="logo">
            <i class="ph ph-heartbeat"></i> MedConnect
        </div>
        <div class="nav-links" id="navLinks">
            <a href="#landing">Home</a>
            <a href="#services">Services</a>
            <a href="#doctors">Doctors</a>
            <a href="#contact">Contact</a>
        </div>
        <div class="nav-auth" id="navAuth">
            <a href="login.php" class="btn btn-primary">Login / Sign Up</a>
        </div>
        <div class="hamburger" onclick="toggleMenu()">
            <i class="ph ph-list"></i>
        </div>
    </nav>

    <!-- Main Content Container -->
    <main id="app">
        <div class="blob-container">
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
        </div>

        <section id="landing" class="hero-section fade-in">
            <div class="hero-text">
                <h1>Your Health, <br><span class="text-gradient">Now Connected.</span></h1>
                <p>Experience the future of healthcare with expert medical consultation, AI-driven symptom analysis, and instant e-prescriptions—right at your fingertips.</p>
                <div style="display: flex; gap: 1rem;">
                    <a href="login.php" class="btn btn-primary">Join the Portal</a>
                    <a href="#doctors" class="btn btn-outline" style="background:white;">Explore Specialists</a>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-image-container">
                    <img src="assets/img/hero.png" alt="Medical Consultation">
                </div>
                <div class="stats-card">
                    <h2>24/7</h2>
                    <p>Specialist Access</p>
                    <hr style="margin: 1rem 0; opacity: 0.2">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <i class="ph ph-shield-check" style="font-size: 2rem; color: var(--primary)"></i>
                        <div>
                            <strong>Fully Secure</strong><br>
                            <small>Safe & Private</small>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="services" class="features-grid fade-in">
            <div class="feature-card">
                <i class="ph ph-stethoscope feature-icon"></i>
                <h3>Symptom Checker</h3>
                <p>AI-powered analysis to guide you to the right specialist instantly.</p>
            </div>
            <div class="feature-card">
                <i class="ph ph-video-camera feature-icon"></i>
                <h3>Video Consults</h3>
                <p>HD video calls with certified doctors without the waiting room.</p>
            </div>
            <div class="feature-card">
                <i class="ph ph-pill feature-icon"></i>
                <h3>E-Prescriptions</h3>
                <p>Sent directly to your nearest pharmacy for easy pickup.</p>
            </div>
        </section>

        <section id="doctors" class="fade-in" style="margin-top: 4rem;">
            <h2 style="text-align: center; margin-bottom: 2rem;">Our Specialists</h2>
            <div class="features-grid">
                <?php if (!empty($doctors)): ?>
                    <?php foreach ($doctors as $doctor): ?>
                        <div class="feature-card">
                            <img src="<?php echo getDoctorImage($doctor['full_name']); ?>" 
                                 alt="<?php echo htmlspecialchars($doctor['full_name']); ?>" 
                                 style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;">
                            <h3><?php echo htmlspecialchars($doctor['full_name']); ?></h3>
                            <p><?php echo htmlspecialchars($doctor['specialization']); ?></p>
                            <p style="font-size: 0.85rem; color: #64748b; margin-top: 0.5rem;">
                                <?php echo htmlspecialchars($doctor['years_experience'] ?? '0'); ?>+ years experience
                            </p>
                            <a href="login.php" class="btn btn-outline" style="margin-top: 1rem;">Book Appointment</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; grid-column: 1/-1;">Our specialized doctors will be available soon.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="footer-content">
            <div class="footer-brand">
                <div class="logo"><i class="ph ph-heartbeat"></i> MedConnect</div>
                <p>Revolutionizing healthcare through technology and compassion. Your health, our priority.</p>
            </div>
            <div class="footer-links">
                <h4>Quick Links</h4>
                <a href="#landing">Home</a>
                <a href="#services">Services</a>
                <a href="#doctors">Our Doctors</a>
            </div>
            <div class="footer-contact">
                <h4>Support</h4>
                <p><i class="ph ph-envelope"></i> help@medconnect.com</p>
                <p><i class="ph ph-phone"></i> 24/7 Helpline Active</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 MedConnect Portal. Built for excellence.</p>
        </div>
    </footer>

    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>

</html>
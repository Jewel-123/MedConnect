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
    <!-- Icons (using Phosphor Icons for a premium look, loaded via CDN) -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>

<body>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="logo">
            <i class="ph ph-heartbeat"></i> MedConnect
        </div>
        <div class="nav-links" id="navLinks">
            <a href="#" onclick="navigateToSection('landing')">Home</a>
            <a href="#" onclick="navigateToSection('services')">Services</a>
            <a href="#" onclick="navigateToSection('doctors')">Doctors</a>
            <a href="#" onclick="navigateToSection('contact')">Contact</a>
        </div>
        <div class="nav-auth" id="navAuth">
            <a href="login.php" class="btn btn-primary">Login / Sign Up</a>
        </div>
        <div class="hamburger" onclick="toggleMenu()">
            <i class="ph ph-list"></i>
        </div>
    </nav>

    <script>
        // Check auth status and update navbar
        function updateNavbar() {
            const currentUser = sessionStorage.getItem('currentUser');
            const navAuth = document.getElementById('navAuth');
            if (currentUser) {
                const user = JSON.parse(currentUser);
                navAuth.innerHTML = `
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <span style="font-weight: 500; font-size: 0.9rem; color: #1e293b;">Hello, ${user.name.split(' ')[0]}</span>
                        <button onclick="navigateToDashboard()" class="btn btn-primary">Dashboard</button>
                        <a href="#" onclick="logout()" style="color: #64748b; font-size: 0.9rem; text-decoration: none;">Logout</a>
                    </div>
                `;
            }
        }
        window.addEventListener('DOMContentLoaded', updateNavbar);
    </script>

    <!-- Main Content Container -->
    <main id="app">
        <!-- content injected via JS -->
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 MedConnect. All rights reserved.</p>
    </footer>

    <script src="script.js"></script>
    
    <script>
        // Auto-redirect admin users to admin dashboard
        window.addEventListener('DOMContentLoaded', function() {
            const currentUser = sessionStorage.getItem('currentUser');
            if (currentUser) {
                try {
                    const user = JSON.parse(currentUser);
                    if (user.role === 'admin') {
                        // Redirect admin to dashboard
                        window.location.href = 'admin_dashboard.php';
                    }
                } catch (e) {
                    console.error('Error parsing user data:', e);
                }
            }
        });
    </script>
</body>

</html>
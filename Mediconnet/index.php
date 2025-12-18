<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedConnect - Expert Medical Consultation</title>
    <link rel="stylesheet" href="style.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons (using Phosphor Icons for a premium look, loaded via CDN) -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>

<body>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="logo">
            <i class="ph ph-heartbeat"></i> MedConnect
        </div>
        <div class="nav-links">
            <a href="#" onclick="showPage('landing')">Home</a>
            <a href="#services">Services</a>
            <a href="#doctors">Doctors</a>
            <a href="#contact">Contact</a>
        </div>
        <div class="nav-auth">
            <button class="btn btn-primary" onclick="showPage('auth')">Login / Sign Up</button>
        </div>
    </nav>

    <!-- Main Content Container -->
    <main id="app">
        <!-- content injected via JS -->
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 MedConnect. All rights reserved.</p>
    </footer>

    <script src="script.js"></script>
</body>

</html>
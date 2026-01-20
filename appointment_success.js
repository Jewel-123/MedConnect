// Check for appointment success message in URL
window.addEventListener('load', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('appointment_success') === 'true') {
        const doctorName = urlParams.get('doctor') || 'the doctor';

        // Create success notification
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(16, 185, 129, 0.3);
            z-index: 10000;
            max-width: 400px;
            animation: slideIn 0.5s ease-out;
        `;
        notification.innerHTML = `
            <div style="display: flex; align-items: start; gap: 1rem;">
                <i class="ph-fill ph-check-circle" style="font-size: 2rem;"></i>
                <div>
                    <strong style="display: block; font-size: 1.1rem; marginBottom: 0.5rem;">Appointment Confirmed!</strong>
                    <p style="margin: 0; opacity: 0.95;">Your appointment with Dr. ${doctorName} has been successfully booked. You'll receive a confirmation notification.</p>
                </div>
            </div>
        `;

        // Add animation keyframes
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(400px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(400px); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        document.body.appendChild(notification);

        // Remove notification after 5 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.5s ease-out';
            setTimeout(() => notification.remove(), 500);
        }, 5000);

        // Clean URL (remove success parameters)
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

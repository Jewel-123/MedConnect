
// ========================================
// APPOINTMENT MANAGEMENT (New Feature)
// ========================================

/**
 * Confirm an appointment request
 */
async function confirmAppointment(appointmentId) {
    if (!await confirm('Confirm this appointment?')) return;

    try {
        const response = await fetch('appointment_api.php?action=confirm_appointment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                appointment_id: appointmentId
            })
        });

        const result = await response.json();

        if (result.success) {
            await alert('Appointment confirmed successfully!');
            location.reload();
        } else {
            await alert('Error: ' + (result.error || 'Failed to confirm appointment'));
        }
    } catch (error) {
        console.error('Error confirming appointment:', error);
        await alert('Failed to confirm appointment. Please try again.');
    }
}

/**
 * Cancel an appointment
 */
async function cancelAppointment(appointmentId) {
    const reason = await prompt('Reason for cancellation (optional):');
    if (reason === null) return; // User cancelled

    try {
        const response = await fetch('appointment_api.php?action=cancel_appointment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                appointment_id: appointmentId,
                reason: reason || ''
            })
        });

        const result = await response.json();

        if (result.success) {
            await alert('Appointment cancelled successfully!');
            location.reload();
        } else {
            await alert('Error: ' + (result.error || 'Failed to cancel appointment'));
        }
    } catch (error) {
        console.error('Error cancelling appointment:', error);
        await alert('Failed to cancel appointment. Please try again.');
    }
}

// Make functions globally accessible
window.confirmAppointment = confirmAppointment;
window.cancelAppointment = cancelAppointment;

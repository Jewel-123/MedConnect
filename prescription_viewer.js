/**
 * Prescription Viewer for Patient Dashboard
 * Handles viewing and ordering medicines from prescriptions
 */

const PrescriptionViewer = {
    currentPrescription: null,

    /**
     * Open prescription viewer modal
     */
    async open(prescriptionId) {
        try {
            const response = await fetch(`patient_api.php?action=get_prescription_details&prescription_id=${prescriptionId}`);
            const result = await response.json();

            if (result.status === 'success') {
                this.currentPrescription = result.prescription;
                this.renderPrescription(result.prescription, result.items);
                document.getElementById('prescriptionViewerModal').style.display = 'flex';
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Error loading prescription:', error);
            alert('Failed to load prescription');
        }
    },

    /**
     * Close modal
     */
    close() {
        document.getElementById('prescriptionViewerModal').style.display = 'none';
        this.currentPrescription = null;
    },

    /**
     * Render prescription details
     */
    renderPrescription(prescription, items) {
        // Header info
        document.getElementById('rxPrescriptionNumber').textContent = `Rx #${prescription.id}`;
        document.getElementById('rxDoctorName').textContent = prescription.doctor_name;
        document.getElementById('rxQualification').textContent = prescription.qualification || '';
        document.getElementById('rxRegNumber').textContent = prescription.registration_number || 'N/A';
        document.getElementById('rxDate').textContent = new Date(prescription.created_at).toLocaleDateString();
        document.getElementById('rxDiagnosis').textContent = prescription.diagnosis;

        // Medicines list
        const medicinesList = document.getElementById('rxMedicinesList');
        medicinesList.innerHTML = '';

        items.forEach((item, index) => {
            const medicineCard = document.createElement('div');
            medicineCard.className = 'medicine-card';
            medicineCard.innerHTML = `
                <div class="medicine-number">${index + 1}</div>
                <div class="medicine-details">
                    <h4>${item.medicine_name}</h4>
                    <div class="medicine-info">
                        <span><strong>Dosage:</strong> ${item.dosage}</span>
                        <span><strong>Frequency:</strong> ${item.frequency}</span>
                        <span><strong>Duration:</strong> ${item.duration}</span>
                        <span><strong>Quantity:</strong> ${item.quantity}</span>
                    </div>
                    ${item.instructions ? `<p class="instructions">📋 ${item.instructions}</p>` : ''}
                </div>
            `;
            medicinesList.appendChild(medicineCard);
        });

        // Notes
        if (prescription.notes_for_patient) {
            document.getElementById('rxPatientNotes').textContent = prescription.notes_for_patient;
            document.getElementById('rxPatientNotesSection').style.display = 'block';
        } else {
            document.getElementById('rxPatientNotesSection').style.display = 'none';
        }
    },

    /**
     * Open order modal
     */
    openOrderModal() {
        document.getElementById('prescriptionViewerModal').style.display = 'none';
        document.getElementById('medicineOrderModal').style.display = 'flex';
    },

    /**
     * Create medicine order
     */
    async createOrder() {
        const fulfillmentType = document.querySelector('input[name="fulfillmentType"]:checked').value;
        const deliveryAddress = document.getElementById('deliveryAddress').value.trim();
        const deliveryContact = document.getElementById('deliveryContact').value.trim();

        if (fulfillmentType === 'delivery' && !deliveryAddress) {
            alert('Please enter delivery address');
            return;
        }

        if (fulfillmentType === 'delivery' && !deliveryContact) {
            alert('Please enter contact number');
            return;
        }

        const btn = document.getElementById('orderMedicinesBtn');
        btn.disabled = true;
        btn.textContent = 'Processing...';

        try {
            const response = await fetch('patient_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'create_medicine_order',
                    prescription_id: this.currentPrescription.id,
                    fulfillment_type: fulfillmentType,
                    delivery_address: deliveryAddress,
                    delivery_contact: deliveryContact
                })
            });

            const result = await response.json();

            if (result.status === 'success') {
                alert(`Order created successfully! Order Number: ${result.order_number}`);
                document.getElementById('medicineOrderModal').style.display = 'none';
                this.close();

                // Reload patient dashboard
                if (typeof loadPatientDashboardData === 'function') {
                    loadPatientDashboardData();
                }
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Error creating order:', error);
            alert('Failed to create order');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Confirm Order';
        }
    },

    /**
     * Toggle delivery fields
     */
    toggleDeliveryFields() {
        const fulfillmentType = document.querySelector('input[name="fulfillmentType"]:checked').value;
        const deliveryFields = document.getElementById('deliveryFields');

        if (fulfillmentType === 'delivery') {
            deliveryFields.style.display = 'block';
        } else {
            deliveryFields.style.display = 'none';
        }
    },

    /**
     * Print prescription
     */
    printPrescription() {
        window.print();
    }
};

// Export for global use
window.PrescriptionViewer = PrescriptionViewer;

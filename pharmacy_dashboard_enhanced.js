/**
 * Redesigned Pharmacy Dashboard JavaScript
 * Handles real-time updates for the new City Pharmacy UI
 */

let currentPrescriptionId = null;
let currentOrderId = null;
let prescriptionsData = [];

document.addEventListener('DOMContentLoaded', () => {
    initDashboard();
});

async function initDashboard() {
    await fetchStats();
    await fetchQueue();
    await fetchHistory();
    await fetchMedicinesInventory();

    // Set up medicine inventory search/filter event listeners
    document.getElementById('medicineSearchInput')?.addEventListener('input', handleMedicineFilter);
    document.getElementById('categoryFilter')?.addEventListener('change', handleMedicineFilter);
    document.getElementById('lowStockFilter')?.addEventListener('change', handleMedicineFilter);

    // Auto refresh every 60s
    setInterval(() => {
        fetchStats();
        fetchQueue();
        fetchHistory();
    }, 60000);

    // Refresh medicine inventory every 5 minutes
    setInterval(() => {
        fetchMedicinesInventory();
    }, 300000);
}

async function fetchHistory() {
    try {
        const response = await fetch('pharmacy_api_enhanced.php?action=get_history');
        const data = await response.json();
        const historyBody = document.getElementById('historyQueueBody');
        if (!data.success || !data.history || data.history.length === 0) {
            historyBody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px;">No completed records.</td></tr>';
            return;
        }
        historyBody.innerHTML = data.history.map(item => `
            <tr>
                <td>#${item.id.toString().padStart(4, '0')}</td>
                <td>${item.patient_name}</td>
                <td>Dr. ${item.doctor_name}</td>
                <td>${formatDate(item.completed_at)}</td>
                <td>₹${item.total_amount || '0.00'}</td>
                <td><span class="badge-status" style="background:#059669">Completed</span></td>
            </tr>
        `).join('');
    } catch (e) { console.error('History fetch failed:', e); }
}

async function fetchStats() {
    try {
        const response = await fetch('pharmacy_api_enhanced.php?action=get_dashboard_stats');
        const data = await response.json();

        if (data.success) {
            const s = data.stats;
            document.getElementById('newPrescriptionsCount').textContent = s.pending_prescriptions || 0;
            document.getElementById('inProcessCount').textContent = s.active_orders || 0;
            document.getElementById('completedTodayCount').textContent = s.completed_today || 0;
            document.getElementById('lowStockCount').textContent = s.low_stock_alerts || 0;
        }
    } catch (e) {
        console.error('Stats fetch failed:', e);
    }
}

async function fetchQueue() {
    try {
        const response = await fetch('pharmacy_api_enhanced.php?action=get_pending_prescriptions');
        const data = await response.json();

        const queueBody = document.getElementById('prescriptionQueueBody');
        queueBody.innerHTML = '';

        if (!data.success || !data.prescriptions) {
            queueBody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:40px; color:#64748b;">No active entries in queue</td></tr>`;
            return;
        }

        const items = data.prescriptions;
        prescriptionsData = items.map(p => ({
            id: p.id,
            display_id: '#' + p.id.toString().padStart(4, '0'),
            patient_name: p.patient_name,
            doctor_name: 'Dr. ' + (p.doctor_name || 'Medical Team'),
            date: p.ordered_at || p.created_at,
            priority: p.urgency_level || 'Normal',
            status: p.status,
            type: 'prescription',
            raw: p
        }));

        if (prescriptionsData.length === 0) {
            queueBody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:40px; color:#64748b;">No active entries in queue</td></tr>`;
            return;
        }

        prescriptionsData.forEach(item => {
            const row = document.createElement('tr');
            const statusColor = getStatusBadgeColor(item.status);
            const priorityClass = item.priority.toLowerCase() === 'urgent' || item.priority.toLowerCase() === 'emergency' ? 'priority-urgent' : 'priority-normal';

            let buttonsHtml = `<button class="btn-view" onclick="viewDetails(${item.id}, 'prescription')">View</button>`;

            if (item.status === 'Pending' || item.status === 'sent_to_pharmacy') {
                buttonsHtml += `<button class="btn-verify" onclick="verifyPrescription(${item.id})">Verify</button>`;
            } else if (item.status === 'Verified') {
                buttonsHtml += `<button class="btn-verify" style="background-color:#0d9488;" onclick="generateBill(${item.id})">Generate Bill</button>`;
            } else if (item.status === 'Paid') {
                buttonsHtml += `<button class="btn-dispense" onclick="dispensePrescription(${item.id})">Dispense</button>`;
            } else if (item.status === 'Dispensed') {
                buttonsHtml += `<button class="btn-complete" onclick="completePrescription(${item.id})">Complete</button>`;
            } else if (item.status === 'Awaiting Payment') {
                buttonsHtml += `<span style="font-size: 0.8rem; color: #64748b; margin-left: 5px;">Waiting for Pay</span>`;
            } else if (item.status === 'Completed') {
                buttonsHtml += `<span style="font-size: 0.8rem; color: #059669; margin-left: 5px;">Completed ✅</span>`;
            }

            row.innerHTML = `
                <td style="font-weight:700; color:#1e56a0;">${item.display_id}</td>
                <td style="font-weight:600;">${item.patient_name}</td>
                <td>${item.doctor_name}</td>
                <td style="color:#64748b; font-size:0.85rem;">${formatDate(item.date)}</td>
                <td class="${priorityClass}">${item.priority}</td>
                <td><span class="badge-status" style="background-color:${statusColor}">${item.status}</span></td>
                <td>
                    <div style="display:flex; gap:5px; align-items:center;">
                        ${buttonsHtml}
                    </div>
                </td>
            `;
            queueBody.appendChild(row);
        });

    } catch (e) {
        console.error('Queue fetch failed:', e);
    }
}

function getStatusBadgeColor(status) {
    const map = {
        'Pending': '#f68338',        // Orange
        'sent_to_pharmacy': '#f68338',
        'Verified': '#0d9488',       // Teal
        'Awaiting Payment': '#0f766e', // Dark Teal
        'Paid': '#10b981',           // Emerald
        'Dispensed': '#2dd4bf',      // Cyan/Teal
        'Completed': '#059669',      // Green
        'Cancelled': '#f43f5e'       // Rose
    };
    return map[status] || '#64748b';
}

function formatDate(dateStr) {
    if (!dateStr) return '--';
    const d = new Date(dateStr);
    return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function viewDetails(id, type) {
    const item = prescriptionsData.find(i => i.id === id && i.type === type);
    if (!item) return;

    // Fill E-Prescription Form
    document.getElementById('form-patient-name').textContent = item.patient_name;
    document.getElementById('form-patient-id').textContent = '#' + (item.raw.patient_id || '----');
    document.getElementById('form-diagnosis').textContent = item.raw.diagnosis || item.raw.consultation_diagnosis || 'No diagnosis provided';

    // Attempt to parse age/gender if available (mocking if not in raw)
    document.getElementById('form-patient-age').textContent = item.raw.age || '45';
    document.getElementById('form-patient-gender').textContent = item.raw.gender || 'Male';

    const medBody = document.getElementById('form-medications-body');
    medBody.innerHTML = '';

    const meds = item.raw.items || [];
    if (meds.length === 0) {
        medBody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:20px;">No medications found</td></tr>`;
    } else {
        let grandTotal = 0;

        meds.forEach((m, index) => {
            const tr = document.createElement('tr');

            // Extract pricing info
            const price = parseFloat(m.price || 0);
            const quantity = parseInt(m.quantity || 1);
            const lineTotal = price * quantity;
            grandTotal += lineTotal;

            // Display medicine category and generic name if available
            const medInfo = m.medicine_name || 'Unknown Medicine';
            const genericInfo = m.generic_name ? `<br/><small style="color:#64748b;">(${m.generic_name})</small>` : '';

            tr.innerHTML = `
                <td><strong>${medInfo}</strong>${genericInfo}</td>
                <td>${m.dosage || '--'}</td>
                <td>${m.frequency || '--'}</td>
                <td>${m.duration || '--'}</td>
                <td style="text-align:right;">₹${price.toFixed(2)}</td>
                <td style="text-align:center;">${quantity}</td>
                <td style="text-align:right; font-weight:600;">₹${lineTotal.toFixed(2)}</td>
            `;
            medBody.appendChild(tr);
        });

        // Add grand total row
        const totalRow = document.createElement('tr');
        totalRow.style.backgroundColor = '#f1f5f9';
        totalRow.style.borderTop = '2px solid #1e56a0';
        totalRow.innerHTML = `
            <td colspan="6" style="text-align:right; padding:12px; font-weight:700; font-size:1.05rem;">Grand Total:</td>
            <td style="text-align:right; font-weight:700; font-size:1.1rem; color:#1e56a0;">₹${grandTotal.toFixed(2)}</td>
        `;
        medBody.appendChild(totalRow);

        // Display total amount if available from prescription
        if (item.raw.total_amount) {
            const billRow = document.createElement('tr');
            billRow.style.backgroundColor = '#ecfdf5';
            billRow.innerHTML = `
                <td colspan="7" style="text-align:center; padding:10px; color:#059669;">
                    <strong>Official Bill Total: ₹${parseFloat(item.raw.total_amount).toFixed(2)}</strong>
                </td>
            `;
            medBody.appendChild(billRow);
        }
    }

    // Scroll to form
    document.querySelector('.form-header').scrollIntoView({ behavior: 'smooth' });
}

async function verifyPrescription(id) {
    if (!confirm('Verify this prescription and lock for processing?')) return;
    try {
        const res = await fetch('pharmacy_api_enhanced.php?action=verify_prescription', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ prescription_id: id })
        });
        const data = await res.json();
        if (data.success) {
            alert('✅ Prescription Verified. Now click "Generate Bill".');
            fetchStats();
            fetchQueue();
        } else alert(data.error || 'Failed to verify');
    } catch (e) {
        console.error(e);
        alert('Connection error');
    }
}

async function generateBill(id) {
    if (!confirm('Calculate total and send bill to patient?')) return;
    try {
        const res = await fetch('pharmacy_api_enhanced.php?action=generate_bill', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ prescription_id: id })
        });
        const data = await res.json();
        if (data.success) {
            alert('💵 Bill Generated! Status: Awaiting Payment.');
            fetchStats();
            fetchQueue();
        } else alert(data.error || 'Failed to generate bill');
    } catch (e) {
        console.error(e);
        alert('Connection error');
    }
}

async function dispensePrescription(id) {
    if (!confirm('Release medications and deduct stock from inventory?')) return;
    try {
        const res = await fetch('pharmacy_api_enhanced.php?action=dispense_prescription', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ prescription_id: id })
        });
        const data = await res.json();
        if (data.success) {
            alert('📦 Items dispensed. Stock updated.');
            fetchStats();
            fetchQueue();
        } else alert(data.error || 'Failed to dispense');
    } catch (e) {
        console.error(e);
        alert('Connection error');
    }
}

async function completePrescription(id) {
    if (!confirm('Finalize order and move to history?')) return;
    try {
        const res = await fetch('pharmacy_api_enhanced.php?action=complete_prescription', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ prescription_id: id })
        });
        const data = await res.json();
        if (data.success) {
            alert('✅ Order completed successfully.');
            fetchStats();
            fetchQueue();
        } else alert(data.error || 'Failed to complete');
    } catch (e) {
        console.error(e);
        alert('Connection error');
    }
}

// ==================================================
// Medicine Inventory Functions
// ==================================================

let allMedicines = [];
let filteredMedicines = [];

async function fetchMedicinesInventory() {
    try {
        const search = document.getElementById('medicineSearchInput')?.value || '';
        const category = document.getElementById('categoryFilter')?.value || '';
        const lowStockOnly = document.getElementById('lowStockFilter')?.checked || false;

        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (category) params.append('category', category);
        if (lowStockOnly) params.append('low_stock_only', 'true');

        const response = await fetch(`pharmacy_api_enhanced.php?action=get_medicines_inventory&${params.toString()}`);
        const data = await response.json();

        if (data.success) {
            allMedicines = data.medicines;
            filteredMedicines = data.medicines;

            // Populate category filter dropdown
            if (data.categories && data.categories.length > 0) {
                const categoryFilter = document.getElementById('categoryFilter');
                const currentValue = categoryFilter.value;
                categoryFilter.innerHTML = '<option value="">All Categories</option>';
                data.categories.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat;
                    option.textContent = cat;
                    if (cat === currentValue) option.selected = true;
                    categoryFilter.appendChild(option);
                });
            }

            renderMedicinesTable(filteredMedicines);
        } else {
            console.error('Failed to fetch medicines:', data.error);
        }
    } catch (e) {
        console.error('Medicine fetch error:', e);
    }
}

function renderMedicinesTable(medicines) {
    const tbody = document.getElementById('medicineInventoryBody');

    if (!medicines || medicines.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:40px; color:#64748b;">No medicines found</td></tr>';
        return;
    }

    tbody.innerHTML = '';

    medicines.forEach(med => {
        const row = document.createElement('tr');

        // Determine stock status
        const isLowStock = med.stock <= (med.low_stock_threshold || 10);
        const stockBadgeColor = isLowStock ? '#f43f5e' : '#10b981';
        const stockBadgeText = isLowStock ? 'Low Stock' : 'In Stock';

        row.innerHTML = `
            <td style="font-weight:600;">${med.name}</td>
            <td style="color:#64748b; font-size:0.9rem;">${med.generic_name || '--'}</td>
            <td><span style="background:#f1f5f9; padding:4px 10px; border-radius:4px; font-size:0.85rem;">${med.category || '--'}</span></td>
            <td style="font-weight:600;">₹${parseFloat(med.price).toFixed(2)}</td>
            <td>
                <input type="number" 
                       id="stock-${med.id}" 
                       value="${med.stock}" 
                       min="0" 
                       style="width:80px; padding:6px; border:1px solid #ddd; border-radius:4px; text-align:center;"
                       onchange="updateStockValue(${med.id})">
            </td>
            <td><span class="badge-status" style="background-color:${stockBadgeColor}">${stockBadgeText}</span></td>
            <td>
                <button class="btn" style="background:#0d9488; color:white; padding:6px 15px; font-size:0.85rem;" onclick="updateMedicineStock(${med.id})">
                    <i class="fas fa-save"></i> Update
                </button>
            </td>
        `;

        tbody.appendChild(row);
    });
}

async function updateMedicineStock(medicineId) {
    const stockInput = document.getElementById(`stock-${medicineId}`);
    const newStock = parseInt(stockInput.value);

    if (newStock < 0) {
        alert('Stock cannot be negative');
        return;
    }

    if (!confirm(`Update stock for this medicine to ${newStock} units?`)) {
        return;
    }

    try {
        const response = await fetch('pharmacy_api_enhanced.php?action=update_medicine_stock', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                medicine_id: medicineId,
                new_stock: newStock
            })
        });

        const data = await response.json();

        if (data.success) {
            alert('✅ Stock updated successfully!');
            // Refresh inventory and stats
            await fetchMedicinesInventory();
            await fetchStats();
        } else {
            alert('Error: ' + (data.error || 'Failed to update stock'));
            // Reset input to original value
            const originalMed = allMedicines.find(m => m.id === medicineId);
            if (originalMed) stockInput.value = originalMed.stock;
        }
    } catch (e) {
        console.error(e);
        alert('Connection error. Please try again.');
    }
}

function updateStockValue(medicineId) {
    // This is called when the input value changes
    // We don't auto-save, user must click Update button
}

function handleMedicineFilter() {
    fetchMedicinesInventory();
}

// ==================================================
// Add New Medicine Functions
// ==================================================

function openAddMedicineModal() {
    document.getElementById('addMedicineModal').style.display = 'flex';
    // Clear all form fields
    document.getElementById('newMedicineName').value = '';
    document.getElementById('newMedicineGeneric').value = '';
    document.getElementById('newMedicineCategory').value = '';
    document.getElementById('newMedicineUnit').value = 'tablet';
    document.getElementById('newMedicinePrice').value = '';
    document.getElementById('newMedicineStock').value = '';
    document.getElementById('newMedicineLowStock').value = '10';
    document.getElementById('newMedicineManufacturer').value = '';
    document.getElementById('newMedicineDescription').value = '';
}

function closeAddMedicineModal() {
    document.getElementById('addMedicineModal').style.display = 'none';
}

async function submitNewMedicine() {
    // Get form values
    const name = document.getElementById('newMedicineName').value.trim();
    const genericName = document.getElementById('newMedicineGeneric').value.trim();
    const category = document.getElementById('newMedicineCategory').value.trim();
    const unit = document.getElementById('newMedicineUnit').value;
    const price = parseFloat(document.getElementById('newMedicinePrice').value);
    const stock = parseInt(document.getElementById('newMedicineStock').value);
    const lowStockThreshold = parseInt(document.getElementById('newMedicineLowStock').value);
    const manufacturer = document.getElementById('newMedicineManufacturer').value.trim();
    const description = document.getElementById('newMedicineDescription').value.trim();

    // Validate required fields
    if (!name) {
        alert('Medicine name is required');
        return;
    }

    if (isNaN(price) || price < 0) {
        alert('Please enter a valid price (must be 0 or greater)');
        return;
    }

    if (isNaN(stock) || stock < 0) {
        alert('Please enter a valid stock quantity (must be 0 or greater)');
        return;
    }

    try {
        const response = await fetch('pharmacy_api_enhanced.php?action=add_new_medicine', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name: name,
                generic_name: genericName,
                category: category,
                unit: unit,
                price: price,
                stock: stock,
                low_stock_threshold: lowStockThreshold,
                manufacturer: manufacturer,
                description: description
            })
        });

        const data = await response.json();

        if (data.success) {
            alert('✅ Medicine added successfully!');
            closeAddMedicineModal();
            // Refresh the medicine inventory and stats
            await fetchMedicinesInventory();
            await fetchStats();
        } else {
            alert('Error: ' + (data.error || 'Failed to add medicine'));
        }
    } catch (e) {
        console.error(e);
        alert('Connection error. Please try again.');
    }
}

// Close modal when clicking outside
document.addEventListener('click', function (event) {
    const modal = document.getElementById('addMedicineModal');
    if (modal && event.target === modal) {
        closeAddMedicineModal();
    }
});

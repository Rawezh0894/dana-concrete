// Function to populate edit modal with note data
async function populateEditModal(noteId) {
    try {
        // Get note data from the current notes array
        const note = allNotes.find(n => n.id == noteId);
        
        if (!note) {
            showAlert('error', 'زانیاری تێبینی نەدۆزرایەوە');
            return;
        }
        
        const noteData = {
            id: note.id,
            date: note.date || '',
            time: note.time || '',
            customer_name: note.customer_name || '',
            location: note.location || '',
            recipient: note.recipient || '',
            meter_amount: note.meter_amount || '',
            formula_name: note.formula_name || '',
            mixer_car_name: note.mixer_car_name || '',
            mixer_driver_name: note.mixer_driver_name || '',
            pump_car_name: note.pump_car_name || '',
            pump_driver_name: note.pump_driver_name || ''
        };
        
        if (!noteData) {
            showAlert('error', 'زانیاری تێبینی نەدۆزرایەوە');
            return;
        }
        
        // Populate the edit form
        document.getElementById('edit_note_id').value = noteData.id;
        document.getElementById('edit_date').value = noteData.date;
        document.getElementById('edit_time').value = noteData.time;
        document.getElementById('edit_location').value = noteData.location;
        document.getElementById('edit_recipient').value = noteData.recipient;
        document.getElementById('edit_meter_amount').value = noteData.meter_amount;
        
        // Set customer dropdown with Select2
        const customerSelect = document.getElementById('edit_customer_id');
        for (let option of customerSelect.options) {
            if (option.textContent.trim() === noteData.customer_name.trim()) {
                option.selected = true;
                break;
            }
        }
        $(customerSelect).trigger('change');
        
        // Set formula dropdown
        const formulaSelect = document.getElementById('edit_formula_id');
        for (let option of formulaSelect.options) {
            if (option.textContent.trim() === noteData.formula_name.trim()) {
                option.selected = true;
                break;
            }
        }
        
        // Set mixer car dropdown
        const mixerCarSelect = document.getElementById('edit_mixer_car_id');
        for (let option of mixerCarSelect.options) {
            if (option.textContent.trim() === noteData.mixer_car_name.trim()) {
                option.selected = true;
                break;
            }
        }
        
        // Set mixer driver dropdown
        const mixerDriverSelect = document.getElementById('edit_mixer_driver_id');
        for (let option of mixerDriverSelect.options) {
            if (option.textContent.trim() === noteData.mixer_driver_name.trim()) {
                option.selected = true;
                break;
            }
        }
        
        // Set pump car dropdown
        const pumpCarSelect = document.getElementById('edit_pump_car_id');
        for (let option of pumpCarSelect.options) {
            if (option.textContent.trim() === noteData.pump_car_name.trim()) {
                option.selected = true;
                break;
            }
        }
        
        // Set pump driver dropdown
        const pumpDriverSelect = document.getElementById('edit_pump_driver_id');
        for (let option of pumpDriverSelect.options) {
            if (option.textContent.trim() === noteData.pump_driver_name.trim()) {
                option.selected = true;
                break;
            }
        }
        
        // Show the edit modal
        const editModal = new bootstrap.Modal(document.getElementById('editNoteModal'));
        editModal.show();
        
    } catch (error) {
        console.error('Error populating edit modal:', error);
        showAlert('error', 'هەڵەیەک لە بارکردنی زانیاریەکان هەیە');
    }
}

// Handle edit button clicks
document.addEventListener('click', function(e) {
    if (e.target.closest('.edit-note')) {
        const noteId = e.target.closest('.edit-note').getAttribute('data-id');
        populateEditModal(noteId);
    }
});

// Handle edit form submission
document.addEventListener('DOMContentLoaded', function() {
    const editNoteForm = document.getElementById('editNoteForm');
    if (!editNoteForm) return;

    // Flag to prevent multiple submissions
    let isSubmitting = false;
    
    // Initialize Select2 for customer dropdown only in the edit modal
    if ($('#editNoteModal').length > 0) {
        enableSelect2('#edit_customer_id', '#editNoteModal');
        
        // Helper function to safely destroy Select2
        function safeDestroySelect2(selector) {
            try {
                const element = $(selector);
                if (element.length > 0 && element.hasClass('select2-hidden-accessible')) {
                    element.select2('destroy');
                }
            } catch(e) {
                // Silently ignore errors
            }
        }
        
        // Destroy any existing Select2 instances on other dropdowns
        safeDestroySelect2('#edit_formula_id');
        safeDestroySelect2('#edit_mixer_car_id');
        safeDestroySelect2('#edit_mixer_driver_id');
        safeDestroySelect2('#edit_pump_car_id');
        safeDestroySelect2('#edit_pump_driver_id');
    }

    editNoteForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Prevent multiple submissions
        if (isSubmitting) {
            showAlert('warning', 'تکایە چاوەڕوان بە...');
            return;
        }
        
        const formData = new FormData(editNoteForm);
        
        // Handle optional fields - convert empty strings to null
        const optionalFields = ['edit_mixer_car_id', 'edit_mixer_driver_id', 'edit_pump_car_id', 'edit_pump_driver_id'];
        optionalFields.forEach(field => {
            const value = formData.get(field);
            if (value === '') {
                formData.set(field, 'null');
            }
        });
        
        // Validate required fields
        const requiredFields = ['edit_date', 'edit_time', 'edit_customer_id', 'edit_location', 'edit_meter_amount', 'edit_formula_id'];
        for (let field of requiredFields) {
            if (!formData.get(field)) {
                showAlert('error', 'تکایە هەموو خانە پێویستەکان پڕبکەرەوە');
                return;
            }
        }

        // Validate meter amount
        const meterAmount = parseFloat(formData.get('edit_meter_amount'));
        if (isNaN(meterAmount) || meterAmount <= 0) {
            showAlert('error', 'بڕی مەتر دەبێت ژمارەیەکی دروست بێت');
            return;
        }

        // Set submitting flag and disable submit button
        isSubmitting = true;
        const submitBtn = editNoteForm.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...';

        try {
            const response = await fetch('../process/notes/update.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showAlert('success', result.message);
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('editNoteModal'));
                if (modal) {
                    modal.hide();
                }
                
                // Reload notes table
                if (window.reloadNotes) {
                    window.reloadNotes();
                }
                
                // Dispatch custom event for real-time badge update
                document.dispatchEvent(new CustomEvent('noteMarkedAsRead'));
                
                // Also update badge immediately if we're on the concrete receipts page
                if (window.updateUnreadNotesBadge) {
                    window.updateUnreadNotesBadge();
                }
            } else {
                showAlert('error', result.error || 'هەڵەیەک ڕویدا');
            }
        } catch (error) {
            console.error('Error updating note:', error);
            showAlert('error', 'هەڵەیەک لە پەیوەندی بە سێرڤەرەوە هەیە');
        } finally {
            // Reset submitting flag and enable submit button
            isSubmitting = false;
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    });
});

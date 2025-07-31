document.addEventListener('DOMContentLoaded', function() {
    const addNoteForm = document.getElementById('addNoteForm');
    if (!addNoteForm) return;

    // Set default date to tomorrow (سبەینێ)
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const tomorrowFormatted = tomorrow.toISOString().split('T')[0];
    document.getElementById('date').value = tomorrowFormatted;

    // Flag to prevent multiple submissions
    let isSubmitting = false;

    // Initialize Select2 for customer dropdown only in the add modal
    if ($('#addNoteModal').length > 0) {
        enableSelect2('#customer_id', '#addNoteModal');
        
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
        safeDestroySelect2('#formula_id');
        safeDestroySelect2('#mixer_car_id');
        safeDestroySelect2('#mixer_driver_id');
        safeDestroySelect2('#pump_car_id');
        safeDestroySelect2('#pump_driver_id');
    }

    addNoteForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log('Form submission started');
        
        // Prevent multiple submissions
        if (isSubmitting) {
            showAlert('warning', 'تکایە چاوەڕوان بە...');
            return;
        }
        
        const formData = new FormData(addNoteForm);
        
        // Handle optional fields - convert empty strings to null
        const optionalFields = ['mixer_car_id', 'mixer_driver_id', 'pump_car_id', 'pump_driver_id'];
        optionalFields.forEach(field => {
            const value = formData.get(field);
            if (value === '') {
                formData.set(field, 'null');
            }
        });
        
        // Validate required fields
        const requiredFields = ['date', 'time', 'customer_id', 'location', 'meter_amount', 'formula_id'];
        for (let field of requiredFields) {
            const value = formData.get(field);
            console.log(`Field ${field}:`, value);
            if (!value) {
                showAlert('error', `تکایە خانەی ${field} پڕبکەرەوە`);
                return;
            }
        }

        // Validate meter amount
        const meterAmount = parseFloat(formData.get('meter_amount'));
        if (isNaN(meterAmount) || meterAmount <= 0) {
            showAlert('error', 'بڕی مەتر دەبێت ژمارەیەکی دروست بێت');
            return;
        }

        // Set submitting flag and disable submit button
        isSubmitting = true;
        const submitBtn = addNoteForm.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...';

        try {
            console.log('Submitting form data:', Object.fromEntries(formData));
            
            const response = await fetch('../process/notes/add.php', {
                method: 'POST',
                body: formData
            });

            console.log('Response status:', response.status);
            const responseText = await response.text();
            console.log('Response text:', responseText);
            
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                showAlert('error', 'هەڵەیەک لە وەڵامەکە هەیە');
                return;
            }

            if (result.success) {
                showAlert('success', result.message);
                addNoteForm.reset();
                document.getElementById('date').value = tomorrowFormatted; // Reset to tomorrow's date
                
                // Reset Select2 dropdowns
                $('#customer_id').val('').trigger('change');
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addNoteModal'));
                if (modal) {
                    modal.hide();
                }
                
                // Reload notes table
                if (window.reloadNotes) {
                    window.reloadNotes();
                }
                
                // Dispatch custom event for real-time badge update
                document.dispatchEvent(new CustomEvent('noteAdded'));
                
                // Also update badge immediately if we're on the concrete receipts page
                if (window.updateUnreadNotesBadge) {
                    window.updateUnreadNotesBadge();
                }
                
                // Play notification sound if we're on the concrete receipts page
                if (window.playNotificationSound) {
                    // Force enable audio for new note notifications
                    if (window.forceEnableAudio) {
                        window.forceEnableAudio();
                    }
                    
                    setTimeout(() => {
                        window.playNotificationSound();
                    }, 500); // Small delay to ensure badge is updated first
                }
                
                // Dispatch custom event for real-time sound notification
                document.dispatchEvent(new CustomEvent('noteAddedWithSound'));
            } else {
                showAlert('error', result.error || 'هەڵەیەک ڕویدا');
            }
        } catch (error) {
            console.error('Error adding note:', error);
            showAlert('error', 'هەڵەیەک لە پەیوەندی بە سێرڤەرەوە هەیە');
        } finally {
            // Reset submitting flag and enable submit button
            isSubmitting = false;
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    });
});

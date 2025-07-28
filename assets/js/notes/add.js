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

    addNoteForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Prevent multiple submissions
        if (isSubmitting) {
            showAlert('warning', 'تکایە چاوەڕوان بە...');
            return;
        }
        
        const formData = new FormData(addNoteForm);
        
        // Validate required fields
        const requiredFields = ['date', 'time', 'customer_id', 'location', 'meter_amount', 'formula_id'];
        for (let field of requiredFields) {
            if (!formData.get(field)) {
                showAlert('error', 'تکایە هەموو خانە پێویستەکان پڕبکەرەوە');
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
            const response = await fetch('../process/notes/add.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showAlert('success', result.message);
                addNoteForm.reset();
                document.getElementById('date').value = tomorrowFormatted; // Reset to tomorrow's date
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addNoteModal'));
                if (modal) {
                    modal.hide();
                }
                
                // Reload notes table
                if (window.reloadNotes) {
                    window.reloadNotes();
                }
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

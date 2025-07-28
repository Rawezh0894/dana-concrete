document.addEventListener('DOMContentLoaded', function() {
    const addNoteForm = document.getElementById('addNoteForm');
    if (!addNoteForm) return;

    // Set default date to tomorrow (سبەینێ)
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const tomorrowFormatted = tomorrow.toISOString().split('T')[0];
    document.getElementById('date').value = tomorrowFormatted;

    addNoteForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
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
        }
    });
});

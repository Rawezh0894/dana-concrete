// Prevent double submission flag
let isDriverSubmitting = false;

// Check if addDriverModal exists (not the one in driversManagementModal)
const addDriverModal = document.getElementById('addDriverModal');
if (addDriverModal) {
    // Remove existing event listeners first
    const addDriverForm = document.getElementById('addDriverForm');
    if (addDriverForm) {
        // Use jQuery to remove any existing handlers
        if (typeof $ !== 'undefined') {
            $(addDriverForm).off('submit');
        }
        
        // Add new event listener with double submission prevention
        addDriverForm.onsubmit = async function(e) {
            e.preventDefault();
            
            // Prevent double submission
            if (isDriverSubmitting) {
                return false;
            }
            
            isDriverSubmitting = true;
            const form = e.target;
            const formData = new FormData(form);
            
            try {
                const res = await fetch('../process/location_driver/add_driver.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                isDriverSubmitting = false; // Reset flag
                
                if (data.success) {
                    // زیادکردنی option ـی نوێ بۆ select
                    const select = document.getElementById('driver_id');
                    if (select) {
                        const opt = document.createElement('option');
                        opt.value = data.id;
                        opt.textContent = data.name;
                        select.appendChild(opt);
                        select.value = data.id;
                    }
                    Swal.fire('سەرکەوتوو!', 'شۆفێر زیادکرا', 'success');
                    form.reset();
                    var modal = bootstrap.Modal.getInstance(document.getElementById('addDriverModal'));
                    if (modal) {
                        modal.hide();
                    }
                } else {
                    Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
                }
            } catch (error) {
                isDriverSubmitting = false; // Reset flag on error
                Swal.fire('هەڵە!', 'هەڵەیەک ڕویدا', 'error');
            }
        };
    }
}

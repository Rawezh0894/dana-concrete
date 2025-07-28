function swalAlert(title, message, icon) {
    Swal.fire({
        icon: icon, // 'success', 'error', 'warning', 'info', 'question'
        title: title,
        text: message,
        confirmButtonText: 'باشە'
    });
}

// Add showAlert function for compatibility
function showAlert(type, message) {
    let icon = 'info';
    let title = 'زانیاری';
    
    switch(type) {
        case 'success':
            icon = 'success';
            title = 'سەرکەوتوو';
            break;
        case 'error':
            icon = 'error';
            title = 'هەڵە';
            break;
        case 'warning':
            icon = 'warning';
            title = 'ئاگادارکردنەوە';
            break;
        case 'info':
            icon = 'info';
            title = 'زانیاری';
            break;
    }
    
    Swal.fire({
        icon: icon,
        title: title,
        text: message,
        confirmButtonText: 'باشە'
    });
}

// Utility functions for preventing multiple submissions
const SubmissionManager = {
    // Store submission states for different forms/actions
    states: new Map(),
    
    // Check if a form/action is currently submitting
    isSubmitting: function(key) {
        return this.states.get(key) || false;
    },
    
    // Set submission state
    setSubmitting: function(key, value = true) {
        this.states.set(key, value);
    },
    
    // Reset submission state
    resetSubmitting: function(key) {
        this.states.set(key, false);
    },
    
    // Prevent multiple submissions for form submit
    preventMultipleSubmit: function(formElement, submitFunction) {
        const formId = formElement.id || 'form_' + Math.random().toString(36).substr(2, 9);
        
        formElement.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Check if already submitting
            if (SubmissionManager.isSubmitting(formId)) {
                showAlert('warning', 'تکایە چاوەڕوان بە...');
                return;
            }
            
            // Set submitting state
            SubmissionManager.setSubmitting(formId, true);
            
            // Get submit button and store original state
            const submitBtn = formElement.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
            const originalBtnDisabled = submitBtn ? submitBtn.disabled : false;
            
            // Disable submit button and show loading state
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...';
            }
            
            try {
                // Execute the submit function
                await submitFunction(e, formElement);
            } catch (error) {
                console.error('Form submission error:', error);
                showAlert('error', 'هەڵەیەک لە پەیوەندی بە سێرڤەرەوە هەیە');
            } finally {
                // Reset submitting state
                SubmissionManager.resetSubmitting(formId);
                
                // Restore submit button state
                if (submitBtn) {
                    submitBtn.disabled = originalBtnDisabled;
                    submitBtn.innerHTML = originalBtnText;
                }
            }
        });
    },
    
    // Prevent multiple clicks for action buttons
    preventMultipleClick: function(buttonElement, actionFunction, actionKey = null) {
        const key = actionKey || buttonElement.id || 'btn_' + Math.random().toString(36).substr(2, 9);
        
        buttonElement.addEventListener('click', async function(e) {
            e.preventDefault();
            
            // Check if already processing
            if (SubmissionManager.isSubmitting(key)) {
                showAlert('warning', 'تکایە چاوەڕوان بە...');
                return;
            }
            
            // Set processing state
            SubmissionManager.setSubmitting(key, true);
            
            // Store original button state
            const originalBtnText = buttonElement.innerHTML;
            const originalBtnDisabled = buttonElement.disabled;
            
            // Disable button and show loading state
            buttonElement.disabled = true;
            buttonElement.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...';
            
            try {
                // Execute the action function
                await actionFunction(e, buttonElement);
            } catch (error) {
                console.error('Action error:', error);
                showAlert('error', 'هەڵەیەک لە پەیوەندی بە سێرڤەرەوە هەیە');
            } finally {
                // Reset processing state
                SubmissionManager.resetSubmitting(key);
                
                // Restore button state
                buttonElement.disabled = originalBtnDisabled;
                buttonElement.innerHTML = originalBtnText;
            }
        });
    }
}; 
// Edit button click is handled in ag_grid_customer.js (avoid duplicate handlers)

$(document).ready(function () {
// Handle update submit
    const editCustomerForm = $('#editCustomerForm');
    if (editCustomerForm.length) {
        // Multiple submission prevention flag
        let isUpdating = false;
        
        editCustomerForm.on('submit', function (e) {
            e.preventDefault();
            
            // Prevent multiple submissions
            if (isUpdating) {
                showAlert('warning', 'تکایە چاوەڕوان بە...');
                return false;
            }
            
            // Set updating flag and disable submit button
            isUpdating = true;
            const submitBtn = $(this).find('button[type="submit"]');
            const originalBtnText = submitBtn.html();
            submitBtn.prop('disabled', true);
            submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');
            
            const formData = new FormData(this);
            
            $.ajax({
                url: '../process/customer/update_customer.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(data) {
                    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editCustomerModal'));
                    if (data.success) {
                        modal.hide();
                        if (typeof reloadCustomers === 'function') {
                            reloadCustomers();
                        } else if (typeof loadCustomers === 'function') {
                        loadCustomers();
                        }
                        // Refresh summary stats
                        if (typeof loadSummaryStats === 'function') loadSummaryStats();
                        swalAlert('سەرکەوتوو', 'زانیاری کڕیار نوێکرایەوە!', 'success');
                    } else {
                        swalAlert('هەڵە', data.message || 'هەڵەیەک هەیە', 'error');
                    }
                },
                error: function() {
                    swalAlert('هەڵە', 'هەڵەیەک هەیە لە پەیوەندیدا.', 'error');
                },
                complete: function() {
                    // Reset updating flag and restore submit button
                    isUpdating = false;
                    submitBtn.prop('disabled', false);
                    submitBtn.html(originalBtnText);
                }
            });
        });
    }
    
    // Handle currency type change for edit modal opening debt
    const editOpeningDebtUsd = $('#editCustomerOpeningDebtUsd');
    const editOpeningDebtIqd = $('#editCustomerOpeningDebtIqd');
    
    if (editOpeningDebtUsd.length && editOpeningDebtIqd.length) {
        editOpeningDebtUsd.on('input', function() {
            if ($(this).val() > 0) {
                editOpeningDebtIqd.val('');
                editOpeningDebtIqd.prop('disabled', true);
            } else {
                editOpeningDebtIqd.prop('disabled', false);
            }
        });
        
        editOpeningDebtIqd.on('input', function() {
            if ($(this).val() > 0) {
                editOpeningDebtUsd.val('');
                editOpeningDebtUsd.prop('disabled', true);
            } else {
                editOpeningDebtUsd.prop('disabled', false);
            }
        });
    }
    });

// Handle delete button click for customers

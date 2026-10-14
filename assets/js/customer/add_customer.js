$(document).ready(function () {
    const addCustomerForm = $('#addCustomerForm');
    if (addCustomerForm.length) {
        // Multiple submission prevention flag
        let isSubmitting = false;
        
        addCustomerForm.on('submit', function (e) {
            e.preventDefault();
            
            // Prevent multiple submissions
            if (isSubmitting) {
                showAlert('warning', 'تکایە چاوەڕوان بە...');
                return false;
            }
            
            // Set submitting flag and disable submit button
            isSubmitting = true;
            const submitBtn = $(this).find('button[type="submit"]');
            const originalBtnText = submitBtn.html();
            submitBtn.prop('disabled', true);
            submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');
            
            // Always send is_company (checkbox alone is omitted when unchecked)
            const isCompany = $('#customer_is_company').is(':checked') ? '1' : '0';
            $('#customer_is_company_value').val(isCompany);
            const formData = new FormData(this);
            formData.set('is_company', isCompany);
            
            $.ajax({
                url: '../process/customer/add_customer.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(data) {
                    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addCustomerModal'));
                    if (data.success) {
                        addCustomerForm[0].reset();
                        $('#customer_is_recipient').prop('checked', false);
                        $('#customer_is_company').prop('checked', false);
                        $('#customer_is_company_value').val('0');
                        modal.hide();
                        if (typeof reloadCustomers === 'function') {
                            reloadCustomers();
                        } else if (typeof loadCustomers === 'function') {
                        loadCustomers();
                        }
                        // Refresh summary stats
                        if (typeof loadSummaryStats === 'function') loadSummaryStats();
                        swalAlert('سەرکەوتوو', 'کڕیار بەسەرکەوتوویی زیادکرا!', 'success');
                    } else {
                        swalAlert('هەڵە', data.message || 'هەڵەیەک هەیە', 'error');
                    }
                },
                error: function() {
                    swalAlert('هەڵە', 'هەڵەیەک هەیە لە پەیوەندیدا.', 'error');
                },
                complete: function() {
                    // Reset submitting flag and restore submit button
                    isSubmitting = false;
                    submitBtn.prop('disabled', false);
                    submitBtn.html(originalBtnText);
                }
            });
        });
    }
});

// Handle currency type change for opening debt
function handleOpeningDebtCurrencyChange() {
    const openingDebtUsd = $('#customer_opening_debt_usd');
    const openingDebtIqd = $('#customer_opening_debt_iqd');
    
    if (openingDebtUsd.length && openingDebtIqd.length) {
        openingDebtUsd.on('input', function() {
            if ($(this).val() > 0) {
                openingDebtIqd.val('');
                openingDebtIqd.prop('disabled', true);
            } else {
                openingDebtIqd.prop('disabled', false);
            }
        });
        
        openingDebtIqd.on('input', function() {
            if ($(this).val() > 0) {
                openingDebtUsd.val('');
                openingDebtUsd.prop('disabled', true);
            } else {
                openingDebtUsd.prop('disabled', false);
            }
                });
            }
}

// Handle currency type change for edit modal opening debt
function handleEditOpeningDebtCurrencyChange() {
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
}

// Initialize currency handling
$(document).ready(function() {
    handleOpeningDebtCurrencyChange();
    handleEditOpeningDebtCurrencyChange();
    
    // Keep hidden is_company in sync with the switch
    $('#customer_is_company').on('change', function () {
        $('#customer_is_company_value').val(this.checked ? '1' : '0');
    });
    $('#editCustomerIsCompany').on('change', function () {
        $('#editCustomerIsCompanyValue').val(this.checked ? '1' : '0');
    });

    // Add form: company switch always starts off
    $('#customer_is_company').prop('checked', false);
    $('#customer_is_company_value').val('0');

    // Reset add form when modal is shown
    $('#addCustomerModal').on('show.bs.modal', function() {
        $('#addCustomerForm')[0].reset();
        $('#customer_opening_debt_usd, #customer_opening_debt_iqd').prop('disabled', false);
        $('#customer_is_recipient').prop('checked', false);
        $('#customer_is_company').prop('checked', false);
        $('#customer_is_company_value').val('0');
    });
    
    // Edit form: only unlock debt fields on show — do NOT clear is_company here
    $('#editCustomerModal').on('show.bs.modal', function() {
        $('#editCustomerOpeningDebtUsd, #editCustomerOpeningDebtIqd').prop('disabled', false);
    });

    // Clear flags only after modal is fully closed
    $('#editCustomerModal').on('hidden.bs.modal', function() {
        $('#editCustomerIsRecipient').prop('checked', false);
        $('#editCustomerIsCompany').prop('checked', false);
        $('#editCustomerIsCompanyValue').val('0');
    });
});

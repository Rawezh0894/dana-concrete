// Handle edit button click for customers
$(document).on('click', '.edit-customer-btn', function() {
    const btn = $(this);
    const id = btn.data('id');
    
    $.get(`../process/customer/get_customer.php?id=${id}`, function(response) {
        if (response.success && response.data) {
            const customer = response.data;
            $('#editCustomerModal').modal('show');
            $('#editCustomerId').val(customer.id);
            $('#editCustomerName').val(customer.name || '');
            $('#editCustomerMobile1').val(customer.mobile1 || '');
            $('#editCustomerMobile2').val(customer.mobile2 || '');
            
            // Handle numeric values properly
            const usdValue = parseFloat(customer.opening_debt_usd || 0);
            const iqdValue = parseFloat(customer.opening_debt_iqd || 0);
            
            $('#editCustomerOpeningDebtUsd').val(usdValue > 0 ? usdValue : '');
            $('#editCustomerOpeningDebtIqd').val(iqdValue > 0 ? iqdValue : '');
            
            // Set is_recipient checkbox
            const isRecipient = parseInt(customer.is_recipient || 0);
            $('#editCustomerIsRecipient').prop('checked', isRecipient === 1);
            
            // Enable/disable fields based on values
            if (usdValue > 0) {
                $('#editCustomerOpeningDebtIqd').prop('disabled', true);
            } else if (iqdValue > 0) {
                $('#editCustomerOpeningDebtUsd').prop('disabled', true);
            } else {
                $('#editCustomerOpeningDebtUsd, #editCustomerOpeningDebtIqd').prop('disabled', false);
            }
        } else {
            console.error('Error loading customer data:', response.error);
            swalAlert('هەڵە', 'هەڵەیەک هەیە لە وەرگرتنی داتای کڕیار', 'error');
        }
    }, 'json').fail(function() {
        console.error('Error:', 'Network error');
        swalAlert('هەڵە', 'هەڵەیەک هەیە لە پەیوەندیدا', 'error');
    });
});

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
                        loadCustomers();
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

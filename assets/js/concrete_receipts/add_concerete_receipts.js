// Multiple submission prevention flag
let submitting = false;
$(document).ready(function() {
    // Restore form data from localStorage (only if not coming from notes)
    const storageKey = 'addConcreteReceiptFormData';
    const $form = $('#addConcreteReceiptForm');
    
    // Check if we're coming from notes page or returning from printing
    const urlParams = new URLSearchParams(window.location.search);
    const isFromNotes = urlParams.get('open_add') === '1' && urlParams.get('preserve_data') !== '1';
    const isReturningFromPrint = urlParams.get('preserve_data') === '1';
    
    // Make isFromNotes available globally for this script
    window.isFromNotes = isFromNotes;
    
    if (!isFromNotes) {
        // Restore data if not coming from notes (including when returning from print)
        const saved = localStorage.getItem(storageKey);
        if (saved) {
            try {
                const data = JSON.parse(saved);
                Object.entries(data).forEach(([k, v]) => {
                    if (k === 'receipt_number') return; // never restore receipt_number
                    const $el = $form.find(`[name="${k}"]`);
                    if ($el.is('select')) {
                        $el.val(v).trigger('change');
                    } else {
                        $el.val(v);
                    }
                });
            } catch(e) {}
        }
    } else {
        // Clear localStorage if coming from notes (not from print)
        localStorage.removeItem(storageKey);
    }
    // Save form data on change (only if not coming from notes)
    $form.on('input change', 'input, select, textarea', function() {
        // Don't save data if we're coming from notes
        if (window.isFromNotes) {
            return;
        }
        
        const data = {};
        $form.serializeArray().forEach(({name, value}) => {
            if (name !== 'receipt_number') data[name] = value;
        });
        localStorage.setItem(storageKey, JSON.stringify(data));
    });

    $('#addConcreteReceiptForm').on('submit', async function(e) {
        e.preventDefault();
        
        // Prevent multiple submissions
        if (submitting) {
            showAlert('warning', 'تکایە چاوەڕوان بە...');
            return false;
        }
        
        // Set submitting flag and disable submit button
        submitting = true;
        const submitBtn = $(this).find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        submitBtn.prop('disabled', true);
        submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');
        
        try {
            const formData = new FormData(this);
            const res = await fetch('../process/concrete_receipts/add_concerete_receipts.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'پسوڵە زیادکرا',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1000,
                    timerProgressBar: true,
                    didClose: () => {
                        if (data.id) {
                            // Save all fields except meter_amount, mixer_car_id, mixer_driver_id (only if not from notes)
                            if (!window.isFromNotes) {
                                const allData = {};
                                $form.serializeArray().forEach(({name, value}) => {
                                    if (!["meter_amount","mixer_car_id","mixer_driver_id","receipt_number"].includes(name)) {
                                        allData[name] = value;
                                    }
                                });
                                localStorage.setItem(storageKey, JSON.stringify(allData));
                            } else {
                                // Reset window.isFromNotes to false after saving
                                window.isFromNotes = false;
                            }
                            // Open receipt in new tab for printing
                            window.open('../pages/central_receipts.php?id=' + data.id + '&auto_print=1&return_to_form=1', '_blank');
                        }
                        // Always reset form and close modal
                        $('#addConcreteReceiptForm')[0].reset();
                        $('#addConcreteReceiptModal').modal('hide');
                        
                        // Reload data to show the new receipt
                        if (window.reloadConcreteReceipts) window.reloadConcreteReceipts();
                        if (window.reloadConcreteReceiptsSummary) window.reloadConcreteReceiptsSummary();
                        
                        // Clear localStorage if coming from notes (but not when returning from print)
                        const urlParams = new URLSearchParams(window.location.search);
                        const preserveData = urlParams.get('preserve_data') === '1';
                        if (window.isFromNotes && !preserveData) {
                            localStorage.removeItem(storageKey);
                        }
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: data.message || 'هەڵەیەک ڕویدا لە زیادکردنی پسوڵە!'
                });
            }
        } catch (error) {
            console.error('Error adding concrete receipt:', error);
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'هەڵەیەک لە پەیوەندی بە سێرڤەرەوە هەیە'
            });
        } finally {
            // Reset submitting flag and restore submit button
            submitting = false;
            submitBtn.prop('disabled', false);
            submitBtn.html(originalBtnText);
        }
    });

    $('#addConcreteReceiptModal').on('show.bs.modal', function() {
        // Check if we're coming from notes page (use global variable)
        const isFromNotes = window.isFromNotes;
        const urlParams = new URLSearchParams(window.location.search);
        const preserveData = urlParams.get('preserve_data') === '1';
        
        // Clear form if coming from notes (but not when preserving data)
        if (window.isFromNotes && !preserveData) {
            $('#addConcreteReceiptForm')[0].reset();
            // Clear all select2 dropdowns
            $('#customer_id, #formulas_id, #mixer_car_id, #mixer_driver_id, #pump_car_id, #pump_driver_id').val('').trigger('change');
            // Clear localStorage for this form
            localStorage.removeItem('addConcreteReceiptFormData');
        }
        
        $.get('../process/concrete_receipts/get_next_receipt_number.php', function(res) {
            if (res && res.success && res.next) {
                $('#receipt_number').val(res.next);
            } else {
                $('#receipt_number').val('A-0001');
            }
        }, 'json').fail(function() {
            $('#receipt_number').val('A-0001');
        });
    });
});
